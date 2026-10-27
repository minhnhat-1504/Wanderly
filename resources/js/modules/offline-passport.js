/**
 * Offline Travel Passport Module (Sprint 4 - Task E4-02)
 * Handles client-side localStorage caching, connectivity status monitoring,
 * and offline restoration for vouchers, emergency hotlines, and day-by-day itinerary.
 */

export function initOfflinePassport(tripId, initialData = null) {
    const storageKey = `wanderly_passport_${tripId}`;

    // Cache initial data if provided by server
    if (initialData) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(initialData));
            console.log(`[OfflinePassport] Successfully cached snapshot for trip #${tripId} in localStorage.`);
        } catch (e) {
            console.warn('[OfflinePassport] Unable to write to localStorage:', e);
        }
    }

    function updateNetworkStatus(isOnline) {
        const indicator = document.getElementById('network-status-indicator');
        const statusText = document.getElementById('network-status-text');
        const statusDot = document.getElementById('network-status-dot');
        const offlineBanner = document.getElementById('offline-banner');

        if (!indicator) return;

        if (isOnline) {
            indicator.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200';
            if (statusDot) statusDot.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse';
            if (statusText) statusText.textContent = 'Online • Ready';
            if (offlineBanner) offlineBanner.classList.add('hidden');
        } else {
            indicator.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300';
            if (statusDot) statusDot.className = 'w-2 h-2 rounded-full bg-amber-500';
            if (statusText) statusText.textContent = 'Offline Mode Active';
            if (offlineBanner) offlineBanner.classList.remove('hidden');
        }
    }

    window.addEventListener('online', () => updateNetworkStatus(true));
    window.addEventListener('offline', () => updateNetworkStatus(false));

    // Initial check
    updateNetworkStatus(navigator.onLine);

    return {
        getCachedData() {
            try {
                const raw = localStorage.getItem(storageKey);
                return raw ? JSON.parse(raw) : initialData;
            } catch (e) {
                return initialData;
            }
        },
        saveData(data) {
            try {
                localStorage.setItem(storageKey, JSON.stringify(data));
                return true;
            } catch (e) {
                console.error('[OfflinePassport] Error saving data:', e);
                return false;
            }
        }
    };
}

if (typeof window !== 'undefined') {
    window.initOfflinePassport = initOfflinePassport;
}
