<?php

namespace App\Http\Controllers\API;

use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Pembayaran;
use Midtrans\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Exception;
use Illuminate\Support\Facades\Validator;

class PembayaranController extends Controller
{
    protected $request;
    protected $response;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->response = [];

        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');
    }

    public function submitPembayaran(Request $request)
    {
        Log::info('Request Data: ', $request->all());
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 402);
        }

        $data = DB::transaction(function () use ($request) {
            $student = Student::find($request->student_id);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Siswa Tidak ditemukan!'
                ], 404);
            }

            Log::info('Creating Pembayaran record.');
            $pembayaran = Pembayaran::create([
                'student_id' => $student->id,
                'donor_name' => $student->nama,
                'donor_email' => $request->donor_email,
                'donation_type' => 'SPP',
                'amount' => floatval($student->tagihan_spp),
                'note' => 'Pembayaran SPP untuk ' . $student->nama,
                // 'donor_name' => $this->request->donor_name,
                // 'donor_email' => $this->request->donor_email,
                // 'donation_type' => $this->request->donation_type,
                // 'amount' => floatval($this->request->amount),
                // 'note' => $this->request->note,
            ]);

            Log::info('Created Pembayaran record: ', $pembayaran->toArray());

            $payload = [
                'transaction_details' => [
                    'order_id' => $pembayaran->id,
                    'gross_amount' => $pembayaran->amount
                ],
                'customer_details' => [
                    'first_name' => $pembayaran->donor_name,
                    'email' => $pembayaran->donor_email
                ],
                'item_details' => [
                    [
                        'id' => 'SPP-' . $student->id,
                        'price' => $pembayaran->amount,
                        'quantity' => 1,
                        'name' => 'Pembayaran SPP'
                    ]
                ]
            ];
            Log::info('Payload for Midtrans: ', $payload);

            try {
                $snapToken = Snap::getSnapToken($payload);
                Log::info('Snap Token: ', ['snap_token' => $snapToken]);
                $pembayaran->snap_token = $snapToken;
                $pembayaran->save();

                $this->response['snap_token'] = $snapToken;
                return $pembayaran;
            } catch (Exception $e) {
                Log::error('Midtrans API error: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Proses Pembayaran Error'
                ], 500);
            }
        });
        // Log::info('Transaction Completed: ', $data->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran sedang diproses',
            'data' => $data
        ]);
    }

    public function notificationHandler(Request $request)
    {
        $notif = new Notification();

        $transaction = $notif->transaction_status;
        $type = $notif->payment_type;
        $orderId = $notif->order_id;
        $fraud = $notif->fraud_status;
        $pembayaran = Pembayaran::findOrFail($orderId);

        if ($transaction == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $pembayaran->setPending();
                } else {
                    $pembayaran->setSuccess();
                }
            }
        } elseif ($transaction == 'settlement') {
            $pembayaran->setSuccess();
        } elseif ($transaction == 'pending') {
            $pembayaran->setPending();
        } elseif ($transaction == 'deny') {
            $pembayaran->setFailed();
        } elseif ($transaction == 'expire') {
            $pembayaran->setExpired();
        } elseif ($transaction == 'cancel') {
            $pembayaran->setFailed();
        }
    }
}
