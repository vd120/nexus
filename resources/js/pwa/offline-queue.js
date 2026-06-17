const DB_NAME    = 'nexus-pwa';
const DB_VERSION = 1;
const STORE_QUEUE = 'offline-queue';
const MAX_RETRY   = 3;

let db = null;
let sequenceCounter = 0;

function openDB() {
    return new Promise((resolve, reject) => {
        if (db) { resolve(db); return; }
        const req = indexedDB.open(DB_NAME, DB_VERSION);
        req.onupgradeneeded = (e) => {
            const database = e.target.result;
            if (!database.objectStoreNames.contains(STORE_QUEUE)) {
                const store = database.createObjectStore(STORE_QUEUE, { keyPath: 'id' });
                store.createIndex('status_seq', ['status', 'sequenceNumber'], { unique: false });
                store.createIndex('recordId',   'recordId',                  { unique: false });
            }
        };
        req.onsuccess = (e) => { db = e.target.result; resolve(db); };
        req.onerror   = (e) => reject(e.target.error);
    });
}

function uuid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}

export async function enqueue(type, recordId, data) {
    try {
        const database = await openDB();
        const item = {
            id:             uuid(),
            type,
            recordId:       String(recordId),
            data,
            sequenceNumber: ++sequenceCounter,
            timestamp:      Date.now(),
            retryCount:     0,
            status:         'pending',
            lastAttemptedAt: null,
        };
        return new Promise((resolve, reject) => {
            const tx    = database.transaction(STORE_QUEUE, 'readwrite');
            const store = tx.objectStore(STORE_QUEUE);
            store.add(item).onsuccess = () => resolve(item);
            tx.onerror = (e) => reject(e.target.error);
        });
    } catch (e) {
        console.warn('[OfflineQueue] enqueue failed:', e);
    }
}

async function getPendingItems() {
    const database = await openDB();
    return new Promise((resolve) => {
        const tx    = database.transaction(STORE_QUEUE, 'readonly');
        const store = tx.objectStore(STORE_QUEUE);
        const index = store.index('status_seq');
        const range = IDBKeyRange.bound(['pending', 0], ['pending', Infinity]);
        const items = [];
        index.openCursor(range).onsuccess = (e) => {
            const cursor = e.target.result;
            if (cursor) { items.push(cursor.value); cursor.continue(); }
            else resolve(items.sort((a, b) => a.sequenceNumber - b.sequenceNumber));
        };
    });
}

async function updateItem(id, changes) {
    const database = await openDB();
    return new Promise((resolve) => {
        const tx    = database.transaction(STORE_QUEUE, 'readwrite');
        const store = tx.objectStore(STORE_QUEUE);
        const req   = store.get(id);
        req.onsuccess = (e) => {
            const item = Object.assign({}, e.target.result, changes);
            store.put(item).onsuccess = () => resolve();
        };
    });
}

async function replayItem(item) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const headers = {
        'Content-Type':     'application/json',
        'X-CSRF-TOKEN':     csrf,
        'Accept':           'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    try {
        let response;
        if (item.type === 'post_reaction') {
            response = await fetch(`/posts/${item.recordId}/react`, {
                method: 'POST', headers, body: JSON.stringify(item.data),
            });
        } else if (item.type === 'post_reaction_remove') {
            response = await fetch(`/posts/${item.recordId}/unreact`, {
                method: 'DELETE', headers, body: JSON.stringify(item.data),
            });
        } else if (item.type === 'chat_message') {
            const url = (item.data && item.data.store_url) || `/chat/${item.recordId}`;
            response = await fetch(url, {
                method: 'POST', headers, body: JSON.stringify({ content: item.data.content }),
            });
        } else if (item.type === 'follow_user') {
            response = await fetch(`/users/${item.recordId}/follow`, {
                method: 'POST', headers,
            });
        }
        return response && (response.ok || response.status === 409); // 409 = already done
    } catch (e) {
        return false;
    }
}

export async function replayQueue() {
    if (!navigator.onLine) return;
    try {
        const items = await getPendingItems();
        for (const item of items) {
            await updateItem(item.id, { status: 'syncing', lastAttemptedAt: Date.now() });
            const success = await replayItem(item);
            if (success) {
                await updateItem(item.id, { status: 'done' });
            } else {
                const newRetry = (item.retryCount || 0) + 1;
                await updateItem(item.id, {
                    retryCount: newRetry,
                    status:     newRetry >= MAX_RETRY ? 'failed' : 'pending',
                });
            }
        }
    } catch (e) {
        console.warn('[OfflineQueue] replayQueue error:', e);
    }
}

function showOfflineIndicator(show) {
    const el = document.getElementById('offline-indicator');
    if (!el) return;
    if (show) {
        el.removeAttribute('hidden');
    } else {
        el.setAttribute('hidden', '');
        if (window.showToast) window.showToast(
            (window.pwaTranslations?.back_online) || 'You\'re back online!',
            'success', null, null, 2000
        );
    }
}

export function init() {
    if (!window.indexedDB) return;

    // Open DB eagerly to initialise sequenceCounter
    openDB().then(async () => {
        const items = await getPendingItems();
        sequenceCounter = items.reduce((max, i) => Math.max(max, i.sequenceNumber), 0);
    }).catch(() => {});

    // Online/offline visual indicator
    window.addEventListener('offline', () => showOfflineIndicator(true));
    window.addEventListener('online',  () => {
        showOfflineIndicator(false);
        replayQueue();
    });
    window.addEventListener('focus',   () => { if (navigator.onLine) replayQueue(); });

    // Listen for Background Sync relay message from SW (Android)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data?.type === 'REPLAY_OFFLINE_QUEUE') replayQueue();
        });
    }

    // Register Background Sync if available (Android Chrome)
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        navigator.serviceWorker.ready.then((reg) => {
            reg.sync.register('nexus-offline-sync').catch(() => {});
        }).catch(() => {});
    }

    // Show indicator immediately if already offline
    if (!navigator.onLine) showOfflineIndicator(true);
}
