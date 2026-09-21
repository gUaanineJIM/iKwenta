@if ($products->isEmpty())
    @if (!empty($q))
        <div class="owner-products-empty">
            <span class="owner-products-empty__icon" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7" />
                    <path d="M21 21L16.5 16.5" />
                </svg>
            </span>

            <strong>No matches for "{{ $q }}"</strong>

            <p>Try a different keyword — names and descriptions are searched.</p>
        </div>
    @else
        <div class="owner-products-empty">
            <span class="owner-products-empty__icon" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8L12 3L3 8V16L12 21L21 16V8Z" />
                    <path d="M3 8L12 13L21 8" />
                    <path d="M12 13V21" />
                </svg>
            </span>

            <strong>No products yet</strong>

            <p>Products you add to your store will appear here.</p>

            <button type="button" class="owner-btn owner-btn--primary owner-products-empty__btn"
                data-open-modal="add-product-modal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5V19M5 12H19" />
                </svg>

                <span>Add Your First Product</span>
            </button>
        </div>
    @endif
@else
    <p class="owner-products-count">{{ $products->count() }}
        {{ $products->count() === 1 ? 'product' : 'products' }} in your catalog</p>

    <div class="owner-products-grid">
        @foreach ($products as $product)
            <article class="owner-product-card" data-product-id="{{ $product->product_id }}">
                <div class="owner-product-card__top">
                    <span class="owner-product-card__icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 8L12 3L3 8V16L12 21L21 16V8Z" />
                            <path d="M3 8L12 13L21 8" />
                            <path d="M12 13V21" />
                        </svg>
                    </span>

                    <div class="owner-product-card__actions">
                        <button type="button" class="owner-product-card__action" data-edit-product
                            aria-label="Edit {{ $product->product_name }}" title="Edit product">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3Z" />
                            </svg>
                        </button>

                        <button type="button" class="owner-product-card__action owner-product-card__action--danger"
                            data-delete-product aria-label="Delete {{ $product->product_name }}" title="Delete product">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18" />
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                <path d="M10 11v6M14 11v6" />
                            </svg>
                        </button>
                    </div>
                </div>

                <h3 class="owner-product-card__name">{{ $product->product_name }}</h3>

                @if ($product->description)
                    <p class="owner-product-card__desc">{{ $product->description }}</p>
                @endif

                <div class="owner-product-card__foot">
                    <strong class="owner-product-card__price">₱ {{ number_format((float) $product->price, 2) }}</strong>

                    <span class="owner-product-card__added">
                        Added {{ $product->created_at?->diffForHumans() }}
                    </span>
                </div>
            </article>
        @endforeach
    </div>
@endif