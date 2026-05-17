<template>
    <Head title="Weekly Report - Admin" />

    <AppLayout>
        <div class="min-h-screen bg-white dark:bg-zinc-950 text-gray-900 dark:text-zinc-100 font-mono">

            <!-- Header -->
            <header class="border-b border-gray-200 dark:border-zinc-800 bg-white/90 dark:bg-zinc-950/90 backdrop-blur sticky top-0 z-40">
                <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-md bg-emerald-500 flex items-center justify-center">
                            <svg class="w-4 h-4 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Admin Panel</p>
                            <h1 class="text-sm font-bold text-gray-900 dark:text-zinc-100 leading-tight">Weekly Work Report</h1>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-zinc-500">
                        Target: <span class="text-emerald-600 dark:text-emerald-400 font-bold">14.5 jam / minggu</span>
                    </div>
                </div>
            </header>

            <main class="max-w-7xl mx-auto px-6 py-8 space-y-8">

                <!-- ── STAT CARDS ── -->
                <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div
                        class="col-span-2 md:col-span-1 lg:col-span-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl p-4 flex flex-col gap-1">
                        <p class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-wider">Total User</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-zinc-100">{{ totalUsers }}</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl p-4 flex flex-col gap-1">
                        <p class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-wider">Total Laporan</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-zinc-100">{{ reports.length }}</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl p-4 flex flex-col gap-1">
                        <p class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-wider">Minggu Tercatat</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-zinc-100">{{ totalWeeks }}</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 border border-emerald-200 dark:border-emerald-900 rounded-xl p-4 flex flex-col gap-1">
                        <p class="text-xs text-emerald-600 dark:text-emerald-600 uppercase tracking-wider">Memenuhi</p>
                        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ memenuhi }}</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 border border-red-200 dark:border-red-900 rounded-xl p-4 flex flex-col gap-1">
                        <p class="text-xs text-red-600 dark:text-red-600 uppercase tracking-wider">Tidak Memenuhi</p>
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ tidakMemenuhi }}</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl p-4 flex flex-col gap-1">
                        <p class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-wider">Tingkat Kepatuhan</p>
                        <p class="text-3xl font-bold"
                            :class="complianceRate >= 70 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                            {{ complianceRate }}%
                        </p>
                    </div>
                </section>

                <!-- ── COMPLIANCE BAR ── -->
                <section class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-bold text-gray-600 dark:text-zinc-400 uppercase tracking-widest">Tingkat Kepatuhan
                            Keseluruhan</p>
                        <span class="text-xs text-gray-500 dark:text-zinc-500">{{ memenuhi }} / {{ reports.length }}</span>
                    </div>
                    <div class="h-3 bg-gray-200 dark:bg-zinc-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                            :class="complianceRate >= 70 ? 'bg-emerald-500' : 'bg-red-500'"
                            :style="{ width: complianceRate + '%' }"></div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-600 dark:text-zinc-600">
                        <span>0%</span>
                        <span class="text-gray-800 dark:text-zinc-400 font-bold">{{ complianceRate }}%</span>
                        <span>100%</span>
                    </div>
                </section>

                <!-- ── TAB NAVIGATION ── -->
                <div class="flex gap-1 border-b border-gray-200 dark:border-zinc-800">
                    <button v-for="tab in [
                        { key: 'tabel', label: '📋 Tabel Laporan' },
                        { key: 'user', label: '👤 Per User' },
                        { key: 'trend', label: '📊 Tren Mingguan' },
                    ]" :key="tab.key" @click="activeTab = tab.key"
                        class="px-4 py-2 text-xs font-bold uppercase tracking-widest transition-all border-b-2 -mb-px"
                        :class="activeTab === tab.key
                            ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                            : 'border-transparent text-gray-500 dark:text-zinc-500 hover:text-gray-700 dark:hover:text-zinc-300'">
                        {{ tab.label }}
                    </button>
                </div>

                <!-- ══════════════════════════════════════
                     TAB 1: TABEL LAPORAN
                ══════════════════════════════════════ -->
                <section v-if="activeTab === 'tabel'" class="space-y-4">

                    <!-- Filter & Search Bar -->
                    <div class="flex flex-col md:flex-row gap-3">
                        <input v-model="search" type="text" placeholder="Cari user ID atau nama..."
                            class="flex-1 bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 rounded-lg px-4 py-2 text-sm text-gray-900 dark:text-zinc-200 placeholder-gray-400 dark:placeholder-zinc-600 focus:outline-none focus:border-emerald-500 transition"
                            @input="currentPage = 1" />
                        <div class="flex gap-2 flex-wrap">
                            <button v-for="f in [
                                { val: 'all', label: 'Semua' },
                                { val: 'memenuhi', label: '✅ Memenuhi' },
                                { val: 'tidak_memenuhi', label: '❌ Tidak Memenuhi' },
                            ]" :key="f.val" @click="setFilter(f.val)"
                                class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider border transition"
                                :class="filterStatus === f.val
                                    ? 'bg-emerald-500 border-emerald-500 text-white dark:text-black'
                                    : 'bg-gray-100 dark:bg-zinc-900 border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-zinc-400 hover:border-gray-400 dark:hover:border-zinc-500'">
                                {{ f.label }}
                            </button>

                            <!-- ✅ Tombol Export Excel (Diperbarui dengan target="_blank") -->
                            <a :href="exportUrl" target="_blank" :class="[
                                'px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider border transition flex items-center gap-1.5',
                                isExporting
                                    ? 'bg-emerald-100 dark:bg-emerald-500/20 border-emerald-300 dark:border-emerald-500/50 text-emerald-700 dark:text-emerald-500 cursor-wait'
                                    : 'bg-gray-100 dark:bg-zinc-900 border-gray-300 dark:border-zinc-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500 hover:border-emerald-500 hover:text-white dark:hover:text-black'
                            ]" @click="handleExportClick">
                                <!-- Spinner saat loading -->
                                <svg v-if="isExporting" class="w-3.5 h-3.5 animate-spin" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                </svg>
                                <!-- Icon download normal -->
                                <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                {{ isExporting ? 'Mengekspor...' : 'Export Excel' }}
                            </a>
                        </div>
                    </div>

                    <!-- Filter Week Start — terpisah dari search & status -->
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-widest font-mono">Minggu:</span>

                        <div class="flex items-center gap-2 bg-white dark:bg-zinc-900 border rounded-lg px-3 py-2 transition"
                            :class="selectedWeek ? 'border-emerald-500/50' : 'border-gray-300 dark:border-zinc-700'">
                            <svg class="w-3.5 h-3.5 text-gray-400 dark:text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <input type="date" :value="selectedWeek" @change="handleWeekInput"
                                class="bg-transparent text-sm text-gray-900 dark:text-zinc-200 focus:outline-none w-36" />
                        </div>

                        <!-- Info snap ke Senin -->
                        <span v-if="selectedWeek" class="text-xs text-gray-500 dark:text-zinc-500 font-mono">
                            Minggu: <span class="text-emerald-600 dark:text-emerald-400">{{ selectedWeek }}</span>
                            <button @click="clearWeek"
                                class="ml-2 text-gray-400 dark:text-zinc-600 hover:text-red-500 dark:hover:text-red-400 transition">✕</button>
                        </span>
                        <span v-else class="text-xs text-gray-400 dark:text-zinc-600 italic">Semua minggu</span>
                    </div>

                    <!-- Table -->
                    <div class="rounded-xl border border-gray-200 dark:border-zinc-800 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-zinc-900 border-b border-gray-200 dark:border-zinc-800">
                                    <th v-for="col in [
                                        { key: 'user_id', label: 'User' },
                                        { key: 'week_start', label: 'Mulai Minggu' },
                                        { key: 'total_minutes', label: 'Total Jam' },
                                        { key: 'status', label: 'Status' },
                                    ]" :key="col.key" @click="toggleSort(col.key)"
                                        class="px-4 py-3 text-left text-xs text-gray-500 dark:text-zinc-400 uppercase tracking-widest cursor-pointer hover:text-gray-700 dark:hover:text-zinc-200 transition select-none">
                                        {{ col.label }}
                                        <span class="ml-1 text-gray-400 dark:text-zinc-600">
                                            {{ sortKey === col.key ? (sortDir === 'asc' ? '↑' : '↓') : '⇅' }}
                                        </span>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs text-gray-500 dark:text-zinc-400 uppercase tracking-widest">
                                        Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="paginated.length === 0">
                                    <td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-zinc-600 text-sm">
                                        Tidak ada data ditemukan.
                                    </td>
                                </tr>
                                <tr v-for="(row, i) in paginated" :key="i"
                                    class="border-b border-gray-100 dark:border-zinc-800/60 hover:bg-gray-50 dark:hover:bg-zinc-900/60 transition">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-7 h-7 rounded-full bg-gray-200 dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-zinc-400">
                                                {{ String(row.user_name || row.user_id).charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <p class="text-gray-900 dark:text-zinc-200 font-semibold text-xs">{{ row.user_name || `User
                                                    #${row.user_id}` }}</p>
                                                <p class="text-gray-500 dark:text-zinc-600 text-xs">ID: {{ row.user_id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-zinc-400 text-xs">{{ fmtWeek(row.week_start) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-bold"
                                            :class="row.total_minutes >= 870 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                            {{ fmtMinutes(row.total_minutes) }}
                                        </span>
                                        <span class="text-gray-500 dark:text-zinc-600 text-xs ml-1">({{ Math.floor(row.total_minutes) }}m)</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-bold uppercase tracking-wider"
                                            :class="row.status === 'memenuhi'
                                                ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30'
                                                : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/30'">
                                            {{ row.status === 'memenuhi' ? '✅ Memenuhi' : '❌ Tidak Memenuhi' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-1.5 bg-gray-200 dark:bg-zinc-800 rounded-full overflow-hidden w-24">
                                                <div class="h-full rounded-full"
                                                    :class="row.status === 'memenuhi' ? 'bg-emerald-500' : 'bg-red-500'"
                                                    :style="{ width: Math.min(100, Math.round((row.total_minutes / 870) * 100)) + '%' }">
                                                </div>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-zinc-600">{{ Math.min(100,
                                                Math.round((row.total_minutes / 870) * 100)) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-zinc-500">
                        <span>
                            Menampilkan {{ Math.min((currentPage - 1) * perPage + 1, filtered.length) }}–{{
                                Math.min(currentPage * perPage, filtered.length) }} dari {{ filtered.length }} laporan
                        </span>
                        <div class="flex gap-1">
                            <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
                                class="px-3 py-1.5 rounded-md bg-gray-100 dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 hover:border-gray-400 dark:hover:border-zinc-500 transition disabled:opacity-30 text-gray-700 dark:text-zinc-300">←
                                Prev</button>
                            <button v-for="p in totalPages" :key="p" @click="currentPage = p"
                                class="px-3 py-1.5 rounded-md border transition font-bold" :class="currentPage === p
                                    ? 'bg-emerald-500 border-emerald-500 text-white dark:text-black'
                                    : 'bg-gray-100 dark:bg-zinc-900 border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-zinc-400 hover:border-gray-400 dark:hover:border-zinc-500'">{{ p
                                }}</button>
                            <button @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                :disabled="currentPage === totalPages || totalPages === 0"
                                class="px-3 py-1.5 rounded-md bg-gray-100 dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 hover:border-gray-400 dark:hover:border-zinc-500 transition disabled:opacity-30 text-gray-700 dark:text-zinc-300">Next
                                →</button>
                        </div>
                    </div>
                </section>

                <!-- ══════════════════════════════════════
                     TAB 2: PER USER
                ══════════════════════════════════════ -->
                <section v-if="activeTab === 'user'" class="space-y-3">
                    <p class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-widest mb-4">Ringkasan per karyawan</p>

                    <div v-if="userStats.length === 0" class="text-center py-16 text-gray-400 dark:text-zinc-600">Tidak ada data.</div>

                    <div v-for="(u, i) in userStats" :key="u.user_id"
                        class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl p-5 flex flex-col md:flex-row md:items-center gap-4 hover:border-gray-300 dark:hover:border-zinc-700 transition">
                        <!-- Rank + Avatar -->
                        <div class="flex items-center gap-4 flex-shrink-0">
                            <span class="text-gray-400 dark:text-zinc-700 font-bold text-sm w-6 text-right">{{ i + 1 }}</span>
                            <div
                                class="w-10 h-10 rounded-full bg-gray-200 dark:bg-zinc-800 border-2 border-gray-300 dark:border-zinc-700 flex items-center justify-center font-bold text-gray-700 dark:text-zinc-300">
                                {{ u.user_name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-zinc-200 text-sm">{{ u.user_name }}</p>
                                <p class="text-gray-500 dark:text-zinc-600 text-xs">ID #{{ u.user_id }}</p>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex gap-6 flex-1 flex-wrap">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-zinc-200">{{ u.total }}</p>
                                <p class="text-xs text-gray-500 dark:text-zinc-600">Laporan</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ u.memenuhi }}</p>
                                <p class="text-xs text-gray-500 dark:text-zinc-600">Memenuhi</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ u.tidak_memenuhi }}</p>
                                <p class="text-xs text-gray-500 dark:text-zinc-600">Tidak</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-700 dark:text-zinc-400">{{ fmtMinutes(Math.round(u.total_minutes /
                                    u.total)) }}</p>
                                <p class="text-xs text-gray-500 dark:text-zinc-600">Rata-rata/minggu</p>
                            </div>
                        </div>

                        <!-- Mini compliance bar -->
                        <div class="flex-shrink-0 w-full md:w-40">
                            <div class="flex justify-between text-xs text-gray-600 dark:text-zinc-600 mb-1">
                                <span>Kepatuhan</span>
                                <span
                                    :class="Math.round((u.memenuhi / u.total) * 100) >= 70 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                    {{ Math.round((u.memenuhi / u.total) * 100) }}%
                                </span>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-zinc-800 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                    :class="Math.round((u.memenuhi / u.total) * 100) >= 70 ? 'bg-emerald-500' : 'bg-red-500'"
                                    :style="{ width: Math.round((u.memenuhi / u.total) * 100) + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ══════════════════════════════════════
                     TAB 3: TREN MINGGUAN
                ══════════════════════════════════════ -->
                <section v-if="activeTab === 'trend'" class="space-y-4">
                    <p class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Tren 8 minggu terakhir</p>

                    <div v-if="weeklyTrend.length === 0" class="text-center py-16 text-gray-400 dark:text-zinc-600">Tidak ada data.</div>

                    <!-- Bar Chart -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl p-6">
                        <div class="flex items-end gap-2 h-48">
                            <div v-for="w in weeklyTrend" :key="w.week" class="flex-1 flex flex-col items-center gap-1">
                                <div class="relative w-full flex flex-col-reverse gap-0.5" :style="{ height: '160px' }">
                                    <!-- Tidak Memenuhi -->
                                    <div class="w-full bg-red-500/70 rounded-sm transition-all duration-500"
                                        :style="{ height: (w.tidak / maxTrendTotal * 160) + 'px' }"
                                        :title="`Tidak: ${w.tidak}`"></div>
                                    <!-- Memenuhi -->
                                    <div class="w-full bg-emerald-500/80 rounded-sm transition-all duration-500"
                                        :style="{ height: (w.memenuhi / maxTrendTotal * 160) + 'px' }"
                                        :title="`Memenuhi: ${w.memenuhi}`"></div>
                                </div>
                                <p class="text-gray-500 dark:text-zinc-600 text-xs text-center leading-tight">
                                    {{ w.week.slice(5) }}
                                </p>
                            </div>
                        </div>
                        <!-- Legend -->
                        <div class="flex gap-4 mt-4 justify-center text-xs text-gray-600 dark:text-zinc-400">
                            <span class="flex items-center gap-1.5"><span
                                    class="w-3 h-3 rounded-sm bg-emerald-500/80 inline-block"></span> Memenuhi</span>
                            <span class="flex items-center gap-1.5"><span
                                    class="w-3 h-3 rounded-sm bg-red-500/70 inline-block"></span> Tidak Memenuhi</span>
                        </div>
                    </div>

                    <!-- Table ringkasan per minggu -->
                    <div class="rounded-xl border border-gray-200 dark:border-zinc-800 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-zinc-900 border-b border-gray-200 dark:border-zinc-800">
                                    <th class="px-4 py-3 text-left text-xs text-gray-500 dark:text-zinc-400 uppercase tracking-widest">
                                        Minggu Mulai</th>
                                    <th class="px-4 py-3 text-left text-xs text-gray-500 dark:text-zinc-400 uppercase tracking-widest">
                                        Total User</th>
                                    <th class="px-4 py-3 text-left text-xs text-emerald-600 dark:text-emerald-500 uppercase tracking-widest">
                                        Memenuhi</th>
                                    <th class="px-4 py-3 text-left text-xs text-red-600 dark:text-red-500 uppercase tracking-widest">Tidak
                                        Memenuhi</th>
                                    <th class="px-4 py-3 text-left text-xs text-gray-500 dark:text-zinc-400 uppercase tracking-widest">
                                        Kepatuhan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="w in weeklyTrend" :key="w.week"
                                    class="border-b border-gray-100 dark:border-zinc-800/60 hover:bg-gray-50 dark:hover:bg-zinc-900/60 transition">
                                    <td class="px-4 py-3 text-gray-500 dark:text-zinc-400 text-xs">{{ fmtWeek(w.week) }}</td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-zinc-300 font-bold">{{ w.total }}</td>
                                    <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400 font-bold">{{ w.memenuhi }}</td>
                                    <td class="px-4 py-3 text-red-600 dark:text-red-400 font-bold">{{ w.tidak }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-20 h-1.5 bg-gray-200 dark:bg-zinc-800 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full"
                                                    :class="Math.round((w.memenuhi / w.total) * 100) >= 70 ? 'bg-emerald-500' : 'bg-red-500'"
                                                    :style="{ width: Math.round((w.memenuhi / w.total) * 100) + '%' }">
                                                </div>
                                            </div>
                                            <span class="text-xs"
                                                :class="Math.round((w.memenuhi / w.total) * 100) >= 70 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                                {{ Math.round((w.memenuhi / w.total) * 100) }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Footer -->
                <div class="pt-4 border-t border-gray-200 dark:border-zinc-800 text-center text-xs text-gray-400 dark:text-zinc-700">
                    Admin Panel · Weekly Work Report · Target 14.5 jam/minggu (870 menit)
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<script setup>
// (script tetap sama persis seperti sebelumnya)
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    reports: {
        type: Array,
        default: () => []
    }
})

// ── State ─────────────────────────────────────────────────────────────────────
const search = ref('')
const filterStatus = ref('all')
const sortKey = ref('week_start')
const sortDir = ref('desc')
const currentPage = ref(1)
const perPage = 10
const activeTab = ref('tabel')
const isExporting = ref(false)

// State filter week
const selectedWeek = ref('')

// Konversi tanggal apapun ke Senin minggu tersebut
const snapToMonday = (dateStr) => {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    const day = date.getDay()
    const diff = day === 0 ? -6 : 1 - day
    date.setDate(date.getDate() + diff)
    return date.toISOString().split('T')[0]
}

const handleWeekInput = (e) => {
    if (e.target instanceof HTMLInputElement) {
        selectedWeek.value = snapToMonday(e.target.value)
        currentPage.value = 1
    }
}

const clearWeek = () => {
    selectedWeek.value = ''
    currentPage.value = 1
}


// ── Computed: Filter + Sort + Paginate ────────────────────────────────────────
const filtered = computed(() => {
    let data = [...props.reports]

    if (search.value) {
        const q = search.value.toLowerCase()
        data = data.filter(r =>
            String(r.user_id).includes(q) ||
            (r.user_name && r.user_name.toLowerCase().includes(q)) ||
            r.week_start.includes(q)
        )
    }

    if (filterStatus.value !== 'all') {
        data = data.filter(r => r.status === filterStatus.value)
    }

    if (selectedWeek.value) {
        data = data.filter(r => r.week_start === selectedWeek.value)
    }

    data.sort((a, b) => {
        let va = a[sortKey.value]
        let vb = b[sortKey.value]
        if (sortKey.value === 'total_minutes') {
            va = Number(va); vb = Number(vb)
        }
        if (va < vb) return sortDir.value === 'asc' ? -1 : 1
        if (va > vb) return sortDir.value === 'asc' ? 1 : -1
        return 0
    })

    return data
})

const totalPages = computed(() => Math.ceil(filtered.value.length / perPage))
const paginated = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return filtered.value.slice(start, start + perPage)
})

// ── Computed: Stats ───────────────────────────────────────────────────────────
const totalUsers = computed(() => new Set(props.reports.map(r => r.user_id)).size)
const totalWeeks = computed(() => new Set(props.reports.map(r => r.week_start)).size)
const memenuhi = computed(() => props.reports.filter(r => r.status === 'memenuhi').length)
const tidakMemenuhi = computed(() => props.reports.filter(r => r.status === 'tidak_memenuhi').length)
const complianceRate = computed(() =>
    props.reports.length ? Math.round((memenuhi.value / props.reports.length) * 100) : 0
)

// ── Computed: Per User Stats ──────────────────────────────────────────────────
const userStats = computed(() => {
    const map = {}
    for (const r of props.reports) {
        if (!map[r.user_id]) {
            map[r.user_id] = {
                user_id: r.user_id,
                user_name: r.user_name || `User #${r.user_id}`,
                total: 0,
                memenuhi: 0,
                tidak_memenuhi: 0,
                total_minutes: 0,
            }
        }
        map[r.user_id].total++
        map[r.user_id].total_minutes += Number(r.total_minutes)
        if (r.status === 'memenuhi') map[r.user_id].memenuhi++
        else map[r.user_id].tidak_memenuhi++
    }
    return Object.values(map).sort((a, b) => b.memenuhi - a.memenuhi)
})

// ── Computed: Weekly Trend ────────────────────────────────────────────────────
const weeklyTrend = computed(() => {
    const map = {}
    for (const r of props.reports) {
        if (!map[r.week_start]) map[r.week_start] = { week: r.week_start, memenuhi: 0, tidak: 0, total: 0 }
        map[r.week_start].total++
        if (r.status === 'memenuhi') map[r.week_start].memenuhi++
        else map[r.week_start].tidak++
    }
    return Object.values(map).sort((a, b) => a.week.localeCompare(b.week)).slice(-8)
})

const maxTrendTotal = computed(() => Math.max(...weeklyTrend.value.map(w => w.total), 1))

// ── Computed: Export URL — sinkron dengan filter aktif ────────────────────────
const exportUrl = computed(() => {
    const params = new URLSearchParams()
    if (search.value) params.set('search', search.value)
    if (filterStatus.value !== 'all') params.set('status', filterStatus.value)
    if (selectedWeek.value) params.set('week_start', selectedWeek.value)
    const qs = params.toString()
    return `/admin/weekly-report/export${qs ? `?${qs}` : ''}`
})

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtMinutes(m) {
    const h = Math.floor(m / 60)
    const min = Math.floor(m % 60)
    return `${h}j ${min}m`
}

function fmtWeek(dateStr) {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function toggleSort(key) {
    if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    else { sortKey.value = key; sortDir.value = 'desc' }
    currentPage.value = 1
}

function setFilter(val) {
    filterStatus.value = val
    currentPage.value = 1
}

// ✅ Diperbarui: Feedback visual + pencegahan klik saat loading
function handleExportClick(e) {
    if (isExporting.value) {
        e.preventDefault(); // Cegah buka tab baru lagi jika sedang proses
        return;
    }
    isExporting.value = true;
    setTimeout(() => { isExporting.value = false }, 3000);
}
</script>