((root, factory) => {
    if (typeof module === 'object' && module.exports) {
        module.exports = factory(require('zxcvbn'));
    } else {
        root.CBISPasswordEstimator = factory(root.zxcvbn);
    }
})(typeof globalThis !== 'undefined' ? globalThis : this, (zxcvbn) => {
    const levels = [
        { level: 'weak', label: 'Weak', width: 25 },
        { level: 'weak', label: 'Weak', width: 25 },
        { level: 'medium', label: 'Medium', width: 50 },
        { level: 'strong', label: 'Strong', width: 75 },
        { level: 'very-strong', label: 'Very Strong', width: 100 },
    ];

    const collectPasswordUserInputs = (values = []) => {
        const inputs = new Set();
        values.forEach(value => {
            if (typeof value !== 'string' || !value.trim()) return;
            const normalized = value.trim().toLowerCase().normalize('NFKC');
            inputs.add(normalized);
            (normalized.match(/[\p{L}\p{N}]+/gu) || []).forEach(word => {
                if (word.length > 1) inputs.add(word);
            });
            if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
                const [year, month, day] = normalized.split('-');
                inputs.add(`${year}${month}${day}`);
                inputs.add(`${day}${month}${year}`);
                inputs.add(`${month}${day}${year}`);
            }
        });
        return [...inputs];
    };

    const estimatePasswordStrength = (password, userInputs = []) => {
        if (!password) {
            return { level: 'empty', label: 'Enter a password', width: 0, score: null, feedback: '', estimatedGuessBits: 0 };
        }
        if (typeof zxcvbn !== 'function') {
            return { level: 'unavailable', label: 'Strength unavailable', width: 0, score: null, feedback: 'Password requirements still apply.', estimatedGuessBits: null };
        }

        const result = zxcvbn(password, collectPasswordUserInputs(userInputs));
        const usesPersonalWords = result.sequence.some(match => match.dictionary_name === 'user_inputs');
        const feedback = usesPersonalWords
            ? 'Avoid names, dates, and words associated with your account.'
            : [result.feedback.warning, result.feedback.suggestions[0]].filter(Boolean).join(' ');

        return {
            ...levels[result.score],
            score: result.score,
            feedback,
            estimatedGuessBits: Math.log2(result.guesses),
        };
    };

    return { collectPasswordUserInputs, estimatePasswordStrength };
});
