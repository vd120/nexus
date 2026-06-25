import { CryptoCore } from "./crypto-core.js";
import { E2EDBManager } from "./db-manager.js";

export class E2EManager {
    constructor() {
        this.db = new E2EDBManager();
        this.initialized = false;
        this.keysGenerated = false;
        // Session-level caches — avoids repeated DB reads and ECDH re-derivation
        this._peerKeyCache = new Map(); // userId → { ecdh_public_key, ecdsa_public_key }
        this._sharedSecretCache = new Map(); // userId → CryptoKey (AES-GCM derived via ECDH)
        this._groupKeyCache = new Map(); // keyId → CryptoKey (imported AES-GCM)
        this._backupStatus = null; // null = unknown, true/false = cached
        this._prekey = null; // cached prekey — avoids DB read per message

        // Eagerly open DB and pre-warm caches while page is still rendering.
        // Module scripts run before DOMContentLoaded, so by the time the
        // DOMContentLoaded handler fires the DB is already open and the
        // shared secret is pre-derived — decrypt is near-instant.
        this._initPromise = (async () => {
            try {
                await this.db.open();
                this.initialized = true;
                this._prekey = await this.db.getPrekey();

                // Pre-derive shared secret for the active P2P conversation
                if (
                    this._prekey &&
                    !window.isGroupChat &&
                    window.activeRecipientId
                ) {
                    await this._getSharedSecret(window.activeRecipientId);
                }
            } catch (_) {
                // Non-fatal — init() will retry
            }
        })();
    }

    async init() {
        await this._initPromise;
        if (!this.initialized) {
            // Fallback if eager init failed
            await this.db.open();
            this.initialized = true;
        }
    }

    async ensureKeys() {
        const hasKeys = await this.db.hasKeys();
        if (!hasKeys) {
            await this.generateKeys();
        }
        this.keysGenerated = true;
        return this.keysGenerated;
    }

    async generateKeys() {
        const ecdh = await CryptoCore.generateECDHKeyPair();
        const ecdsa = await CryptoCore.generateECDSAKeyPair();

        await this.db.storeIdentityKey(ecdsa.privateKey, ecdsa.publicKey);
        await this.db.storePrekey(ecdh.privateKey, ecdh.publicKey);
        // Invalidate all caches when keys regenerate
        this._prekey = null;
        this._sharedSecretCache.clear();
        this._peerKeyCache.clear();
    }

    async registerKeys() {
        const identity = await this.db.getIdentityKey();
        const prekey = await this.db.getPrekey();
        if (!identity || !prekey) throw new Error("Keys not generated");

        const ecdhPub = await CryptoCore.exportPublicKey(prekey.public_key);
        const ecdsaPub = await CryptoCore.exportPublicKey(identity.public_key);

        const response = await fetch("/api/e2e/keys/register", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
            body: JSON.stringify({
                ecdh_public_key: ecdhPub,
                ecdsa_public_key: ecdsaPub,
            }),
        });

        if (!response.ok) throw new Error("Failed to register public keys");

        const identityRecord = await this.db.get("user-keys", "identity");
        identityRecord.backup_status = false;
        await this.db.put("user-keys", identityRecord);
        this._backupStatus = false;

        return true;
    }

    async fetchPeerKeys(userId) {
        const uid = String(userId);

        // 1. In-memory cache (same session, fastest)
        if (this._peerKeyCache.has(uid)) {
            return this._peerKeyCache.get(uid);
        }

        // 2. IndexedDB cache (survives page refresh, valid 24h)
        const cached = await this.db.getPeerKeys(uid);
        if (cached && Date.now() - cached.fetched_at < 86400000) {
            const isEcdsaKey = cached.ecdsa_public_key instanceof CryptoKey;
            const isEcdhKey = cached.ecdh_public_key instanceof CryptoKey;
            if (isEcdsaKey && isEcdhKey) {
                this._peerKeyCache.set(uid, cached);
                return cached;
            }
        }

        // 3. Network fetch
        const response = await fetch(`/api/e2e/keys/${userId}`, {
            headers: { Accept: "application/json" },
        });

        if (!response.ok) throw new Error("Failed to fetch peer keys");
        const data = await response.json();

        const [ecdhKey, ecdsaKey] = await Promise.all([
            CryptoCore.importPublicKey(
                data.ecdh_public_key,
                { name: "ECDH", namedCurve: "P-256" },
                [],
            ),
            CryptoCore.importPublicKey(
                data.ecdsa_public_key,
                { name: "ECDSA", namedCurve: "P-256" },
                ["verify"],
            ),
        ]);

        const keys = { ecdh_public_key: ecdhKey, ecdsa_public_key: ecdsaKey };
        this._peerKeyCache.set(uid, keys);
        await this.db.storePeerKeys(uid, ecdhKey, ecdsaKey);
        return keys;
    }

    async invalidatePeerCache(userId) {
        const uid = String(userId);
        this._peerKeyCache.delete(uid);
        this._sharedSecretCache.delete(uid);
        await this.db.delete("peer-keys", uid);
    }

    async _getSharedSecret(peerUserId) {
        const uid = String(peerUserId);
        if (this._sharedSecretCache.has(uid)) {
            return this._sharedSecretCache.get(uid);
        }
        const prekey = this._prekey || (await this.db.getPrekey());
        if (!prekey) throw new Error("No local prekey");
        if (!this._prekey) this._prekey = prekey;
        const peer = await this.fetchPeerKeys(uid);
        const secret = await CryptoCore.deriveSharedSecret(
            prekey.private_key,
            peer.ecdh_public_key,
        );
        this._sharedSecretCache.set(uid, secret);
        return secret;
    }

    async _getImportedGroupKey(keyId, rawKey) {
        if (this._groupKeyCache.has(keyId)) {
            return this._groupKeyCache.get(keyId);
        }
        const key = await CryptoCore.importSymmetricKey(rawKey);
        this._groupKeyCache.set(keyId, key);
        return key;
    }

    async encryptMessage(conversationId, recipientId, payload) {
        const sharedSecret = await this._getSharedSecret(recipientId);
        const { ciphertext, iv } = await CryptoCore.encryptMessage(
            sharedSecret,
            payload,
        );

        const identity = await this.db.getIdentityKey();
        const signature = await CryptoCore.signData(
            identity.private_key,
            ciphertext,
        );

        const pubKey = await CryptoCore.exportPublicKey(identity.public_key);
        const keyId = btoa(JSON.stringify(pubKey));

        return {
            __nexus_encrypted__: true,
            version: 1,
            sender_id:
                window.SOCKET_CONFIG?.userId || window.NexusUser?.id || 0,
            ciphertext,
            iv,
            signature,
            key_id: keyId,
        };
    }

    async decryptMessage(encryptedPayload, senderId, peerId = null) {
        if (!encryptedPayload.__nexus_encrypted__) {
            return encryptedPayload;
        }

        const myId = window.SOCKET_CONFIG?.userId || window.NexusUser?.id || 0;

        // The shared secret is derived from our prekey + peer's ECDH key.
        // For sent messages (senderId == myId), peer is the recipient.
        const resolvedPeerId =
            peerId ||
            (String(senderId) === String(myId)
                ? window.activeRecipientId || senderId
                : senderId);
        let sharedSecret = await this._getSharedSecret(resolvedPeerId);

        // Verify signature (sender identity check — soft fail on key rotation)
        let senderPubKey;
        if (String(senderId) === String(myId)) {
            const identity = await this.db.getIdentityKey();
            senderPubKey = identity.public_key;
        } else {
            const peer = await this.fetchPeerKeys(resolvedPeerId);
            senderPubKey = peer.ecdsa_public_key;
        }

        let isValid = await CryptoCore.verifySignature(
            senderPubKey,
            encryptedPayload.ciphertext,
            encryptedPayload.signature,
        );
        if (!isValid) {
            console.warn(
                "[E2E] Signature verification failed — key may have rotated. Decrypting anyway.",
            );
        }

        try {
            const decrypted = await CryptoCore.decryptMessage(
                sharedSecret,
                encryptedPayload.ciphertext,
                encryptedPayload.iv,
            );
            if (!isValid) decrypted._signatureWarning = true;
            return decrypted;
        } catch (err) {
            console.warn(
                "[E2E] Decryption failed, potential key mismatch. Invalidating cache and retrying...",
                err,
            );

            // Invalidate cache
            await this.invalidatePeerCache(resolvedPeerId);

            // Fetch fresh keys & derive new secret
            const peer = await this.fetchPeerKeys(resolvedPeerId);
            sharedSecret = await this._getSharedSecret(resolvedPeerId);

            // Re-verify signature with fresh public key
            if (String(senderId) !== String(myId)) {
                senderPubKey = peer.ecdsa_public_key;
                isValid = await CryptoCore.verifySignature(
                    senderPubKey,
                    encryptedPayload.ciphertext,
                    encryptedPayload.signature,
                );
            }

            // Retry decryption
            const decrypted = await CryptoCore.decryptMessage(
                sharedSecret,
                encryptedPayload.ciphertext,
                encryptedPayload.iv,
            );
            if (!isValid) decrypted._signatureWarning = true;
            return decrypted;
        }
    }

    async backupKeys(passphrase) {
        const identity = await this.db.getIdentityKey();
        const prekey = await this.db.getPrekey();

        const privateKeyData = {
            identity_private: await CryptoCore.exportPrivateKey(
                identity.private_key,
            ),
            prekey_private: await CryptoCore.exportPrivateKey(
                prekey.private_key,
            ),
        };

        const { ciphertext, salt, iv } = await CryptoCore.encryptPrivateKey(
            passphrase,
            privateKeyData,
        );

        const response = await fetch("/api/e2e/keys/backup", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
            body: JSON.stringify({ ciphertext, salt, iv }),
        });

        if (!response.ok) throw new Error("Failed to backup keys");

        const identityRecord = await this.db.get("user-keys", "identity");
        identityRecord.backup_status = true;
        await this.db.put("user-keys", identityRecord);
        this._backupStatus = true;

        return true;
    }

    async restoreKeys(passphrase) {
        const response = await fetch("/api/e2e/keys/backup", {
            headers: { Accept: "application/json" },
        });

        if (!response.ok) throw new Error("No backup found");
        const data = await response.json();

        const privateKeyData = await CryptoCore.decryptPrivateKey(
            passphrase,
            data.ciphertext,
            data.salt,
            data.iv,
        );

        const [ecdsaPriv, ecdhPriv] = await Promise.all([
            CryptoCore.importPrivateKey(
                privateKeyData.identity_private,
                { name: "ECDSA", namedCurve: "P-256" },
                ["sign"],
            ),
            CryptoCore.importPrivateKey(
                privateKeyData.prekey_private,
                { name: "ECDH", namedCurve: "P-256" },
                ["deriveKey", "deriveBits"],
            ),
        ]);

        const [ecdsaPub, ecdhPub] = await Promise.all([
            CryptoCore.importPublicKey(
                {
                    kty: privateKeyData.identity_private.kty,
                    crv: privateKeyData.identity_private.crv,
                    x: privateKeyData.identity_private.x,
                    y: privateKeyData.identity_private.y,
                },
                { name: "ECDSA", namedCurve: "P-256" },
                ["verify"],
            ),
            CryptoCore.importPublicKey(
                {
                    kty: privateKeyData.prekey_private.kty,
                    crv: privateKeyData.prekey_private.crv,
                    x: privateKeyData.prekey_private.x,
                    y: privateKeyData.prekey_private.y,
                },
                { name: "ECDH", namedCurve: "P-256" },
                [],
            ),
        ]);

        await this.db.storeIdentityKey(ecdsaPriv, ecdsaPub);
        await this.db.storePrekey(ecdhPriv, ecdhPub);

        // Invalidate all caches — new keys in place
        this._prekey = null;
        this._sharedSecretCache.clear();
        this._peerKeyCache.clear();

        const identityRecord = await this.db.get("user-keys", "identity");
        identityRecord.backup_status = true;
        await this.db.put("user-keys", identityRecord);
        this._backupStatus = true;

        return true;
    }

    async hasBackup() {
        // Use cached value if known for this session
        if (this._backupStatus !== null) return this._backupStatus;

        // Check IndexedDB backup_status flag first (no network)
        try {
            const identity = await this.db.getIdentityKey();
            if (identity?.backup_status === true) {
                this._backupStatus = true;
                return true;
            }
        } catch (_) {}

        // Fall back to network check
        try {
            const response = await fetch("/api/e2e/keys/backup", {
                headers: { Accept: "application/json" },
            });
            this._backupStatus = response.ok;
            return this._backupStatus;
        } catch {
            return false;
        }
    }

    async computeSafetyNumber(peerUserId) {
        const identity = await this.db.getIdentityKey();
        const myPub = await CryptoCore.exportPublicKey(identity.public_key);
        const peer = await this.fetchPeerKeys(peerUserId);
        const peerPub = await CryptoCore.exportPublicKey(peer.ecdsa_public_key);
        return CryptoCore.computeSafetyNumber(myPub, peerPub);
    }

    async checkPeerKeysChanged(userId, currentEcdsaPub) {
        const cached = await this.db.getPeerKeys(userId);
        if (!cached) return false;
        const cachedPub = await CryptoCore.exportPublicKey(
            cached.ecdsa_public_key,
        );
        return JSON.stringify(cachedPub) !== JSON.stringify(currentEcdsaPub);
    }

    async createGroupKey() {
        const keyId = crypto.randomUUID();
        const key = await CryptoCore.generateGroupKey();
        const rawKey = await CryptoCore.exportSymmetricKey(key);
        return { keyId, key, rawKey };
    }

    async distributeGroupKey(conversationId, groupKeyId, groupKey, memberIds) {
        const rawKey = await CryptoCore.exportSymmetricKey(groupKey);
        const prekey = await this.db.getPrekey();

        // Encrypt per-member in parallel
        const keysPayload = await Promise.all(
            memberIds.map(async (memberId) => {
                const peer = await this.fetchPeerKeys(memberId);
                const sharedSecret = await CryptoCore.deriveSharedSecret(
                    prekey.private_key,
                    peer.ecdh_public_key,
                );
                const { ciphertext, iv } = await CryptoCore.encryptMessage(
                    sharedSecret,
                    { key: rawKey },
                );
                return {
                    user_id: parseInt(memberId),
                    key_id: groupKeyId,
                    encrypted_key: ciphertext,
                    iv,
                };
            }),
        );

        const response = await fetch("/api/e2e/group-keys/update", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
            body: JSON.stringify({
                conversation_id: conversationId,
                keys: keysPayload,
            }),
        });

        if (!response.ok) throw new Error("Failed to distribute group keys");
        return true;
    }

    async fetchAndCacheGroupKeys(conversationId) {
        const response = await fetch(`/api/e2e/group-keys/${conversationId}`, {
            headers: { Accept: "application/json" },
        });
        if (!response.ok) return [];

        const data = await response.json();
        const encryptedKeys = data.encrypted_keys || [];

        // Decrypt all keys in parallel
        const results = await Promise.allSettled(
            encryptedKeys.map(async (ek) => {
                if (!ek.sender_id) {
                    console.warn(`Group key ${ek.key_id} missing sender_id`);
                    return null;
                }
                const sharedSecret = await this._getSharedSecret(ek.sender_id);
                const decryptedPayload = await CryptoCore.decryptMessage(
                    sharedSecret,
                    ek.encrypted_key,
                    ek.iv,
                );
                return {
                    key_id: ek.key_id,
                    raw_key: decryptedPayload.key,
                    active: true,
                    rotated_at: ek.created_at,
                };
            }),
        );

        const decryptedKeys = results
            .filter((r) => r.status === "fulfilled" && r.value)
            .map((r) => r.value);

        if (decryptedKeys.length > 0) {
            await this.db.storeGroupKeys(conversationId, decryptedKeys);
        }
        return decryptedKeys;
    }

    async encryptGroupMessage(conversationId, payload) {
        let groupKeys = await this.db.getGroupKeys(conversationId);
        if (!groupKeys || !groupKeys.keys?.length) {
            const fetched = await this.fetchAndCacheGroupKeys(conversationId);
            groupKeys = { keys: fetched };
        }
        let keys = groupKeys.keys || [];

        // Auto-bootstrap if still no keys
        if (
            !keys.length &&
            window.isGroupChat &&
            window.groupMemberIds?.length > 0
        ) {
            try {
                console.log(
                    "[E2E] Bootstrapping group keys for",
                    conversationId,
                );
                const newKeyData = await this.createGroupKey();
                await this.distributeGroupKey(
                    conversationId,
                    newKeyData.keyId,
                    newKeyData.key,
                    window.groupMemberIds,
                );
                const newRecord = {
                    key_id: newKeyData.keyId,
                    raw_key: newKeyData.rawKey,
                    active: true,
                    rotated_at: Date.now(),
                };
                await this.db.storeGroupKeys(conversationId, [newRecord]);
                keys = [newRecord];
            } catch (err) {
                console.error("[E2E] Failed to bootstrap group keys", err);
            }
        }

        if (!keys.length) throw new Error("No group keys available");

        const activeKey = keys.find((k) => k.active) || keys[keys.length - 1];
        if (!activeKey) throw new Error("No active group key");

        const key = await this._getImportedGroupKey(
            activeKey.key_id,
            activeKey.raw_key,
        );
        const { ciphertext, iv } = await CryptoCore.encryptWithSymmetricKey(
            key,
            payload,
        );

        const identity = await this.db.getIdentityKey();
        const signature = await CryptoCore.signData(
            identity.private_key,
            ciphertext,
        );

        return {
            __nexus_encrypted__: true,
            version: 1,
            sender_id:
                window.SOCKET_CONFIG?.userId || window.NexusUser?.id || 0,
            ciphertext,
            iv,
            signature,
            key_id: activeKey.key_id,
        };
    }

    async decryptGroupMessage(encryptedPayload) {
        if (!encryptedPayload.__nexus_encrypted__) {
            return encryptedPayload;
        }

        const convId = String(encryptedPayload.conversation_id || "");
        let groupKeys = await this.db.getGroupKeys(convId);
        if (!groupKeys || !groupKeys.keys?.length) {
            const fetched = await this.fetchAndCacheGroupKeys(convId);
            groupKeys = { keys: fetched };
        }

        const keys = groupKeys.keys || [];
        let keyRecord = keys.find((k) => k.key_id === encryptedPayload.key_id);
        if (!keyRecord) {
            console.log(
                "[E2E] Group key not found in cache. Refetching group keys...",
            );
            const fetched = await this.fetchAndCacheGroupKeys(convId);
            groupKeys = { keys: fetched };
            const freshKeys = groupKeys.keys || [];
            keyRecord = freshKeys.find(
                (k) => k.key_id === encryptedPayload.key_id,
            );
            if (!keyRecord)
                throw new Error("Group key not found for this message");
        }

        const key = await this._getImportedGroupKey(
            keyRecord.key_id,
            keyRecord.raw_key,
        );
        const decrypted = await CryptoCore.decryptWithSymmetricKey(
            key,
            encryptedPayload.ciphertext,
            encryptedPayload.iv,
        );

        // Signature verification (soft fail — key rotation is expected)
        if (encryptedPayload.sender_id && encryptedPayload.signature) {
            try {
                const myId =
                    window.SOCKET_CONFIG?.userId || window.NexusUser?.id || 0;
                let senderPubKey;
                if (String(encryptedPayload.sender_id) === String(myId)) {
                    const identity = await this.db.getIdentityKey();
                    senderPubKey = identity?.public_key;
                } else {
                    const peer = await this.fetchPeerKeys(
                        encryptedPayload.sender_id,
                    );
                    senderPubKey = peer?.ecdsa_public_key;
                }
                if (senderPubKey) {
                    const isValid = await CryptoCore.verifySignature(
                        senderPubKey,
                        encryptedPayload.ciphertext,
                        encryptedPayload.signature,
                    );
                    if (!isValid) {
                        console.warn(
                            "[E2E] Group message signature unverified — sender key may have rotated.",
                        );
                        decrypted._signatureWarning = true;
                    }
                }
            } catch (sigErr) {
                console.warn("[E2E] Group signature check failed:", sigErr);
                decrypted._signatureWarning = true;
            }
        }

        return decrypted;
    }

    static isBrowserSupported() {
        return (
            typeof window !== "undefined" &&
            window.crypto &&
            window.crypto.subtle &&
            typeof indexedDB !== "undefined"
        );
    }
}

window.E2EManager = E2EManager;
if (typeof window !== "undefined" && !window.e2eManager) {
    window.e2eManager = new E2EManager();
}
if (typeof window !== "undefined" && !window.getE2EManager) {
    window.getE2EManager = async function() {
        if (window.e2eManager && window.e2eManager.initialized) return window.e2eManager;
        return new Promise((resolve) => {
            const interval = setInterval(() => {
                if (window.e2eManager && window.e2eManager.initialized) {
                    clearInterval(interval);
                    resolve(window.e2eManager);
                }
            }, 50);
            setTimeout(() => {
                clearInterval(interval);
                resolve(window.e2eManager || null);
            }, 3000);
        });
    };
}
