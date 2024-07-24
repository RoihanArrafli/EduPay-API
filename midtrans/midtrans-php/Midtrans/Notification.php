<?php

namespace Midtrans;

use Illuminate\Support\Facades\Log;

/**
 * Read raw post input and parse as JSON. Provide getters for fields in notification object
 *
 * Example:
 *
 * ```php
 * 
 *   namespace Midtrans;
 * 
 *   $notif = new Notification();
 *   echo $notif->order_id;
 *   echo $notif->transaction_status;
 * ```
 */
class Notification
{
    private $response;

    public function __construct($input_source = "php://input")
    {
        // $this->response = json_decode(file_get_contents($input_source), true);
        $raw_notification = json_decode(file_get_contents($input_source), true);
        Log::info('Payload mentah notifikasi: ', $raw_notification);

        // Cek apakah transaction_id ada dalam payload
        if (isset($raw_notification['transaction_id'])) {
            // Ambil status transaksi menggunakan transaction_id
            $status_response = Transaction::status($raw_notification['transaction_id']);
            $this->response = $status_response;
        } else {
            // Jika transaction_id tidak ada, log peringatan dan simpan payload mentah sebagai respon
            Log::warning('transaction_id tidak ditemukan dalam payload notifikasi');
            $this->response = (object)$raw_notification;
        }
        // $status_response = Transaction::status($raw_notification['transaction_id']);
        // $this->response = $status_response;
    }

    public function __get($name)
    {
        if (isset($this->response->$name)) {
            return $this->response->$name;
        }
    }

    public function getResponse()
    {
        return $this->response;
    }
}
