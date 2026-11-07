@extends('admin.layout')
@section('title', 'Inventory — NEON')
@section('content')
    <div class="admin-title"><div><span class="eyebrow">ARCHIVAL REGISTRY / INVENTORY</span><h1>INVENTORY & CATALOG</h1></div><a class="button button-dark" href="{{ route('admin.products.create') }}">+ ADD PRODUCT</a></div>
    @include('admin.products.partials.tabs')
    @include('admin.products.partials.filters')
    @include('admin.products.partials.table', ['mode' => 'inventory', 'empty' => 'Belum ada produk aktif.'])
@endsection
