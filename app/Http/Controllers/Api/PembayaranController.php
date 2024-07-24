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
    // protected $response;

    public function __construct(Request $request)
    {
        $this->request = $request;
        // $this->response = [];

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
            ], 422);
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
                $snapResponse = Snap::createTransaction($payload);
                $snapToken = $snapResponse->token ?? null;
                $transactionId = $snapResponse->transaction_id ?? null;
                Log::info('Snap Token: ', ['snap_token' => $snapToken]);
                Log::info('Transaction ID: ', ['transaction_id' => $transactionId]);
                if (!$snapToken) {
                    throw new Exception('Gagal mendapatkan token Snap dari Midtrans');
                    
                }
                $pembayaran->snap_token = $snapToken;
                $pembayaran->transaction_id = $transactionId;
                $pembayaran->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Pembayaran sedang diproses!',
                    'data' => [
                        'order_id' => $pembayaran->id,
                        'snap_token' => $snapToken,
                        'transaction_id' => $transactionId,
                        'pembayaran' => $pembayaran
                    ]
                ]);

            } catch (Exception $e) {
                Log::error('Midtrans API error: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Proses Pembayaran Error: ' . $e->getMessage(),
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

    protected $notif;

    public function notificationHandler(Request $request)
{
    Log::info('Data Notifikasi: ', $request->all());
    $notif = new Notification();
    Log::info('Respon Raw Notifikasi: ', (array)$notif->getResponse());

    $transaction = $notif->transaction_status ?? null;
    $type = $notif->payment_type ?? null;
    $orderId = $notif->order_id ?? null;
    $fraud = $notif->fraud_status ?? null;
    $transactionId = $notif->transaction_id ?? null;

    if (!$orderId) {
        Log::error('Handler Notifikasi: order_id tidak ditemukan');
        return response()->json([
            'success' => false,
            'message' => 'order_id tidak ditemukan'
        ], 400);
    }

    $pembayaran = Pembayaran::find($orderId);
    if (!$pembayaran) {
        Log::error('Handler Notifikasi: Pembayaran tidak ditemukan untuk order_id ' . $orderId);
        return response()->json([
           'success' => false,
           'message' => 'Pembayaran tidak ditemukan'
        ], 404);
    }

    try {
        switch ($transaction) {
            case 'capture':
                if ($type == 'credit_card') {
                    if ($fraud == 'challenge') {
                        $pembayaran->setPending();
                    } else {
                        $pembayaran->setSuccess();
                    }
                }
                break;
            case 'settlement':
                $pembayaran->setSuccess();
                break;
            case 'pending':
                $pembayaran->setPending();
                break;
            case 'deny':
                $pembayaran->setFailed();
                break;
            case 'expire':
                $pembayaran->setExpired();
                break;
            case 'cancel':
                $pembayaran->setFailed();
                break;
            default:
                Log::error('Handler Notifikasi: status transaksi tidak diketahui');
                return response()->json([
                    'success' => false,
                    'message' => 'status transaksi tidak diketahui'
                ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi pembayaran terkirim',
            'data' => [
                'order_id' => $orderId,
                'transaction' => $transaction,
                'type' => $type,
                'transaction_id' => $transactionId,
            ]
        ]);
    } catch (Exception $e) {
        Log::error('Handler Notifikasi: ' . $e->getMessage(), ['exception' => $e]);
        return response()->json([
            'success' => false,
            'message' => 'Error proses notifikasi: ' . $e->getMessage()
        ], 500);
    }
}

    

    

    // public function notificationHandler(Request $request)
    // {
    //     // Log::info('Notification Data: ', $request->all());
    //     $notif = new Notification();
    //     Log::info('Notification Raw response: ', $notif->getResponse());

    //     // if (!$notif) {
    //     //     Log::error('Notification Handler: No notification received');
    //     //     return response()->json([
    //     //        'success' => false,
    //     //        'message' => 'No notification received'
    //     //     ], 400);
    //     // }

    //     $transaction = $notif->transaction_status ?? null;
    //     $type = $notif->payment_type ?? null;
    //     $orderId = $notif->order_id ?? null;
    //     $fraud = $notif->fraud_status ?? null;
    //     // $transaction_id = $notif->transaction_id ?? null;

    //     if (!$orderId) {
    //         Log::error('Notification Handler: Missing order_id');
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Missing order_id'
    //         ], 400);
    //     }

    //     $pembayaran = Pembayaran::find($orderId);
    //     if (!$pembayaran) {
    //         Log::error('Notification Handler: Pembayaran tidak ditemukan untuk order_id ' . $orderId);
    //         return response()->json([
    //            'success' => false,
    //            'message' => 'Pembayaran tidak ditemukan'
    //         ], 404);
    //     }

    //     try {
    //         switch ($transaction) {
    //             case 'capture':
    //                 if ($type == 'credit_card') {
    //                     if ($fraud == 'challenge') {
    //                         $pembayaran->setPending();
    //                     } else {
    //                         $pembayaran->setSuccess();
    //                     }
    //                 }
    //                 break;
    //             case 'settlement':
    //                 $pembayaran->setSuccess();
    //                 break;
    //             case 'pending':
    //                 $pembayaran->setPending();
    //                 break;
    //             case 'deny':
    //                 $pembayaran->setFailed();
    //                 break;
    //             case 'expire':
    //                 $pembayaran->setExpired();
    //                 break;
    //             case 'cancel':
    //                 $pembayaran->setFailed();
    //                 break;
    //             default:
    //             Log::error('Notification Handler: status transaksi tidak diketahui');
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'status transaksi tidak diketahui'
    //             ], 400);
    //         }
            
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Notifikasi pembayaran terkirim',
    //             'data' => [
    //                 'order_id' => $orderId,
    //                 'transaction' => $transaction,
    //                 'type' => $type,
    //                 // 'transaction_id' => $transaction_id,
    //             ]
    //         ]);
    //     } catch (Exception $e) {
    //         Log::error('Notification Handler: ' . $e->getMessage(), ['exception' => $e]);
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error proses notifikasi: ' . $e->getMessage()
    //         ], 500);
    //     }
        
    //     // if ($transaction == 'capture') {
    //     //     if ($type == 'credit_card') {
    //     //         if ($fraud == 'challenge') {
    //     //             $pembayaran->setPending();
    //     //         } else {
    //     //             $pembayaran->setSuccess();
    //     //         }
    //     //     }
    //     // } elseif ($transaction == 'settlement') {
    //     //     $pembayaran->setSuccess();
    //     // } elseif ($transaction == 'pending') {
    //     //     $pembayaran->setPending();
    //     // } elseif ($transaction == 'deny') {
    //     //     $pembayaran->setFailed();
    //     // } elseif ($transaction == 'expire') {
    //     //     $pembayaran->setExpired();
    //     // } elseif ($transaction == 'cancel') {
    //     //     $pembayaran->setFailed();
    //     // }
    // }
}
