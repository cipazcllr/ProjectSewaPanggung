# Railway Deployment

Panduan singkat deploy project Laravel ini ke Railway lewat GitHub.

## 1. Push ke GitHub

Jika folder ini belum menjadi repo Git sendiri, jalankan dari root project ini:

```bash
git init
git add .
git commit -m "Prepare Laravel app for Railway"
git branch -M main
git remote add origin https://github.com/USERNAME/REPOSITORY.git
git push -u origin main
```

Pastikan `.env` tidak ikut ter-commit. File ini sudah masuk `.gitignore`.

## 2. Buat Service Web di Railway

1. Buat project baru di Railway.
2. Pilih **Deploy from GitHub repo**.
3. Pilih repository project ini.
4. Tambahkan service database MySQL.
5. Isi variables Laravel.

Start command web sudah disiapkan di `railway.json`:

```bash
sh railway/start-web.sh
```

Script tersebut menjalankan migration, cache config/route, lalu start server di `$PORT`.

Project ini memakai Vite 8, jadi build membutuhkan Node 22. Versi Node sudah dikunci lewat `package.json`, `.nvmrc`, dan `.node-version`.

## 3. Variables Railway

Isi variable berikut di Railway:

```env
APP_NAME="Hilmy Project"
APP_ENV=production
APP_KEY=base64:ISI_DARI_php_artisan_key_generate_show
APP_DEBUG=false
APP_URL=https://DOMAIN_RAILWAY_KAMU

LOG_CHANNEL=stderr
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=ISI_GOOGLE_APP_PASSWORD
MAIL_FROM_ADDRESS=your-gmail@gmail.com
MAIL_FROM_NAME="Hilmy Project"
```

Generate `APP_KEY` dengan:

```bash
php artisan key:generate --show
```

## 4. Worker Email Slip Gaji

Untuk pengiriman email slip gaji, buat service kedua di Railway dari repo yang sama.

Set start command service worker:

```bash
sh railway/start-worker.sh
```

Gunakan variables yang sama seperti service web, terutama database, queue, dan mail.

## 5. Kirim Email Berdasarkan paid_at

Jalankan command ini dari Railway shell atau lokal yang terhubung ke database production:

```bash
php artisan salary-slips:send-emails --date=2026-05-25
```

Worker akan memproses job dan mengirim PDF ke `employee_email`.
