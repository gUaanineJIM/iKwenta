@if ($paginator->hasPages())
    <nav class="pagination" data-pagination role="navigation" aria-label="Pagination">
        <div class="pagination__info">
            @if ($paginator->firstItem())
                <span>
                    Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
                </span>
            @else
                <span>{{ $paginator->count() }} {{ $paginator->count() === 1 ? 'result' : 'results' }}</span>
            @endif
        </div>

        <div class="pagination__list">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="pagination__link is-disabled" aria-disabled="true" aria-label="Previous page">‹</span>
            @else
                <a class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">‹</a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="pagination__link is-disabled" aria-disabled="true">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination__link is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination__link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">›</a>
            @else
                <span class="pagination__link is-disabled" aria-disabled="true" aria-label="Next page">›</span>
            @endif
        </div>
    </nav>
@endif