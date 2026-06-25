export class CryptoCore {
    static async generateECDHKeyPair() {
        return window.crypto.subtle.generateKey(
            { name: "ECDH", namedCurve: "P-256" },
            true,
            ["deriveKey", "deriveBits"],
        );
    }

    static async generateECDSAKeyPair() {
        return window.crypto.subtle.generateKey(
            { name: "ECDSA", namedCurve: "P-256" },
            true,
            ["sign", "verify"],
        );
    }

    static async deriveSharedSecret(privateKey, publicKey) {
        const bits = await window.crypto.subtle.deriveBits(
            { name: "ECDH", public: publicKey },
            privateKey,
            256,
        );
        const hash = await window.crypto.subtle.digest("SHA-256", bits);
        return window.crypto.subtle.importKey(
            "raw",
            hash,
            { name: "AES-GCM", length: 256 },
            false,
            ["encrypt", "decrypt"],
        );
    }

    static _toBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = "";
        // Process in chunks to avoid call stack overflow on large payloads
        const CHUNK = 8192;
        for (let i = 0; i < bytes.length; i += CHUNK) {
            binary += String.fromCharCode(...bytes.subarray(i, i + CHUNK));
        }
        return btoa(binary);
    }

    static async encryptMessage(key, plaintext) {
        const iv = window.crypto.getRandomValues(new Uint8Array(12));
        const encoded = new TextEncoder().encode(JSON.stringify(plaintext));
        const ciphertext = await window.crypto.subtle.encrypt(
            { name: "AES-GCM", iv },
            key,
            encoded,
        );
        return {
            ciphertext: this._toBase64(ciphertext),
            iv: this._toBase64(iv),
        };
    }

    static async decryptMessage(key, ciphertextB64, ivB64) {
        const ciphertext = Uint8Array.from(atob(ciphertextB64), (c) =>
            c.charCodeAt(0),
        );
        const iv = Uint8Array.from(atob(ivB64), (c) => c.charCodeAt(0));
        const plaintext = await window.crypto.subtle.decrypt(
            { name: "AES-GCM", iv },
            key,
            ciphertext,
        );
        return JSON.parse(new TextDecoder().decode(plaintext));
    }

    static async signData(privateKey, data) {
        const encoded = new TextEncoder().encode(data);
        const signature = await window.crypto.subtle.sign(
            { name: "ECDSA", hash: "SHA-256" },
            privateKey,
            encoded,
        );
        return this._toBase64(signature);
    }

    static async verifySignature(publicKey, data, signatureB64) {
        const encoded = new TextEncoder().encode(data);
        const signature = Uint8Array.from(atob(signatureB64), (c) =>
            c.charCodeAt(0),
        );
        return window.crypto.subtle.verify(
            { name: "ECDSA", hash: "SHA-256" },
            publicKey,
            signature,
            encoded,
        );
    }

    static async deriveBackupKey(passphrase, salt) {
        const enc = new TextEncoder();
        const keyMaterial = await window.crypto.subtle.importKey(
            "raw",
            enc.encode(passphrase),
            "PBKDF2",
            false,
            ["deriveKey"],
        );
        return window.crypto.subtle.deriveKey(
            {
                name: "PBKDF2",
                salt,
                iterations: 100000,
                hash: "SHA-256",
            },
            keyMaterial,
            { name: "AES-GCM", length: 256 },
            false,
            ["encrypt", "decrypt"],
        );
    }

    static async encryptPrivateKey(key, privateKeyData) {
        const iv = window.crypto.getRandomValues(new Uint8Array(12));
        const salt = window.crypto.getRandomValues(new Uint8Array(16));
        const derivedKey = await this.deriveBackupKey(key, salt);
        const encoded = new TextEncoder().encode(
            JSON.stringify(privateKeyData),
        );
        const ciphertext = await window.crypto.subtle.encrypt(
            { name: "AES-GCM", iv },
            derivedKey,
            encoded,
        );
        return {
            ciphertext: this._toBase64(ciphertext),
            salt: this._toBase64(salt),
            iv: this._toBase64(iv),
        };
    }

    static async decryptPrivateKey(passphrase, ciphertextB64, saltB64, ivB64) {
        const salt = Uint8Array.from(atob(saltB64), (c) => c.charCodeAt(0));
        const iv = Uint8Array.from(atob(ivB64), (c) => c.charCodeAt(0));
        const ciphertext = Uint8Array.from(atob(ciphertextB64), (c) =>
            c.charCodeAt(0),
        );
        const derivedKey = await this.deriveBackupKey(passphrase, salt);
        const plaintext = await window.crypto.subtle.decrypt(
            { name: "AES-GCM", iv },
            derivedKey,
            ciphertext,
        );
        return JSON.parse(new TextDecoder().decode(plaintext));
    }

    static async exportPublicKey(key) {
        if (!key) return null;
        if (typeof key === "object" && "kty" in key) {
            return {
                kty: key.kty,
                crv: key.crv,
                x: key.x,
                y: key.y,
            };
        }
        const exported = await window.crypto.subtle.exportKey("jwk", key);
        return {
            kty: exported.kty,
            crv: exported.crv,
            x: exported.x,
            y: exported.y,
        };
    }

    static async importPublicKey(jwk, algorithm, usage) {
        return window.crypto.subtle.importKey(
            "jwk",
            jwk,
            algorithm,
            true,
            usage,
        );
    }

    static async exportPrivateKey(key) {
        if (!key) return null;
        if (typeof key === "object" && "kty" in key) {
            return key;
        }
        return window.crypto.subtle.exportKey("jwk", key);
    }

    static async importPrivateKey(jwk, algorithm, usage) {
        return window.crypto.subtle.importKey(
            "jwk",
            jwk,
            algorithm,
            true,
            usage,
        );
    }

    static async computeSafetyNumber(ownPubKey, peerPubKey) {
        const enc = new TextEncoder();
        const strA = JSON.stringify(ownPubKey);
        const strB = JSON.stringify(peerPubKey);
        const sorted = [strA, strB].sort();
        const combined = enc.encode(sorted[0] + sorted[1]);
        const hash = await window.crypto.subtle.digest("SHA-256", combined);
        const hex = Array.from(new Uint8Array(hash))
            .map((b) => b.toString(16).padStart(2, "0"))
            .join("");
        return hex.match(/.{1,8}/g).join("-");
    }

    static async generateGroupKey() {
        return window.crypto.subtle.generateKey(
            { name: "AES-GCM", length: 256 },
            true,
            ["encrypt", "decrypt"],
        );
    }

    static async exportSymmetricKey(key) {
        const raw = await window.crypto.subtle.exportKey("raw", key);
        return this._toBase64(raw);
    }

    static async importSymmetricKey(keyB64) {
        const raw = Uint8Array.from(atob(keyB64), (c) => c.charCodeAt(0));
        return window.crypto.subtle.importKey(
            "raw",
            raw,
            { name: "AES-GCM", length: 256 },
            false,
            ["encrypt", "decrypt"],
        );
    }

    static async encryptWithSymmetricKey(key, plaintext) {
        return this.encryptMessage(key, plaintext);
    }

    static async decryptWithSymmetricKey(key, ciphertextB64, ivB64) {
        return this.decryptMessage(key, ciphertextB64, ivB64);
    }
}

window.CryptoCore = CryptoCore;
