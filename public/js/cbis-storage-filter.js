(() => {
    document.addEventListener('cbis:live-document', (event) => {
        const current = document.getElementById('storage-expiration-date');
        const next = event.detail?.document?.getElementById('storage-expiration-date');
        if (!current || !next || document.activeElement === current
            || current.form?.dataset.autoSubmitting === 'true') return;

        const selectedExpiry = new URL(window.location.href).searchParams.get('expiration_date') || '';
        if (current.value !== selectedExpiry || current.innerHTML === next.innerHTML) return;

        // Keep the select and its automatic-filter handler while updating available dates.
        current.replaceChildren(...Array.from(next.options, (option) => option.cloneNode(true)));
        current.value = selectedExpiry;
    });
})();
