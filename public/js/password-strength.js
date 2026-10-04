(() => {
    const password = document.getElementById('registrationPassword');
    const confirmation = document.getElementById('registrationPasswordConfirmation');
    const strength = document.getElementById('passwordStrength');
    const match = document.getElementById('passwordMatch');
    const show = document.getElementById('showRegistrationPassword');
    const feedback = document.getElementById('passwordStrengthFeedback');
    if (!password || !confirmation || !strength || !match || !show) return;

    const label = strength.querySelector('strong');
    const bar = strength.querySelector('span');
    const meter = strength.querySelector('.cbis-password-strength-track');
    const contextFields = [
        'registrationFirstName', 'registrationMiddleName', 'registrationLastName',
        'registrationEmail', 'registrationMobile', 'birthDate',
    ].map(id => document.getElementById(id)).filter(Boolean);
    contextFields.push(...document.querySelectorAll('.js-negros-address select'));

    const updateStrength = () => {
        const userInputs = ['CBIS', 'Red Cross', ...contextFields.map(field => field.value)];
        const estimator = globalThis.CBISPasswordEstimator;
        const state = estimator
            ? estimator.estimatePasswordStrength(password.value, userInputs)
            : { label: password.value ? 'Strength unavailable' : 'Enter a password', width: 0, level: password.value ? 'unavailable' : 'empty', feedback: '', score: null };

        label.textContent = state.label;
        bar.style.width = `${state.width}%`;
        strength.dataset.strength = state.level;
        meter?.setAttribute('aria-valuenow', String(state.score ?? 0));
        meter?.setAttribute('aria-valuetext', state.label);
        if (feedback) {
            feedback.textContent = state.feedback;
            feedback.hidden = !state.feedback;
        }
    };

    const updateMatch = () => {
        const value = password.value;
        match.textContent = confirmation.value === '' ? '' : (confirmation.value === value ? 'Passwords match.' : 'Passwords do not match.');
        match.className = `small mt-2 ${confirmation.value === value ? 'text-success' : 'text-danger'}`;
        confirmation.setCustomValidity(confirmation.value !== '' && confirmation.value !== value ? 'Passwords do not match.' : '');
    };

    password.addEventListener('input', () => { updateStrength(); updateMatch(); });
    password.addEventListener('change', () => { updateStrength(); updateMatch(); });
    confirmation.addEventListener('input', updateMatch);
    confirmation.addEventListener('change', updateMatch);
    contextFields.forEach(field => {
        field.addEventListener('input', updateStrength);
        field.addEventListener('change', updateStrength);
    });
    show.addEventListener('change', () => {
        const type = show.checked ? 'text' : 'password';
        password.type = type;
        confirmation.type = type;
    });
    updateStrength();
    updateMatch();
})();
