<template>
  <Head :title="message ? success ? (isCheckIn ? 'Check In Berhasil' : 'Check Out Berhasil') : 'Absensi Gagal' : 'Mengambil Lokasi...'" />

  <div class="min-h-screen bg-white text-zinc-800 dark:bg-zinc-950 dark:text-zinc-100 font-sans flex flex-col">
    <header class="border-b border-gray-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
      <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-emerald-500 rounded-md flex items-center justify-center">
            <QrCode class="w-4 h-4 text-black" />
          </div>
          <div>
            <p class="text-sm text-zinc-600 uppercase tracking-widest">Panel Absensi</p>
            <h1 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 leading-tight">Absensi Digital</h1>
          </div>
        </div>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 font-mono">{{ currentTime }}</p>
      </div>
    </header>

    <main v-if="message" class="flex-1 w-full max-w-5xl mx-auto px-6 py-10 flex items-center justify-center">
      <div class="w-full max-w-xl mx-auto bg-white border rounded-2xl p-8 flex flex-col items-center gap-5 text-center dark:bg-zinc-900"
        :class="success ? 'border-emerald-300 dark:border-emerald-500/30' : 'border-red-300 dark:border-red-500/30'">
        <div class="w-16 h-16 rounded-full flex items-center justify-center border"
          :class="success ? 'bg-emerald-50 border-emerald-300 dark:bg-emerald-500/15 dark:border-emerald-500/50' : 'bg-red-50 border-red-300 dark:bg-red-500/10 dark:border-red-500/40'">
          <component :is="!success ? XCircle : isCheckIn ? LogIn : LogOut" class="w-7 h-7"
            :class="success ? 'text-emerald-500 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'" />
        </div>

        <h2 class="text-3xl font-bold leading-tight text-zinc-900 dark:text-zinc-100">
          {{ success ? (isCheckIn ? 'Check In Berhasil' : 'Check Out Berhasil') : 'Absensi Gagal' }}
        </h2>

        <p class="leading-relaxed text-zinc-700 dark:text-zinc-300">{{ props.message }}</p>

        <p class="text-sm text-zinc-500 dark:text-zinc-400 font-mono">
          {{ !success ? 'Gagal' : isCheckIn ? 'Check In' : 'Check Out' }} · {{ currentTimeLong }}
        </p>

        <!-- Motivational boost: success only, one line, different
             for check-in vs check-out, rotating by day of month so
             it is not the same sentence every single day. -->
        <div v-if="motivation" class="w-full bg-gray-50 dark:bg-zinc-800/60 border border-gray-200 dark:border-zinc-700 rounded-xl px-4 py-3 flex items-start gap-3 text-left">
          <Sparkles class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-500 dark:text-emerald-400" />
          <p class="text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ motivation }}</p>
        </div>
      </div>
    </main>

    <main v-else class="flex-1 flex items-center justify-center px-6 py-10">
      <!-- Locating / submitting: spinner only, nothing is sent yet -->
      <div v-if="stage === 'locating' || stage === 'submitting'" class="bg-gray-50 px-8 py-10 rounded-2xl shadow-lg text-center w-full max-w-sm dark:bg-zinc-900">
        <div class="flex justify-center mb-6">
          <div class="w-10 h-10 border-4 border-gray-200 border-t-zinc-800 rounded-full animate-spin dark:border-zinc-700 dark:border-t-white"></div>
        </div>
        <h1 class="text-lg font-semibold mb-2">
          {{ stage === 'locating' ? 'Mengambil Lokasi' : 'Mengirim Absensi' }}
        </h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
          {{ stage === 'locating' ? 'Mohon tunggu, kami sedang mendeteksi lokasi Anda...' : 'Mohon tunggu, absensi Anda sedang dicatat...' }}
        </p>
      </div>

      <!-- Confirm: nothing is sent until the employee taps this button.
           If GPS failed, they can retry here instead of burning the
           one-use QR code on a guaranteed failure. -->
      <div v-else class="bg-gray-50 px-8 py-10 rounded-2xl shadow-lg text-center w-full max-w-sm dark:bg-zinc-900">
        <h1 class="text-lg font-semibold mb-2">
          Konfirmasi Absensi
        </h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
          Absensi akan dicatat untuk akun yang sedang login, dengan lokasi dari GPS ponsel Anda.
        </p>
        <div class="text-left bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-xl px-4 py-3 mb-4 space-y-1">
          <p class="text-sm font-semibold" :class="hasLocation ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'">
            {{ hasLocation ? 'Lokasi terdeteksi' : 'Lokasi belum terdeteksi' }}
          </p>
          <p v-if="locationError" class="text-sm text-zinc-600 dark:text-zinc-400">{{ locationError }}</p>
          <p v-if="!hasLocation" class="text-sm text-zinc-600 dark:text-zinc-400">Aktifkan GPS / izin lokasi, lalu coba lagi. Absensi belum dicatat.</p>
          <p v-else class="text-sm text-zinc-600 dark:text-zinc-400">Lokasi ini dipakai untuk memeriksa jarak Anda dari kantor. Lokasi dari ponsel bukan bukti mutlak kehadiran.</p>
        </div>
        <div class="flex flex-col gap-2">
          <Button @click="submitAttendance" :disabled="!hasLocation || form.processing" class="w-full">
            {{ form.processing ? 'Mengirim...' : 'Ya, catat absensi saya' }}
          </Button>
          <Button variant="outline" @click="retryLocation" :disabled="locating" class="w-full">
            {{ locating ? 'Mengambil lokasi...' : 'Coba lagi ambil lokasi' }}
          </Button>
        </div>
      </div>
    </main>

    <div class="flex justify-center pb-8 px-6">
      <Link :href="route('dashboard')">
        <Button class="shadow-lg">Kembali ke Dashboard</Button>
      </Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { QrCode, LogIn, LogOut, XCircle, Sparkles } from 'lucide-vue-next';
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

// Motivational boost, success only. Rotates by day of month, so the
// sentence changes daily instead of repeating identically every day.
const checkInMotivation = [
  'Semangat bekerja hari ini — mulai dari satu tugas terpenting, selesaikan sebaik mungkin.',
  'Hari baru, kesempatan baru. Kerja pelan tapi konsisten, hasilnya akan terasa.',
  'Kehadiran tepat waktu adalah awal yang baik. Fokus, dan buat hari ini berarti.',
];
const checkOutMotivation = [
  'Kerja hari ini sudah selesai. Istirahat yang cukup, besok kita lanjut lagi.',
  'Terima kasih atas kerja hari ini. Selamat beristirahat — besok mulai lagi dengan semangat baru.',
  'Satu hari kerja tuntas. Luangkan waktu untuk istirahat dan orang-orang terdekat.',
];
const motivation = computed(() => {
  if (!success.value) return null;
  const list = isCheckIn.value ? checkInMotivation : checkOutMotivation;
  return list[new Date().getDate() % list.length];
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

// Clock: one time source, shown once in the header and once in the
// result detail line.
const now = ref(new Date());
let timer: ReturnType<typeof setInterval>;

const currentTime = computed(() =>
  now.value.toLocaleString('id-ID', {
    day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
);

const currentTimeLong = computed(() =>
  now.value.toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  })
);

// Confirm-before-send flow: the page must NOT post on mount, or a
// failed GPS read burns the one-use QR code. It locates first, shows
// a confirm screen, and only posts when the employee taps the button.
// If the page already has a result (props.message), stay on it.
const stage = ref<'locating' | 'confirm' | 'submitting'>(props.message ? 'confirm' : 'locating')
const locating = ref(false)
const locationError = ref<string | null>(null)
const hasLocation = computed(() => form.latitude !== null && form.longitude !== null)

const locate = async () => {
  locating.value = true
  locationError.value = null
  try {
    const coords = await getLocation()
    form.latitude = coords.latitude
    form.longitude = coords.longitude
  } catch (e) {
    form.latitude = null
    form.longitude = null
    locationError.value = typeof e === 'string' ? e : 'Lokasi tidak dapat dideteksi.'
  } finally {
    locating.value = false
    if (stage.value === 'locating') stage.value = 'confirm'
  }
}

const retryLocation = () => {
  stage.value = 'locating'
  locate()
}

const submitAttendance = () => {
  if (!hasLocation.value) return
  stage.value = 'submitting'
  form.post(route('qrcode.scanned', { token: props.token }), {
    onSuccess: () => form.reset(),
    onError: (errors) => {
      console.error(errors)
      // Back to the confirm screen so the employee can retry
      // instead of being stuck on a spinner.
      stage.value = 'confirm'
    },
  })
}

onMounted(async () => {
  // A result page (after a post, or a refreshed result) must not
  // re-locate and re-post on its own.
  if (props.message === null || props.message === undefined) {
    await locate()
  }

  timer = setInterval(() => { now.value = new Date(); }, 1000);
});

onUnmounted(() => clearInterval(timer));
</script>
