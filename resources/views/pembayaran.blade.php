<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran SPP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2>Pembayaran SPP</h2>
        <form id="pembayaran-form">
            <div class="mb-3">
                <label for="student_id" class="form-label">ID Siswa</label>
                <input type="number" class="form-control" id="student_id" name="student_id" required>
            </div>
            <div class="mb-3">
                <label for="donor_email" class="form-label">Email</label>
                <input type="email" class="form-control" id="donor_email" name="donor_email" required>
            </div>
            <button type="submit" class="btn btn-primary">Kirim</button>
        </form>
        <div id="payment-message" class="mt-3"></div>
        <div class="mt-5">
            <h2>Scan Barcode</h2>
            <div id="reader" style="width:500px"></div>
            <div id="result" class="mt-3"></div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#pembayaran-form').on('submit', function(e) {
                e.preventDefault();

                let student_id = $('#student_id').val();
                let donor_email = $('#donor_email').val();

                $.ajax({
                    url: '/api/v1/pembayaran',
                    method: 'POST',
                    data: {
                        student_id: student_id,
                        donor_email: donor_email
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#payment-message').html(`
                                <div class="alert alert-success">
                                    ${response.message}
                                    <br>
                                    Snap Token: ${response.data.snap_token}
                                    <br>
                                    Order ID: ${response.data.order_id}
                                </div>
                            `);
                            window.snap.pay(response.data.snap_token);
                        } else {
                            $('#payment-message').html(`
                                <div class="alert alert-danger">
                                    ${response.message}
                                </div>
                            `);
                        }
                    },
                    error: function(xhr) {
                        $('#payment-message').html(`
                            <div class="alert alert-danger">
                                ${xhr.responseJSON.message}
                            </div>
                        `);
                    }
                });
            });

            window.addEventListener('notification', function(event) {
                let data = event.detail;
                $.ajax({
                    url: '/api/v1/notification',
                    method: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            alert('Notifikasi pembayaran diterima dan diproses.');
                        } else {
                            alert('Gagal memproses notifikasi pembayaran.');
                        }
                    },
                    error: function(xhr) {
                        alert('Error memproses notifikasi pembayaran: ' + xhr.responseJSON.message);
                    }
                });
            });

            function onScanSuccess(decodedText, decodedResult) {
                $('#result').html(`
                    <div class="alert alert-success">
                        Hasil Scan: ${decodedText}
                    </div>
                `);
                // Tindakan setelah scan berhasil, misalnya mengisi form dengan hasil scan
                $('#student_id').val(decodedText);
            }

            function onScanFailure(error) {
                console.warn(`Kode QR tidak terbaca: ${error}`);
            }

            let html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", { fps: 10, qrbox: 250 });
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        });
    </script>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.clientKey') }}"></script>
</body>
</html>
