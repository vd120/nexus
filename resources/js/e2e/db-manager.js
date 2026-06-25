export class E2EDBManager {
    constructor() {
        this.dbName = "nexus-e2e";
        this.version = 1;
        this.db = null;
    }

    async open() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains("user-keys")) {
                    db.createObjectStore("user-keys", { keyPath: "key_type" });
                }
                if (!db.objectStoreNames.contains("peer-keys")) {
                    db.createObjectStore("peer-keys", { keyPath: "user_id" });
                }
                if (!db.objectStoreNames.contains("group-keys")) {
                    db.createObjectStore("group-keys", {
                        keyPath: "conversation_id",
                    });
                }
            };
            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };
            request.onerror = () => reject(request.error);
        });
    }

    async put(storeName, value) {
        if (!this.db) await this.open();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, "readwrite");
            const store = tx.objectStore(storeName);
            const request = store.put(value);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async get(storeName, key) {
        if (!this.db) await this.open();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, "readonly");
            const store = tx.objectStore(storeName);
            const request = store.get(key);
            request.onsuccess = () => resolve(request.result || null);
            request.onerror = () => reject(request.error);
        });
    }

    async getAll(storeName) {
        if (!this.db) await this.open();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, "readonly");
            const store = tx.objectStore(storeName);
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async delete(storeName, key) {
        if (!this.db) await this.open();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, "readwrite");
            const store = tx.objectStore(storeName);
            const request = store.delete(key);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    async clear(storeName) {
        if (!this.db) await this.open();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, "readwrite");
            const store = tx.objectStore(storeName);
            const request = store.clear();
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    async hasKeys() {
        const keys = await this.getAll("user-keys");
        return keys.length >= 2;
    }

    async getIdentityKey() {
        return this.get("user-keys", "identity");
    }

    async getPrekey() {
        return this.get("user-keys", "prekey");
    }

    async storeIdentityKey(privateKey, publicKey) {
        const myId = String(window.SOCKET_CONFIG?.userId || window.NexusUser?.id || 0);
        return this.put("user-keys", {
            key_type: "identity",
            user_id: myId,
            private_key: privateKey,
            public_key: publicKey,
            backup_status: false,
            created_at: Date.now(),
        });
    }

    async storePrekey(privateKey, publicKey) {
        const myId = String(window.SOCKET_CONFIG?.userId || window.NexusUser?.id || 0);
        return this.put("user-keys", {
            key_type: "prekey",
            user_id: myId,
            private_key: privateKey,
            public_key: publicKey,
            backup_status: false,
            created_at: Date.now(),
        });
    }

    async getPeerKeys(userId) {
        return this.get("peer-keys", String(userId));
    }

    async storePeerKeys(userId, ecdhPublicKey, ecdsaPublicKey) {
        return this.put("peer-keys", {
            user_id: String(userId),
            ecdh_public_key: ecdhPublicKey,
            ecdsa_public_key: ecdsaPublicKey,
            fetched_at: Date.now(),
        });
    }

    async getGroupKeys(conversationId) {
        return this.get("group-keys", String(conversationId));
    }

    async storeGroupKeys(conversationId, keys) {
        return this.put("group-keys", {
            conversation_id: String(conversationId),
            keys,
        });
    }
}
