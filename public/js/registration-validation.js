(() => {
    const form = document.getElementById('registrationForm');
    if (!form) return;

    const services = [...form.querySelectorAll('.js-service')];
    const servicesError = document.getElementById('servicesError');
    const donorFields = [...form.querySelectorAll('.js-donor-field')];
    const birthDate = document.getElementById('birthDate');
    const password = document.getElementById('registrationPassword');
    const nameFields = [...form.querySelectorAll('.js-person-name')];
    const fields = [...form.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]), select')];
    const feedbackByField = new Map();
    const touchedFields = new Set();

    fields.forEach((field, index) => {
        const feedbackId = `${field.id || `registrationField${index}`}-error`;
        let feedback = document.getElementById(feedbackId);
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = feedbackId;
            feedback.className = 'cbis-field-error text-danger small mt-1';
            feedback.hidden = true;
            field.insertAdjacentElement('afterend', feedback);
        }
        feedback.setAttribute('aria-live', 'polite');
        const descriptions = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
        descriptions.add(feedback.id);
        field.setAttribute('aria-describedby', [...descriptions].join(' '));
        feedbackByField.set(field, feedback);
    });

    const showFeedback = (field) => {
        const feedback = feedbackByField.get(field);
        if (!feedback) return;
        const invalid = !field.disabled && !field.validity.valid;
        let message = field.validationMessage;
        if (field.validity.valueMissing) {
            const label = field.labels?.[0] || field.parentElement.querySelector('label');
            const name = label?.textContent.replace(/\*/g, '').trim().replace(/\s+/g, ' ') || 'This field';
            message = `${name} is required.`;
        }
        feedback.textContent = invalid ? message : '';
        feedback.hidden = !invalid;
        field.classList.toggle('is-invalid', invalid);
        if (invalid) field.setAttribute('aria-invalid', 'true');
        else field.removeAttribute('aria-invalid');
    };

    const updateServices = (showError = false) => {
        const selected = services.some(field => field.checked);
        const donorEnabled = services.some(field => field.value === 'donor' && field.checked);
        services[0]?.setCustomValidity(selected ? '' : 'Select at least one service to continue.');
        servicesError.hidden = selected || !showError;
        if (!selected && showError) servicesError.textContent = 'Select at least one service to continue.';
        services.forEach(field => {
            field.classList.toggle('is-invalid', !selected && showError);
            if (!selected && showError) field.setAttribute('aria-invalid', 'true');
            else field.removeAttribute('aria-invalid');
        });
        donorFields.forEach(wrapper => {
            wrapper.hidden = !donorEnabled;
            const field = wrapper.querySelector('select');
            field.required = donorEnabled;
            if (!donorEnabled || touchedFields.has(field)) showFeedback(field);
        });
    };

    const updateBirthDate = () => {
        birthDate.setCustomValidity(birthDate.value && birthDate.value > birthDate.max
            ? 'Birth date must be before today.' : '');
    };

    const updateName = (field) => {
        field.setCustomValidity(field.required && field.value !== '' && field.value.trim() === ''
            ? 'Enter your name; spaces alone are not valid.' : '');
    };

    const updatePassword = () => {
        const value = password.value;
        const meetsRequirements = [...value].length >= 10 && /\p{Ll}/u.test(value) && /\p{Lu}/u.test(value)
            && /\p{N}/u.test(value) && /[\p{Z}\p{S}\p{P}]/u.test(value);
        password.setCustomValidity(value && !meetsRequirements
            ? 'Use 10 or more characters with uppercase, lowercase, a number, and a symbol.' : '');
    };

    services.forEach(field => field.addEventListener('change', () => updateServices(true)));
    fields.forEach(field => {
        const update = () => {
            if (field === birthDate) updateBirthDate();
            if (field === password) updatePassword();
            if (nameFields.includes(field)) updateName(field);
            if (touchedFields.has(field)) showFeedback(field);
            if (field === password) {
                const confirmation = document.getElementById('registrationPasswordConfirmation');
                if (touchedFields.has(confirmation)) showFeedback(confirmation);
            }
        };
        field.addEventListener('input', update);
        field.addEventListener('change', update);
        field.addEventListener('blur', () => {
            touchedFields.add(field);
            update();
        });
    });

    form.addEventListener('invalid', event => {
        if (services.includes(event.target)) updateServices(true);
        else {
            touchedFields.add(event.target);
            showFeedback(event.target);
        }
    }, true);

    form.addEventListener('submit', event => {
        updateServices(true);
        updateBirthDate();
        updatePassword();
        nameFields.forEach(updateName);
        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });

    updateServices(!servicesError.hidden);
    updateBirthDate();
    updatePassword();
    nameFields.forEach(updateName);
})();
