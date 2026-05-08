const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
} = require('@whiskeysockets/baileys');
const express = require('express');
const bodyParser = require('body-parser');
const pino = require('pino');
const qrcode = require('qrcode-terminal');

const app = express();
app.use(bodyParser.json());

const PORT = process.env.WA_BOT_PORT || 3000;

let sock = null;
let isConnected = false;

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
            const shouldReconnect =
                lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;

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
    const { phone, name, type, time, weeklyDurasi } = req.body;

    if (!phone || !name || !type || !time) {
        return res.status(400).json({ success: false, message: 'Parameter tidak lengkap.' });
    }

    if (!isConnected || !sock) {
        return res.status(503).json({ success: false, message: 'Bot WhatsApp belum terhubung.' });
    }

    const jid = formatPhone(phone);

    const emoji = type === 'check_in' ? '🟢' : '🔴';
    const label = type === 'check_in' ? 'Check In' : 'Check Out';

    const weeklyLine = type === 'check_in'
        ? `📊 Total kerja minggu ini (sebelum saat ini): *${weeklyDurasi || '0 menit'}*`
        : `📊 Total kerja minggu ini (termasuk saat ini): *${weeklyDurasi || '0 menit'}*`;

    const message =
        `${emoji} *Notifikasi Absensi*\n\n` +
        `Halo, *${name}*!\n` +
        `Anda berhasil melakukan *${label}* pada:\n` +
        `🕐 ${time}\n\n` +
        `${weeklyLine}\n\n` +
        `_Pesan ini dikirim otomatis oleh sistem absensi._`;

    try {
        await sock.sendMessage(jid, { text: message });
        console.log(`✉️  Notifikasi ${label} terkirim ke ${phone}`);
        return res.json({ success: true, message: 'Notifikasi berhasil dikirim.' });
    } catch (err) {
        console.error('❌ Gagal kirim pesan:', err.message);
        return res.status(500).json({ success: false, message: err.message });
    }
});

// ─────────────────────────────────────────────
// Start server
// ─────────────────────────────────────────────
app.listen(PORT, () => {
    console.log(`🚀 WA Bot server berjalan di http://localhost:${PORT}`);
});

connectToWhatsApp();