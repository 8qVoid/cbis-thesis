(() => {
    const intervalMs = 15000;
    let loading = false;

    const refresh = async () => {
        if (loading || document.visibilityState !== 'visible') return;
        loading = true;

        try {
            const response = await fetch(window.location.href, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'text/html' },
            });
            if (response.redirected && new URL(response.url).pathname !== window.location.pathname) {
                window.location.assign(response.url);
                return;
            }
            if (!response.ok || !response.headers.get('content-type')?.includes('text/html')) return;

            const next = new DOMParser().parseFromString(await response.text(), 'text/html');
            const currentNotifications = document.querySelector('[data-live-notifications]');
            const nextNotifications = next.querySelector('[data-live-notifications]');
            if (currentNotifications && nextNotifications && !currentNotifications.contains(document.activeElement)
                && !currentNotifications.querySelector('.dropdown-menu.show')
                && currentNotifications.outerHTML !== nextNotifications.outerHTML) {
                currentNotifications.replaceWith(nextNotifications);
            }

            if (!document.querySelector('.modal.show')) {
                document.querySelectorAll('[data-live-region]').forEach((current) => {
                    const name = current.getAttribute('data-live-region');
                    const replacement = [...next.querySelectorAll('[data-live-region]')]
                        .find((item) => item.getAttribute('data-live-region') === name);
                    if (!replacement || current.contains(document.activeElement)
                        || current.querySelector('.cbis-event-actions[open], .cbis-row-actions[open]')) return;
                    if (current.outerHTML !== replacement.outerHTML) current.replaceWith(replacement);
                });
            }

            document.dispatchEvent(new CustomEvent('cbis:live-document', { detail: { document: next } }));
        } catch (_) {
            // Keep the current page usable when the network or local server is unavailable.
        } finally {
            loading = false;
        }
    };

    window.setInterval(refresh, intervalMs);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') refresh();
    });
})();
