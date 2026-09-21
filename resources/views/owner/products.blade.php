@extends('layouts.owner', ['activeSection' => 'products'])

@section('title', 'Products | iKwenta')

@section('content')
    <section class="owner-section owner-products" aria-label="Products">
        {{-- =================== Heading =================== --}}
        <div class="page-heading owner-products__heading">
            <div>
                <span class="owner-eyebrow"> Product Catalog </span>

                <h1>Products</h1>

                <p>Manage the products available in your store.</p>
            </div>
        </div>

        {{-- =================== Toolbar =================== --}}
        <div class="owner-products__toolbar">
            <label class="owner-products__search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" />
                    <path d="M21 21L16.5 16.5" />
                </svg>

                <input type="search" placeholder="Search products…" data-product-search autocomplete="off"
                    aria-label="Search products" />

                <button type="button" class="owner-products__search-clear" data-search-clear hidden aria-label="Clear search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>
            </label>

            <button type="button" class="owner-btn owner-btn--primary" data-open-modal="add-product-modal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5V19M5 12H19" />
                </svg>

                <span>Add Product</span>
            </button>
        </div>

        {{-- =================== Product list region =================== --}}
        <div id="products-region" data-products-region>
            @include('components.owner.products-list', ['products' => $products, 'q' => ($q ?? '')])
        </div>
    </section>

    {{-- =================== Add Product modal =================== --}}
    <div class="modal owner-modal" id="add-product-modal" aria-hidden="true">
        <div class="modal-backdrop" data-modal-close></div>

        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="add-product-title">
            <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>

            <div class="owner-modal__head">
                <span class="owner-modal__head-icon" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 8L12 3L3 8V16L12 21L21 16V8Z" />
                        <path d="M3 8L12 13L21 8" />
                        <path d="M12 13V21" />
                    </svg>
                </span>

                <div>
                    <span class="modal-badge"> Inventory </span>

                    <h2 class="modal-title" id="add-product-title">Add Products</h2>

                    <p class="modal-subtitle">Add one or more products and save them all in one go.</p>
                </div>
            </div>

            <form id="add-product-form" class="owner-modal__form" novalidate>
                @csrf

                <div class="owner-modal__rows" data-product-rows></div>

                <button type="button" class="owner-modal__add-row" data-add-row>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" aria-hidden="true">
                        <path d="M12 5V19M5 12H19" />
                    </svg>

                    <span>Add another product</span>
                </button>

                <p class="owner-modal__error" data-form-error hidden>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 8V12M12 16H12.01" />
                    </svg>

                    <span>Please complete the highlighted fields before saving.</span>
                </p>

                <div class="owner-modal__summary">
                    <span class="owner-modal__summary-count" data-summary-count>1 product ready to save</span>

                    <span class="owner-modal__total" data-summary-total>₱ 0.00</span>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close> Cancel </button>

                    <button type="submit" class="btn btn-primary" id="add-product-submit">
                        <span>Save Products</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- =================== Delete product confirm modal =================== --}}
    <div class="modal owner-modal owner-modal--danger" id="delete-product-modal" aria-hidden="true">
        <div class="modal-backdrop" data-modal-close></div>

        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-product-title">
            <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>

            <div class="owner-modal__head">
                <span class="owner-modal__head-icon owner-modal__head-icon--danger" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18" />
                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        <path d="M10 11v6M14 11v6" />
                    </svg>
                </span>

                <div>
                    <span class="modal-badge"> Catalog </span>

                    <h2 class="modal-title" id="delete-product-title">Delete product?</h2>

                    <p class="modal-subtitle" data-delete-product-name>This product will be permanently removed.</p>
                </div>
            </div>

            <p class="owner-modal__error" data-delete-error hidden>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 8V12M12 16H12.01" />
                </svg>

                <span></span>
            </p>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-modal-close> Cancel </button>

                <button type="button" class="btn btn-primary owner-modal__delete-btn" id="delete-product-confirm">
                    <span>Delete Product</span>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('add-product-modal');
            if (!modal) return;

            const form = modal.querySelector('#add-product-form');
            const rowsWrap = modal.querySelector('[data-product-rows]');
            const addRowBtn = modal.querySelector('[data-add-row]');
            const summaryCount = modal.querySelector('[data-summary-count]');
            const summaryTotal = modal.querySelector('[data-summary-total]');
            const formError = modal.querySelector('[data-form-error]');
            const submit = modal.querySelector('#add-product-submit');
            const close = modal.querySelector('[data-modal-close]');
            const productsRegion = document.querySelector('[data-products-region]');
            const modalTitle = modal.querySelector('.modal-title');
            const modalSubtitle = modal.querySelector('.modal-subtitle');
            const summary = modal.querySelector('.owner-modal__summary');

            const deleteModal = document.getElementById('delete-product-modal');
            const deleteNameEl = deleteModal ? deleteModal.querySelector('[data-delete-product-name]') : null;
            const deleteError = deleteModal ? deleteModal.querySelector('[data-delete-error]') : null;
            const deleteErrorText = deleteError ? deleteError.querySelector('span') : null;
            const deleteConfirm = document.getElementById('delete-product-confirm');

            const searchInput = document.querySelector('[data-product-search]');
            const searchClear = document.querySelector('[data-search-clear]');

            const csrfToken = form.querySelector('[name="_token"]').value;

            const SUBMIT_LABEL = 'Save Products';
            const ADD_TITLE = 'Add Products';
            const ADD_SUBTITLE = 'Add one or more products and save them all in one go.';
            const EDIT_TITLE = 'Edit Product';
            const EDIT_SUBTITLE = 'Update the details of this product.';
            const ERROR_MESSAGE = 'Something went wrong while saving. Please try again.';
            const POLL_INTERVAL = 6000;

            let editingId = null;
            let deleteId = null;

            const money = (amount) => '₱' + amount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const formatPrice = (value) => {
                const parts = value.replace(/[^0-9.]/g, '').split('.');
                return parts[0] + (parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '');
            };

            const debounce = (fn, delay) => {
                let timer;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn(...args), delay);
                };
            };

            const showToast = (message, type = 'success') => {
                const stack = document.getElementById('toast-stack');
                if (!stack || !message) return;

                const toast = document.createElement('div');
                toast.className = 'toast toast--' + type;
                toast.setAttribute('role', 'status');

                const icon = document.createElement('span');
                icon.className = 'toast__icon';
                icon.setAttribute('aria-hidden', 'true');
                icon.textContent = type === 'success' ? '✓' : type === 'danger' ? '!' : 'i';

                const text = document.createElement('span');
                text.className = 'toast__message';
                text.textContent = message;

                const close = document.createElement('button');
                close.type = 'button';
                close.className = 'toast__close';
                close.setAttribute('aria-label', 'Dismiss notification');
                close.textContent = '×';
                close.addEventListener('click', dismiss);

                toast.append(icon, text, close);
                stack.appendChild(toast);

                let dismissed = false;
                let timer = null;

                function dismiss() {
                    if (dismissed) return;
                    dismissed = true;
                    clearTimeout(timer);
                    toast.classList.add('toast--leaving');
                    setTimeout(() => toast.remove(), 220);
                }

                timer = setTimeout(dismiss, 4200);
            };

            const buildRow = () => {
                const row = document.createElement('div');
                row.className = 'owner-modal__row';
                row.dataset.row = '';
                row.innerHTML = [
                    '<span class="owner-modal__row-index" data-row-index>1</span>',
                    '<div class="owner-modal__row-fields">',
                    '  <div class="form-field owner-modal__field">',
                    '    <label class="form-label">Product Name*</label>',
                    '    <input class="form-input" data-name type="text" required maxlength="150" placeholder="e.g. Rice 5kg" autocomplete="off">',
                    '  </div>',
                    '  <div class="form-field owner-modal__field">',
                    '    <label class="form-label">Price*</label>',
                    '    <div class="owner-modal__price">',
                    '      <span class="owner-modal__price-symbol" aria-hidden="true">₱</span>',
                    '      <input class="form-input" data-price type="text" inputmode="decimal" required placeholder="0.00" autocomplete="off">',
                    '    </div>',
                    '  </div>',
                    '</div>',
                    '<button type="button" class="owner-modal__remove" data-remove-row aria-label="Remove product">',
                    '  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>',
                    '</button>',
                    '<div class="owner-modal__row-desc">',
                    '  <div class="form-field owner-modal__field">',
                    '    <label class="form-label">Description <span class="owner-modal__optional">(optional)</span></label>',
                    '    <textarea class="form-input owner-modal__textarea" data-desc rows="2" placeholder="Short description of the product"></textarea>',
                    '  </div>',
                    '</div>'
                ].join('');
                return row;
            };

            const rows = () => [...rowsWrap.querySelectorAll('[data-row]')];

            const updateSummary = () => {
                const list = rows();
                const count = list.length;
                const total = list.reduce((sum, row) => {
                    const value = parseFloat(row.querySelector('[data-price]').value);
                    return sum + (isNaN(value) ? 0 : value);
                }, 0);

                summaryCount.textContent = count + (count === 1 ? ' product ready to save' : ' products ready to save');
                summaryTotal.textContent = money(total);
                summaryTotal.classList.toggle('owner-modal__total--zero', !total);

                list.forEach((row, i) => {
                    row.querySelector('[data-row-index]').textContent = String(i + 1);
                    row.querySelector('[data-remove-row]').disabled = count === 1;
                });
            };

            const addRow = () => {
                const row = buildRow();
                rowsWrap.appendChild(row);

                row.querySelector('[data-price]').addEventListener('input', (event) => {
                    event.target.value = formatPrice(event.target.value);
                    event.target.classList.remove('is-invalid');
                    formError.hidden = true;
                    updateSummary();
                });

                row.querySelector('[data-price]').addEventListener('change', (event) => {
                    if (event.target.value) {
                        event.target.value = formatPrice(parseFloat(event.target.value).toFixed(2));
                    }
                    updateSummary();
                });

                row.querySelector('[data-name]').addEventListener('input', (event) => {
                    event.target.classList.remove('is-invalid');
                    formError.hidden = true;
                });

                row.querySelector('[data-remove-row]').addEventListener('click', () => {
                    if (rows().length === 1) return;
                    row.classList.add('owner-modal__row--leaving');
                    setTimeout(() => {
                        row.remove();
                        updateSummary();
                    }, 220);
                });

                updateSummary();
            };

            const resetForm = () => {
                editingId = null;
                rowsWrap.innerHTML = '';
                formError.hidden = true;
                setModalMode();
                addRow();
            };

            const setModalMode = () => {
                const editing = editingId !== null;

                modalTitle.textContent = editing ? EDIT_TITLE : ADD_TITLE;
                modalSubtitle.textContent = editing ? EDIT_SUBTITLE : ADD_SUBTITLE;
                addRowBtn.hidden = editing;
                summary.hidden = editing;

                const label = submit.querySelector('span');
                if (label) label.textContent = editing ? 'Save Changes' : SUBMIT_LABEL;
            };

            const showModal = (element) => {
                element.classList.add('is-open');
                element.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };

            const hideModal = (element) => {
                element.classList.remove('is-open');
                element.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            const openEdit = (card) => {
                resetForm();

                const name = card.querySelector('.owner-product-card__name').textContent.trim();
                const priceText = card.querySelector('.owner-product-card__price').textContent.replace(/[^0-9.]/g, '');
                const descEl = card.querySelector('.owner-product-card__desc');

                const row = rows()[0];
                row.querySelector('[data-name]').value = name;
                row.querySelector('[data-price]').value = priceText;
                row.querySelector('[data-desc]').value = descEl ? descEl.textContent.trim() : '';

                editingId = card.dataset.productId;
                setModalMode();
                updateSummary();
                showModal(modal);
                row.querySelector('[data-name]').focus();
            };

            const openDelete = (card) => {
                deleteId = card.dataset.productId;
                deleteNameEl.textContent = 'This will permanently remove "' +
                    card.querySelector('.owner-product-card__name').textContent.trim() +
                    '" from the catalog.';
                deleteError.hidden = true;

                const label = deleteConfirm.querySelector('span');
                if (label) label.textContent = 'Delete Product';
                deleteConfirm.disabled = false;
                deleteConfirm.classList.remove('is-loading');

                showModal(deleteModal);
            };

            const runDeleteConfirm = async () => {
                deleteError.hidden = true;

                const label = deleteConfirm.querySelector('span');

                deleteConfirm.disabled = true;
                deleteConfirm.classList.add('is-loading');
                if (label) label.textContent = 'Deleting…';

                try {
                    const response = await fetch('{{ route('owner.products.destroy', ['product' => '__ID__']) }}'.replace('__ID__', deleteId), {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const message = payload.message || 'Unable to delete this product.';
                        if (deleteErrorText) deleteErrorText.textContent = message;
                        deleteError.hidden = false;
                        showToast(message, 'danger');

                        deleteConfirm.disabled = false;
                        deleteConfirm.classList.remove('is-loading');
                        if (label) label.textContent = 'Delete Product';
                        return;
                    }

                    hideModal(deleteModal);
                    showToast(payload.message || 'Product deleted.', 'success');
                    await refreshProducts();
                } catch (error) {
                    const message = 'Something went wrong while deleting. Please try again.';
                    if (deleteErrorText) deleteErrorText.textContent = message;
                    deleteError.hidden = false;
                    showToast(message, 'danger');

                    deleteConfirm.disabled = false;
                    deleteConfirm.classList.remove('is-loading');
                    if (label) label.textContent = 'Delete Product';
                }
            };

            if (deleteConfirm) deleteConfirm.addEventListener('click', runDeleteConfirm);

            const validate = () => {
                let valid = true;
                let firstInvalid = null;

                rows().forEach((row) => {
                    const name = row.querySelector('[data-name]');
                    const price = row.querySelector('[data-price]');
                    const priceValue = parseFloat(price.value);

                    const nameBad = !name.value.trim();
                    const priceBad = !(priceValue > 0);

                    name.classList.toggle('is-invalid', nameBad);
                    price.classList.toggle('is-invalid', priceBad);

                    if (nameBad || priceBad) valid = false;
                    if ((nameBad || priceBad) && !firstInvalid) {
                        firstInvalid = nameBad ? name : price;
                    }
                });

                formError.hidden = valid;
                if (firstInvalid) firstInvalid.focus();
                return valid;
            };

            const refreshProducts = async () => {
                if (!productsRegion || document.hidden) return;

                try {
                    const url = new URL('{{ route('owner.products.list') }}', window.location.origin);
                    const term = searchInput ? searchInput.value.trim() : '';
                    if (term) url.searchParams.set('q', term);

                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });

                    if (!response.ok) return;

                    const html = await response.text();
                    if (html && html.trim() !== productsRegion.innerHTML.trim()) {
                        productsRegion.innerHTML = html;
                    }
                } catch (error) {
                    // silent: keep polling
                }
            };

            const applyServerErrors = (errors) => {
                if (editingId !== null) {
                    const row = rows()[0];
                    if (!row) return;

                    row.querySelector('[data-name]').classList.toggle('is-invalid', Boolean(errors.product_name));
                    row.querySelector('[data-price]').classList.toggle('is-invalid', Boolean(errors.price));
                    return;
                }

                rows().forEach((row, index) => {
                    const name = row.querySelector('[data-name]');
                    const price = row.querySelector('[data-price]');
                    const nameError = errors['products.' + index + '.product_name'];
                    const priceError = errors['products.' + index + '.price'];

                    name.classList.toggle('is-invalid', Boolean(nameError));
                    price.classList.toggle('is-invalid', Boolean(priceError));
                });
            };

            const showFormError = (message) => {
                formError.querySelector('span').textContent = message;
                formError.hidden = false;
            };

            if (addRowBtn) addRowBtn.addEventListener('click', addRow);

            if (searchInput) {
                searchInput.addEventListener('input', debounce(() => {
                    if (searchClear) searchClear.hidden = searchInput.value.length === 0;
                    refreshProducts();
                }, 250));

                searchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && searchInput.value) {
                        searchInput.value = '';
                        if (searchClear) searchClear.hidden = true;
                        refreshProducts();
                    }
                });
            }

            if (searchClear) {
                searchClear.addEventListener('click', (event) => {
                    event.preventDefault();
                    searchInput.value = '';
                    searchClear.hidden = true;
                    refreshProducts();
                    searchInput.focus();
                });
            }

            if (form) {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (!validate()) return;

                    const first = rows()[0];
                    const isEditing = editingId !== null;
                    const payload = isEditing
                        ? {
                            product_name: first.querySelector('[data-name]').value.trim(),
                            price: first.querySelector('[data-price]').value,
                            description: first.querySelector('[data-desc]').value.trim(),
                        }
                        : {
                            products: rows().map((row) => ({
                                product_name: row.querySelector('[data-name]').value.trim(),
                                price: row.querySelector('[data-price]').value,
                                description: row.querySelector('[data-desc]').value.trim(),
                            })),
                        };

                    const url = isEditing
                        ? '{{ route('owner.products.update', ['product' => '__ID__']) }}'.replace('__ID__', editingId)
                        : '{{ route('owner.products.store') }}';

                    const label = submit.querySelector('span');
                    let failureMessage = ERROR_MESSAGE;

                    submit.disabled = true;
                    submit.classList.add('is-loading');
                    if (label) label.textContent = 'Saving…';

                    try {
                        const response = await fetch(url, {
                            method: isEditing ? 'PUT' : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(payload),
                        });

                        const serverPayload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            failureMessage = serverPayload.message || ERROR_MESSAGE;
                            applyServerErrors(serverPayload.errors || {});
                            showFormError(failureMessage);
                            throw new Error(failureMessage);
                        }

                        if (label) label.textContent = serverPayload.message || SUBMIT_LABEL;
                        showToast(
                            serverPayload.message || (isEditing ? 'Product updated.' : rows().length + ' product(s) saved.'),
                            'success'
                        );

                        await refreshProducts();

                        setTimeout(() => {
                            resetForm();
                            if (label) label.textContent = SUBMIT_LABEL;
                            submit.disabled = false;
                            submit.classList.remove('is-loading');
                            if (close) close.click();
                        }, 500);
                    } catch (error) {
                        if (label) label.textContent = 'Retry';
                        submit.disabled = false;
                        submit.classList.remove('is-loading');
                        showToast(failureMessage, 'danger');
                    }
                });
            }

            document.addEventListener('click', (event) => {
                const deleteBtn = event.target.closest('[data-delete-product]');
                if (deleteBtn) {
                    openDelete(deleteBtn.closest('[data-product-id]'));
                    return;
                }

                const editBtn = event.target.closest('[data-edit-product]');
                if (editBtn) {
                    openEdit(editBtn.closest('[data-product-id]'));
                    return;
                }

                const opener = event.target.closest('[data-open-modal="add-product-modal"]');
                if (opener) resetForm();
            });

            refreshProducts();
            setInterval(refreshProducts, POLL_INTERVAL);

            document.addEventListener('visibilitychange', refreshProducts);

            resetForm();
        })();
    </script>
@endpush