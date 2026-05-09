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

WA_BOT_URL=http://localhost:3000
```
Jalankan website dan bot wa:
```
composer run dev

cd wa-bot
node index.js
```

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