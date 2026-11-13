@extends('admin.layout')
@section('title', ($product->exists ? 'Edit' : 'New').' Product — NEON')
@section('content')
    <div class="admin-title"><div><span class="eyebrow">INVENTORY CONTROL</span><h1>{{ $product->exists ? 'EDIT PIECE' : 'ADD NEW PIECE' }}</h1></div><a class="text-link" href="{{ route('admin.products.index') }}">← KEMBALI KE INVENTARIS</a></div>
    <form class="admin-form" method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">@csrf @if ($product->exists)@method('PUT')@endif
        <section class="admin-form-section">
            <div class="admin-form-section-head">
                <span class="eyebrow">FOTO PRODUK</span>
                <h2>Foto utama</h2>
                <p>Ambil langsung dari kamera atau unggah file. Format JPG, PNG, atau WEBP.</p>
            </div>
            <div class="photo-manager" data-photo-manager>
                <div class="photo-manager-preview" data-photo-preview>
                    <img @if ($product->image_url) src="{{ $product->image_url }}" @endif alt="Foto utama {{ $product->title }}" data-photo-preview-image @if (! $product->image_url) hidden @endif>
                    <div class="photo-preview-empty" data-photo-preview-empty @if ($product->image_url) hidden @endif>Belum ada foto utama</div>
                </div>
                <div class="photo-manager-controls">
                    <label class="field">Unggah foto<input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-photo-input="main"></label>
                    <div class="photo-manager-actions">
                        <button class="button button-outline" type="button" data-camera-open data-camera-target="main">Ambil dari kamera</button>
                        @if ($product->getRawOriginal('image_path'))
                            <label class="checkbox-line"><input type="checkbox" name="remove_image" value="1" data-photo-remove> Hapus foto upload saat ini</label>
                        @endif
                    </div>
                    <label class="field">Atau URL foto eksternal<input type="url" inputmode="url" name="image_url" value="{{ old('image_url', $product->getRawOriginal('image_url')) }}" placeholder="https://..."></label>
                    <p class="admin-hint">Foto hasil unggahan dipakai lebih dulu. URL eksternal menjadi cadangan bila tidak ada upload.</p>
                </div>
            </div>
        </section>

        <section class="admin-form-section">
            <div class="admin-form-section-head">
                <span class="eyebrow">GALERI</span>
                <h2>Foto tambahan</h2>
                <p>Maksimal {{ \App\Models\Product::MAX_GALLERY_PHOTOS }} foto. Klik thumbnail di halaman toko untuk melihat foto besar.</p>
            </div>
            <div class="gallery-manager" data-gallery-manager data-max-gallery="{{ \App\Models\Product::MAX_GALLERY_PHOTOS }}">
                <input type="hidden" name="gallery_manage" value="1">
                <div class="gallery-items" data-gallery-items>
                    @foreach ($product->galleryItems() as $item)
                        <div class="gallery-item" data-gallery-item>
                            <img src="{{ $item['url'] }}" alt="Foto galeri {{ $product->title }}">
                            <input type="hidden" name="gallery_keep[]" value="{{ $item['path'] }}">
                            <button class="gallery-item-remove" type="button" aria-label="Hapus foto galeri" data-gallery-remove>×</button>
                        </div>
                    @endforeach
                </div>
                <div class="gallery-manager-controls">
                    <label class="field">Unggah foto galeri<input type="file" name="gallery_files[]" accept="image/jpeg,image/png,image/webp" multiple data-photo-input="gallery"></label>
                    <div class="photo-manager-actions">
                        <button class="button button-outline" type="button" data-camera-open data-camera-target="gallery">Ambil dari kamera</button>
                        <span class="admin-hint" data-gallery-count>{{ count($product->galleryItems()) }} / {{ \App\Models\Product::MAX_GALLERY_PHOTOS }} foto</span>
                    </div>
                </div>
                <div class="gallery-url-list" data-gallery-url-list>
                    @foreach (old('gallery_urls', []) as $url)
                        <div class="gallery-url-row" data-gallery-url-row>
                            <input type="url" inputmode="url" name="gallery_urls[]" value="{{ $url }}" placeholder="https://...">
                            <button class="text-link" type="button" data-gallery-url-remove>Hapus</button>
                        </div>
                    @endforeach
                </div>
                <button class="text-link" type="button" data-gallery-url-add>+ Tambah URL foto galeri</button>
                <template data-gallery-url-template>
                    <div class="gallery-url-row" data-gallery-url-row>
                        <input type="url" inputmode="url" name="gallery_urls[]" placeholder="https://...">
                        <button class="text-link" type="button" data-gallery-url-remove>Hapus</button>
                    </div>
                </template>
            </div>
        </section>

        <section class="admin-form-section">
            <div class="admin-form-section-head">
                <span class="eyebrow">DETAIL PRODUK</span>
                <h2>Informasi utama</h2>
            </div>
            <div class="form-grid"><label class="field field-wide">Nama produk<input name="title" value="{{ old('title', $product->title) }}" required></label><label class="field">SKU<input name="sku" value="{{ old('sku', $product->sku) }}" required></label><label class="field">Kategori<input name="category" value="{{ old('category', $product->category) }}" placeholder="t-shirts" required></label><label class="field">Harga (IDR)<input type="number" min="1" inputmode="numeric" name="price" value="{{ old('price', $product->price) }}" required></label><label class="field">Harga coret<input type="number" min="1" inputmode="numeric" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price) }}"></label><label class="field">Ukuran<input name="size" value="{{ old('size', $product->size) }}"></label><label class="field">Skor kondisi (0–10)<input type="number" min="0" max="10" step="0.1" inputmode="decimal" name="condition_score" value="{{ old('condition_score', $product->condition_score) }}"></label><label class="field">Era<input name="era" value="{{ old('era', $product->era) }}"></label><label class="field">Stok<input type="number" min="0" inputmode="numeric" name="stock" value="{{ old('stock', $product->stock ?? 1) }}" required></label><label class="field field-wide">Deskripsi<textarea name="description" rows="3">{{ old('description', $product->description) }}</textarea></label><label class="field field-wide">Catatan kondisi<textarea name="condition_notes" rows="2">{{ old('condition_notes', $product->condition_notes) }}</textarea></label>
        @foreach (['pit_to_pit' => 'Pit to pit', 'length' => 'Length', 'shoulder' => 'Shoulder'] as $key => $label)<label class="field">{{ $label }} (cm)<input type="number" min="0" max="300" step="0.1" inputmode="decimal" name="{{ $key }}" value="{{ old($key, data_get($product->measurements, $key)) }}"></label>@endforeach</div>
        </section>

        <label class="checkbox-line"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))> Tampilkan sebagai pilihan kurator</label><label class="checkbox-line"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $product->is_published ?? true))> Publikasikan produk</label><label class="checkbox-line"><input type="checkbox" name="is_upcoming" value="1" @checked(old('is_upcoming', $product->is_upcoming))> Tandai sebagai upcoming (segera hadir, belum bisa dibeli)</label><button class="button button-dark admin-form-submit" type="submit">SIMPAN PRODUK</button>
    </form>
@endsection
