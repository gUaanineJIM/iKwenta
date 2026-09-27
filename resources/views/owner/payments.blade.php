@extends('layouts.owner', ['activeSection' => 'payments'])

@section('title', 'Payments | iKwenta')

@section('content')
    <section class="owner-section owner-payments" data-payments-page>
        <div class="page-heading owner-products__heading">
            <div>
                <span class="owner-eyebrow">Collections</span>
                <h1>Payments</h1>
                <p>Every payment recorded across your store, newest first.</p>
            </div>
        </div>

        <div class="owner-payments__toolbar" data-payments-toolbar>
            <label class="owner-products__search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M21 21L16.5 16.5"></path>
                </svg>
                <input
                    type="search"
                    placeholder="Search by customer, code, or receiver"
                    data-payments-search
                    aria-label="Search payments"
                    value="{{ $q }}"
                    autocomplete="off"
                >
            </label>

            <button
                type="button"
                class="owner-payments__toggle{{ $activeFilterCount > 0 ? ' is-active' : '' }}"
                data-payments-toggle
                aria-expanded="{{ $activeFilterCount > 0 ? 'true' : 'false' }}"
                aria-controls="payments-filters"
            >
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 5h18l-7 8v5l-4 2v-7L3 5Z"></path>
                </svg>
                <span>Filters</span>
                <span class="owner-payments__toggle-count" data-payments-toggle-count{{ $activeFilterCount === 0 ? ' hidden' : '' }}>{{ $activeFilterCount }}</span>
            </button>
        </div>

        <div class="owner-payments__filters-wrap" id="payments-filters" data-payments-filters{{ $activeFilterCount === 0 ? ' hidden' : '' }}>
            <div class="owner-payments__filters">
                <label class="owner-payments__field">
                    <span class="owner-payments__field-label">From</span>
                    <input type="date" data-payments-from value="{{ $from }}" aria-label="Payments from date">
                </label>

                <label class="owner-payments__field">
                    <span class="owner-payments__field-label">To</span>
                    <input type="date" data-payments-to value="{{ $to }}" aria-label="Payments to date">
                </label>

                <label class="owner-payments__field">
                    <span class="owner-payments__field-label">Min amount</span>
                    <input type="number" step="0.01" min="0" placeholder="0.00" data-payments-min
                        value="{{ $min }}" aria-label="Minimum payment amount">
                </label>

                <label class="owner-payments__field">
                    <span class="owner-payments__field-label">Max amount</span>
                    <input type="number" step="0.01" min="0" placeholder="0.00" data-payments-max
                        value="{{ $max }}" aria-label="Maximum payment amount">
                </label>

                <label class="owner-payments__field">
                    <span class="owner-payments__field-label">Received by</span>
                    <select data-payments-receiver aria-label="Filter payments by receiver">
                        <option value="">All receivers</option>
                        @foreach ($receivers as $receiver)
                            <option value="{{ $receiver->user_id }}" @selected($receivedBy === $receiver->user_id)>
                                {{ $receiver->full_name }} ({{ $receiver->username }})
                            </option>
                        @endforeach
                    </select>
                </label>

                <button type="button" class="owner-payments__reset" data-payments-reset>
                    Clear filters
                </button>
            </div>
        </div>

        <div id="payments-region" data-payments-region>
            @include('components.owner.payments-list')
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const page = document.querySelector('[data-payments-page]');
            if (!page) return;

            const region = page.querySelector('[data-payments-region]');
            const search = page.querySelector('[data-payments-search]');
            const from = page.querySelector('[data-payments-from]');
            const to = page.querySelector('[data-payments-to]');
            const min = page.querySelector('[data-payments-min]');
            const max = page.querySelector('[data-payments-max]');
            const receiver = page.querySelector('[data-payments-receiver]');
            const reset = page.querySelector('[data-payments-reset]');
            const toggle = page.querySelector('[data-payments-toggle]');
            const toggleCount = page.querySelector('[data-payments-toggle-count]');
            const filtersPanel = page.querySelector('[data-payments-filters]');

            const baseUrl = @js(route('owner.payments.list'));

            const advancedFilters = () => [from, to, min, max, receiver].filter(Boolean);

            const setPanelOpen = (open) => {
                if (!toggle || !filtersPanel) return;
                toggle.setAttribute('aria-expanded', String(open));
                filtersPanel.hidden = !open;
            };

            // The badge sits outside the refreshed region, so keep it in sync by hand.
            const syncToggleBadge = () => {
                if (!toggle) return;

                const count = (search && search.value.trim() ? 1 : 0)
                    + advancedFilters().filter((field) => field.value !== '').length;

                toggle.classList.toggle('is-active', count > 0);

                if (toggleCount) {
                    toggleCount.textContent = String(count);
                    toggleCount.hidden = count === 0;
                }
            };

            const applyFilters = (url) => {
                const term = search ? search.value.trim() : '';
                if (term) url.searchParams.set('q', term);
                if (from && from.value) url.searchParams.set('from', from.value);
                if (to && to.value) url.searchParams.set('to', to.value);
                if (min && min.value) url.searchParams.set('min', min.value);
                if (max && max.value) url.searchParams.set('max', max.value);
                if (receiver && receiver.value) url.searchParams.set('received_by', receiver.value);
            };

            const render = async (url) => {
                region.classList.add('is-loading');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) return;

                    const html = await response.text();
                    if (html && html.trim() !== region.innerHTML.trim()) {
                        region.innerHTML = html;
                    }
                } catch (error) {
                    // silent: keep the current list
                } finally {
                    region.classList.remove('is-loading');
                }
            };

            const refresh = async () => {
                const url = new URL(baseUrl, window.location.origin);
                applyFilters(url);
                await render(url);
            };

            const debounce = (fn, delay) => {
                let timer;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn(...args), delay);
                };
            };

            const refreshDebounced = debounce(refresh, 250);

            if (search) {
                search.addEventListener('input', () => {
                    syncToggleBadge();
                    refreshDebounced();
                });
            }

            advancedFilters().forEach((field) => {
                field.addEventListener('change', () => {
                    syncToggleBadge();
                    refresh();
                });
            });

            if (toggle) {
                toggle.addEventListener('click', () => {
                    setPanelOpen(toggle.getAttribute('aria-expanded') !== 'true');
                });
            }

            if (reset) {
                reset.addEventListener('click', () => {
                    [search, from, to, min, max, receiver].forEach((field) => {
                        if (field) field.value = '';
                    });

                    syncToggleBadge();
                    refresh();
                });
            }

            // Paginate in place — links() already carry the active filters and the page number.
            region.addEventListener('click', (event) => {
                const link = event.target.closest('.pagination a[href]');
                if (!link) return;

                event.preventDefault();
                window.history.replaceState({}, '', link.href);
                render(link.href);
            });
        })();
    </script>
@endpush
