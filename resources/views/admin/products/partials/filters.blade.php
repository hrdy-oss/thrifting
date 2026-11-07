<form class="admin-filters" method="GET" action="{{ request()->url() }}">
    <input name="q" value="{{ request('q') }}" placeholder="Cari judul atau SKU" autocomplete="off">
    <select name="category">
        <option value="">Semua kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst($category) }}</option>
        @endforeach
    </select>
    <button class="button button-dark" type="submit">CARI</button>
    @if (request()->filled('q') || request()->filled('category'))
        <a class="text-link" href="{{ request()->url() }}">RESET</a>
    @endif
</form>
