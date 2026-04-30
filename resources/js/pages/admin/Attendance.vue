<script setup lang="ts">
import { ref, computed } from 'vue'
import { CalendarDays, Clock, X, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'

interface Attendance {
  id: number
  user_id: number
  user_name: string
  qrcode: string
  date: string
  check_in?: string | null
  check_out?: string | null
  duration_minutes?: number | null
}

interface Props {
  attendances?: Attendance[]
}

const props = defineProps<Props>()

const selectedDate = ref('')

type SortKey = 'userName' | 'date' | 'checkIn' | 'checkOut' | 'hoursWorked'
type SortDir = 'asc' | 'desc'

const sortKey = ref<SortKey>('date')
const sortDir = ref<SortDir>('desc')
const sortChanging = ref(false)

function toggleSort(key: SortKey) {
  sortChanging.value = true
  setTimeout(() => { sortChanging.value = false }, 300)

  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

function getSortIcon(key: SortKey) {
  if (sortKey.value !== key) return 'none'
  return sortDir.value === 'asc' ? 'asc' : 'desc'
}

const safeRecords = computed(() => props.attendances ?? [])

const normalizedRecords = computed(() =>
  safeRecords.value.map(r => {
    const hours = (r.duration_minutes ?? 0) / 60
    return {
      id: r.id,
      userName: r.user_name ?? 'Unknown',
      date: r.date,
      checkIn: r.check_in ?? '-',
      checkOut: r.check_out ?? '-',
      hoursWorked: hours,
    }
  })
)

const filteredRecords = computed(() => {
  let records = normalizedRecords.value

  if (selectedDate.value) {
    records = records.filter(r => r.date === selectedDate.value)
  }

  return [...records].sort((a, b) => {
    const valA = a[sortKey.value]
    const valB = b[sortKey.value]

    if (typeof valA === 'number' && typeof valB === 'number') {
      return sortDir.value === 'asc' ? valA - valB : valB - valA
    }

    const strA = String(valA ?? '').toLowerCase()
    const strB = String(valB ?? '').toLowerCase()

    if (strA === '-' && strB !== '-') return 1
    if (strB === '-' && strA !== '-') return -1

    if (strA < strB) return sortDir.value === 'asc' ? -1 : 1
    if (strA > strB) return sortDir.value === 'asc' ? 1 : -1
    return 0
  })
})

const stats = computed(() =>
  filteredRecords.value.reduce(
    (acc, r) => {
      acc.totalHours += r.hoursWorked
      acc.count++
      return acc
    },
    { totalHours: 0, count: 0 }
  )
)

function clearDate() {
  selectedDate.value = ''
}

function getInitial(name: string) {
  return name?.charAt(0)?.toUpperCase() || '?'
}

const columns: { key: SortKey; label: string; hint: string }[] = [
  { key: 'userName',    label: 'Employee',     hint: 'A–Z / Z–A'         },
  { key: 'date',        label: 'Date',         hint: 'Terlama / Terbaru'  },
  { key: 'checkIn',     label: 'Check In',     hint: 'Paling awal / Akhir'},
  { key: 'checkOut',    label: 'Check Out',    hint: 'Paling awal / Akhir'},
  { key: 'hoursWorked', label: 'Hours Worked', hint: 'Tercepat / Terlama' },
]
</script>

<template>
  <AppLayout>
    <div class="page-root space-y-8">

      <!-- Header -->
      <div class="header-enter">
        <h1 class="text-3xl font-bold text-white tracking-tight">Attendance</h1>
        <p class="text-neutral-400 mt-2 text-sm">Track daily attendance records</p>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="stat-card" style="--delay: 0.05s">
          <div class="stat-icon">
            <Clock class="w-5 h-5 text-white" />
          </div>
          <div>
            <p class="text-neutral-400 text-sm">Total Hours</p>
            <p class="text-white font-semibold text-lg leading-tight">
              {{ stats.totalHours.toFixed(1) }}<span class="text-neutral-500 text-sm font-normal">h</span>
            </p>
          </div>
        </div>

        <div class="stat-card" style="--delay: 0.1s">
          <div class="stat-icon">
            <CalendarDays class="w-5 h-5 text-white" />
          </div>
          <div>
            <p class="text-neutral-400 text-sm">Total Records</p>
            <p class="text-white font-semibold text-lg leading-tight">{{ stats.count }}</p>
          </div>
        </div>

      </div>

      <!-- Filter Bar -->
      <div class="filter-enter flex flex-wrap items-center gap-4">

        <div class="filter-pill" :class="{ 'filter-pill--active': selectedDate }">
          <CalendarDays class="w-4 h-4 text-neutral-400 flex-shrink-0" />
          <input
            v-model="selectedDate"
            type="date"
            class="bg-transparent text-white focus:outline-none text-sm"
          />
          <button
            v-if="selectedDate"
            @click="clearDate"
            class="ml-1 text-neutral-500 hover:text-white transition-colors"
            title="Tampilkan semua tanggal"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="ml-auto flex items-center gap-2 text-sm">
          <span class="text-neutral-500">
            Menampilkan
            <span class="text-white font-medium tabular-nums">{{ filteredRecords.length }}</span>
            record
          </span>
          <span class="text-neutral-700">·</span>
          <span v-if="selectedDate" class="text-neutral-500">
            Tanggal: <span class="text-white">{{ selectedDate }}</span>
          </span>
          <span v-else class="text-neutral-700 italic text-xs">Semua tanggal</span>
        </div>

      </div>

      <!-- Sort hint badge -->
      <Transition name="hint">
        <div v-if="sortKey" class="sort-hint-bar">
          <span class="sort-hint-dot" />
          Urutan aktif:
          <strong class="text-white">{{ columns.find(c => c.key === sortKey)?.label }}</strong>
          —
          <span class="text-neutral-400">{{ sortDir === 'asc' ? '↑ Ascending' : '↓ Descending' }}</span>
        </div>
      </Transition>

      <!-- Table -->
      <div class="table-card table-enter">
        <div class="overflow-x-auto">
          <table class="w-full">

            <thead class="table-head">
              <tr>
                <th
                  v-for="col in columns"
                  :key="col.key"
                  class="text-left py-4 px-6 text-sm font-medium"
                >
                  <button
                    @click="toggleSort(col.key)"
                    class="sort-btn group"
                    :class="{ 'sort-btn--active': sortKey === col.key }"
                    :title="col.hint"
                  >
                    <span>{{ col.label }}</span>
                    <span class="sort-icon-wrap">
                      <ArrowUp
                        v-if="getSortIcon(col.key) === 'asc'"
                        class="sort-arrow sort-arrow--active"
                      />
                      <ArrowDown
                        v-else-if="getSortIcon(col.key) === 'desc'"
                        class="sort-arrow sort-arrow--active"
                      />
                      <ArrowUpDown
                        v-else
                        class="sort-arrow sort-arrow--idle group-hover:opacity-50"
                      />
                    </span>
                  </button>
                </th>
              </tr>
            </thead>

            <tbody :class="{ 'tbody--sorting': sortChanging }">
              <tr
                v-for="(record, index) in filteredRecords"
                :key="record.id"
                class="table-row"
                :style="{ '--row-delay': `${index * 35}ms` }"
              >
                <!-- Employee -->
                <td class="py-4 px-6">
                  <div class="flex items-center gap-3">
                    <div class="avatar">
                      <span>{{ getInitial(record.userName) }}</span>
                    </div>
                    <span class="text-white font-medium">{{ record.userName }}</span>
                  </div>
                </td>

                <!-- Date -->
                <td class="py-4 px-6 text-white tabular-nums">{{ record.date }}</td>

                <!-- Check In -->
                <td class="py-4 px-6 text-white tabular-nums">
                  <span v-if="record.checkIn !== '-'" class="checkin-chip">{{ record.checkIn }}</span>
                  <span v-else class="text-neutral-600">—</span>
                </td>

                <!-- Check Out -->
                <td class="py-4 px-6 text-white tabular-nums">
                  <span v-if="record.checkOut !== '-'" class="checkout-chip">{{ record.checkOut }}</span>
                  <span v-else class="text-neutral-600">—</span>
                </td>

                <!-- Hours Worked -->
                <td class="py-4 px-6">
                  <div class="hours-wrap">
                    <span class="hours-value">{{ record.hoursWorked.toFixed(1) }}h</span>
                    <div
                      class="hours-bar"
                      :style="{ '--w': Math.min(record.hoursWorked / 12, 1) }"
                    />
                  </div>
                </td>
              </tr>

              <!-- Empty state -->
              <tr v-if="filteredRecords.length === 0">
                <td colspan="5" class="py-16 text-center">
                  <div class="flex flex-col items-center gap-3 empty-state">
                    <CalendarDays class="w-10 h-10 text-neutral-700" />
                    <p class="text-neutral-500 font-medium">Tidak ada data attendance ditemukan.</p>
                    <p class="text-neutral-700 text-xs">
                      {{ selectedDate ? 'Coba pilih tanggal lain.' : 'Tidak ada data di database.' }}
                    </p>
                    <button
                      v-if="selectedDate"
                      @click="clearDate"
                      class="mt-1 text-xs text-neutral-400 underline hover:text-white transition"
                    >
                      Tampilkan semua tanggal
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>

          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<style scoped>

/* ── Page load animations ── */
.header-enter {
  animation: slideDown 0.45s cubic-bezier(.22,1,.36,1) both;
}
.filter-enter {
  animation: slideDown 0.5s cubic-bezier(.22,1,.36,1) 0.08s both;
}
.table-enter {
  animation: slideUp 0.55s cubic-bezier(.22,1,.36,1) 0.14s both;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-16px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Stat cards ── */
.stat-card {
  background: #09090b;
  border: 1px solid #27272a;
  border-radius: 0.75rem;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  animation: slideUp 0.5s cubic-bezier(.22,1,.36,1) var(--delay, 0s) both;
  transition: border-color 0.25s, transform 0.25s;
  cursor: default;
}
.stat-card:hover {
  border-color: #52525b;
  transform: translateY(-2px);
}
.stat-icon {
  width: 2.5rem;
  height: 2.5rem;
  background: #27272a;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.25s;
}
.stat-card:hover .stat-icon {
  background: #3f3f46;
}

/* ── Filter pill ── */
.filter-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #09090b;
  border: 1px solid #27272a;
  border-radius: 0.5rem;
  padding: 0.5rem 1rem;
  transition: border-color 0.25s;
}
.filter-pill:focus-within,
.filter-pill--active {
  border-color: #52525b;
}

/* ── Sort hint bar ── */
.sort-hint-bar {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.78rem;
  color: #71717a;
  padding: 0.35rem 0.75rem;
  background: #09090b;
  border: 1px solid #27272a;
  border-radius: 0.5rem;
  width: fit-content;
}
.sort-hint-dot {
  width: 6px;
  height: 6px;
  border-radius: 9999px;
  background: #52525b;
  animation: pulse 1.8s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.4; transform: scale(0.7); }
}

/* hint transition */
.hint-enter-active { transition: all 0.3s ease; }
.hint-leave-active { transition: all 0.2s ease; }
.hint-enter-from  { opacity: 0; transform: translateY(-6px); }
.hint-leave-to    { opacity: 0; transform: translateY(-4px); }

/* ── Table card ── */
.table-card {
  background: #09090b;
  border: 1px solid #27272a;
  border-radius: 0.75rem;
  overflow: hidden;
}

/* ── Table head ── */
.table-head {
  background: #111113;
  border-bottom: 1px solid #27272a;
}
.table-head th {
  color: #a1a1aa;
}

/* ── Sort button ── */
.sort-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: none;
  border: none;
  padding: 0.25rem 0.5rem;
  margin: -0.25rem -0.5rem;
  border-radius: 0.375rem;
  cursor: pointer;
  color: #a1a1aa;
  font-size: 0.875rem;
  font-weight: 500;
  transition: color 0.2s, background 0.2s;
  white-space: nowrap;
}
.sort-btn:hover {
  color: #ffffff;
  background: #1c1c1f;
}
.sort-btn--active {
  color: #ffffff;
}
.sort-btn--active .sort-icon-wrap {
  background: #27272a;
  border-radius: 0.25rem;
}

.sort-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.25rem;
  height: 1.25rem;
  transition: background 0.2s;
}
.sort-arrow {
  width: 0.8rem;
  height: 0.8rem;
  transition: transform 0.25s cubic-bezier(.22,1,.36,1);
}
.sort-arrow--active {
  color: #e4e4e7;
}
.sort-arrow--idle {
  opacity: 0.25;
}

/* ── Table rows ── */
.table-row {
  border-top: 1px solid #1c1c1f;
  transition: background 0.18s;
  animation: rowIn 0.4s cubic-bezier(.22,1,.36,1) var(--row-delay, 0ms) both;
}
.table-row:hover {
  background: #111113;
}

@keyframes rowIn {
  from { opacity: 0; transform: translateX(-8px); }
  to   { opacity: 1; transform: translateX(0); }
}

/* tbody flash when sorting changes */
.tbody--sorting .table-row {
  animation: sortFlash 0.28s ease both;
}
@keyframes sortFlash {
  0%   { opacity: 0.4; }
  100% { opacity: 1; }
}

/* ── Avatar ── */
.avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 9999px;
  background: #27272a;
  border: 1px solid #3f3f46;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.78rem;
  font-weight: 600;
  color: #d4d4d8;
  flex-shrink: 0;
  transition: background 0.2s, border-color 0.2s, transform 0.2s;
}
.table-row:hover .avatar {
  background: #3f3f46;
  border-color: #71717a;
  transform: scale(1.08);
}

/* ── Check-in / Check-out chips ── */
.checkin-chip,
.checkout-chip {
  display: inline-block;
  padding: 0.15rem 0.55rem;
  border-radius: 0.375rem;
  font-size: 0.8rem;
  font-variant-numeric: tabular-nums;
  border: 1px solid transparent;
  transition: background 0.2s;
}
.checkin-chip {
  background: #1c1c1f;
  color: #e4e4e7;
  border-color: #27272a;
}
.checkout-chip {
  background: #1c1c1f;
  color: #a1a1aa;
  border-color: #27272a;
}
.table-row:hover .checkin-chip {
  background: #27272a;
  border-color: #3f3f46;
}
.table-row:hover .checkout-chip {
  background: #27272a;
  border-color: #3f3f46;
}

/* ── Hours worked mini-bar ── */
.hours-wrap {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.hours-value {
  color: #ffffff;
  font-weight: 500;
  font-size: 0.875rem;
  font-variant-numeric: tabular-nums;
}
.hours-bar {
  height: 3px;
  border-radius: 9999px;
  background: #27272a;
  position: relative;
  width: 60px;
  overflow: hidden;
}
.hours-bar::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 9999px;
  background: #e4e4e7;
  transform-origin: left;
  transform: scaleX(var(--w, 0));
  transition: transform 0.6s cubic-bezier(.22,1,.36,1);
}

/* ── Empty state ── */
.empty-state {
  animation: fadeIn 0.4s ease both;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

</style>