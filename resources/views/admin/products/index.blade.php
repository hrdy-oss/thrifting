@extends('admin.layout')
@section('title', 'Inventory — NEON')
@section('content')
    <div class="admin-title"><div><span class="eyebrow">ARCHIVAL REGISTRY / INVENTORY</span><h1>INVENTORY & CATALOG</h1></div><a class="button button-dark" href="{{ route('admin.products.create') }}">+ ADD PRODUCT</a></div>
    <div class="admin-table-wrap">
        <table class="admin-table admin-table-cards">
            <thead><tr><th>ITEM</th><th>SKU</th><th>KATEGORI</th><th>HARGA</th><th>STOK</th><th>STATUS</th><th>AKSI</th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td data-label="ITEM"><div class="table-product">@if ($product->image_url)<img src="{{ $product->image_url }}" alt="">@endif<strong>{{ $product->title }}</strong></div></td>
                        <td data-label="SKU">{{ $product->sku }}</td>
                        <td data-label="KATEGORI">{{ $product->category }}</td>
                        <td data-label="HARGA">Rp{{ number_format($product->price, 0, ',', '.') }}</td>
                        <td data-label="STOK">{{ $product->stock }}</td>
                        <td data-label="STATUS">{{ $product->is_published ? 'LIVE' : 'ARCHIVED' }}</td>
                        <td data-label="AKSI" class="table-actions"><a href="{{ route('admin.products.edit', $product) }}">EDIT</a><form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Arsipkan produk ini?')">@csrf @method('DELETE')<button>ARSIP</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $products->links() }}
    </div>
@endsection
