@extends('admin.layout')
@section('title', 'Upcoming — NEON')
@section('content')
    <div class="admin-title"><div><span class="eyebrow">NEXT DROP / UPCOMING</span><h1>UPCOMING DROP</h1></div><a class="button button-dark" href="{{ route('upcoming.index') }}" target="_blank">LIHAT HALAMAN PUBLIK ↗</a></div>
    @include('admin.products.partials.tabs')
    @include('admin.products.partials.filters')
    @include('admin.products.partials.table', ['mode' => 'upcoming', 'empty' => 'Belum ada produk yang ditandai upcoming.'])
@endsection
