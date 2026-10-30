<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Absensi Digital" />

    <div class="flex min-h-svh flex-col items-center justify-center bg-white px-6 py-12 text-center text-gray-900 dark:bg-zinc-950 dark:text-zinc-100">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-zinc-500">SKAQS</p>
        <h1 class="mt-3 text-4xl font-black">Absensi Digital</h1>
        <p class="mt-3 max-w-md text-base text-gray-600 dark:text-zinc-400">
            Hadir untuk berkarya, scan untuk memulai. Absensi karyawan dengan QR Code,
            Senin sampai Sabtu.
        </p>

        <Link
            :href="route('login')"
            class="mt-8 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-8 py-3 font-bold text-white hover:bg-emerald-700"
        >
            Masuk
        </Link>

        <ul class="mt-10 grid max-w-lg gap-3 text-left text-sm text-gray-600 dark:text-zinc-400 sm:grid-cols-3">
            <li class="rounded-xl border border-gray-200 p-4 dark:border-zinc-800">Satu QR Code untuk satu karyawan.</li>
            <li class="rounded-xl border border-gray-200 p-4 dark:border-zinc-800">Target 14,5 jam per minggu, Senin–Sabtu.</li>
            <li class="rounded-xl border border-gray-200 p-4 dark:border-zinc-800">Akun dibuat oleh admin — tidak ada pendaftaran publik.</li>
        </ul>
    </div>
</template>
