<div class="card mt-4">
    <div class="card-header fw-bold">Submitted Documents</div>
    <div class="list-group list-group-flush">
        @foreach($reservation->documents as $document)
            <div class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="overflow-hidden">
                    <div class="fw-semibold">{{ $document->type === 'identification' ? 'Identification' : "Doctor’s blood request / prescription" }}</div>
                    <small class="text-muted text-break">{{ $document->original_name }}</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#documentViewer"
                    data-document-url="{{ route('reservations.documents.show', [$reservation, $document->id]) }}"
                    data-document-name="{{ $document->original_name }}">View document</button>
            </div>
        @endforeach
    </div>
</div>
<div class="modal fade" id="documentViewer" tabindex="-1" aria-labelledby="documentViewerTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5 text-break" id="documentViewerTitle">Document preview</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close preview"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="js-document-status mb-2" role="status" aria-live="polite"></div>
                <div class="js-document-content text-center"></div>
            </div>
            <div class="modal-footer">
                <a class="btn btn-outline-secondary js-document-open" target="_blank" rel="noopener">Open in new tab</a>
                <a class="btn btn-outline-secondary js-document-download">Download copy</a>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(() => {
    const modal = document.getElementById('documentViewer');
    const content = modal.querySelector('.js-document-content');
    const status = modal.querySelector('.js-document-status');
    let controller, objectUrl;
    const cleanup = () => {
        controller?.abort();
        content.replaceChildren();
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    };
    modal.addEventListener('show.bs.modal', async event => {
        cleanup();
        const trigger = event.relatedTarget;
        if (!trigger?.dataset.documentUrl) return;
        const url = trigger.dataset.documentUrl;
        const name = trigger.dataset.documentName;
        modal.querySelector('#documentViewerTitle').textContent = name;
        modal.querySelector('.js-document-open').href = url;
        modal.querySelector('.js-document-download').href = `${url}?download=1`;
        status.textContent = 'Loading document…';
        status.classList.remove('text-danger');
        const request = new AbortController(); controller = request;
        try {
            const response = await fetch(url, { signal: request.signal, cache: 'no-store', headers: { Accept: 'application/pdf,image/jpeg,image/png' } });
            if (!response.ok) throw new Error(response.status === 404 ? 'This file is no longer available. Please ask for another copy.' : 'Could not open this document. Please try again.');
            const blob = await response.blob();
            if (request.signal.aborted) return;
            if (!['application/pdf', 'image/jpeg', 'image/png'].includes(blob.type)) throw new Error('Preview unavailable. Please sign in again or try opening the document in a new tab.');
            objectUrl = URL.createObjectURL(blob);
            const preview = document.createElement(blob.type === 'application/pdf' ? 'iframe' : 'img');
            if (blob.type === 'application/pdf') {
                preview.title = name;
                preview.style.cssText = 'width:100%;height:65vh;border:0;background:white';
                status.textContent = 'If the PDF preview is unavailable on your device, use Open in new tab.';
            } else {
                preview.alt = name;
                preview.style.cssText = 'max-width:100%;height:auto;display:block;margin:auto';
                preview.addEventListener('error', () => { status.textContent = 'The image could not be displayed. Try opening it in a new tab.'; });
                status.textContent = '';
            }
            preview.src = objectUrl;
            content.append(preview);
        } catch (error) {
            if (request.signal.aborted) return;
            status.textContent = error.message; status.classList.add('text-danger');
        }
    });
    modal.addEventListener('hide.bs.modal', cleanup);
})();
</script>
@endpush
