@props(['items' => null, 'paginator' => null])
@php($items = $items ?? $paginator)
@if($items && $items->hasPages())
<nav class="pagination" aria-label="Phân trang">
@if($items->onFirstPage())<span class="button" aria-disabled="true">Trang trước</span>@else<a class="button" rel="prev" href="{{ $items->previousPageUrl() }}">Trang trước</a>@endif
<span>Trang {{ $items->currentPage() }} / {{ $items->lastPage() }} · {{ $items->total() }} mục</span>
@if($items->hasMorePages())<a class="button" rel="next" href="{{ $items->nextPageUrl() }}">Trang sau</a>@else<span class="button" aria-disabled="true">Trang sau</span>@endif
</nav>
@endif
