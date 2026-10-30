<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { QrCode, Clock, LogIn, LogOut, RefreshCw, AlertCircle, ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface Props {
  qrCodeImage?: string,
  qr_token?: string,
  expires_at?: string,
}

const form = useForm({
  type: '',
  expires_in_minutes: 2,
});

const props = defineProps<Props>();

// One QR code is for one employee only. Watch the code's status so the
// operator is told as soon as it has been used, instead of showing a
// still-valid-looking code to the next employee in line.
const isUsed = ref(false)
let statusInterval: ReturnType<typeof setInterval> | null = null

const checkQrStatus = async () => {
  if (!props.qr_token || isUsed.value || isExpired.value) return
  try {
    const res = await fetch(route('qrcode.status', { token: props.qr_token }), {
      headers: { Accept: 'application/json' },
    })
    if (!res.ok) return
    const data = await res.json()
    if (data.is_used) {
      isUsed.value = true
      if (statusInterval) clearInterval(statusInterval)
      if (countdownInterval) clearInterval(countdownInterval)
    }
  } catch {
    // Status check is a convenience only; a failed check must not hide
    // or break the QR code itself.
  }
}

const startStatusPolling = () => {
  if (statusInterval) clearInterval(statusInterval)
  if (!props.qr_token) return
  statusInterval = setInterval(checkQrStatus, 4000)
}

const now = ref(new Date())
let clockInterval: ReturnType<typeof setInterval> | null = null

const formatTime = (date: Date): string => {
  return date.toLocaleString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// One-line stepper for the QR expiry: left arrow, value, right
// arrow, 30 seconds per step, clamped to 30 seconds – 10 minutes.
// The server field is still expires_in_minutes (a float), so 30
// seconds is sent as 0.5 and passes the server's min:0.1 rule.
const expirySeconds = computed(() => Math.round(form.expires_in_minutes * 60))

const formattedDuration = computed(() => {
  const seconds = expirySeconds.value
  if (seconds < 60) return `${seconds} Detik`
  if (seconds % 60 === 0) return `${seconds / 60} Menit`
  return `${Math.floor(seconds / 60)} Menit ${seconds % 60} Detik`
})

const stepDuration = (deltaSeconds: number) => {
  const seconds = Math.min(600, Math.max(30, expirySeconds.value + deltaSeconds))
  form.expires_in_minutes = seconds / 60
}

const submit = () => {
  // Keep the chosen type: after one employee has used a code, the
  // operator makes a fresh code for the next employee with one click,
  // without re-picking the type.
  isUsed.value = false
  form.post(route('qrcode.create'), {
    onSuccess: () => {
      startStatusPolling()
    },
    onError: (errors) => {
      console.error('Error creating QR Code:', errors);
    }
  });
}

const remainingSeconds = ref(0)
const isExpired = ref(false)
let countdownInterval: ReturnType<typeof setInterval> | null = null

const countdownDisplay = computed(() => {
  const m = String(Math.floor(remainingSeconds.value / 60)).padStart(2, '0')
  const s = String(remainingSeconds.value % 60).padStart(2, '0')
  return `${m}:${s}`
})

const urgencyLevel = computed(() => {
  if (remainingSeconds.value <= 15) return 'critical'
  if (remainingSeconds.value <= 35) return 'warning'
  return 'normal'
})

const startCountdown = () => {
  if (!props.expires_at) return

  if (countdownInterval) clearInterval(countdownInterval)

  const tick = () => {
    const nowMs = new Date().getTime()
    const expire = new Date(props.expires_at as string).getTime()
    const diff = Math.max(0, Math.floor((expire - nowMs) / 1000))
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
    isUsed.value = false
    startCountdown()
    startStatusPolling()
  }
})

onMounted(() => {
  clockInterval = setInterval(() => { now.value = new Date() }, 1000)

  if (props.expires_at) {
    startCountdown()
    startStatusPolling()
  }

  onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval)
    if (statusInterval) clearInterval(statusInterval)
    if (countdownInterval) clearInterval(countdownInterval)
  });
});
</script>

<template>
  <Head title="Absensi QR Code" />

  <div class="min-h-screen bg-gray-50 dark:bg-zinc-950 text-gray-800 dark:text-zinc-100 font-sans">
    <header class="border-b bg-white dark:bg-zinc-950 border-gray-200 dark:border-zinc-800">
      <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-emerald-500 rounded-md flex items-center justify-center">
            <QrCode class="w-4 h-4 text-white dark:text-black" />
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-zinc-400 uppercase tracking-widest">Panel Scan</p>
            <h1 class="text-sm font-bold text-gray-900 dark:text-zinc-100 leading-tight">Absensi Digital</h1>
          </div>
        </div>
        <p class="text-sm text-gray-600 dark:text-zinc-400 font-mono">{{ formatTime(now) }}</p>
      </div>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-10">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        <!-- QR Code -->
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 flex flex-col items-center gap-5">
          <div class="flex items-center justify-between w-full">
            <p class="text-sm text-gray-600 dark:text-zinc-400 uppercase tracking-widest font-mono">QR Code</p>
            <span
              v-if="qrCodeImage"
              role="status"
              aria-live="polite"
              class="text-xs px-2 py-0.5 rounded-full font-mono"
              :class="{
                'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20': urgencyLevel === 'normal',
                'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-500/20': urgencyLevel === 'warning',
                'bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/20': urgencyLevel === 'critical',
              }"
            >
              {{ isExpired ? 'Expired' : isUsed ? 'Sudah dipakai' : 'Aktif' }}
            </span>
          </div>

          <div class="relative">
            <div
              v-if="!qrCodeImage"
              class="w-52 h-52 border-2 border-dashed border-gray-300 dark:border-zinc-700 rounded-xl flex flex-col items-center justify-center gap-3 text-gray-400 dark:text-zinc-400"
            >
              <QrCode class="w-12 h-12" />
              <p class="text-sm text-center px-4">Pilih tipe &amp; buat QR Code</p>
            </div>

            <div v-else class="relative">
              <div
                v-if="isExpired"
                class="absolute inset-0 z-10 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-sm rounded-xl flex flex-col items-center justify-center gap-2"
              >
                <AlertCircle class="w-8 h-8 text-red-500 dark:text-red-400" />
                <p class="text-sm text-red-600 dark:text-red-400 font-semibold">QR Code Expired</p>
                <p class="text-sm text-gray-600 dark:text-zinc-400">Halaman akan direfresh...</p>
                <RefreshCw class="w-4 h-4 text-gray-500 dark:text-zinc-400 animate-spin" />
              </div>

              <div
                class="bg-white rounded-xl p-3"
                :class="{ 'opacity-40': isExpired || isUsed }"
              >
                <img :src="qrCodeImage" alt="QR Code absensi" class="w-[300px] h-[300px]" />
              </div>

              <div
                v-if="isUsed"
                class="absolute inset-0 z-10 bg-white/85 dark:bg-zinc-950/85 backdrop-blur-sm rounded-xl flex flex-col items-center justify-center gap-2 p-4 text-center"
              >
                <p class="text-sm font-semibold text-gray-900 dark:text-zinc-100">QR Code sudah dipakai</p>
                <p class="text-sm text-gray-600 dark:text-zinc-400">Satu QR Code hanya untuk satu karyawan.</p>
                <button
                  type="button"
                  @click="submit"
                  :disabled="form.processing || !form.type"
                  class="mt-2 px-5 py-2.5 rounded-xl font-semibold text-sm bg-emerald-500 hover:bg-emerald-400 text-white dark:text-black cursor-pointer disabled:opacity-50"
                >
                  {{ form.processing ? 'Membuat...' : 'Buat QR Code baru' }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="qrCodeImage" class="w-full">
            <div class="bg-gray-100 dark:bg-zinc-800/60 border border-gray-200 dark:border-zinc-700 rounded-xl px-4 py-3 flex items-center gap-3">
              <Clock class="w-4 h-4 flex-shrink-0 text-gray-500 dark:text-zinc-400" />
              <div class="flex-1">
                <p class="text-sm text-gray-600 dark:text-zinc-400">Berlaku selama</p>
                <p class="font-mono font-bold text-lg leading-tight text-gray-800 dark:text-zinc-200">
                  {{ isExpired ? '00:00' : countdownDisplay }}
                </p>
              </div>
              <p class="text-xs text-gray-500 dark:text-zinc-400">
                {{ isExpired ? 'Expired' : urgencyLevel === 'critical' ? 'Segera expire!' : urgencyLevel === 'warning' ? 'Hampir habis' : 'Tersisa' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Form -->
        <div class="flex flex-col gap-5">
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-zinc-100">Buat QR Code</h2>
            <p class="text-sm text-gray-500 dark:text-zinc-400 mt-1">Satu QR Code untuk satu karyawan, berlaku {{ formattedDuration }}.</p>
          </div>

          <form @submit.prevent="submit" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 flex flex-col gap-6">
            <div class="space-y-3">
              <p class="text-sm font-semibold text-gray-800 dark:text-zinc-200">Pilih Tipe Absensi</p>
              <div class="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  :aria-pressed="form.type === 'check_in'"
                  @click="form.type = 'check_in'"
                  class="flex items-center gap-3 px-4 py-3 rounded-xl border transition-all text-left"
                  :class="form.type === 'check_in'
                    ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-300 dark:border-emerald-500/50 text-emerald-700 dark:text-emerald-400'
                    : 'bg-gray-50 dark:bg-zinc-800/50 border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-zinc-400 hover:border-gray-300 dark:hover:border-zinc-600'"
                >
                  <LogIn class="w-4 h-4 flex-shrink-0" />
                  <div>
                    <p class="text-sm font-semibold">Check In</p>
                    <p class="text-sm opacity-80">Masuk kerja</p>
                  </div>
                </button>

                <button
                  type="button"
                  :aria-pressed="form.type === 'check_out'"
                  @click="form.type = 'check_out'"
                  class="flex items-center gap-3 px-4 py-3 rounded-xl border transition-all text-left"
                  :class="form.type === 'check_out'
                    ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-300 dark:border-emerald-500/50 text-emerald-700 dark:text-emerald-400'
                    : 'bg-gray-50 dark:bg-zinc-800/50 border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-zinc-400 hover:border-gray-300 dark:hover:border-zinc-600'"
                >
                  <LogOut class="w-4 h-4 flex-shrink-0" />
                  <div>
                    <p class="text-sm font-semibold">Check Out</p>
                    <p class="text-sm opacity-80">Selesai kerja</p>
                  </div>
                </button>
              </div>
            </div>

            <div class="space-y-3">
              <p class="text-sm font-semibold text-gray-800 dark:text-zinc-200">Masa Berlaku QR Code</p>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  aria-label="Kurangi 30 detik"
                  :disabled="expirySeconds <= 30"
                  @click="stepDuration(-30)"
                  class="w-10 h-10 flex shrink-0 items-center justify-center rounded-xl border border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800/50 text-gray-600 dark:text-zinc-400 hover:border-gray-300 dark:hover:border-zinc-600 disabled:opacity-40"
                >
                  <ChevronLeft class="w-5 h-5" />
                </button>
                <div
                  role="status"
                  aria-live="polite"
                  class="w-48 shrink-0 px-4 h-10 flex items-center justify-center rounded-xl border border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800/50 text-base font-bold whitespace-nowrap text-gray-800 dark:text-zinc-100"
                >
                  {{ formattedDuration }}
                </div>
                <button
                  type="button"
                  aria-label="Tambah 30 detik"
                  :disabled="expirySeconds >= 600"
                  @click="stepDuration(30)"
                  class="w-10 h-10 flex shrink-0 items-center justify-center rounded-xl border border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800/50 text-gray-600 dark:text-zinc-400 hover:border-gray-300 dark:hover:border-zinc-600 disabled:opacity-40"
                >
                  <ChevronRight class="w-5 h-5" />
                </button>
              </div>
              <p class="text-sm text-gray-500 dark:text-zinc-400">Rentang 30 detik – 10 menit, langkah 30 detik. Default 2 menit.</p>
            </div>

            <button
              type="submit"
              :disabled="form.processing || !form.type"
              class="w-full flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm transition-all"
              :class="form.type && !form.processing
                ? 'bg-emerald-500 hover:bg-emerald-400 text-white dark:text-black cursor-pointer'
                : 'bg-gray-300 dark:bg-zinc-800 text-gray-500 dark:text-zinc-400 cursor-not-allowed'"
            >
              <RefreshCw v-if="form.processing" class="w-4 h-4 animate-spin" />
              <QrCode v-else class="w-4 h-4" />
              {{ form.processing ? 'Membuat...' : 'Buat QR Code' }}
            </button>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>
