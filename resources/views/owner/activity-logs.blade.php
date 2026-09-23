@extends('layouts.owner', ['activeSection' => 'activity'])

@section('title', 'Activity Logs | iKwenta')

@section('content')
    <section class="owner-section owner-activity" data-activity-page>
        <div class="page-heading owner-products__heading">
            <div>
                <span class="owner-eyebrow">Audit Trail</span>
                <h1>Activity Logs</h1>
                <p>Every recorded action across your store, newest first.</p>
            </div>
        </div>

        <div class="owner-products__toolbar owner-activity__toolbar">
            <label class="owner-products__search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M21 21L16.5 16.5"></path>
                </svg>
                <input
                    type="search"
                    placeholder="Search activity"
                    data-activity-search
                    aria-label="Search activity logs"
                    value="{{ $q }}"
                    autocomplete="off"
                >
            </label>

            <label class="owner-activity__filter">
                <span class="owner-activity__filter-label">Record type</span>
                <select data-activity-filter aria-label="Filter activity by record type">
                    <option value="" @selected($filter === '')>All activity</option>
                    <option value="customers" @selected($filter === 'customers')>Customers</option>
                    <option value="products" @selected($filter === 'products')>Products</option>
                    <option value="debts" @selected($filter === 'debts')>Debts</option>
                    <option value="payments" @selected($filter === 'payments')>Payments</option>
                    <option value="auth" @selected($filter === 'auth')>Sign-ins &amp; sign-outs</option>
                </select>
            </label>
        </div>

        <div id="activity-region" data-activity-region>
            @include('components.owner.activity-logs-list', ['logs' => $logs, 'entries' => $entries, 'q' => $q, 'filter' => $filter])
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const page = document.querySelector('[data-activity-page]');
            if (!page) return;

            const region = page.querySelector('[data-activity-region]');
            const search = page.querySelector('[data-activity-search]');
            const filter = page.querySelector('[data-activity-filter]');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const refresh = async () => {
                const url = new URL('{{ route('owner.activity-logs.list') }}', window.location.origin);
                const term = search ? search.value.trim() : '';
                if (term) url.searchParams.set('q', term);
                if (filter && filter.value) url.searchParams.set('filter', filter.value);

                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                        },
                    });

                    if (!response.ok) return;

                    const html = await response.text();
                    if (html && html.trim() !== region.innerHTML.trim()) {
                        region.innerHTML = html;
                    }
                } catch (error) {
                    // silent: keep the current list
                }
            };

            const debounce = (fn, delay) => {
                let timer;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn(...args), delay);
                };
            };

            if (search) {
                search.addEventListener('input', debounce(refresh, 250));
            }

            if (filter) {
                filter.addEventListener('change', refresh);
            }
        })();
    </script>
@endpush