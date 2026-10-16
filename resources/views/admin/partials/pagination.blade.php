@if ($paginator->hasPages())
    <div class="pagination">
        <span>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>
        <div class="pagination-links">
            @if ($paginator->onFirstPage()) <span>‹</span> @else <a href="{{ $paginator->previousPageUrl() }}">‹</a> @endif
            <span class="current">{{ $paginator->currentPage() }}</span>
            @if ($paginator->hasMorePages()) <a href="{{ $paginator->nextPageUrl() }}">›</a> @else <span>›</span> @endif
        </div>
    </div>
@endif
