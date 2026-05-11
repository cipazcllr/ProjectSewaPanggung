# API Documentation

Dokumentasi ini menjelaskan endpoint REST API untuk autentikasi, dashboard, produk, order, dan user.

## Base URL

```text
http://localhost:8000/api
```

Jalankan server lokal:

```bash
php artisan serve
```

## Authentication

Endpoint selain `POST /auth/login` membutuhkan bearer token.

Header:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

Token didapat dari response login. Jika database sudah di-seed, token awal untuk akun admin adalah:

```http
Authorization: Bearer ProjectHilmyAdminToken
```

Catatan: setelah login berhasil, gunakan nilai `token` dari response login karena token lama akan diganti.

## Response Error Umum

Unauthenticated:

```json
{
  "message": "Unauthenticated."
}
```

Validation error:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Pesan error"]
  }
}
```

## Auth

### Login

```http
POST /api/auth/login
```

Request:

```json
{
  "email": "Admin@gmail.com",
  "password": "ProjectHilmy"
}
```

Response:

```json
{
  "ok": true,
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "Admin@gmail.com",
    "role": "admin"
  },
  "token": "generated-login-token"
}
```

### Logout

```http
POST /api/auth/logout
```

Response:

```json
{
  "success": true
}
```

### Current User

```http
GET /api/auth/me
```

Response:

```json
{
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "Admin@gmail.com",
    "role": "admin"
  }
}
```

## Dashboard

### Summary Stats

```http
GET /api/dashboard/stats
```

Response:

```json
{
  "totalRevenue": 2500000,
  "pendingOrders": 3,
  "lowStock": 2
}
```

### Revenue Chart

```http
GET /api/dashboard/chart?startDate=2025-01-01&endDate=2025-12-31
```

Query params:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `startDate` | date | No | Tanggal awal, format `YYYY-MM-DD` |
| `endDate` | date | No | Tanggal akhir, format `YYYY-MM-DD` |

Response:

```json
[
  {
    "date": "2025-01-01",
    "revenue": 150000
  }
]
```

### Recent Orders

```http
GET /api/dashboard/recent-orders?limit=5
```

Query params:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `limit` | integer | No | Jumlah order terbaru, default `5`, maksimal `100` |

Response:

```json
[
  {
    "id": 1,
    "order_number": "ORD-001",
    "customer": "Budi",
    "total": "150000.00",
    "date": "2025-10-20",
    "status": "Pending",
    "items": []
  }
]
```

## Products

### List Products

```http
GET /api/products?page=1&limit=10&search=query&sort=name
```

Query params:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `page` | integer | No | Halaman, default `1` |
| `limit` | integer | No | Jumlah data, default `10`, maksimal `100` |
| `search` | string | No | Cari berdasarkan nama produk |
| `sort` | string | No | Salah satu: `id`, `name`, `price`, `stock`, `created_at` |

Response:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Produk A",
      "price": "25000.00",
      "stock": 20
    }
  ],
  "total": 1,
  "page": 1
}
```

### Product Detail

```http
GET /api/products/{id}
```

Response:

```json
{
  "id": 1,
  "name": "Produk A",
  "price": "25000.00",
  "stock": 20
}
```

### Create Product

```http
POST /api/products
```

Request:

```json
{
  "name": "Produk A",
  "price": 25000,
  "stock": 20
}
```

Response:

```json
{
  "id": 1,
  "name": "Produk A",
  "price": "25000.00",
  "stock": 20
}
```

### Update Product

```http
PUT /api/products/{id}
```

Request:

```json
{
  "name": "Produk A Update",
  "price": 30000,
  "stock": 15
}
```

Response:

```json
{
  "id": 1,
  "name": "Produk A Update",
  "price": "30000.00",
  "stock": 15
}
```

### Delete Product

```http
DELETE /api/products/{id}
```

Response:

```json
{
  "success": true
}
```

### Bulk Update Stock

```http
POST /api/products/bulk-update-stock
```

Request:

```json
[
  {
    "productId": 1,
    "qty": 5
  },
  {
    "productId": 2,
    "qty": -3
  }
]
```

Response:

```json
{
  "success": true,
  "updated": 2
}
```

Catatan: `qty` bernilai positif untuk menambah stok dan negatif untuk mengurangi stok.

## Orders

### List Orders

```http
GET /api/orders?page=1&limit=10&status=Pending&date=2025-10-20
```

Query params:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `page` | integer | No | Halaman, default `1` |
| `limit` | integer | No | Jumlah data, default `10`, maksimal `100` |
| `status` | string | No | Filter status order |
| `date` | date | No | Filter tanggal order, format `YYYY-MM-DD` |

Response:

```json
{
  "data": [
    {
      "id": 1,
      "order_number": "ORD-001",
      "customer": "Budi",
      "total": "150000.00",
      "date": "2025-10-20",
      "status": "Pending",
      "items": []
    }
  ],
  "total": 1,
  "page": 1
}
```

### Order Detail

```http
GET /api/orders/{id}
```

Response:

```json
{
  "id": 1,
  "order_number": "ORD-001",
  "customer": "Budi",
  "total": "150000.00",
  "date": "2025-10-20",
  "status": "Pending",
  "items": [
    {
      "id": 1,
      "product_id": 1,
      "qty": 2,
      "price": "75000.00",
      "subtotal": "150000.00",
      "product": {
        "id": 1,
        "name": "Produk A"
      }
    }
  ]
}
```

### Create Order

```http
POST /api/orders
```

Request:

```json
{
  "customer": "Budi",
  "items": [
    {
      "productId": 1,
      "qty": 2,
      "price": 75000
    }
  ],
  "date": "2025-10-20",
  "status": "Pending"
}
```

Response:

```json
{
  "id": 1,
  "order_number": "ORD-001",
  "customer": "Budi",
  "total": "150000.00",
  "date": "2025-10-20",
  "status": "Pending",
  "items": []
}
```

Catatan: stok produk otomatis berkurang saat order dibuat.

### Update Order

```http
PUT /api/orders/{id}
```

Request update status:

```json
{
  "status": "Completed"
}
```

Request update detail:

```json
{
  "customer": "Budi Update",
  "items": [
    {
      "productId": 1,
      "qty": 1,
      "price": 75000
    }
  ],
  "date": "2025-10-21",
  "status": "Completed"
}
```

Response:

```json
{
  "id": 1,
  "order_number": "ORD-001",
  "customer": "Budi Update",
  "total": "75000.00",
  "date": "2025-10-21",
  "status": "Completed",
  "items": []
}
```

### Delete Order

```http
DELETE /api/orders/{id}
```

Response:

```json
{
  "success": true
}
```

Catatan: stok item order dikembalikan saat order dihapus.

### Export Order PDF

```http
GET /api/orders/export/pdf?orderId=ORD-001
```

Query params:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `orderId` | string | Yes | Bisa `order_number` seperti `ORD-001` atau ID numeric |

Response:

```text
PDF file
```

### Validate Stock

```http
POST /api/orders/validate-stock
```

Request:

```json
[
  {
    "productId": 1,
    "qty": 2
  }
]
```

Response tersedia:

```json
{
  "available": true,
  "errors": []
}
```

Response stok kurang:

```json
{
  "available": false,
  "errors": [
    {
      "productId": 1,
      "requested": 10,
      "available": 3
    }
  ]
}
```

## Salary Slips

Semua endpoint slip gaji membutuhkan header autentikasi:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

### List Salary Slips

```http
GET /api/salary-slips?page=1&limit=10&search=budi&status=Paid&period=2026-05
```

Query params:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `page` | integer | No | Halaman, default `1` |
| `limit` | integer | No | Jumlah data, default `10`, maksimal `100` |
| `search` | string | No | Cari berdasarkan nama, email, atau jabatan karyawan |
| `status` | string | No | Filter status, contoh `Paid` atau `Unpaid` |
| `period` | string | No | Filter periode, format `YYYY-MM` |

Response:

```json
{
  "data": [
    {
      "id": 1,
      "employee_name": "Budi",
      "employee_email": "budi@example.com",
      "position": "Staff",
      "period": "2026-05",
      "basic_salary": "5000000.00",
      "allowance": "500000.00",
      "deduction": "100000.00",
      "net_salary": "5400000.00",
      "status": "Paid",
      "paid_at": "2026-05-25",
      "notes": null
    }
  ],
  "total": 1,
  "page": 1
}
```

### Salary Slip Detail

```http
GET /api/salary-slips/{id}
```

### Create Salary Slip

```http
POST /api/salary-slips
```

Request:

```json
{
  "employee_name": "Budi",
  "employee_email": "budi@example.com",
  "position": "Staff",
  "period": "2026-05",
  "basic_salary": 5000000,
  "allowance": 500000,
  "deduction": 100000,
  "status": "Paid",
  "paid_at": "2026-05-25",
  "notes": "Gaji bulan Mei"
}
```

`net_salary` dihitung otomatis dari `basic_salary + allowance - deduction`.

### Update Salary Slip

```http
PUT /api/salary-slips/{id}
```

Request boleh parsial:

```json
{
  "status": "Paid",
  "paid_at": "2026-05-25"
}
```

### Delete Salary Slip

```http
DELETE /api/salary-slips/{id}
```

Response:

```json
{
  "success": true
}
```

### Export Salary Slip PDF

```http
GET /api/salary-slips/export/pdf?id={id}
```

Response:

```text
PDF file
```

### Send Salary Slip Emails

Email slip gaji dikirim oleh queue job berdasarkan tanggal `paid_at`. Job akan mengambil slip gaji yang:

- `paid_at` sama dengan tanggal command
- `employee_email` terisi
- `email_sent_at` masih kosong

Jalankan command untuk memasukkan job ke queue:

```bash
php artisan salary-slips:send-emails --date=2026-05-25
```

Jalankan worker queue untuk memproses pengiriman:

```bash
php artisan queue:work
```

Untuk mengirim ulang slip yang sudah pernah terkirim:

```bash
php artisan salary-slips:send-emails --date=2026-05-25 --force
```

Setelah email berhasil dikirim, kolom `email_sent_at` akan otomatis diisi.

Catatan: default `.env` memakai `MAIL_MAILER=log`, jadi email akan masuk ke log Laravel. Untuk benar-benar mengirim email, ubah konfigurasi SMTP di `.env`.

## Users

### List Users

```http
GET /api/users?page=1&limit=10&role=admin&search=query
```

Query params:

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `page` | integer | No | Halaman, default `1` |
| `limit` | integer | No | Jumlah data, default `10`, maksimal `100` |
| `role` | string | No | Filter role |
| `search` | string | No | Cari berdasarkan nama atau email |

Response:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Admin",
      "email": "Admin@gmail.com",
      "role": "admin"
    }
  ],
  "total": 1,
  "page": 1
}
```

### User Detail

```http
GET /api/users/{id}
```

Response:

```json
{
  "id": 1,
  "name": "Admin",
  "email": "Admin@gmail.com",
  "role": "admin"
}
```

### Create User

```http
POST /api/users
```

Request:

```json
{
  "name": "Admin",
  "email": "Admin@gmail.com",
  "role": "admin",
  "password": "ProjectHilmy"
}
```

Response:

```json
{
  "id": 1,
  "name": "Admin",
  "email": "Admin@gmail.com",
  "role": "admin"
}
```

### Update User

```http
PUT /api/users/{id}
```

Request:

```json
{
  "name": "Admin Update",
  "email": "Admin@gmail.com",
  "role": "admin",
  "password": "ProjectHilmy"
}
```

`password` boleh dikosongkan atau tidak dikirim jika tidak ingin mengganti password.

Response:

```json
{
  "id": 1,
  "name": "Admin Update",
  "email": "admin@example.com",
  "role": "admin"
}
```

### Delete User

```http
DELETE /api/users/{id}
```

Response:

```json
{
  "success": true
}
```

### Check Email Availability

```http
POST /api/users/check-email
```

Request:

```json
{
  "email": "Admin@gmail.com",
  "excludeId": 1
}
```

Response:

```json
{
  "available": true
}
```

### Change Password

```http
PUT /api/users/change-password
```

Request:

```json
{
  "oldPassword": "password123",
  "newPassword": "newpassword123"
}
```

Response:

```json
{
  "success": true
}
```

## Quick Test Flow

1. Jalankan migration:

```bash
php artisan migrate
```

2. Buat user pertama lewat tinker atau database seeder:

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'Admin@gmail.com',
    'role' => 'admin',
    'password' => 'ProjectHilmy',
]);
```

3. Login:

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"Admin@gmail.com\",\"password\":\"ProjectHilmy\"}"
```

4. Gunakan token untuk endpoint protected:

```bash
curl http://localhost:8000/api/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer generated-login-token"
```
