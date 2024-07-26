# EduPay-API

EduPay-API is a Laravel-based API for managing student payments, including tuition fees, via Midtrans payment gateway.

## Features

- Manage student data (CRUD)
- Manage class data (CRUD)
- Handle payments via Midtrans
- Payment notification handling

## Installation

1. Clone the repository:

```bash
git clone https://github.com/RoihanArrafli/EduPay-API.git
cd EduPay-API```

Install dependencies:
```composer install```
Copy the example .env file and configure your environment variables:
```bash
cp .env.example .env```
Generate an application key:
```bash
php artisan key:generate``
Set up your database and Midtrans credentials in the .env file:
```plaintext
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

MIDTRANS_CLIENTKEY=your_midtrans_client_key
MIDTRANS_SERVERKEY=your_midtrans_server_key
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true```
Run the database migrations:
```bash
php artisan migrate```
Usage
Adding a Class
**Endpoint:** ```POST``` ```/api/v1/kelas```

**Request Body:**

json
```{
    "tingkat_kelas": "10",
    "nominal_spp": 200000
}```

**Adding a Student**
**Endpoint:** ```POST``` ```/api/v1/students/```

Request Body:

json
```{
    "nama": "nama",
    "alamat": "alamat",
    "jenis_kelamin": "jenis_kelamin",
    "ortu": "ortu",
    "TTL": "TTL",
    "nis": "nis",
    "kelas_id": "sesuai id kelas yg ditambahkan"
}```

**Creating a Payment**
**Endpoint:** ```POST``` ```/api/v1/payments```

Request Body:

json
```{
    "nis": "sesuaikan nis",
    "nama": "nama",
    "email": "email"
}```

After sending the request, copy the redirect_url/checkout_link from the response and paste it into a web browser. Select a payment method, e.g., BRIVA, copy the BRIVA code and paste it into the Midtrans Simulator. You can change the bank according to the chosen payment method and click inquire -> pay.

Logging
The application logs Midtrans responses and updates student data accordingly. Logs can be found in the storage/logs/laravel.log file.

Contributing
Contributions are welcome! Please submit a pull request or open an issue to discuss improvements or bugs.

License
This project is open-source and available under the MIT License.

css
```
Make sure to adjust any specific details and customize the text as needed. This `README.md` file```
