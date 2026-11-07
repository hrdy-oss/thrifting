@extends('admin.layout')
@section('title', 'Arsip — NEON')
@section('content')
    <div class="admin-title"><div><span class="eyebrow">ARCHIVE / RESTORE</span><h1>ARSIP PRODUK</h1></div></div>
    @include('admin.products.partials.tabs')
    @include('admin.products.partials.filters')
    @include('admin.products.partials.table', ['mode' => 'archived', 'empty' => 'Belum ada produk di arsip.'])
@endsection
