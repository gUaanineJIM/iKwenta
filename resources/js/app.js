(() => {
    // =========================
    // THEME
    // =========================

    const html = document.documentElement;
    const themeToggle = document.querySelector('[data-theme-toggle]');

    const applyTheme = (theme) => {
        html.setAttribute('data-theme', theme === 'dark' ? 'dark' : 'light');
    };

    const getSavedTheme = () => {
        try {
            return localStorage.getItem('ikwenta-theme');
        } catch {
            return null;
        }
    };

    const getSystemTheme = () =>
        window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

    const setTheme = (theme) => {
        applyTheme(theme);
        try {
            localStorage.setItem('ikwenta-theme', theme);
        } catch {}
    };

    applyTheme(getSavedTheme() || getSystemTheme());

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            setTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        });
    }

    // =========================
    // MODALS
    // =========================

    const openModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        const autofocus = modal.querySelector('input:not([type="hidden"]), button');
        if (autofocus && window.innerWidth > 768) autofocus.focus();
    };

    const closeModal = (modal) => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        const hiddenInput = modal.querySelector('input[type="hidden"]');
        if (hiddenInput) hiddenInput.value = '';
    };

    const closeAllModals = () => {
        document.querySelectorAll('.modal.is-open').forEach(closeModal);
    };

    // Open triggers
    document.querySelectorAll('[data-open-modal]').forEach((trigger) => {
        const targetId = trigger.getAttribute('data-open-modal');
        const handler = (e) => {
            e.preventDefault();
            closeAllModals();
            setTimeout(() => openModal(targetId), 120);
        };

        trigger.addEventListener('click', handler);

        if (trigger.tagName === 'A') {
            trigger.setAttribute('role', 'button');
            trigger.tabIndex = 0;
            trigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') handler(e);
            });
        }
    });

    // Close triggers (includes backdrop)
    document.querySelectorAll('[data-modal-close]').forEach((el) => {
        el.addEventListener('click', () => {
            const modal = el.closest('.modal');
            if (modal) closeModal(modal);
        });
    });

    // Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.is-open').forEach(closeModal);
        }

        // Switch modals with arrow keys inside modal
        const active = document.activeElement;
        if (active && active.classList.contains('modal-switch')) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                const link = active.querySelector('a');
                if (link) link.click();
            }
        }
    });

    // Sync with back button / history
    if (window.history && window.history.pushState) {
        history.replaceState({ modal: null }, '');
        window.addEventListener('popstate', (e) => {
            if (!e.state || !e.state.modal) {
                closeAllModals();
                return;
            }
        });
    }

    // =========================
    // 6-DIGIT CODE INPUT
    // =========================

    document.querySelectorAll('[data-code-input]').forEach((group) => {
        const inputs = Array.from(group.querySelectorAll('.code-input'));
        const targetInput = document.getElementById(group.getAttribute('data-target'));

        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                const value = input.value.replace(/\D/g, '').slice(0, 1);
                input.value = value;
                input.classList.toggle('filled', value !== '');

                updateCodeValue();

                if (value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    e.preventDefault();
                    inputs[index - 1].focus();
                    inputs[index - 1].value = '';
                    inputs[index - 1].classList.remove('filled');
                    updateCodeValue();
                }

                if (['ArrowLeft', 'ArrowRight'].includes(e.key)) {
                    e.preventDefault();
                    const next = e.key === 'ArrowLeft' ? inputs[index - 1] : inputs[index + 1];
                    if (next) next.focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, inputs.length);
                pasted.split('').forEach((char, i) => {
                    if (inputs[i]) {
                        inputs[i].value = char;
                        inputs[i].classList.add('filled');
                    }
                });
                const lastIndex = Math.min(pasted.length, inputs.length) - 1;
                if (lastIndex >= 0) inputs[lastIndex].focus();
                updateCodeValue();
            });
        });

        const updateCodeValue = () => {
            if (targetInput) targetInput.value = inputs.map((i) => i.value).join('');
        };

        group.closest('form').addEventListener('submit', () => updateCodeValue());
    });

    // =========================
    // PASSWORD TOGGLE
    // =========================

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.getAttribute('data-password-toggle'));
            if (!input) return;

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';

            const icon = button.querySelector('[data-password-icon]');
            if (icon) icon.textContent = show ? '🙈' : '👁';

            input.focus();
        });
    });
})();