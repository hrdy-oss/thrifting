@extends('storefront.layout')

@section('title', 'Shop — NEON')

@section('content')
    <section class="page-shell"><div class="section-heading" data-reveal><div><span class="eyebrow">NEON ARCHIVE / CATALOG</span><h1>ALL THE PIECES</h1></div><span class="eyebrow">{{ $products->total() }} ITEMS</span></div>
        <form class="catalog-filters" method="GET" action="{{ route('products.index') }}">
            <input name="q" value="{{ request('q') }}" placeholder="Cari item, era, atau ukuran" autocomplete="off">
            <select name="category">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst($category) }}</option>
                @endforeach
            </select>
            <button class="button button-dark" type="submit">FILTER</button>
        </form>
        @if ($categories->isNotEmpty())
            <div class="category-chips">
                <a class="category-chip {{ request('category') ? '' : 'is-active' }}" href="{{ route('products.index', ['q' => request('q')]) }}">Semua</a>
                @foreach ($categories as $category)
                    <a class="category-chip {{ request('category') === $category ? 'is-active' : '' }}" href="{{ route('products.index', ['category' => $category, 'q' => request('q')]) }}">{{ ucfirst($category) }}</a>
                @endforeach
            </div>
        @endif
        <div class="product-grid" data-reveal-stagger>@forelse ($products as $product)@include('storefront.partials.product-card', ['product' => $product])@empty<p>Belum ada barang di kategori ini.</p>@endforelse</div><div class="pagination">{{ $products->links() }}</div>
    </section>
@endsection
