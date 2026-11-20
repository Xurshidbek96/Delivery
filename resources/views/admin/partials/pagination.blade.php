@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Sahifalar">
        <div class="pagination-info">
            <b>{{ $paginator->firstItem() }}</b>–<b>{{ $paginator->lastItem() }}</b>, jami <b>{{ $paginator->total() }}</b>
        </div>
        <div class="pager">
            @if ($paginator->onFirstPage())
                <span class="disabled"><i class="ti ti-chevron-left"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Oldingi"><i class="ti ti-chevron-left"></i></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="disabled">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Keyingi"><i class="ti ti-chevron-right"></i></a>
            @else
                <span class="disabled"><i class="ti ti-chevron-right"></i></span>
            @endif
        </div>
    </nav>
@endif
