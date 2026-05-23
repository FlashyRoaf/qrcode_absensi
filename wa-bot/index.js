require('dotenv').config();
const { default: makeWASocket, useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion } = require('@whiskeysockets/baileys');
const express = require('express');
const bodyParser = require('body-parser');
const pino = require('pino');
const qrcode = require('qrcode-terminal');
const quotesData = require('./quotes.json');

const app = express();
app.use(bodyParser.json());

const PORT = process.env.WA_BOT_PORT || 3000;

let sock = null;
let isConnected = false;

function getRandomQuote() {
    return quotesData[Math.floor(Math.random() * quotesData.length)];
}

// ─────────────────────────────────────────────
// Inisialisasi koneksi WhatsApp
// ─────────────────────────────────────────────
async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState('./auth_info');
    const { version } = await fetchLatestBaileysVersion();

    sock = makeWASocket({
        version,
        auth: state,
        logger: pino({ level: 'silent' }),
        // getMessage: async (key) => {
        //     return { conversation: 'hello' };
        // },
    });

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('\n📱 Scan QR Code berikut menggunakan WhatsApp Anda:');
            console.log('   (Settings → Linked Devices → Link a Device)\n');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'close') {
            isConnected = false;
            const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;

            console.log('⚠️  Koneksi terputus. Reconnect:', shouldReconnect);
            if (shouldReconnect) {
                setTimeout(connectToWhatsApp, 3000);
            } else {
                console.log('❌ Logged out. Hapus folder auth_info lalu restart.');
            }
        }

        if (connection === 'open') {
            isConnected = true;
            console.log('✅ Bot WhatsApp terhubung!');
        }
    });

    // sock.ev.on('messages.upsert', ({ messages, type }) => {
    //     messages.forEach((msg) => {
    //         if (msg.key.fromMe) return; // skip pesan dari bot sendiri
    //         console.log('Chat ID:', msg.key.remoteJid);
    //         console.log('Type:', type);
    //     });
    // });

    // sock.ev.on('messages.upsert', (upsert) => {
    //     console.log('📨 Upsert received:', JSON.stringify(upsert, null, 2));
    // });

    sock.ev.on('creds.update', saveCreds);
}

// ─────────────────────────────────────────────
// Helper: format nomor WA (628xxx)
// ─────────────────────────────────────────────
function formatPhone(phone) {
    // Hapus karakter non-digit
    let cleaned = phone.replace(/\D/g, '');

    // Ganti awalan 0 dengan 62
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.slice(1);
    }

    // Pastikan sudah pakai 62
    if (!cleaned.startsWith('62')) {
        cleaned = '62' + cleaned;
    }

    return cleaned + '@s.whatsapp.net';
}

// ─────────────────────────────────────────────
// Health check endpoint
// ─────────────────────────────────────────────
app.get('/status', (req, res) => {
    res.json({ connected: isConnected });
});

// ─────────────────────────────────────────────
// Endpoint: kirim notifikasi absensi
// POST /send-notification
// Body: { phone, name, type, time }
//   - phone : nomor WA pekerja (format 08xx / 628xx)
//   - name  : nama pekerja
//   - type  : "check_in" atau "check_out"
//   - time  : waktu absensi (string)
// ─────────────────────────────────────────────
app.post('/send-notification', async (req, res) => {
    const { phone, name, type, time, weeklyDurasi, weeklyTarget } = req.body;

    const randomQuote = getRandomQuote();
    
    const checkInGreetings = [
        `Halo *${name}*! Senang kamu sudah hadir hari ini 😊`,
        `Hai *${name}*! Terima kasih sudah datang, tim jadi lebih lengkap! 🌟`,
        `Halo *${name}*! Kehadiranmu sangat berarti untuk tim hari ini ✨`,
        `Yay, *${name}* sudah siap tempur! 💪`,
        `Selamat datang, *${name}*! Hari ini pasti luar biasa! 🌟`,
        `*${name}* sudah hadir! Tim makin lengkap! 🎉`,
    ];

    const checkOutGreetings = [
        `Terima kasih atas kerja kerasmu hari ini, *${name}*! Istirahat yang baik ya 🙏`,
        `Makasih banyak *${name}*! Kontribusimu hari ini sangat berarti 💙`,
        `*${name}* sudah berjuang hari ini! Selamat beristirahat, kamu layak mendapatkannya 😊`,
        `Terima kasih sudah memberikan yang terbaik hari ini, *${name}*! Sampai jumpa besok 👋`,
        `Hebat *${name}*! Hari ini sudah selesai, saatnya quality time bersama orang tersayang 🏡`,
        `Kerja keras *${name}* hari ini sangat berarti! 🙌`,
        `Istirahat yang baik ya, *${name}*! Kamu sudah luar biasa hari ini! 😊`,
        `*${name}* sudah berjuang hari ini! Saatnya recharge! ⚡`,
    ];

    const greetings =
        type === 'check_in'
            ? checkInGreetings[Math.floor(Math.random() * checkInGreetings.length)]
            : checkOutGreetings[Math.floor(Math.random() * checkOutGreetings.length)];

    if (!phone || !name || !type || !time) {
        return res.status(400).json({ success: false, message: 'Parameter tidak lengkap.' });
    }

    if (!isConnected || !sock) {
        return res.status(503).json({ success: false, message: 'Bot WhatsApp belum terhubung.' });
    }

    const jid = formatPhone(phone);

    const emoji = type === 'check_in' ? '🟢' : '🔴';
    const label = type === 'check_in' ? 'Check In' : 'Check Out';

    const weeklyLine =
        type === 'check_in'
            ? `📊 Total minggu ini (sebelum saat ini): *${weeklyDurasi || '0 menit'}*`
            : `📊 Total minggu ini (termasuk saat ini): *${weeklyDurasi || '0 menit'}*`;

    const message =
        `${emoji} *${label}* berhasil!\n\n` +
        `${greetings}\n\n` +
        `🕐 Waktu: *${time}*\n` +
        `${weeklyLine}\n` +
        `🎯 Sisa perjuanganmu: ${weeklyTarget} lagi. Semangat!\n\n ` +
        `💬 _"${randomQuote}"_\n\n` +
        `_Pesan otomatis satpam absensi_ 🤖`;

    try {
        await sock.sendMessage(jid, { text: message });
        console.log(`✉️  Notifikasi ${label} terkirim ke ${phone}`);
        return res.json({ success: true, message: 'Notifikasi berhasil dikirim.' });
    } catch (err) {
        console.error('❌ Gagal kirim pesan:', err.message);
        return res.status(500).json({ success: false, message: err.message });
    }
});

const fs = require('fs');

app.post('/send-file', async (req, res) => {
    try {
        const { groupId, filePath, filename, caption } = req.body;

        if (!isConnected || !sock) {
            return res.status(503).json({ success: false, message: 'Bot belum terhubung.' });
        }

        const fileBuffer = fs.readFileSync(filePath);

        await sock.sendMessage(groupId, {
            document: fileBuffer,
            mimetype: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            fileName: filename,
            caption: caption,
        });

        console.log(`✅ Weekly report terkirim ke grup`);
        res.json({ success: true });
    } catch (err) {
        console.error('❌ Gagal kirim file:', err.message);
        res.status(500).json({ success: false, message: err.message });
    }
});

// ─────────────────────────────────────────────
// Start server
// ─────────────────────────────────────────────
app.listen(PORT, () => {
    console.log(`🚀 WA Bot server berjalan di http://localhost:${PORT}`);
});

connectToWhatsApp();
