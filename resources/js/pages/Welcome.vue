<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
    <Head title="STAKS - Absensi Organisasi">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-gradient-to-br from-slate-50 to-blue-50 p-6 text-slate-800 lg:justify-center lg:p-8 dark:from-gray-950 dark:to-slate-900 dark:text-slate-200"
    >
        <!-- Header / Nav -->
        <header class="mb-10 w-full max-w-[335px] not-has-[nav]:hidden lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-lg border border-blue-200 bg-white/80 px-5 py-2 text-sm font-medium text-blue-700 shadow-sm backdrop-blur transition hover:border-blue-400 hover:bg-white dark:border-slate-700 dark:bg-slate-800/80 dark:text-blue-400 dark:hover:border-slate-600 dark:hover:bg-slate-800"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-lg border border-transparent px-5 py-2 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:bg-white/60 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:bg-slate-800/60"
                    >
                        Masuk
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Main Content -->
        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-700 lg:grow starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col overflow-hidden rounded-2xl border border-gray-200/80 bg-white/70 shadow-xl shadow-gray-200/50 backdrop-blur lg:max-w-4xl lg:flex-row dark:border-slate-800/80 dark:bg-slate-900/70 dark:shadow-slate-950/30"
            >
                <!-- Left Side - Text Content -->
                <div
                    class="flex-1 p-8 pb-10 text-[15px] leading-relaxed lg:p-12"
                >
                    <!-- Badge -->
                    <span
                        class="mb-4 inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold tracking-wide text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                    >
                        STMIK KHARISMA
                    </span>

                    <h1 class="mb-2 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        STAKS
                    </h1>
                    <p class="mb-1 text-lg font-semibold text-blue-600 dark:text-blue-400">
                        Sistem Absensi Organisasi
                    </p>
                    <p class="mb-6 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                        Catat kehadiran anggota dengan mudah, cepat, dan terintegrasi. <br />Dibangun khusus untuk
                        lingkungan digital STMIK Kharisma.
                    </p>

                    <!-- Feature List -->
                    <ul class="mb-6 flex flex-col gap-3">
                        <li class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">
                                <strong class="font-semibold text-slate-900 dark:text-white">Presensi Real-time</strong>
                                <br />Catat kehadiran langsung dari perangkat apa pun.
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">
                                <strong class="font-semibold text-slate-900 dark:text-white">Laporan Lengkap</strong>
                                <br />Rekap kehadiran anggota secara otomatis dan terperinci.
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">
                                <strong class="font-semibold text-slate-900 dark:text-white">Notifikasi Cerdas</strong>
                                <br />Dapatkan pengingat jadwal dan info penting seputar organisasi.
                            </span>
                        </li>
                    </ul>

                    <!-- CTA Button -->
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 hover:shadow-lg active:scale-[0.98] dark:bg-blue-500 dark:hover:bg-blue-400 dark:text-gray-900"
                    >
                        <span>Masuk ke Dashboard</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                </div>

                <!-- Right Side - Visual / Illustration -->
                <div
                    class="relative flex w-full items-center justify-center overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 p-10 lg:w-[420px] lg:p-16 dark:from-blue-950 dark:via-indigo-950 dark:to-slate-950"
                >
                    <!-- Abstract digital pattern background -->
                    <div class="absolute inset-0 opacity-10 dark:opacity-20">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_120%,rgba(255,255,255,0.8),transparent_70%)] dark:bg-[radial-gradient(circle_at_50%_120%,rgba(59,130,246,0.5),transparent_70%)]"></div>
                    </div>

                    <!-- Content in the visual area -->
                    <div class="relative z-10 text-center text-white">
                        <!-- Icon representing attendance / check-in -->
                        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/20 backdrop-blur shadow-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold tracking-tight">STAKS</h2>
                        <p class="mt-1 text-sm font-medium text-blue-100 dark:text-blue-200">Sistem Absensi Tepercaya</p>
                        <p class="mt-4 text-xs text-blue-200/80 dark:text-blue-300/70">
                            Powered by STMIK Kharisma
                        </p>
                    </div>

                    <!-- Decorative border radius fix -->
                    <div class="absolute inset-0 rounded-ee-2xl rounded-es-2xl lg:rounded-es-none lg:rounded-se-2xl shadow-[inset_0px_0px_0px_1px_rgba(255,255,255,0.15)] dark:shadow-[inset_0px_0px_0px_1px_rgba(255,255,255,0.08)]"></div>
                </div>
            </main>
        </div>

        <!-- Footer spacing -->
        <div class="hidden h-14 lg:block"></div>
    </div>
</template>