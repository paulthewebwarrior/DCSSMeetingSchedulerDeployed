(() => {
    'use strict';
    const state = document.getElementById('form-state');
    const old = state ? JSON.parse(state.textContent) : {};
    const forms = [...document.forms].filter(form => form.method.toLowerCase() === 'post');
    for (const form of forms) {
        const action = form.elements.namedItem('action');
        if ((!old.action && !action) || (action && action.value === old.action)) {
            for (const [name, value] of Object.entries(old)) {
                const field = form.elements.namedItem(name);
                if (field && field instanceof HTMLElement && field.type !== 'password' && name !== 'csrf_token' && name !== 'action') {
                    field.value = value;
                }
            }
            if (old.action === 'save_availability' && old.availability_id !== '-1') {
                document.getElementById('avail-form-title').textContent = 'Edit Consultation Hours';
                document.getElementById('btn-save-avail').textContent = 'Save Changes';
            }
        }

        const fields = [...form.querySelectorAll('input:not([type="hidden"]), select, textarea')];
        const clearError = field => {
            field.setCustomValidity('');
            field.removeAttribute('aria-invalid');
            const error = document.getElementById(field.dataset.errorId);
            if (error) error.remove();
            if (field.dataset.errorId) {
                const ids = (field.getAttribute('aria-describedby') || '').split(' ').filter(id => id !== field.dataset.errorId);
                if (ids.length) field.setAttribute('aria-describedby', ids.join(' '));
                else field.removeAttribute('aria-describedby');
            }
        };
        fields.forEach((field, index) => {
            field.dataset.errorId = `error-${forms.indexOf(form)}-${index}`;
            field.addEventListener('input', () => {
                // Cross-field validation is recalculated on every submission.
                fields.forEach(clearError);
            });
            field.addEventListener('change', () => fields.forEach(clearError));
            field.addEventListener('invalid', () => {
                field.setAttribute('aria-invalid', 'true');
                let error = document.getElementById(field.dataset.errorId);
                if (!error) {
                    error = document.createElement('p');
                    error.id = field.dataset.errorId;
                    error.className = 'field-error';
                    field.insertAdjacentElement('afterend', error);
                    field.setAttribute('aria-describedby', `${field.getAttribute('aria-describedby') || ''} ${error.id}`.trim());
                }
                error.textContent = field.validationMessage;
            });
        });

        form.noValidate = true;
        form.addEventListener('submit', event => {
            if (event.defaultPrevented) return;
            fields.forEach(clearError);
            for (const field of fields) {
                if (field.required && field.type !== 'password' && !field.value.trim()) {
                    field.setCustomValidity('Please fill in this field.');
                }
                if (field.name === 'email' && field.value && !/@ue\.edu\.ph$/i.test(field.value.trim())) {
                    field.setCustomValidity('Use your UE email address ending in @ue.edu.ph.');
                }
                if (field.type === 'password' && Array.from(field.value).length > 64) {
                    field.setCustomValidity('Password must be 64 characters or fewer.');
                }
                if (field.type === 'password' && field.minLength >= 6 && field.value && !field.value.trim()) {
                    field.setCustomValidity('Password cannot contain only spaces.');
                }
            }
            const confirm = form.elements.namedItem('confirm_password');
            const password = form.elements.namedItem('new_password') || form.elements.namedItem('password');
            if (confirm && password && confirm.value !== password.value) {
                confirm.setCustomValidity('Passwords do not match.');
            }
            const current = form.elements.namedItem('current_password');
            if (current && password && password.value && current.value === password.value) {
                password.setCustomValidity('Choose a password different from your current password.');
            }
            const start = form.elements.namedItem('start_time');
            const end = form.elements.namedItem('end_time');
            if (start && end && start.value && end.value && end.value <= start.value) {
                end.setCustomValidity('End time must be later than start time.');
            }
            if (!form.reportValidity()) {
                event.preventDefault();
                form.querySelector('[aria-invalid="true"]')?.focus();
                return;
            }
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = 'true';
            form.querySelectorAll('button[type="submit"]').forEach(button => {
                button.disabled = true;
                button.textContent = 'Please wait…';
            });
        });
        form.addEventListener('reset', () => fields.forEach(clearError));
    }
    const alert = document.querySelector('.alert-banner');
    if (alert) alert.focus();
    window.addEventListener('pageshow', event => {
        if (event.persisted) window.location.reload();
    });
})();
