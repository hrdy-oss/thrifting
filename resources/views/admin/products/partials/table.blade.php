@php
    $statusClasses = [
        'LIVE' => 'status-live',
        'UPCOMING' => 'status-upcoming',
        'ARSIP' => 'status-arsip',
        'STOK HABIS' => 'status-stok-habis',
    ];
@endphp
<div class="admin-table-wrap">
    <table class="admin-table admin-table-cards">
        <thead><tr><th>ITEM</th><th>SKU</th><th>KATEGORI</th><th>HARGA</th><th>STOK</th><th>STATUS</th><th>AKSI</th></tr></thead>
        <tbody>
            @forelse ($products as $product)
                @php
                    $status = match (true) {
                        $mode === 'archived' => 'ARSIP',
                        $product->is_upcoming => 'UPCOMING',
                        $product->stock < 1 => 'STOK HABIS',
                        default => 'LIVE',
                    };
                @endphp
                <tr>
                    <td data-label="ITEM"><div class="table-product">@if ($product->image_url)<img src="{{ $product->image_url }}" alt="">@endif<strong>{{ $product->title }}</strong></div></td>
                    <td data-label="SKU">{{ $product->sku }}</td>
                    <td data-label="KATEGORI">{{ $product->category }}</td>
                    <td data-label="HARGA">Rp{{ number_format($product->price, 0, ',', '.') }}</td>
                    <td data-label="STOK">{{ $product->stock }}</td>
                    <td data-label="STATUS"><span class="status-tag {{ $statusClasses[$status] ?? '' }}">{{ $status }}</span></td>
                    <td data-label="AKSI" class="table-actions">
                        <a href="{{ route('admin.products.edit', $product) }}">EDIT</a>
                        @if ($mode === 'archived')
                            <form method="POST" action="{{ route('admin.products.restore', $product) }}" onsubmit="return confirm('Pulihkan produk ini? Stok akan diisi 1 bila kosong.')">@csrf @method('PATCH')<button type="submit">PULIHKAN</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.products.toggle-upcoming', $product) }}">@csrf @method('PATCH')<button type="submit">{{ $product->is_upcoming ? 'BATALKAN UPCOMING' : 'JADIKAN UPCOMING' }}</button></form>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Arsipkan produk ini? Stok akan dijadikan 0.')">@csrf @method('DELETE')<button type="submit">ARSIPKAN</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">{{ $empty ?? 'Belum ada produk.' }}</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $products->links() }}
</div>
