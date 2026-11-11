@php
    $paginator = $paginator ?? null;
    $noun = $noun ?? 'results';
@endphp
@if($paginator && method_exists($paginator, 'total') && $paginator->total() > 0)
    <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-3 border-top">
        <div class="small text-muted">
            Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} {{ $noun }}
        </div>
        @if ($paginator->hasPages())
            <div class="pagination-custom">
                <nav aria-label="Report pagination">
                    <ul class="pagination justify-content-center gap-2 mb-0">
                        <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}">&laquo; Previous</a>
                        </li>
                        <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                            <a class="page-link" href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}">Next &raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        @endif
    </div>
@endif
