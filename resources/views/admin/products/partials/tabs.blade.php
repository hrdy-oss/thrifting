<div class="admin-tabs">
    <a class="admin-tab {{ request()->routeIs('admin.products.index') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">INVENTORY <span>{{ $counts['inventory'] }}</span></a>
    <a class="admin-tab {{ request()->routeIs('admin.products.upcoming') ? 'is-active' : '' }}" href="{{ route('admin.products.upcoming') }}">UPCOMING <span>{{ $counts['upcoming'] }}</span></a>
    <a class="admin-tab {{ request()->routeIs('admin.products.archived') ? 'is-active' : '' }}" href="{{ route('admin.products.archived') }}">ARSIP <span>{{ $counts['archived'] }}</span></a>
</div>
