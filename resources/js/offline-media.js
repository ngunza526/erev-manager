const manifestEndpoint = '/api/media/offline-manifest';

const serviceWorkerReady = async () => {
  if (!('serviceWorker' in navigator)) return null;

  try {
    return await navigator.serviceWorker.ready;
  } catch {
    return null;
  }
};

export const offlineMedia = {
  async manifest({ token } = {}) {
    const response = await fetch(manifestEndpoint, {
      headers: {
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
    });

    if (!response.ok) {
      return { status: 'failed', http_status: response.status, data: [] };
    }

    return response.json();
  },

  async cacheAvailable({ token } = {}) {
    const manifest = await this.manifest({ token });
    const urls = (manifest.data || [])
      .map((item) => item.url)
      .filter((url) => typeof url === 'string' && url.length > 0);
    const registration = await serviceWorkerReady();

    if (!registration?.active || urls.length === 0) {
      return { status: 'skipped', cached_count: 0, manifest };
    }

    registration.active.postMessage({ type: 'EREVE_CACHE_MEDIA', urls });

    return { status: 'queued', cached_count: urls.length, manifest };
  },
};

if (typeof window !== 'undefined') {
  window.ereveOfflineMedia = offlineMedia;
}
