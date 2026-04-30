<template>
  <Head :title="success ? (isCheckIn ? 'Check In Berhasil' : 'Check Out Berhasil') : 'Absensi Gagal'" />

  <div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans overflow-x-hidden relative flex items-center justify-center">

    <!-- Subtle grid background -->
    <div class="fixed inset-0 z-0 pointer-events-none"
      style="background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 40px 40px;">
    </div>

    <!-- Glow blob — hijau kalau sukses, merah kalau gagal -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] z-0 pointer-events-none"
      :style="success
        ? 'background: radial-gradient(ellipse, rgba(16,185,129,0.06) 0%, transparent 70%)'
        : 'background: radial-gradient(ellipse, rgba(239,68,68,0.06) 0%, transparent 70%)'">
    </div>

    <!-- ── HEADER ── -->
    <header class="fixed top-0 left-0 right-0 z-20 border-b border-zinc-800 bg-zinc-950/80 backdrop-blur">
      <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-emerald-500 rounded-md flex items-center justify-center">
            <QrCode class="w-4 h-4 text-black" />
          </div>
          <div>
            <p class="text-xs text-zinc-500 uppercase tracking-widest">Admin Panel</p>
            <h1 class="text-sm font-bold text-zinc-100 leading-tight">Absensi Digital</h1>
          </div>
        </div>
        <p class="text-xs text-zinc-600 font-mono">{{ currentTime }}</p>
      </div>
    </header>

    <!-- ── MAIN CONTENT ── -->
    <main class="relative z-10 w-full max-w-5xl mx-auto px-6 py-10 pt-28 pb-24">

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">

        <!-- ── KIRI: STATUS VISUAL ── -->
        <div class="flex flex-col gap-5">

          <!-- Status Card Utama -->
          <div class="bg-zinc-900 border rounded-2xl p-8 flex flex-col items-center gap-6 relative overflow-hidden"
            :class="{
              'border-emerald-500/30': success && isCheckIn,
              'border-zinc-700':       success && !isCheckIn,
              'border-red-500/30':     !success,
            }">

            <!-- Glow bg di dalam card -->
            <div class="absolute inset-0 pointer-events-none rounded-2xl"
              :style="!success
                ? 'background: radial-gradient(ellipse at 50% 0%, rgba(239,68,68,0.08) 0%, transparent 65%)'
                : isCheckIn
                  ? 'background: radial-gradient(ellipse at 50% 0%, rgba(16,185,129,0.08) 0%, transparent 65%)'
                  : 'background: radial-gradient(ellipse at 50% 0%, rgba(113,113,122,0.05) 0%, transparent 65%)'">
            </div>

            <!-- Orb icon -->
            <div class="relative flex items-center justify-center">
              <!-- Outer ring pulse -->
              <div class="absolute w-28 h-28 rounded-full border animate-ping-slow"
                :class="{
                  'border-emerald-500/20': success && isCheckIn,
                  'border-zinc-600/30':   success && !isCheckIn,
                  'border-red-500/20':    !success,
                }">
              </div>
              <!-- Mid ring -->
              <div class="absolute w-20 h-20 rounded-full border"
                :class="{
                  'border-emerald-500/40': success && isCheckIn,
                  'border-zinc-600/50':   success && !isCheckIn,
                  'border-red-500/40':    !success,
                }">
              </div>
              <!-- Core -->
              <div class="w-16 h-16 rounded-full flex items-center justify-center relative z-10 border"
                :class="{
                  'bg-emerald-500/15 border-emerald-500/50 shadow-emerald-glow': success && isCheckIn,
                  'bg-zinc-800 border-zinc-600':                                 success && !isCheckIn,
                  'bg-red-500/10 border-red-500/40 shadow-red-glow':            !success,
                }">
                <component
                  :is="!success ? XCircle : isCheckIn ? LogIn : LogOut"
                  class="w-7 h-7"
                  :class="{
                    'text-emerald-400': success && isCheckIn,
                    'text-zinc-400':   success && !isCheckIn,
                    'text-red-400':    !success,
                  }" />
              </div>
            </div>

            <!-- Type badge -->
            <div class="flex items-center gap-2 px-3 py-1 rounded-full border text-xs font-mono uppercase tracking-widest"
              :class="{
                'bg-emerald-500/10 border-emerald-500/30 text-emerald-400': success && isCheckIn,
                'bg-zinc-800/60 border-zinc-700 text-zinc-400':             success && !isCheckIn,
                'bg-red-500/10 border-red-500/30 text-red-400':            !success,
              }">
              <span class="w-1.5 h-1.5 rounded-full animate-pulse"
                :class="{
                  'bg-emerald-400': success && isCheckIn,
                  'bg-zinc-400':   success && !isCheckIn,
                  'bg-red-400':    !success,
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
                <h2 class="text-3xl font-bold leading-tight text-zinc-100">
                  Absensi <span class="text-emerald-400">Berhasil</span><br />Dicatat
                </h2>
                <p class="text-xs text-zinc-600 font-mono mt-2 tracking-widest uppercase">— Selamat Bekerja —</p>
              </template>

              <!-- SUKSES CHECK OUT -->
              <template v-else-if="success && !isCheckIn">
                <h2 class="text-3xl font-bold leading-tight text-zinc-300">
                  Sampai <span class="text-zinc-400">Jumpa</span><br />Lagi
                </h2>
                <p class="text-xs text-zinc-600 font-mono mt-2 tracking-widest uppercase">— Terima Kasih —</p>
              </template>

              <!-- GAGAL -->
              <template v-else>
                <h2 class="text-3xl font-bold leading-tight text-zinc-100">
                  Absensi <span class="text-red-400">Gagal</span><br />Dicatat
                </h2>
                <p class="text-xs text-zinc-600 font-mono mt-2 tracking-widest uppercase">— Harap Perhatikan Pesan —</p>
              </template>
            </div>

            <!-- Status indicator -->
            <div class="w-full bg-zinc-800/60 border rounded-xl px-4 py-3 flex items-center gap-3"
              :class="{
                'border-emerald-500/20': success,
                'border-red-500/20':    !success,
              }">
              <component :is="success ? CheckCircle : AlertCircle"
                class="w-4 h-4 flex-shrink-0"
                :class="success ? 'text-emerald-400' : 'text-red-400'" />
              <div class="flex-1 min-w-0">
                <p class="text-xs text-zinc-500">Status</p>
                <p class="text-sm font-semibold truncate"
                  :class="success ? 'text-emerald-400' : 'text-red-400'">
                  {{ success ? 'QR Code valid & terverifikasi' : 'Absensi tidak dapat diproses' }}
                </p>
              </div>
              <div class="text-right flex-shrink-0">
                <p class="text-xs text-zinc-600 font-mono">{{ currentDate }}</p>
              </div>
            </div>

            <!-- Barcode dekoratif -->
            <div class="flex items-end gap-[2px] h-8 w-full justify-center opacity-25">
              <div v-for="(w, i) in bars" :key="i"
                class="rounded-sm"
                :class="{
                  'bg-emerald-400': success && isCheckIn,
                  'bg-zinc-500':   success && !isCheckIn,
                  'bg-red-400':    !success,
                }"
                :style="`width: ${w}px; height: ${barHeights[i]}%`">
              </div>
            </div>
          </div>

          <!-- Mini info cards -->
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-3 flex items-center gap-3">
              <Clock class="w-4 h-4 text-zinc-500 flex-shrink-0" />
              <div>
                <p class="text-xs text-zinc-600">Waktu</p>
                <p class="text-sm font-mono text-zinc-300">{{ currentTimeShort }}</p>
              </div>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-3 flex items-center gap-3">
              <CalendarDays class="w-4 h-4 text-zinc-500 flex-shrink-0" />
              <div>
                <p class="text-xs text-zinc-600">Tanggal</p>
                <p class="text-sm font-mono text-zinc-300">{{ currentDate }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ── KANAN: PESAN & DETAIL ── -->
        <div class="flex flex-col gap-5">

          <!-- Title -->
          <div>
            <h2 class="text-2xl font-bold"
              :class="success ? 'text-zinc-100' : 'text-red-400'">
              {{ success
                ? (isCheckIn ? 'Check In Berhasil' : 'Check Out Berhasil')
                : 'Absensi Tidak Berhasil' }}
            </h2>
            <p class="text-sm text-zinc-500 mt-1">
              {{ success
                ? (isCheckIn
                    ? 'Kehadiran Anda telah tercatat dalam sistem absensi digital'
                    : 'Waktu keluar Anda telah tercatat. Istirahat yang baik!')
                : 'Terdapat kendala dalam memproses absensi Anda. Baca pesan di bawah.' }}
            </p>
          </div>

          <!-- Pesan card — lebih menonjol saat gagal -->
          <div v-if="props.message"
            class="border rounded-2xl p-6 flex flex-col gap-4"
            :class="{
              'bg-zinc-900 border-emerald-500/20': success,
              'bg-red-500/5 border-red-500/40':   !success,
            }">

            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-full flex items-center justify-center border"
                :class="{
                  'bg-emerald-500/10 border-emerald-500/30': success,
                  'bg-red-500/20 border-red-500/40':         !success,
                }">
                <component :is="success ? MessageSquare : AlertCircle"
                  class="w-3 h-3"
                  :class="success ? 'text-emerald-400' : 'text-red-400'" />
              </div>
              <p class="text-xs uppercase tracking-widest font-mono"
                :class="success ? 'text-zinc-500' : 'text-red-400'">
                {{ success ? 'Pesan Sistem' : '⚠ Perhatian' }}
              </p>
            </div>

            <p class="leading-relaxed font-medium"
              :class="{
                'text-zinc-300 text-sm': success,
                'text-red-300 text-base': !success,
              }">
              {{ props.message }}
            </p>

            <div class="h-px w-full"
              :style="success
                ? 'background: linear-gradient(90deg, rgba(16,185,129,0.4), transparent)'
                : 'background: linear-gradient(90deg, rgba(239,68,68,0.5), transparent)'">
            </div>
          </div>

          <!-- Ringkasan -->
          <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 space-y-4">
            <p class="text-xs text-zinc-500 uppercase tracking-widest font-mono">Ringkasan Absensi</p>
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2 border-b border-zinc-800/60">
                <span class="text-xs text-zinc-600">Tipe</span>
                <span class="text-xs font-semibold font-mono px-2 py-0.5 rounded-md"
                  :class="{
                    'bg-emerald-500/10 text-emerald-400': success && isCheckIn,
                    'bg-zinc-800 text-zinc-400':          success && !isCheckIn,
                    'bg-red-500/10 text-red-400':         !success,
                  }">
                  {{ !success ? 'GAGAL' : isCheckIn ? 'CHECK IN' : 'CHECK OUT' }}
                </span>
              </div>
              <div class="flex items-center justify-between py-2 border-b border-zinc-800/60">
                <span class="text-xs text-zinc-600">Waktu Scan</span>
                <span class="text-xs font-mono text-zinc-400">{{ currentTimeLong }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-xs text-zinc-600">Status</span>
                <span class="text-xs font-mono flex items-center gap-1.5"
                  :class="success ? 'text-emerald-400' : 'text-red-400'">
                  <span class="w-1.5 h-1.5 rounded-full"
                    :class="success ? 'bg-emerald-400' : 'bg-red-400'"></span>
                  {{ success ? 'Terverifikasi' : 'Tidak Terproses' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Langkah selanjutnya -->
          <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 space-y-4">
            <p class="text-xs text-zinc-500 uppercase tracking-widest font-mono">Langkah Selanjutnya</p>
            <div class="space-y-3">
              <div v-for="(step, i) in nextSteps" :key="i" class="flex items-start gap-3">
                <div class="w-5 h-5 rounded-full bg-zinc-800 border border-zinc-700 flex items-center justify-center text-xs text-zinc-500 flex-shrink-0 mt-0.5">
                  {{ i + 1 }}
                </div>
                <p class="text-xs text-zinc-400 leading-relaxed">{{ step }}</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>

    <!-- ── FOOTER ── -->
    <footer class="fixed bottom-0 left-0 right-0 z-20 border-t border-zinc-800 bg-zinc-950/80 backdrop-blur">
      <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <p class="text-xs text-zinc-700 font-mono">Absensi Digital · Admin Panel</p>
        <p class="text-xs text-zinc-700 font-mono">{{ currentTimeLong }}</p>
      </div>
    </footer>

  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { QrCode, LogIn, LogOut, Clock, CheckCircle, MessageSquare, CalendarDays, XCircle, AlertCircle } from 'lucide-vue-next';

interface Props {
  message?: string;
  type?: 'check_in' | 'check_out';
  success?: boolean;
}

const props = defineProps<Props>();

const isCheckIn = computed(() => (props.type ?? 'check_in') === 'check_in');
const success = computed(() => props.success ?? false);

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

onMounted(() => {
  timer = setInterval(() => { now.value = new Date(); }, 1000);
});

onUnmounted(() => clearInterval(timer));
</script>

<style scoped>
@keyframes ping-slow {
  0%   { transform: scale(1); opacity: 0.6; }
  70%  { transform: scale(1.4); opacity: 0; }
  100% { transform: scale(1.4); opacity: 0; }
}

.animate-ping-slow {
  animation: ping-slow 2.5s cubic-bezier(0, 0, 0.2, 1) infinite;
}

.shadow-emerald-glow {
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.2), 0 0 40px rgba(16, 185, 129, 0.08);
}

.shadow-red-glow {
  box-shadow: 0 0 20px rgba(239, 68, 68, 0.2), 0 0 40px rgba(239, 68, 68, 0.08);
}
</style>