@php
    $owingCustomers = $customerGroups['owing'] ?? collect();
    $paidCustomers = $customerGroups['paid'] ?? collect();
    $hasCustomers = $owingCustomers->isNotEmpty() || $paidCustomers->isNotEmpty();
@endphp

@if (! $hasCustomers)
    <div class="owner-products-empty">
        <span class="owner-products-empty__icon" aria-hidden="true">+</span>
        <strong>{{ $q ? 'No customers found' : 'No customers yet' }}</strong>
        <p>{{ $q ? 'Try another name.' : 'Add a customer to start tracking their balance.' }}</p>
    </div>
@else
    @foreach ([['key' => 'owing', 'title' => 'Debt Remaining', 'description' => 'Customers who still have an unpaid balance.', 'customers' => $owingCustomers], ['key' => 'paid', 'title' => 'Paid Customers', 'description' => 'Customers with no remaining balance.', 'customers' => $paidCustomers]] as $group)
        <section class="owner-customer-group" data-customer-group="{{ $group['key'] }}" aria-labelledby="{{ $group['key'] }}-customers-title">
            <div class="owner-customer-group__heading">
                <div>
                    <h2 id="{{ $group['key'] }}-customers-title">{{ $group['title'] }}</h2>
                    <p>{{ $group['description'] }}</p>
                </div>
                <span class="count-badge">{{ $group['customers']->count() }}</span>
            </div>

            @if ($group['key'] === 'paid' && $group['customers']->isNotEmpty())
                <label class="owner-customer-group__search owner-products__search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="M21 21L16.5 16.5"></path>
                    </svg>
                    <input type="search" data-section-search placeholder="Search paid customers…" aria-label="Search paid customers" autocomplete="off">
                </label>
            @endif

            @if ($group['customers']->isEmpty())
                <div class="owner-customer-group__empty">No customers in this group.</div>
            @else
                <div class="owner-customer-grid" data-section-grid>
                    @foreach ($group['customers'] as $customer)
                        <article class="owner-customer-card" data-customer-row data-customer-id="{{ $customer->customer_id }}" data-customer-details="customer-details-{{ $customer->customer_id }}" tabindex="0" role="button" aria-haspopup="dialog" aria-label="View {{ $customer->full_name }} details">
                            <div class="owner-customer-card__top">
                                @if ($customer->avatar_path)
                                    <img class="owner-customer-card__avatar" src="{{ asset('storage/'.$customer->avatar_path) }}" alt="{{ $customer->full_name }}">
                                @else
                                    <img class="owner-customer-card__avatar" src="{{ asset($customer->gender === 'female' ? 'images/avatar-girl.svg' : 'images/avatar-boy.svg') }}" alt="{{ ucfirst($customer->gender) }} avatar">
                                @endif
                            </div>
                            <h3 class="owner-customer-card__name">{{ $customer->full_name }}</h3>
                            <code class="owner-customer-card__code">{{ $customer->customer_code }}</code>
                            <div class="owner-customer-card__balances"><span>Total debt <strong>₱{{ number_format($customer->total_debt, 2) }}</strong></span><span>Remaining <strong>₱{{ number_format($customer->remaining_balance, 2) }}</strong></span></div>
                            <div class="owner-customer-card__actions" aria-label="Customer actions">
                                @if ((float) $customer->remaining_balance > 0)
                                        <button class="owner-customer-card__quick-action" type="button" data-pay-customer data-id="{{ $customer->customer_id }}" data-name="{{ $customer->full_name }}" data-remaining="{{ $customer->remaining_balance }}" title="Add payment" aria-label="Add payment for {{ $customer->full_name }}"><span aria-hidden="true">₱</span></button>
                                @endif
                                <button class="owner-customer-card__quick-action" type="button" data-edit-customer data-id="{{ $customer->customer_id }}" data-name="{{ $customer->full_name }}" data-gender="{{ $customer->gender }}" title="Edit customer" aria-label="Edit {{ $customer->full_name }}"><span aria-hidden="true">✎</span></button>
                                <button class="owner-customer-card__quick-action owner-customer-card__quick-action--danger" type="button" data-delete-customer data-id="{{ $customer->customer_id }}" data-name="{{ $customer->full_name }}" title="Delete customer" aria-label="Delete {{ $customer->full_name }}"><span aria-hidden="true">×</span></button>
                            </div>
                        </article>

                        <div class="modal owner-modal owner-customer-modal" id="customer-details-{{ $customer->customer_id }}" aria-hidden="true">
                            <div class="modal-backdrop" data-modal-close></div>
                            <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="customer-details-title-{{ $customer->customer_id }}">
                                <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>
                                <div class="owner-customer-modal__profile">
                                    @if ($customer->avatar_path)
                                        <img class="owner-customer-card__avatar" src="{{ asset('storage/'.$customer->avatar_path) }}" alt="{{ $customer->full_name }}">
                                    @else
                                        <img class="owner-customer-card__avatar" src="{{ asset($customer->gender === 'female' ? 'images/avatar-girl.svg' : 'images/avatar-boy.svg') }}" alt="{{ ucfirst($customer->gender) }} avatar">
                                    @endif
                                    <div><span class="modal-badge">Customer account</span><h2 class="modal-title" id="customer-details-title-{{ $customer->customer_id }}">{{ $customer->full_name }}</h2><code>{{ $customer->customer_code }}</code></div>
                                </div>
                                <div class="owner-customer-modal__balances"><span>Total debt<strong>₱{{ number_format($customer->total_debt, 2) }}</strong></span><span>Remaining<strong>₱{{ number_format($customer->remaining_balance, 2) }}</strong></span></div>
                                <div class="owner-customer-details__content">
                                    @forelse ($customer->debts as $debt)
                                        <article class="owner-debt-history">
                                            <header class="owner-debt-history__header"><strong>Loaned {{ $debt->loaned_at?->format('M d, Y h:i A') }}</strong><span>Remaining: ₱{{ number_format((float) $debt->remaining_total, 2) }}</span></header>
                                            <div class="owner-debt-history__columns">
                                                <div>
                                                    <h3>Owed</h3>
                                                    @foreach ($debt->items as $item)
                                                        <p>{{ $item->product_name ?? $item->product?->product_name ?? 'Unknown Product' }} × {{ $item->quantity }}: ₱{{ number_format((float) $item->subtotal, 2) }}</p>
                                                    @endforeach
                                                    @if ((float) $debt->money_amount > 0)
                                                        <p>Money owed: ₱{{ number_format((float) $debt->money_amount, 2) }}</p>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h3>Payment history</h3>
                                                    @forelse ($debt->payments->sortByDesc('payment_date') as $payment)
                                                        <p>₱{{ number_format((float) $payment->amount_paid, 2) }} on {{ $payment->payment_date?->format('M d, Y h:i A') }}</p>
                                                    @empty
                                                        <p>No payments recorded.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </article>
                                    @empty
                                        <p>No debt records for this customer.</p>
                                    @endforelse
                                </div>
                                <div class="modal-actions owner-customer-modal__actions">
                                    @if ((float) $customer->remaining_balance > 0)
                                        <button class="btn btn-primary" type="button" data-pay-customer data-id="{{ $customer->customer_id }}" data-name="{{ $customer->full_name }}" data-remaining="{{ $customer->remaining_balance }}" data-close-details>₱ Record Payment</button>
                                    @endif
                                    <button class="btn btn-secondary" type="button" data-edit-customer data-id="{{ $customer->customer_id }}" data-name="{{ $customer->full_name }}" data-gender="{{ $customer->gender }}" data-close-details>Edit Customer</button>
                                    <button class="btn btn-secondary owner-customer-modal__delete" type="button" data-delete-customer data-id="{{ $customer->customer_id }}" data-name="{{ $customer->full_name }}" data-close-details>Delete</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="owner-customer-group__empty" data-section-empty hidden>No {{ $group['key'] === 'paid' ? 'paid' : '' }} customers match your search.</div>
            @endif
        </section>
    @endforeach
@endif
