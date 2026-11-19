<?php

namespace App\Http\Controllers;

use App\Services\MidtransService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class MidtransNotificationController extends Controller
{
    /**
     * Handle an HTTP notification sent by Midtrans.
     */
    public function __invoke(Request $request, MidtransService $midtrans): JsonResponse
    {
        try {
            $order = $midtrans->handleNotification($request->all());
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Gagal memproses notifikasi.'], 500);
        }

        return response()->json([
            'message' => 'OK',
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
        ]);
    }
}
