@if($records->hasPages())
    <nav class="pagination" aria-label="Pagina's">
        @if($records->onFirstPage()) <span>Vorige</span>
        @else <a href="{{ $records->previousPageUrl() }}">Vorige</a> @endif
        <span>Pagina {{ $records->currentPage() }} van {{ $records->lastPage() }}</span>
        @if($records->hasMorePages()) <a href="{{ $records->nextPageUrl() }}">Volgende</a>
        @else <span>Volgende</span> @endif
    </nav>
@endif
