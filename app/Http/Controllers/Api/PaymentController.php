<?php

namespace App\Http\Controllers\API;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $student = Student::where('nis', $request->nis)->first();
        if (!$student) {
            return response()->json([
                'message' => 'Data siswa tidak ditemukan',
            ]);
        }

        $tagihanSpp = $student->tagihan_spp;
        $itemName = $request->has('item_name') ? $request->item_name : 'Pembayaran SPP ' . $student->nama;

        $params = [
            'transaction_details' => [
                'order_id' => Str::uuid(),
                'gross_amount' => $tagihanSpp,
            ],
            'item_details' => [
                'price' => $tagihanSpp,
                'quantity' => 1,
                'name' => $itemName,
            ],
            'customer_details' => [
                'first_name' => $request->nama,
                'email' => $request->email
            ],
            'enabled_payments' => [
                'credit_card',
                'bca_va',
                'bni_va',
                'bri_va',
                // 'gopay',
                'indomaret',
                'shopeepay',
                'other_qris'

            ]
        ];

        $auth = base64_encode(env('MIDTRANS_SERVERKEY'));

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Basic $auth"
        ])->post('https://app.sandbox.midtrans.com/snap/v1/transactions', $params);

        $response = json_decode($response->body());
        Log::info('Midtrans response:', (array) $response);

        $payment = new Payment;
        $payment->order_id = $params['transaction_details']['order_id'];
        $payment->status = 'pending';
        $payment->amount = $tagihanSpp;
        $payment->nis = $request->nis;
        $payment->nama = $request->nama;
        $payment->email = $request->email;
        $payment->item_name = $itemName;
        $payment->checkout_link = $response->redirect_url;
        $payment->save();

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran sedang diproses',
            'data' => [
                'payment' => $payment,
                'response' => $response
            ]
        ]);
    }

    public function notificationHandler(Request $request)
    {
        Log::info('Midtrans notification received:', $request->all());
        $auth = base64_encode(env('MIDTRANS_SERVERKEY'));

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Basic $auth"
        ])->get("https://api.sandbox.midtrans.com/v2/$request->order_id/status");

        $response = json_decode($response->body());
        Log::info('Midtrans response:', (array) $response);

        $payment = Payment::where('order_id', $response->order_id)->firstOrFail();

        if ($payment->status === 'settlement' || $payment->status === 'capture') {
            return response()->json([
                'message' => 'payment has been already processed',
            ]);
        }

        if ($response->transaction_status === 'capture') {
            $payment->status = 'capture';
        } elseif ($response->transaction_status === 'settlement') {
            $payment->status = 'settlement';

            $student = Student::where('nis', $payment->nis)->first();

            if ($student) {
                Log::info('Student found:', $student->toArray());

                $student->tagihan_spp = 0;
                $student->save();

                Log::info('Student updated:', $student->toArray());
            } else {
                Log::info('Student not found with nis :', $payment->nis);
            }
        } else if ($response->transaction_status === 'pending') {
            $payment->status = 'pending';
        } else if ($response->transaction_status === 'deny') {
            $payment->status = 'deny';
        } else if ($response->transaction_status === 'expire') {
            $payment->status = 'expire';
        } else if ($response->transaction_status === 'cancel') {
            $payment->status = 'cancel';
        }

        $payment->save();
        return response()->json([
            'success' => true,
            'message' => 'Status Pembayaran telah diperbarui'
        ]);
    }
}
