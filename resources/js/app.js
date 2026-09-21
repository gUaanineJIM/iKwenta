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

        const hiddenInput = modal.querySelector('input[type="hidden"]:not([name="_token"]):not([name="_method"])');
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

    // =========================
    // LOGOUT LOADING STATE
    // =========================

    const logoutForm = document.querySelector('[data-logout-form]');

    if (logoutForm) {
        const logoutButton = logoutForm.querySelector('[data-logout-button]');
        const logoutLabel = logoutForm.querySelector('[data-logout-label]');

        logoutForm.addEventListener('submit', () => {
            if (logoutButton) {
                logoutButton.disabled = true;
                logoutButton.classList.add('is-loading');
                logoutButton.setAttribute('aria-busy', 'true');
            }

            if (logoutLabel) {
                logoutLabel.textContent = 'Signing out…';
            }
        });
    }

    // =========================
    // CUSTOMER DASHBOARD SECTIONS
    // =========================

    const customerSection = document.getElementById('customer-section');

    if (customerSection) {
        const sidebar = document.querySelector('[data-sidebar]');
        const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');
        const shell = document.querySelector('[data-shell]');
        const loginUrl = customerSection.getAttribute('data-login-url') || '/';

        const setToggleAria = (expanded) => {
            document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => {
                btn.setAttribute('aria-expanded', String(expanded));
            });
        };

        let collapsed = false;

        const getSavedCollapsed = () => {
            try {
                return localStorage.getItem('ikwenta-sidebar-collapsed');
            } catch {
                return null;
            }
        };

        const isMobileViewport = () => window.matchMedia('(max-width: 767px)').matches;
        const isTabletViewport = () =>
            window.matchMedia('(min-width: 768px) and (max-width: 1023px)').matches;

        let pendingSectionRequest = null;

        const setLoading = (loading) => {
            customerSection.classList.toggle('is-loading', loading);
            customerSection.setAttribute('aria-busy', loading ? 'true' : 'false');
        };

        const setActiveNav = (key) => {
            document.querySelectorAll('[data-nav]').forEach((link) => {
                const isActive = link.getAttribute('data-nav') === key;
                link.classList.toggle('is-active', isActive);

                if (isActive) {
                    link.setAttribute('aria-current', 'page');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        };

        const closeSidebar = () => {
            if (!sidebar) return;

            sidebar.classList.remove('is-open');
            setToggleAria(false);

            document.body.style.overflow = '';

            if (sidebarBackdrop) {
                sidebarBackdrop.classList.remove('is-visible');
                sidebarBackdrop.hidden = true;
            }
        };

        const openSidebar = () => {
            if (!sidebar) return;

            sidebar.classList.add('is-open');
            setToggleAria(true);

            document.body.style.overflow = 'hidden';

            if (sidebarBackdrop) {
                sidebarBackdrop.hidden = false;
                requestAnimationFrame(() => sidebarBackdrop.classList.add('is-visible'));
            }

            const firstLink = sidebar.querySelector('[data-section-link]');
            if (firstLink) firstLink.focus({ preventScroll: true });
        };

        const showLoadError = (url) => {
            customerSection.innerHTML =
                '<div class="empty-state" role="status">' +
                '<span class="empty-state__icon">!</span>' +
                '<div><strong>Something went wrong</strong>' +
                '<p>We could not load this section. Please try again.</p></div></div>' +
                '<a class="section-link" data-section-link href="' + url + '">Try again <span aria-hidden="true">→</span></a>';
        };

        const focusSectionHeading = () => {
            const heading = customerSection.querySelector('[data-section-title]');
            if (!heading) return;

            heading.setAttribute('tabindex', '-1');
            heading.focus({ preventScroll: true });

            const top = heading.getBoundingClientRect().top + window.pageYOffset - 96;
            window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
        };

        const loadSection = (url) => {
            setLoading(true);

            if (pendingSectionRequest) pendingSectionRequest.abort();
            const controller = new AbortController();
            pendingSectionRequest = controller;

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: controller.signal,
            })
                .then((response) => {
                    if (response.status === 401) {
                        window.location.assign(loginUrl);
                        throw new Error('Unauthenticated');
                    }

                    if (!response.ok) {
                        throw new Error('Request failed');
                    }

                    return response.text();
                })
                .then((html) => {
                    customerSection.innerHTML = html;

                    if (window.history && window.history.replaceState) {
                        window.history.replaceState(null, '', url);
                    }

                    setLoading(false);
                    focusSectionHeading();
                })
                .catch((error) => {
                    if (error.name === 'AbortError') return;
                    if (error.message === 'Unauthenticated') return;

                    setLoading(false);
                    showLoadError(url);
                })
                .finally(() => {
                    if (pendingSectionRequest === controller) {
                        pendingSectionRequest = null;
                        setLoading(false);
                    }
                });
        };

        const applyCollapsed = (value) => {
            collapsed = value;

            if (shell) shell.classList.toggle('collapsed', value);

            try {
                localStorage.setItem('ikwenta-sidebar-collapsed', value ? '1' : '0');
            } catch {}

            setToggleAria(!value);
        };

        if (document.querySelector('[data-sidebar-toggle]')) {
            const saved = getSavedCollapsed();

            // No saved preference: expanded on desktop, auto-reduced (icon-only)
            // on tablet while keeping navigation accessible.
            collapsed = saved !== null ? saved === '1' : isTabletViewport();
            applyCollapsed(collapsed);
        }

        // Handle everything with event delegation so it still works after
        // sections are swapped in via fetch.

        document.addEventListener('click', (event) => {
            const sectionLink = event.target.closest('[data-section-link]');
            if (sectionLink) {
                event.preventDefault();

                const key = sectionLink.getAttribute('data-nav');
                if (key) setActiveNav(key);

                loadSection(sectionLink.getAttribute('href'));
                closeSidebar();
                return;
            }

            const pageLink = event.target.closest('[data-pagination] a');
            if (pageLink) {
                event.preventDefault();
                loadSection(pageLink.getAttribute('href'));
                return;
            }

            const debtToggle = event.target.closest('[data-debt-toggle]');
            if (debtToggle) {
                const contentId = debtToggle.getAttribute('aria-controls');
                const content = contentId ? document.getElementById(contentId) : null;
                const isOpen = debtToggle.getAttribute('aria-expanded') === 'true';

                debtToggle.setAttribute('aria-expanded', String(!isOpen));
                debtToggle.classList.toggle('is-open', !isOpen);
                if (content) content.hidden = isOpen;

                const label = debtToggle.querySelector('span');
                if (label) label.textContent = isOpen ? 'View Details' : 'Hide Details';
                return;
            }

            const collapseToggle = event.target.closest('[data-collapse-toggle]');
            if (collapseToggle) {
                const panelId = collapseToggle.getAttribute('aria-controls');
                const panel = panelId ? document.getElementById(panelId) : null;
                const isOpen = collapseToggle.getAttribute('aria-expanded') === 'true';

                collapseToggle.setAttribute('aria-expanded', String(!isOpen));
                collapseToggle.classList.toggle('is-open', !isOpen);
                if (panel) panel.hidden = isOpen;
            }
        });

        document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (isMobileViewport()) {
                    if (sidebar && sidebar.classList.contains('is-open')) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                } else {
                    applyCollapsed(!collapsed);
                }
            });
        });

        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', closeSidebar);
        }

        // Resize across breakpoints: never leave the mobile drawer state stuck
        // when the viewport grows back to an inline sidebar.
        window.addEventListener('resize', () => {
            if (!isMobileViewport()) closeSidebar();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeSidebar();
        });
    }

    // =========================
    // OWNER DASHBOARD SIDEBAR
    // =========================

    const ownerShell = document.querySelector('[data-owner-shell]');

    if (ownerShell) {
        const ownerSidebar = document.querySelector('[data-owner-sidebar]');
        const ownerBackdrop = document.querySelector('[data-owner-backdrop]');

        const setOwnerToggleAria = (expanded) => {
            document.querySelectorAll('[data-owner-sidebar-toggle]').forEach((btn) => {
                btn.setAttribute('aria-expanded', String(expanded));
            });
        };

        const isOwnerMobileViewport = () => window.matchMedia('(max-width: 767px)').matches;
        const isOwnerTabletViewport = () =>
            window.matchMedia('(min-width: 768px) and (max-width: 1023px)').matches;

        let ownerCollapsed = false;

        const getSavedOwnerCollapsed = () => {
            try {
                return localStorage.getItem('ikwenta-owner-sidebar-collapsed');
            } catch {
                return null;
            }
        };

        const closeOwnerSidebar = () => {
            if (!ownerSidebar) return;

            ownerSidebar.classList.remove('is-open');
            setOwnerToggleAria(false);

            document.body.style.overflow = '';

            if (ownerBackdrop) {
                ownerBackdrop.classList.remove('is-visible');
                ownerBackdrop.hidden = true;
            }
        };

        const openOwnerSidebar = () => {
            if (!ownerSidebar) return;

            ownerSidebar.classList.add('is-open');
            setOwnerToggleAria(true);

            document.body.style.overflow = 'hidden';

            if (ownerBackdrop) {
                ownerBackdrop.hidden = false;
                requestAnimationFrame(() => ownerBackdrop.classList.add('is-visible'));
            }
        };

        const applyOwnerCollapsed = (value) => {
            ownerCollapsed = value;

            ownerShell.classList.toggle('collapsed', value);

            try {
                localStorage.setItem('ikwenta-owner-sidebar-collapsed', value ? '1' : '0');
            } catch {}

            setOwnerToggleAria(!value);
        };

        if (document.querySelector('[data-owner-sidebar-toggle]')) {
            const saved = getSavedOwnerCollapsed();

            ownerCollapsed = saved !== null ? saved === '1' : isOwnerTabletViewport();
            applyOwnerCollapsed(ownerCollapsed);
        }

        document.querySelectorAll('[data-owner-sidebar-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (isOwnerMobileViewport()) {
                    if (ownerSidebar && ownerSidebar.classList.contains('is-open')) {
                        closeOwnerSidebar();
                    } else {
                        openOwnerSidebar();
                    }
                } else {
                    applyOwnerCollapsed(!ownerCollapsed);
                }
            });
        });

        if (ownerBackdrop) {
            ownerBackdrop.addEventListener('click', closeOwnerSidebar);
        }

        window.addEventListener('resize', () => {
            if (!isOwnerMobileViewport()) closeOwnerSidebar();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeOwnerSidebar();
        });
    }
})();