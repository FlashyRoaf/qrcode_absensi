<template>
  <Head :title="message ? success ? (isCheckIn ? 'Check In Berhasil' : 'Check Out Berhasil') : 'Absensi Gagal' : 'Mengambil Lokasi...'" />

  <div
    class="min-h-screen bg-white text-zinc-800 dark:bg-zinc-950 dark:text-zinc-100 font-sans overflow-x-hidden relative flex items-center justify-center">

    <!-- Subtle grid background -->
    <div class="fixed inset-0 z-0 pointer-events-none dark:[background-image:linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)]"
      style="background-image: linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px); background-size: 40px 40px;"
    ></div>

    <!-- Glow blob — hijau kalau sukses, merah kalau gagal -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] z-0 pointer-events-none" :style="glowBlobStyle"></div>

    <!-- ── HEADER ── -->
    <header class="fixed top-0 left-0 right-0 z-20 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
      <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-emerald-500 rounded-md flex items-center justify-center">
            <QrCode class="w-4 h-4 text-black" />
          </div>
          <div>
            <p class="text-xs text-zinc-500 uppercase tracking-widest">Admin Panel</p>
            <h1 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 leading-tight">Absensi Digital</h1>
          </div>
        </div>
        <p class="text-xs text-zinc-500 dark:text-zinc-600 font-mono">{{ currentTime }}</p>
      </div>
    </header>

    <!-- ── MAIN CONTENT ── -->
    <main v-if="message" class="relative z-10 w-full max-w-5xl mx-auto px-6 py-10 pt-28 pb-24">

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">

        <!-- ── KIRI: STATUS VISUAL ── -->
        <div class="flex flex-col gap-5">

          <!-- Status Card Utama -->
          <div class="bg-white border rounded-2xl p-8 flex flex-col items-center gap-6 relative overflow-hidden"
            :class="statusCardClasses">

            <!-- Glow bg di dalam card -->
            <div class="absolute inset-0 pointer-events-none rounded-2xl" :style="cardGlowStyle"></div>

            <!-- Orb icon -->
            <div class="relative flex items-center justify-center">
              <!-- Outer ring pulse -->
              <div class="absolute w-28 h-28 rounded-full border animate-ping-slow" :class="{
                'border-emerald-500/20': success && isCheckIn,
                'border-zinc-300/40 dark:border-zinc-600/30': success && !isCheckIn,
                'border-red-500/20': !success,
              }">
              </div>
              <!-- Mid ring -->
              <div class="absolute w-20 h-20 rounded-full border" :class="{
                'border-emerald-500/40': success && isCheckIn,
                'border-zinc-300/60 dark:border-zinc-600/50': success && !isCheckIn,
                'border-red-500/40': !success,
              }">
              </div>
              <!-- Core -->
              <div class="w-16 h-16 rounded-full flex items-center justify-center relative z-10 border" :class="coreClasses">
                <component :is="!success ? XCircle : isCheckIn ? LogIn : LogOut" class="w-7 h-7" :class="{
                  'text-emerald-500 dark:text-emerald-400': success && isCheckIn,
                  'text-zinc-500 dark:text-zinc-400': success && !isCheckIn,
                  'text-red-500 dark:text-red-400': !success,
                }" />
              </div>
            </div>

            <!-- Type badge -->
            <div
              class="flex items-center gap-2 px-3 py-1 rounded-full border text-xs font-mono uppercase tracking-widest"
              :class="typeBadgeClasses">
              <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="{
                'bg-emerald-500 dark:bg-emerald-400': success && isCheckIn,
                'bg-zinc-500 dark:bg-zinc-400': success && !isCheckIn,
                'bg-red-500 dark:bg-red-400': !success,
              }">
              </span>
              <span v-if="!success">Gagal</span>
              <span v-else-if="isCheckIn">Check In</span>
              <span v-else>Check Out</span>
            </div>

            <!-- Headline -->
            <div class="text-center relative z-10">
              <!-- SUKSES CHECK IN -->
              <template v-if="success && isCheckIn">
                <h2 class="text-3xl font-bold leading-tight text-zinc-900 dark:text-zinc-100">
                  Absensi <span class="text-emerald-600 dark:text-emerald-400">Berhasil</span><br />Dicatat
                </h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-600 font-mono mt-2 tracking-widest uppercase">— Selamat Bekerja —</p>
              </template>

              <!-- SUKSES CHECK OUT -->
              <template v-else-if="success && !isCheckIn">
                <h2 class="text-3xl font-bold leading-tight text-zinc-800 dark:text-zinc-300">
                  Sampai <span class="text-zinc-600 dark:text-zinc-400">Jumpa</span><br />Lagi
                </h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-600 font-mono mt-2 tracking-widest uppercase">— Terima Kasih —</p>
              </template>

              <!-- GAGAL -->
              <template v-else>
                <h2 class="text-3xl font-bold leading-tight text-zinc-900 dark:text-zinc-100">
                  Absensi <span class="text-red-600 dark:text-red-400">Gagal</span><br />Dicatat
                </h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-600 font-mono mt-2 tracking-widest uppercase">— Harap Perhatikan Pesan —</p>
              </template>
            </div>

            <!-- Status indicator -->
            <div class="w-full bg-gray-50 border rounded-xl px-4 py-3 flex items-center gap-3 dark:bg-zinc-800/60"
              :class="statusIndicatorBorder">
              <component :is="success ? CheckCircle : AlertCircle" class="w-4 h-4 flex-shrink-0"
                :class="success ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'" />
              <div class="flex-1 min-w-0">
                <p class="text-xs text-zinc-500 dark:text-zinc-500">Status</p>
                <p class="text-sm font-semibold truncate" :class="success ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'">
                  {{ success ? 'QR Code valid & terverifikasi' : 'Absensi tidak dapat diproses' }}
                </p>
              </div>
              <div class="text-right flex-shrink-0">
                <p class="text-xs text-zinc-400 dark:text-zinc-600 font-mono">{{ currentDate }}</p>
              </div>
            </div>

            <!-- Barcode dekoratif -->
            <div class="flex items-end gap-[2px] h-8 w-full justify-center opacity-25">
              <div v-for="(w, i) in bars" :key="i" class="rounded-sm" :class="{
                'bg-emerald-500 dark:bg-emerald-400': success && isCheckIn,
                'bg-zinc-400 dark:bg-zinc-500': success && !isCheckIn,
                'bg-red-500 dark:bg-red-400': !success,
              }" :style="`width: ${w}px; height: ${barHeights[i]}%`">
              </div>
            </div>
          </div>

          <!-- Mini info cards -->
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3 flex items-center gap-3 dark:bg-zinc-900 dark:border-zinc-800">
              <Clock class="w-4 h-4 text-zinc-400 dark:text-zinc-500 flex-shrink-0" />
              <div>
                <p class="text-xs text-zinc-500 dark:text-zinc-600">Waktu</p>
                <p class="text-sm font-mono text-zinc-700 dark:text-zinc-300">{{ currentTimeShort }}</p>
              </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3 flex items-center gap-3 dark:bg-zinc-900 dark:border-zinc-800">
              <CalendarDays class="w-4 h-4 text-zinc-400 dark:text-zinc-500 flex-shrink-0" />
              <div>
                <p class="text-xs text-zinc-500 dark:text-zinc-600">Tanggal</p>
                <p class="text-sm font-mono text-zinc-700 dark:text-zinc-300">{{ currentDate }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ── KANAN: PESAN & DETAIL ── -->
        <div class="flex flex-col gap-5">

          <!-- Title -->
          <div>
            <h2 class="text-2xl font-bold" :class="success ? 'text-zinc-900 dark:text-zinc-100' : 'text-red-600 dark:text-red-400'">
              {{ success ? (isCheckIn ? 'Check In Berhasil' : 'Check Out Berhasil') : 'Absensi Tidak Berhasil' }}
            </h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-500 mt-1">
              {{ success ? (isCheckIn ? 'Kehadiran Anda telah tercatat dalam sistem absensi digital' : 'Waktu keluar Anda telah tercatat. Istirahat yang baik!') : 'Terdapat kendala dalam memproses absensi Anda. Baca pesan di bawah.' }}
            </p>
          </div>

          <!-- Pesan card — lebih menonjol saat gagal -->
          <div v-if="props.message" class="border rounded-2xl p-6 flex flex-col gap-4" :class="messageCardClasses">
            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-full flex items-center justify-center border" :class="{
                'bg-emerald-50 border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/30': success,
                'bg-red-50 border-red-200 dark:bg-red-500/20 dark:border-red-500/40': !success,
              }">
                <component :is="success ? MessageSquare : AlertCircle" class="w-3 h-3"
                  :class="success ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'" />
              </div>
              <p class="text-xs uppercase tracking-widest font-mono"
                :class="success ? 'text-zinc-500 dark:text-zinc-500' : 'text-red-600 dark:text-red-400'">
                {{ success ? 'Pesan Sistem' : '⚠ Perhatian' }}
              </p>
            </div>

            <p class="leading-relaxed font-medium" :class="{
              'text-zinc-700 dark:text-zinc-300 text-sm': success,
              'text-red-700 dark:text-red-300 text-base': !success,
            }">
              {{ props.message }}
            </p>

            <div class="h-px w-full" :style="messageDividerStyle"></div>
          </div>

          <!-- Ringkasan -->
          <div class="bg-white border border-gray-200 rounded-2xl p-5 space-y-4 dark:bg-zinc-900 dark:border-zinc-800">
            <p class="text-xs text-zinc-500 uppercase tracking-widest font-mono">Ringkasan Absensi</p>
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-zinc-800/60">
                <span class="text-xs text-zinc-500 dark:text-zinc-600">Tipe</span>
                <span class="text-xs font-semibold font-mono px-2 py-0.5 rounded-md" :class="{
                  'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400': success && isCheckIn,
                  'bg-gray-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400': success && !isCheckIn,
                  'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400': !success,
                }">
                  {{ !success ? 'GAGAL' : isCheckIn ? 'CHECK IN' : 'CHECK OUT' }}
                </span>
              </div>
              <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-zinc-800/60">
                <span class="text-xs text-zinc-500 dark:text-zinc-600">Waktu Scan</span>
                <span class="text-xs font-mono text-zinc-600 dark:text-zinc-400">{{ currentTimeLong }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-xs text-zinc-500 dark:text-zinc-600">Status</span>
                <span class="text-xs font-mono flex items-center gap-1.5"
                  :class="success ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                  <span class="w-1.5 h-1.5 rounded-full" :class="success ? 'bg-emerald-500 dark:bg-emerald-400' : 'bg-red-500 dark:bg-red-400'"></span>
                  {{ success ? 'Terverifikasi' : 'Tidak Terproses' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Langkah selanjutnya -->
          <div class="bg-white border border-gray-200 rounded-2xl p-5 space-y-4 dark:bg-zinc-900 dark:border-zinc-800">
            <p class="text-xs text-zinc-500 uppercase tracking-widest font-mono">Langkah Selanjutnya</p>
            <div class="space-y-3">
              <div v-for="(step, i) in nextSteps" :key="i" class="flex items-start gap-3">
                <div
                  class="w-5 h-5 rounded-full bg-gray-100 border border-gray-300 flex items-center justify-center text-xs text-zinc-500 flex-shrink-0 mt-0.5 dark:bg-zinc-800 dark:border-zinc-700">
                  {{ i + 1 }}
                </div>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ step }}</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>

    <main v-else>
      <div class="relative z-10 min-h-screen flex items-center justify-center">

        <div class="bg-gray-50 px-8 py-10 rounded-2xl shadow-lg text-center w-full max-w-sm dark:bg-zinc-900">

          <!-- Spinner -->
          <div class="flex justify-center mb-6">
            <div class="w-10 h-10 border-4 border-gray-200 border-t-zinc-800 rounded-full animate-spin dark:border-zinc-700 dark:border-t-white"></div>
          </div>

          <!-- Title -->
          <h1 class="text-lg font-semibold mb-2">
            Mengambil Lokasi
          </h1>

          <!-- Subtitle -->
          <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
            Mohon tunggu, kami sedang mendeteksi lokasi Anda...
          </p>
        </div>

      </div>
    </main>

    <!-- ── FOOTER BUTTON ── -->
    <Link :href="route('dashboard')" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-20 md:left-auto md:translate-x-0 md:right-6">
      <Button class="shadow-lg md:w-auto w-[calc(100vw-3rem)]">
        Kembali ke Dashboard
      </Button>
    </Link>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { QrCode, LogIn, LogOut, Clock, CheckCircle, MessageSquare, CalendarDays, XCircle, AlertCircle} from 'lucide-vue-next';
import Button from '@/components/ui/button/Button.vue';

interface Props {
  token?: string;
  message?: string;
  type?: 'check_in' | 'check_out';
  success?: boolean;
}

const props = defineProps<Props>();

const form = useForm({
  latitude: null as number | null,
  longitude: null as number | null,
})

const isCheckIn = computed(() => (props.type ?? 'check_in') === 'check_in');
const success = computed(() => props.success ?? false);

// Deteksi mode gelap
const isDark = ref(false);
let observer: MutationObserver | null = null;

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark');
  observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark');
  });
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
  if (observer) observer.disconnect();
});

// Glow blob background
const glowBlobStyle = computed(() => {
  if (props.message === null) {
    return
  }
  if (success.value) {
    return isDark.value
      ? 'background: radial-gradient(ellipse, rgba(16,185,129,0.06) 0%, transparent 70%)'
      : 'background: radial-gradient(ellipse, rgba(16,185,129,0.1) 0%, transparent 70%)';
  }
  return isDark.value
    ? 'background: radial-gradient(ellipse, rgba(239,68,68,0.06) 0%, transparent 70%)'
    : 'background: radial-gradient(ellipse, rgba(239,68,68,0.08) 0%, transparent 70%)';
});

// Card main classes
const statusCardClasses = computed(() => ({
  'border-emerald-300 dark:border-emerald-500/30': success.value && isCheckIn.value,
  'border-gray-200 dark:border-zinc-700': success.value && !isCheckIn.value,
  'border-red-300 dark:border-red-500/30': !success.value,
  'bg-white dark:bg-zinc-900': true,
}));

// Glow di dalam card
const cardGlowStyle = computed(() => {
  if (!success.value) {
    return isDark.value
      ? 'background: radial-gradient(ellipse at 50% 0%, rgba(239,68,68,0.08) 0%, transparent 65%)'
      : 'background: radial-gradient(ellipse at 50% 0%, rgba(239,68,68,0.05) 0%, transparent 65%)';
  }
  if (isCheckIn.value) {
    return isDark.value
      ? 'background: radial-gradient(ellipse at 50% 0%, rgba(16,185,129,0.08) 0%, transparent 65%)'
      : 'background: radial-gradient(ellipse at 50% 0%, rgba(16,185,129,0.06) 0%, transparent 65%)';
  }
  return isDark.value
    ? 'background: radial-gradient(ellipse at 50% 0%, rgba(113,113,122,0.05) 0%, transparent 65%)'
    : 'background: radial-gradient(ellipse at 50% 0%, rgba(113,113,122,0.04) 0%, transparent 65%)';
});

// Orb core classes
const coreClasses = computed(() => ({
  'bg-emerald-50 border-emerald-300 shadow-emerald-glow dark:bg-emerald-500/15 dark:border-emerald-500/50 dark:shadow-emerald-glow': success.value && isCheckIn.value,
  'bg-gray-100 border-gray-300 dark:bg-zinc-800 dark:border-zinc-600': success.value && !isCheckIn.value,
  'bg-red-50 border-red-300 shadow-red-glow dark:bg-red-500/10 dark:border-red-500/40 dark:shadow-red-glow': !success.value,
}));

// Type badge
const typeBadgeClasses = computed(() => ({
  'bg-emerald-50 border-emerald-300 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-400': success.value && isCheckIn.value,
  'bg-gray-100 border-gray-300 text-zinc-600 dark:bg-zinc-800/60 dark:border-zinc-700 dark:text-zinc-400': success.value && !isCheckIn.value,
  'bg-red-50 border-red-300 text-red-700 dark:bg-red-500/10 dark:border-red-500/30 dark:text-red-400': !success.value,
}));

// Status indicator border
const statusIndicatorBorder = computed(() => ({
  'border-emerald-200 dark:border-emerald-500/20': success.value,
  'border-red-200 dark:border-red-500/20': !success.value,
}));

// Message card
const messageCardClasses = computed(() => ({
  'bg-white border-emerald-200 dark:bg-zinc-900 dark:border-emerald-500/20': success.value,
  'bg-red-50 border-red-300 dark:bg-red-500/5 dark:border-red-500/40': !success.value,
}));

const messageDividerStyle = computed(() => {
  if (success.value) {
    return isDark.value
      ? 'background: linear-gradient(90deg, rgba(16,185,129,0.4), transparent)'
      : 'background: linear-gradient(90deg, rgba(16,185,129,0.3), transparent)';
  }
  return isDark.value
    ? 'background: linear-gradient(90deg, rgba(239,68,68,0.5), transparent)'
    : 'background: linear-gradient(90deg, rgba(239,68,68,0.3), transparent)';
});

const getLocation = (): Promise<{ latitude: number; longitude: number }> => {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject('Geolocation tidak didukung browser ini')
            return
        }
        navigator.geolocation.getCurrentPosition(
            pos => resolve({
                latitude:  pos.coords.latitude,
                longitude: pos.coords.longitude,
            }),
            err => reject(err.message),
            { enableHighAccuracy: true, timeout: 10000 }
        )
    })
}

// Langkah selanjutnya
const nextSteps = computed(() => {
  if (!success.value) return [
    'Baca pesan di atas untuk mengetahui penyebab kegagalan',
    'Hubungi admin jika masalah terus berlanjut',
    'Coba scan ulang QR Code yang masih aktif',
  ]
  if (isCheckIn.value) return [
    'Absensi Anda sudah tersimpan otomatis dalam sistem',
    'Pastikan Anda bekerja sesuai jadwal yang telah ditentukan',
    'Lakukan Check Out saat jam kerja selesai',
  ]
  return [
    'Absensi keluar Anda sudah tercatat dalam sistem',
    'Durasi kerja hari ini telah dihitung secara otomatis',
    'Sampai jumpa besok, istirahat yang baik!',
  ]
})

// Barcode dekorasi
const bars = Array.from({ length: 42 }, () => [1, 2, 3, 4][Math.floor(Math.random() * 4)]);
const barHeights = Array.from({ length: 42 }, () => {
  const pool = [40, 55, 65, 80, 100];
  return pool[Math.floor(Math.random() * pool.length)];
});

// Clock
const now = ref(new Date());
let timer: ReturnType<typeof setInterval>;

const currentTime = computed(() =>
  now.value.toLocaleString('id-ID', {
    day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
);

const currentTimeShort = computed(() =>
  now.value.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
);

const currentTimeLong = computed(() =>
  now.value.toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  })
);

const currentDate = computed(() =>
  now.value.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
);

onMounted(async () => {
  try {
        const coords = await getLocation()
        form.latitude  = coords.latitude
        form.longitude = coords.longitude
    } catch (e) {
        form.latitude  = null
        form.longitude = null
    }

    form.post(route('qrcode.scanned', { token: props.token }), {
        onSuccess: () => form.reset(),
        onError:   (errors) => console.error(errors),
    })
  
  timer = setInterval(() => { now.value = new Date(); }, 1000);
});

onUnmounted(() => clearInterval(timer));
</script>

<style scoped>
@keyframes ping-slow {
  0% {
    transform: scale(1);
    opacity: 0.6;
  }
  70% {
    transform: scale(1.4);
    opacity: 0;
  }
  100% {
    transform: scale(1.4);
    opacity: 0;
  }
}

.animate-ping-slow {
  animation: ping-slow 2.5s cubic-bezier(0, 0, 0.2, 1) infinite;
}

/* Shadow untuk light mode (subtle) */
.shadow-emerald-glow {
  box-shadow: 0 0 10px rgba(16, 185, 129, 0.15);
}
.shadow-red-glow {
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.15);
}

/* Shadow untuk dark mode (lebih kuat) */
.dark .shadow-emerald-glow {
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.2), 0 0 40px rgba(16, 185, 129, 0.08);
}
.dark .shadow-red-glow {
  box-shadow: 0 0 20px rgba(239, 68, 68, 0.2), 0 0 40px rgba(239, 68, 68, 0.08);
}
</style>