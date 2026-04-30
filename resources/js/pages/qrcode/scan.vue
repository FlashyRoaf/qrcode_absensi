<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { QrCode, Clock, LogIn, LogOut, RefreshCw, AlertCircle } from 'lucide-vue-next';

// ─── INTERFACES (tidak diubah) ───────────────────────────────────────────────

interface Particle {
  id: number;
  size: number;
  left: number;
  top: number;
  delay: number;
  duration: number;
}

interface AttendanceData {
  sessionId: string;
  timestamp: Date;
}

interface Props {
  qrCode?: String,
  expires_at?: String,
}

// ─── BACKEND (tidak diubah) ──────────────────────────────────────────────────

const form = useForm({
  type: '',
  expires_in_minutes: 5,
});

const props = defineProps<Props>();
const particles = ref<Particle[]>([]);
const isScanning = ref(false);

const attendanceData = ref<AttendanceData>({
  sessionId: 'N25-' + Date.now().toString(36),
  timestamp: new Date(),
});

const createParticles = () => {
  const particleCount = 25;
  const newParticles = [];
  for (let i = 0; i < particleCount; i++) {
    const particle: Particle = {
      id: i,
      size: Math.random() * 4 + 2,
      left: Math.random() * 100,
      top: Math.random() * 100,
      delay: Math.random() * 6,
      duration: Math.random() * 3 + 4
    };
    newParticles.push(particle);
  }
  particles.value = newParticles;
};

const startScanning = () => {
  isScanning.value = true;
  setTimeout(() => {
    isScanning.value = false;
  }, 3000);
};

const formatTime = (date: Date): string => {
  return date.toLocaleString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const submit = () => {
  form.post(route('qrcode.create'), {
    onSuccess: () => {
      form.reset();
      console.log('QR Code created successfully');
    },
    onError: (errors) => {
      console.error('Error creating QR Code:', errors);
    }
  });
}

// ─── COUNTDOWN & AUTO-REFRESH (tambahan UI) ──────────────────────────────────

const remainingSeconds = ref(0)
const isExpired = ref(false)
let countdownInterval: ReturnType<typeof setInterval> | null = null

const remainingMinutes = computed(() => Math.floor(remainingSeconds.value / 60))
const remainingSecondsOnly = computed(() => remainingSeconds.value % 60)

const countdownDisplay = computed(() => {
  const m = String(remainingMinutes.value).padStart(2, '0')
  const s = String(remainingSecondsOnly.value).padStart(2, '0')
  return `${m}:${s}`
})

const urgencyLevel = computed(() => {
  if (remainingSeconds.value <= 30) return 'critical'
  if (remainingSeconds.value <= 60) return 'warning'
  return 'normal'
})

const startCountdown = () => {
  if (!props.expires_at) return

  if (countdownInterval) clearInterval(countdownInterval)

  const tick = () => {
    const now = new Date().getTime()
    const expire = new Date(props.expires_at as string).getTime()
    const diff = Math.max(0, Math.floor((expire - now) / 1000))
    remainingSeconds.value = diff

    if (diff <= 0) {
      isExpired.value = true
      if (countdownInterval) clearInterval(countdownInterval)
      // Auto-refresh halaman saat expired
      setTimeout(() => {
        window.location.reload()
      }, 1500)
    }
  }

  tick()
  countdownInterval = setInterval(tick, 1000)
}

watch(() => props.expires_at, (newVal) => {
  if (newVal) {
    isExpired.value = false
    startCountdown()
  }
})

onMounted(() => {
  createParticles();

  const scanInterval = setInterval(startScanning, 10000);
  console.log(props.expires_at)

  if (props.expires_at) {
    startCountdown()
  }

  onUnmounted(() => {
    clearInterval(scanInterval);
    if (countdownInterval) clearInterval(countdownInterval)
  });
});
</script>

<template>
  <Head title="Absensi QR Code" />

  <div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans overflow-x-hidden relative">

    <!-- Subtle grid background -->
    <div class="fixed inset-0 z-0 pointer-events-none"
      style="background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 40px 40px;">
    </div>

    <!-- Glow blob -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] z-0 pointer-events-none"
      style="background: radial-gradient(ellipse, rgba(16,185,129,0.06) 0%, transparent 70%);">
    </div>

    <!-- ── HEADER ── -->
    <header class="relative z-10 border-b border-zinc-800 bg-zinc-950/80 backdrop-blur">
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
        <p class="text-xs text-zinc-600 font-mono">{{ formatTime(attendanceData.timestamp) }}</p>
      </div>
    </header>

    <!-- ── MAIN ── -->
    <main class="relative z-10 max-w-5xl mx-auto px-6 py-10">

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

        <!-- ── KIRI: QR CODE ── -->
        <div class="flex flex-col gap-5">

          <!-- QR Frame -->
          <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 flex flex-col items-center gap-5">
            <div class="flex items-center justify-between w-full">
              <p class="text-xs text-zinc-500 uppercase tracking-widest font-mono">QR Code</p>
              <span
                v-if="qrCode"
                class="text-xs px-2 py-0.5 rounded-full font-mono"
                :class="{
                  'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': urgencyLevel === 'normal',
                  'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20': urgencyLevel === 'warning',
                  'bg-red-500/10 text-red-400 border border-red-500/20': urgencyLevel === 'critical',
                }"
              >
                {{ isExpired ? 'Expired' : 'Aktif' }}
              </span>
            </div>

            <!-- QR Image area -->
            <div class="relative">
              <!-- QR Kosong (placeholder) -->
              <div
                v-if="!qrCode"
                class="w-52 h-52 border-2 border-dashed border-zinc-700 rounded-xl flex flex-col items-center justify-center gap-3 text-zinc-600"
              >
                <QrCode class="w-12 h-12" />
                <p class="text-xs text-center px-4">Pilih tipe & buat QR Code</p>
              </div>

              <!-- QR Ada -->
              <div v-else class="relative">
                <!-- Overlay expired -->
                <div
                  v-if="isExpired"
                  class="absolute inset-0 z-10 bg-zinc-950/80 backdrop-blur-sm rounded-xl flex flex-col items-center justify-center gap-2"
                >
                  <AlertCircle class="w-8 h-8 text-red-400" />
                  <p class="text-sm text-red-400 font-semibold">QR Code Expired</p>
                  <p class="text-xs text-zinc-500">Halaman akan direfresh...</p>
                  <RefreshCw class="w-4 h-4 text-zinc-500 animate-spin" />
                </div>

                <!-- QR SVG -->
                <div
                  class="bg-white rounded-xl p-3"
                  :class="{ 'opacity-40': isExpired }"
                  v-html="qrCode"
                ></div>

                <!-- Scan line animation -->
                <div
                  class="scan-line absolute top-3 left-3 right-3 h-0.5 rounded-full z-10"
                  :class="{ 'scanning': isScanning && !isExpired }"
                ></div>
              </div>
            </div>

            <!-- Info QR -->
            <div v-if="qrCode" class="w-full space-y-2">
              <!-- <div class="flex items-center justify-between text-xs font-mono">
                <span class="text-zinc-600">Session ID</span>
                <span v-if="qrCode" class="text-zinc-400">{{ attendanceData.sessionId }}</span>
              </div> -->

              <!-- Countdown -->
              <div class="bg-zinc-800/60 border rounded-xl px-4 py-3 flex items-center gap-3 transition-all"
                :class="{
                  'border-zinc-700': urgencyLevel === 'normal',
                  'border-yellow-500/30': urgencyLevel === 'warning',
                  'border-red-500/30': urgencyLevel === 'critical',
                }"
              >
                <Clock class="w-4 h-4 flex-shrink-0"
                  :class="{
                    'text-emerald-400': urgencyLevel === 'normal',
                    'text-yellow-400': urgencyLevel === 'warning',
                    'text-red-400': urgencyLevel === 'critical',
                  }"
                />
                <div class="flex-1">
                  <p class="text-xs text-zinc-500">Berlaku selama</p>
                  <p class="font-mono font-bold text-lg leading-tight"
                    :class="{
                      'text-zinc-200': urgencyLevel === 'normal',
                      'text-yellow-400': urgencyLevel === 'warning',
                      'text-red-400': urgencyLevel === 'critical',
                    }"
                  >
                    {{ isExpired ? '00:00' : countdownDisplay }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-zinc-600">lagi</p>
                  <p class="text-xs font-mono"
                    :class="{
                      'text-zinc-500': urgencyLevel === 'normal',
                      'text-yellow-500': urgencyLevel === 'warning',
                      'text-red-500': urgencyLevel === 'critical',
                    }"
                  >
                    {{ isExpired ? 'Expired' : urgencyLevel === 'critical' ? 'Segera expire!' : urgencyLevel === 'warning' ? 'Hampir habis' : 'Tersisa' }}
                  </p>
                </div>
              </div>

              <!-- Progress bar countdown -->
              <div class="h-1 bg-zinc-800 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-1000"
                  :class="{
                    'bg-emerald-500': urgencyLevel === 'normal',
                    'bg-yellow-500': urgencyLevel === 'warning',
                    'bg-red-500': urgencyLevel === 'critical',
                  }"
                  :style="{ width: isExpired ? '0%' : (remainingSeconds / 300 * 100) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── KANAN: FORM ── -->
        <div class="flex flex-col gap-5">

          <!-- Title -->
          <div>
            <h2 class="text-2xl font-bold text-zinc-100">Buat QR Code</h2>
            <p class="text-sm text-zinc-500 mt-1">Generate QR Code untuk sesi absensi karyawan</p>
          </div>

          <!-- Form Card -->
          <form @submit.prevent="submit" class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 flex flex-col gap-6">

            <!-- Step 1: Pilih Tipe -->
            <div class="space-y-3">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-xs font-bold text-emerald-400">1</div>
                <p class="text-sm font-semibold text-zinc-200">Pilih Tipe Absensi</p>
              </div>

              <!-- Toggle Check In / Check Out -->
              <div class="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  @click="form.type = 'check_in'"
                  class="flex items-center gap-3 px-4 py-3 rounded-xl border transition-all text-left"
                  :class="form.type === 'check_in'
                    ? 'bg-emerald-500/10 border-emerald-500/50 text-emerald-400'
                    : 'bg-zinc-800/50 border-zinc-700 text-zinc-400 hover:border-zinc-600'"
                >
                  <LogIn class="w-4 h-4 flex-shrink-0" />
                  <div>
                    <p class="text-sm font-semibold">Check In</p>
                    <p class="text-xs opacity-60">Masuk kerja</p>
                  </div>
                </button>

                <button
                  type="button"
                  @click="form.type = 'check_out'"
                  class="flex items-center gap-3 px-4 py-3 rounded-xl border transition-all text-left"
                  :class="form.type === 'check_out'
                    ? 'bg-emerald-500/10 border-emerald-500/50 text-emerald-400'
                    : 'bg-zinc-800/50 border-zinc-700 text-zinc-400 hover:border-zinc-600'"
                >
                  <LogOut class="w-4 h-4 flex-shrink-0" />
                  <div>
                    <p class="text-sm font-semibold">Check Out</p>
                    <p class="text-xs opacity-60">Selesai kerja</p>
                  </div>
                </button>
              </div>

              <!-- Hidden select untuk form compatibility -->
              <select v-model="form.type" required class="sr-only" aria-hidden="true">
                <option value="check_in">Check In</option>
                <option value="check_out">Check Out</option>
              </select>
            </div>

            <!-- Divider -->
            <div class="border-t border-zinc-800"></div>

            <!-- Info -->
            <div class="bg-zinc-800/40 rounded-xl px-4 py-3 flex items-start gap-3">
              <Clock class="w-4 h-4 text-zinc-500 mt-0.5 flex-shrink-0" />
              <div>
                <p class="text-xs text-zinc-400 font-medium">Durasi QR Code</p>
                <p class="text-xs text-zinc-600 mt-0.5">QR Code akan aktif selama <span class="text-zinc-400">{{ form.expires_in_minutes }} menit</span> dan otomatis expire setelah waktu habis.</p>
              </div>
            </div>

            <!-- Submit -->
            <button
              type="submit"
              :disabled="form.processing || !form.type"
              class="w-full flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm transition-all"
              :class="form.type && !form.processing
                ? 'bg-emerald-500 hover:bg-emerald-400 text-black cursor-pointer'
                : 'bg-zinc-800 text-zinc-600 cursor-not-allowed'"
            >
              <RefreshCw v-if="form.processing" class="w-4 h-4 animate-spin" />
              <QrCode v-else class="w-4 h-4" />
              {{ form.processing ? 'Membuat...' : 'Buat QR Code' }}
            </button>
          </form>

          <!-- Cara penggunaan -->
          <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 space-y-4">
            <p class="text-xs text-zinc-500 uppercase tracking-widest font-mono">Cara Penggunaan</p>
            <div class="space-y-3">
              <div v-for="(step, i) in [
                { text: 'Pilih tipe absensi (Check In atau Check Out)' },
                { text: 'Tekan tombol Buat QR Code' },
                { text: 'Tampilkan QR Code kepada karyawan untuk di-scan' },
                { text: 'QR Code otomatis expire dalam 5 menit' },
              ]" :key="i" class="flex items-start gap-3">
                <div class="w-5 h-5 rounded-full bg-zinc-800 border border-zinc-700 flex items-center justify-center text-xs text-zinc-500 flex-shrink-0 mt-0.5">
                  {{ i + 1 }}
                </div>
                <p class="text-xs text-zinc-400 leading-relaxed">{{ step.text }}</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-zinc-800 mt-10">
      <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <p class="text-xs text-zinc-700 font-mono">Absensi Digital · Admin Panel</p>
        <p class="text-xs text-zinc-700 font-mono">{{ formatTime(new Date()) }}</p>
      </div>
    </footer>

  </div>
</template>

<style scoped>
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

.scan-line {
  background: linear-gradient(90deg, transparent, #10b981, transparent);
  opacity: 0;
}

.scan-line.scanning {
  animation: scan 2s ease-in-out;
}

@keyframes scan {
  0%   { transform: translateY(0);    opacity: 0; }
  20%  { opacity: 1; }
  80%  { opacity: 1; }
  100% { transform: translateY(200px); opacity: 0; }
}
</style>