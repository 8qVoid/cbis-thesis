(() => {
    // Present existing errors and required attributes without changing validation or submission.
    const errorData = document.getElementById('cbis-validation-errors');
    const errors = errorData ? JSON.parse(errorData.textContent) : {};
    const feedbackByField = new WeakMap();
    const forms = [...document.querySelectorAll('main form')].filter(form => form.method.toLowerCase() !== 'get');

    const syncMarkers = form => {
        let hasRequiredField = false;
        form.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select, textarea').forEach(field => {
            const label = field.labels?.[0];
            if (!label || field.readOnly) return;
            let marker = label.querySelector('.cbis-required-marker');
            if (field.required && !marker) {
                marker = document.createElement('span');
                marker.className = 'cbis-required-marker';
                marker.setAttribute('aria-hidden', 'true');
                marker.textContent = '*';
                label.append(marker);
            }
            if (marker) marker.hidden = !field.required;
            hasRequiredField ||= field.required;
        });
        if (hasRequiredField && !form.querySelector('.cbis-required-note')) {
            const note = document.createElement('p');
            note.className = 'cbis-required-note small text-muted';
            note.textContent = 'Fields marked * are required.';
            (form.querySelector(':scope > .card-body') || form).prepend(note);
        }
    };

    const showError = (field, message) => {
        let feedback = feedbackByField.get(field);
        if (!feedback) {
            const described = (field.getAttribute('aria-describedby') || '').split(/\s+/);
            feedback = described.map(id => document.getElementById(id)).find(node => node?.classList.contains('cbis-field-error'));
            feedback ||= [...field.parentElement.querySelectorAll('.cbis-field-error, .invalid-feedback, .text-danger')]
                .find(node => node.textContent.trim() === message);
            if (!feedback) {
                feedback = document.createElement('div');
                field.insertAdjacentElement('afterend', feedback);
            }
            feedback.id ||= `cbis-field-feedback-${feedbackByFieldCounter++}`;
            feedback.classList.add('cbis-field-error', 'invalid-feedback', 'd-block');
            feedback.setAttribute('role', 'alert');
            const descriptions = new Set(described.filter(Boolean));
            descriptions.add(feedback.id);
            field.setAttribute('aria-describedby', [...descriptions].join(' '));
            feedbackByField.set(field, feedback);
        }
        feedback.textContent = message;
        feedback.hidden = false;
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
    };
    let feedbackByFieldCounter = 0;

    forms.forEach(form => {
        syncMarkers(form);
        if (form.id === 'registrationForm') return;
        form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(field => {
            const key = field.name.replace(/\[([^\]]*)\]/g, (_, part) => part ? `.${part}` : '');
            if (key && errors[key]?.length) showError(field, errors[key][0]);
            field.addEventListener('invalid', () => showError(field, field.validationMessage));
            field.addEventListener('input', () => {
                if (!field.validity.valid) return;
                const feedback = feedbackByField.get(field);
                if (feedback) feedback.hidden = true;
                field.classList.remove('is-invalid');
                field.removeAttribute('aria-invalid');
            });
        });
    });
    document.addEventListener('change', event => {
        if (forms.includes(event.target.form)) syncMarkers(event.target.form);
    });
    document.addEventListener('DOMContentLoaded', () => forms.forEach(syncMarkers), { once: true });
})();
