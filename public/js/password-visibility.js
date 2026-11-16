(function () {
    function addPasswordToggle(input) {
        if (input.dataset.passwordVisibilityInitialized === 'true') {
            return;
        }

        let wrapper = input.closest('.password-visibility-field');
        if (!wrapper) {
            wrapper = document.createElement('span');
            wrapper.className = 'password-visibility-field';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.append(input);
        }

        const existingToggles = wrapper.querySelectorAll('.password-visibility-toggle');
        if (existingToggles.length > 0) {
            existingToggles.forEach(function (toggle, index) {
                if (index > 0) {
                    toggle.remove();
                }
            });
            input.dataset.passwordVisibilityInitialized = 'true';
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-visibility-toggle';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
        button.title = 'Show password';

        const icon = document.createElement('i');
        icon.className = 'bi bi-eye';
        icon.setAttribute('aria-hidden', 'true');
        button.append(icon);

        button.addEventListener('click', function () {
            const isVisible = input.type === 'password';

            input.type = isVisible ? 'text' : 'password';
            button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(isVisible));
            button.title = isVisible ? 'Hide password' : 'Show password';
            icon.classList.toggle('bi-eye', !isVisible);
            icon.classList.toggle('bi-eye-slash', isVisible);
        });

        wrapper.append(button);
        input.dataset.passwordVisibilityInitialized = 'true';
    }

    function addPasswordToggles(root) {
        if (root instanceof HTMLInputElement && root.type === 'password') {
            addPasswordToggle(root);
        }

        if (typeof root.querySelectorAll === 'function') {
            root.querySelectorAll('input[type="password"]').forEach(addPasswordToggle);
        }
    }

    function initializePasswordToggles() {
        addPasswordToggles(document);

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(addPasswordToggles);
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePasswordToggles, { once: true });
    } else {
        initializePasswordToggles();
    }
})();
