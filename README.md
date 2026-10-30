# qrcode_absensi
qrcode absensi adalah website sistem absensi yang menggunakan laravel, vite dan inertia dengan fitur notifikasi whatsapp.

## Installation
### Program-program yang dibutuhkan:
- Php
- Nodejs
- Composer

### Program opsional untuk expose LAN:
- mkcert
- nginx

### Extension php yang perlu dinyalakan:
- curl
- fileinfo
- gd
- mbstring
- openssl
- pdo_sqlite
- sqlite3
- zip

Menghapus ; di bagian extension di php.ini

## Windows
### Setup projek:
```
git clone https://github.com/FlashyRoaf/qrcode_absensi.git
```

Di folder projek:
```
composer install
npm install
copy .env.example .env
php artisan key:generate

cd wa-bot
npm install
copy .env.example .env
```

Buka .env di folder projek dan edit:
```
APP_URL=http://localhost:8000

STAKS_LATITUDE=lokasi_latitude
STAKS_LONGITUDE=lokasi_longitude
STAKS_TARGET_MINUTES=870
STAKS_RADIUS_METERS=50

WA_BOT_URL=http://localhost:3000
WA_BOT_SECRET=isi-dengan-rahasia-yang-sama-di-wa-bot
WA_BOT_STORAGE_PATH=/path/to/storage/app/private/reports
```

Buka .env di folder `wa-bot` dan isi dengan nilai yang sama:
```
WA_BOT_SECRET=isi-dengan-rahasia-yang-sama-di-wa-bot
WA_BOT_STORAGE_PATH=/path/to/storage/app/private/reports
```

Penting: bot WhatsApp hanya menerima permintaan dari Laravel yang membawa
`WA_BOT_SECRET` yang sama, dan hanya boleh mengirim file laporan `.xlsx`
dari folder `WA_BOT_STORAGE_PATH`. Tanpa secret yang sama, notifikasi
WhatsApp akan gagal (absensi sendiri tetap tercatat).

Akun: tidak ada pendaftaran publik. Akun baru hanya dibuat admin lewat
Admin > Kelola Pengguna.

Bukti hukuman lama: bukti yang dulu tersimpan di disk publik harus
dipindahkan sekali setelah deploy, supaya tautan `/storage/` lama tidak
lagi bisa dibuka tanpa login:
```
php artisan penalties:move-proofs-to-private --dry-run
php artisan penalties:move-proofs-to-private
```
Perintah ini memindahkan file ke disk privat (jalur file di database
tidak berubah, karena route bukti memeriksa disk privat dulu) dan baru
menghapus salinan publik setelah salinannya terverifikasi.

Cara pakai QR: satu QR Code hanya untuk satu karyawan. Setelah satu
karyawan berhasil scan, operator membuat QR Code baru untuk karyawan
berikutnya (halaman scan akan memberi tahu saat kode sudah dipakai).

Catatan lokasi: jarak yang tercatat berasal dari GPS ponsel karyawan,
bukan bukti mutlak kehadiran. Admin bisa meninjau jarak tersebut di
halaman Absensi, dan hari yang check-in tanpa check-out ditandai
"Check-out hilang" di dasbor.

Jalankan website dan bot wa:
```
composer run dev

cd wa-bot
node index.js
```

### Jadwal otomatis (cron) — wajib di server

Laporan mingguan (Minggu 01:00), pembersihan QR kadaluarsa (harian),
penghapusan bukti hukuman lama (mingguan), dan penghapusan koordinat
GPS lama (mingguan) hanya berjalan jika server menjalankan scheduler
Laravel setiap menit. Tanpa baris ini, semua jadwal di atas diam-diam
tidak pernah berjalan:

```
* * * * * cd /path/to/qrcode_absensi && php artisan schedule:run >> /dev/null 2>&1
```

Ganti `/path/to/qrcode_absensi` dengan folder proyek di server.
Di Windows (server lokal), jalankan `php artisan schedule:work` atau
buat Task Scheduler yang menjalankan `php artisan schedule:run` tiap menit.
Jika jadwal berhenti berjalan, halaman Laporan Mingguan admin akan
menampilkan peringatan.

Perintah `app:generate-penalty` sengaja TIDAK dijadwalkan: generator
laporan mingguan sudah membuat hukuman sendiri lewat PenaltyService.
Jalankan `app:generate-penalty` secara manual hanya untuk backfill
hukuman yang terlewat.

### Checklist deploy produksi

- `APP_ENV=production` dan `APP_DEBUG=false` di `.env` (file
  `.env.example` sudah mengirim `APP_DEBUG=false`; jangan pernah
  menyalinnya ke produksi tanpa memeriksa nilai ini — dengan debug
  aktif, halaman error menampilkan stack trace, cookie, dan token).
- `WA_BOT_SECRET` yang sama di `.env` Laravel dan `.env` `wa-bot`.
- Jalankan sekali setelah deploy: `php artisan penalties:move-proofs-to-private --dry-run`,
  lalu tanpa `--dry-run`.
- Pasang baris cron di atas, lalu tunggu Minggu berikutnya untuk
  memastikan laporan mingguan terbuat otomatis.

Scan qrcode menggunakan WhatsApp

### Menyambungkan laravel dengan smtp email
### Brevo
Di .env folder projek edit:
```
MAIL_MAILER=smtp    
MAIL_SCHEME=null
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=example@smtp-brevo.com
MAIL_PASSWORD=smtpkey
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="Qrcode Absensi"
```

## Expose Laravel ke LAN dengan Protokol HTTPS
Di folder projek jalankan command `npm run build`.
Edit .env di folder projek:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://192.168.x.x
VITE_APP_URL=${APP_URL}
```

Di folder nginx:
```
mkdir ssl
``` 

Lalu jalankan mkcert.exe menggunakan terminal:
```
.\mkcert.exe -install
.\mkcert.exe 192.168.x.x localhost 127.0.0.1
```

Copy cert.pem dan cert-key.pem ke folder ssl yang baru dibuat

Jalankan php-cgi di folder php dengan terminal:
C:\php\php-cgi.exe -b 127.0.0.1:9000

Jalankan nginx.exe di terminal
.\nginx.exe