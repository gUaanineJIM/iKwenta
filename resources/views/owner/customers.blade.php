@extends('layouts.owner', ['activeSection' => 'customers'])

@section('title', 'Customers | iKwenta')

@section('content')
    <section class="owner-section owner-customers" data-customers-page>
        <div class="page-heading owner-products__heading">
            <div><span class="owner-eyebrow">Customer Accounts</span><h1>Customers</h1><p>Track what each customer owes and record payments in seconds.</p></div>
        </div>

        <div class="owner-products__toolbar">
            <label class="owner-products__search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M21 21L16.5 16.5"></path>
                </svg>
                <input type="search" placeholder="Search customers" data-customer-search aria-label="Search customers"></label>
            <button type="button" class="owner-btn owner-btn--primary" data-open-modal="add-customer-modal"><span>＋</span><span>Add Customer</span></button>
        </div>

        <div id="customers-region" data-customers-region>@include('components.owner.customers-list', ['customerGroups' => $customerGroups, 'q' => $q])</div>
    </section>

    <div class="modal owner-modal" id="add-customer-modal" aria-hidden="true">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="customer-modal-title">
            <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>
            <div class="owner-modal__head"><div><span class="modal-badge">Customer account</span><h2 class="modal-title" id="customer-modal-title">Add Customer</h2><p class="modal-subtitle">Create a portal code and record the opening debt.</p></div></div>
            <form class="owner-modal__form" data-customer-form enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="customer_id">
                <label class="field-label">Customer name<input class="form-input" name="full_name" required maxlength="150"></label>
                <fieldset class="customer-gender"><legend>Gender</legend><label><input type="radio" name="gender" value="male" required> Male</label><label><input type="radio" name="gender" value="female"> Female</label></fieldset>
                <label class="field-label">Profile picture <span class="field-hint">Optional</span><input class="form-input" name="avatar" type="file" accept="image/jpeg,image/png,image/webp"></label>
                <label class="field-label">Loaned on (Philippine time)<input class="form-input" name="loaned_at" type="datetime-local" value="{{ now('Asia/Manila')->format('Y-m-d\\TH:i') }}" required></label>
                <fieldset class="customer-debt-types">
                    <legend class="customer-debt-types__legend">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20.59 13.41L11 3.83A2 2 0 0 0 9.59 3.24H4a1 1 0 0 0-1 1v5.59a2 2 0 0 0 .59 1.41l9.58 9.58a2 2 0 0 0 2.83 0l4.59-4.59a2 2 0 0 0 0-2.82z"/>
                            <circle cx="7.5" cy="7.5" r="1.5"/>
                        </svg>
                        <span>Type of debt</span>
                    </legend>
                    <label class="debt-type-card">
                        <span class="debt-type-card__icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 8L12 3L3 8V16L12 21L21 16V8Z"/>
                                <path d="M3 8L12 13L21 8"/>
                                <path d="M12 13V21"/>
                            </svg>
                        </span>
                        <span class="debt-type-card__label">Product</span>
                        <input type="checkbox" name="debt_types[]" value="product" data-type-toggle="product">
                    </label>
                    <label class="debt-type-card">
                        <span class="debt-type-card__icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="6" width="20" height="12" rx="2"/>
                                <circle cx="12" cy="12" r="2.5"/>
                                <path d="M6 12h.01M18 12h.01"/>
                            </svg>
                        </span>
                        <span class="debt-type-card__label">Money</span>
                        <input type="checkbox" name="debt_types[]" value="money" data-type-toggle="money">
                    </label>
                </fieldset>
                <div data-product-fields hidden><div class="customer-product-rows" data-product-rows></div><button type="button" class="owner-modal__add-row" data-add-product>＋ Add product</button></div>
                <label class="field-label" data-money-fields hidden>Money owed (₱)<input class="form-input" name="money_amount" type="number" min="0.01" step="0.01" placeholder="0.00"></label>
                <p class="owner-modal__error" data-customer-error hidden></p>
                <div class="modal-actions"><button type="button" class="btn btn-secondary" data-modal-close>Cancel</button><button type="submit" class="btn btn-primary">Save Customer</button></div>
            </form>
        </div>
    </div>

    <div class="modal owner-modal" id="payment-modal" aria-hidden="true">
        <div class="modal-backdrop" data-modal-close></div><div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="payment-title"><button type="button" class="modal-close" data-modal-close aria-label="Close">×</button><span class="modal-badge">Payment</span><h2 class="modal-title" id="payment-title">Record payment</h2><p class="modal-subtitle" data-payment-name></p><form class="owner-modal__form" data-payment-form>@csrf<input type="hidden" name="customer_id"><label class="field-label">Amount paid (₱)<input class="form-input" name="amount" type="number" min="0.01" step="0.01" required placeholder="0.00"></label><p class="owner-modal__error" data-payment-error hidden></p><div class="modal-actions"><button type="button" class="btn btn-secondary" data-modal-close>Cancel</button><button type="button" class="btn btn-secondary" data-pay-in-full>Paid in Full</button><button type="submit" class="btn btn-primary">Apply payment</button></div></form></div>
    </div>

    <div class="modal owner-modal" id="pay-in-full-modal" aria-hidden="true">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="pay-in-full-title">
            <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>
            <div class="owner-modal__head">
                <span class="owner-modal__head-icon" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <path d="M22 4L12 14.01l-3-3"/>
                    </svg>
                </span>
                <div>
                    <span class="modal-badge">Payment</span>
                    <h2 class="modal-title" id="pay-in-full-title">Mark as paid in full?</h2>
                    <p class="modal-subtitle" data-pay-in-full-name></p>
                </div>
            </div>
            <p class="owner-modal__error" data-pay-in-full-error hidden>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8V12M12 16H12.01"/>
                </svg>
                <span></span>
            </p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="button" class="btn btn-primary" id="pay-in-full-confirm">Yes, settle balance</button>
            </div>
        </div>
    </div>

    <div class="modal owner-modal owner-modal--danger" id="delete-customer-modal" aria-hidden="true">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-customer-title">
            <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>
            <div class="owner-modal__head">
                <span class="owner-modal__head-icon owner-modal__head-icon--danger" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"/>
                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                    </svg>
                </span>
                <div>
                    <span class="modal-badge">Customer account</span>
                    <h2 class="modal-title" id="delete-customer-title">Delete customer?</h2>
                    <p class="modal-subtitle" data-delete-customer-name>This customer will be permanently removed.</p>
                </div>
            </div>
            <p class="owner-modal__error" data-delete-error hidden>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8V12M12 16H12.01"/>
                </svg>
                <span></span>
            </p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="button" class="btn btn-primary owner-modal__delete-btn" id="delete-customer-confirm">Delete Customer</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const page = document.querySelector('[data-customers-page]'); if (!page) return;
    const region = document.querySelector('[data-customers-region]'), form = document.querySelector('[data-customer-form]'), paymentForm = document.querySelector('[data-payment-form]');
    const token = form.querySelector('[name=_token]').value, addModal = document.getElementById('add-customer-modal'), paymentModal = document.getElementById('payment-modal'), payInFullModal = document.getElementById('pay-in-full-modal'), deleteModal = document.getElementById('delete-customer-modal');
    let pendingDeleteId = null;
    const deleteNameEl = deleteModal.querySelector('[data-delete-customer-name]'), deleteError = deleteModal.querySelector('[data-delete-error]'), deleteConfirmBtn = document.getElementById('delete-customer-confirm');
    const payInFullNameEl = payInFullModal.querySelector('[data-pay-in-full-name]'), payInFullError = payInFullModal.querySelector('[data-pay-in-full-error]'), payInFullConfirmBtn = document.getElementById('pay-in-full-confirm');
    const open = (modal) => { modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; };
    const close = (modal) => { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };
    document.addEventListener('click', e => { const closeDetails = e.target.closest('[data-close-details]'); if (closeDetails) close(closeDetails.closest('.modal')); const card = e.target.closest('[data-customer-details]'); const cardAction = e.target.closest('.owner-customer-card__actions'); if (card && !closeDetails && !cardAction) open(document.getElementById(card.dataset.customerDetails)); });
    document.addEventListener('keydown', e => { const card = e.target.closest('[data-customer-details]'); if (card && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); open(document.getElementById(card.dataset.customerDetails)); } });
    const refresh = (q = '') => fetch('/owner/customers/list?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.text()).then(html => region.innerHTML = html);
    const request = (url, options = {}) => { const isMultipart = options.body instanceof FormData; return fetch(url, { ...options, headers: { 'X-CSRF-TOKEN': token, ...(isMultipart ? {} : { 'Content-Type': 'application/json' }), Accept: 'application/json' } }).then(async r => { const data = await r.json(); if (!r.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Request failed.'); return data; }); };
    const showToast = (message, type = 'success') => { const stack = document.getElementById('toast-stack'); if (!stack || !message) return; const toast = document.createElement('div'); toast.className = 'toast toast--' + type; toast.setAttribute('role', 'status'); const icon = document.createElement('span'); icon.className = 'toast__icon'; icon.setAttribute('aria-hidden', 'true'); icon.textContent = type === 'success' ? '✓' : type === 'danger' ? '!' : 'i'; const text = document.createElement('span'); text.className = 'toast__message'; text.textContent = message; const closeBtn = document.createElement('button'); closeBtn.type = 'button'; closeBtn.className = 'toast__close'; closeBtn.setAttribute('aria-label', 'Dismiss notification'); closeBtn.textContent = '×'; closeBtn.addEventListener('click', dismiss); toast.append(icon, text, closeBtn); stack.appendChild(toast); let dismissed = false; let timer = null; function dismiss() { if (dismissed) return; dismissed = true; clearTimeout(timer); toast.classList.add('toast--leaving'); setTimeout(() => toast.remove(), 220); } timer = setTimeout(dismiss, 4200); };
    const productRows = form.querySelector('[data-product-rows]');
    document.querySelector('[data-open-modal="add-customer-modal"]').addEventListener('click', () => { form.reset(); form.customer_id.value = ''; form.querySelector('.customer-debt-types').hidden = false; addModal.querySelector('.modal-title').textContent = 'Add Customer'; addModal.querySelector('.modal-subtitle').textContent = 'Create a portal code and record the opening debt.'; productRows.innerHTML = ''; });
    const addProduct = (row = {}) => { const item = document.createElement('div'); item.className = 'customer-product-row'; item.innerHTML = `<input class="form-input" name="products[][product_name]" placeholder="Product name" required value="${row.name || ''}"><input class="form-input" name="products[][quantity]" type="number" min="1" placeholder="Qty" required value="${row.quantity || 1}"><input class="form-input" name="products[][amount]" type="text" inputmode="decimal" autocomplete="off" pattern="[0-9]+([.][0-9]{1,2})?" placeholder="₱ 0.00" required value="${row.amount || ''}" data-product-amount><button type="button" class="owner-product-card__action owner-product-card__action--danger" data-remove-product aria-label="Remove product">×</button>`; productRows.append(item); };
    form.addEventListener('input', e => { if (e.target.matches('[data-product-amount]')) { e.target.value = e.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\./g, '$1').replace(/^(\d+\.\d{0,2}).*$/, '$1'); } });
    form.querySelector('[data-add-product]').addEventListener('click', () => addProduct());
    form.querySelectorAll('[data-type-toggle]').forEach(toggle => toggle.addEventListener('change', () => { form.querySelector('[data-product-fields]').hidden = !form.querySelector('[value=product]').checked; form.querySelector('[data-money-fields]').hidden = !form.querySelector('[value=money]').checked; if (form.querySelector('[value=product]').checked && !productRows.children.length) addProduct(); }));
    form.addEventListener('click', e => { if (e.target.closest('[data-remove-product]')) e.target.closest('.customer-product-row').remove(); });
    form.addEventListener('submit', e => { e.preventDefault(); const id = form.customer_id.value; const payload = new FormData(); payload.append('full_name', form.full_name.value); if (form.gender.value) payload.append('gender', form.gender.value); if (form.avatar.files[0]) payload.append('avatar', form.avatar.files[0]); const rows = [...form.querySelectorAll('.customer-product-row')]; const debtTypes = [...form.querySelectorAll('[name="debt_types[]"]:checked')].map(x => x.value); if (debtTypes.length) { debtTypes.forEach(type => payload.append('debt_types[]', type)); rows.forEach((row, index) => { payload.append(`products[${index}][product_name]`, row.children[0].value); payload.append(`products[${index}][quantity]`, row.children[1].value); payload.append(`products[${index}][amount]`, row.children[2].value); }); payload.append('money_amount', form.money_amount.value); payload.append('loaned_at', form.loaned_at.value); } if (id) payload.append('_method', 'PUT'); const url = id ? `/owner/customers/${id}` : '/owner/customers'; request(url, { method: 'POST', body: payload }).then(() => { close(addModal); showToast(id ? 'Customer updated.' : 'Customer added.'); form.reset(); form.customer_id.value = ''; form.querySelector('.customer-debt-types').hidden = false; addModal.querySelector('.modal-title').textContent = 'Add Customer'; addModal.querySelector('.modal-subtitle').textContent = 'Create a portal code and record the opening debt.'; productRows.innerHTML = ''; refresh(); }).catch(error => { const el = form.querySelector('[data-customer-error]'); el.textContent = error.message; el.hidden = false; showToast(error.message, 'danger'); }); });
    document.addEventListener('click', e => { const edit = e.target.closest('[data-edit-customer]'), pay = e.target.closest('[data-pay-customer]'), del = e.target.closest('[data-delete-customer]'); if (edit) { form.reset(); form.querySelector('[name=customer_id]').value = edit.dataset.id; form.full_name.value = edit.dataset.name; form.querySelector(`[name=gender][value="${edit.dataset.gender}"]`).checked = true; const title = addModal.querySelector('.modal-title'), subtitle = addModal.querySelector('.modal-subtitle'); if (title) title.textContent = 'Edit Customer'; if (subtitle) subtitle.textContent = 'Update the name, profile, or add a new debt record.'; form.querySelector('.customer-debt-types').hidden = false; form.querySelector('[data-product-fields]').hidden = true; form.querySelector('[data-money-fields]').hidden = true; open(addModal); } if (pay) { paymentForm.customer_id.value = pay.dataset.id; paymentForm.dataset.remaining = pay.dataset.remaining || ''; const paymentName = paymentModal.querySelector('[data-payment-name]'); if (paymentName) paymentName.textContent = pay.dataset.name; open(paymentModal); } if (del) { deleteNameEl.textContent = del.dataset.name; deleteError.hidden = true; pendingDeleteId = del.dataset.id; open(deleteModal); } });
    paymentModal.querySelector('[data-pay-in-full]').addEventListener('click', () => { payInFullNameEl.textContent = `${paymentModal.querySelector('[data-payment-name]').textContent} — the remaining balance of ₱${Number(paymentForm.dataset.remaining || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })} will be settled and recorded in the payment history.`; payInFullError.hidden = true; payInFullConfirmBtn.disabled = false; open(payInFullModal); });
    payInFullConfirmBtn.addEventListener('click', () => { payInFullConfirmBtn.disabled = true; request(`/owner/customers/${paymentForm.customer_id.value}/payments`, { method: 'POST', body: JSON.stringify({ amount: paymentForm.dataset.remaining, pay_in_full: true }) }).then(() => { close(payInFullModal); close(paymentModal); showToast('Payment recorded. Balance settled in full.'); paymentForm.reset(); refresh(); }).catch(error => { payInFullConfirmBtn.disabled = false; payInFullError.querySelector('span').textContent = error.message; payInFullError.hidden = false; showToast(error.message, 'danger'); }); });
    payInFullModal.addEventListener('click', e => { if (e.target.closest('[data-modal-close]')) setTimeout(() => { if (paymentModal.classList.contains('is-open')) document.body.style.overflow = 'hidden'; }, 0); });
    paymentForm.addEventListener('submit', e => { e.preventDefault(); request(`/owner/customers/${paymentForm.customer_id.value}/payments`, { method: 'POST', body: JSON.stringify({ amount: paymentForm.amount.value }) }).then(() => { close(paymentModal); showToast('Payment recorded.'); paymentForm.reset(); refresh(); }).catch(error => { const el = paymentForm.querySelector('[data-payment-error]'); el.textContent = error.message; el.hidden = false; showToast(error.message, 'danger'); }); });
    deleteConfirmBtn.addEventListener('click', () => { if (!pendingDeleteId) return; deleteConfirmBtn.disabled = true; request(`/owner/customers/${pendingDeleteId}`, { method: 'DELETE' }).then(() => { close(deleteModal); pendingDeleteId = null; deleteConfirmBtn.disabled = false; showToast('Customer deleted.'); refresh(); }).catch(error => { deleteConfirmBtn.disabled = false; deleteError.querySelector('span').textContent = error.message; deleteError.hidden = false; showToast(error.message, 'danger'); }); });
    page.querySelector('[data-customer-search]').addEventListener('input', e => refresh(e.target.value));
    page.addEventListener('input', e => {
        const search = e.target.closest('[data-section-search]');
        if (!search) return;
        const group = search.closest('[data-customer-group]');
        const term = search.value.trim().toLowerCase();
        let visible = 0;
        group.querySelectorAll('[data-customer-row]').forEach(card => {
            const name = card.querySelector('.owner-customer-card__name').textContent.toLowerCase();
            const code = card.querySelector('.owner-customer-card__code').textContent.toLowerCase();
            const match = !term || name.includes(term) || code.includes(term);
            card.hidden = !match;
            if (match) visible += 1;
        });
        const empty = group.querySelector('[data-section-empty]');
        if (empty) empty.hidden = visible !== 0;
    });
    document.addEventListener('click', e => { if (e.target.matches('[data-modal-close]')) close(e.target.closest('.modal')); });
})();
</script>
@endpush