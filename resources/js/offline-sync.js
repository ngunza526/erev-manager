const DB_NAME = 'ereve-offline-sync';
const DB_VERSION = 1;
const STORE = 'records';

const openDb = () => new Promise((resolve, reject) => {
  const request = indexedDB.open(DB_NAME, DB_VERSION);
  request.onupgradeneeded = () => {
    const db = request.result;
    if (!db.objectStoreNames.contains(STORE)) {
      const store = db.createObjectStore(STORE, { keyPath: 'client_id' });
      store.createIndex('church_id', 'church_id');
      store.createIndex('status', 'status');
    }
  };
  request.onsuccess = () => resolve(request.result);
  request.onerror = () => reject(request.error);
});

const transact = async (mode, callback) => {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE, mode);
    const store = tx.objectStore(STORE);
    const result = callback(store);
    tx.oncomplete = () => resolve(result);
    tx.onerror = () => reject(tx.error);
  });
};

const allRecords = () => transact('readonly', (store) => new Promise((resolve, reject) => {
  const request = store.getAll();
  request.onsuccess = () => resolve(request.result);
  request.onerror = () => reject(request.error);
}));

const removeRecords = (clientIds) => transact('readwrite', (store) => {
  clientIds.forEach((clientId) => store.delete(clientId));
});

const dispatchCount = async () => {
  const count = (await allRecords()).length;
  window.dispatchEvent(new CustomEvent('ereve-offline-count', { detail: { count } }));
  return count;
};

const deviceId = () => {
  const key = 'ereve_offline_device_id';
  let current = localStorage.getItem(key);
  if (!current) {
    current = crypto.randomUUID();
    localStorage.setItem(key, current);
  }
  return current;
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content;

const groupByChurch = (records) => records.reduce((groups, record) => {
  const churchId = String(record.church_id);
  groups[churchId] = groups[churchId] || [];
  groups[churchId].push(record);
  return groups;
}, {});

export const offlineSync = {
  async enqueue({ church_id, type, payload, client_id = crypto.randomUUID() }) {
    await transact('readwrite', (store) => store.put({
      client_id,
      church_id,
      type,
      payload,
      queued_at: new Date().toISOString(),
      status: 'pending',
    }));
    await dispatchCount();
    return client_id;
  },

  async pendingCount() {
    return dispatchCount();
  },

  async flush(endpoint = '/offline/sync') {
    if (!navigator.onLine) {
      return { status: 'offline', processed_count: 0 };
    }

    const records = await allRecords();
    const groups = groupByChurch(records);
    const summaries = [];

    for (const [churchId, churchRecords] of Object.entries(groups)) {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken() || '',
        },
        body: JSON.stringify({
          device_id: deviceId(),
          client_batch_id: crypto.randomUUID(),
          church_id: Number(churchId),
          records: churchRecords.map(({ client_id, type, payload }) => ({ client_id, type, payload })),
        }),
      });

      if (!response.ok) {
        summaries.push({ church_id: churchId, status: 'failed', http_status: response.status });
        continue;
      }

      const result = await response.json();
      await removeRecords((result.results || []).map((item) => item.client_id));
      summaries.push(result);
    }

    await dispatchCount();
    return { status: 'done', batches: summaries };
  },
};

if (typeof window !== 'undefined' && 'indexedDB' in window) {
  window.ereveOffline = offlineSync;
  window.addEventListener('online', () => offlineSync.flush().catch(() => {}));
  window.addEventListener('load', () => offlineSync.pendingCount().catch(() => {}));
}
