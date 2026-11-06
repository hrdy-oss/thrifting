@extends('admin.layout')
@section('title', 'Orders & Logistics — NEON')
@section('content')
    <div class="admin-title"><div><span class="eyebrow">ORDER DISPATCH / LOGISTICS</span><h1>SALES & ORDERS</h1></div></div>
    <div class="admin-table-wrap">
        <table class="admin-table admin-table-cards">
            <thead><tr><th>ORDER / CUSTOMER</th><th>TUJUAN</th><th>TOTAL</th><th>STATUS</th><th>TRACKING</th><th>UBAH STATUS</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td data-label="ORDER / CUSTOMER"><strong>{{ $order->order_number }}</strong><small class="table-sub">{{ $order->customer_name }} · {{ $order->customer_phone }}</small><a class="table-sub" href="{{ route('orders.track', $order->tracking_token) }}" target="_blank">LIHAT TRACKING →</a></td>
                        <td data-label="TUJUAN">{{ $order->city }}, {{ $order->province }}</td>
                        <td data-label="TOTAL">Rp{{ number_format($order->total, 0, ',', '.') }}</td>
                        <td data-label="STATUS"><span class="status-tag">{{ strtoupper(str_replace('_', ' ', $order->status)) }}</span></td>
                        <td data-label="TRACKING">{{ $order->courier }}<small class="table-sub">{{ $order->tracking_number ?: 'Resi belum diisi' }}</small></td>
                        <td data-label="UBAH STATUS"><form class="status-form" method="POST" action="{{ route('admin.orders.update', $order) }}">@csrf @method('PATCH')<select name="status">@foreach ($statuses as $status)<option value="{{ $status }}" @selected($order->status === $status)>{{ strtoupper(str_replace('_', ' ', $status)) }}</option>@endforeach</select><input name="courier" value="{{ $order->courier }}" placeholder="Kurir"><input name="tracking_number" value="{{ $order->tracking_number }}" placeholder="Nomor resi"><button class="button button-dark" type="submit">UPDATE</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada pesanan.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $orders->links() }}
    </div>
@endsection
