<script setup lang="ts">
import { computed } from 'vue'
import { CheckCircle, Clock } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'

interface AttendanceRecord {
  id: string
  day: string
  date: string
  check_in: string | null
  check_out: string | null
  hours_worked: number
}

interface Props {
  records: AttendanceRecord[]
  present_today: boolean
  avg_hours: number
  hours_worked: number
  target_hours: number
}

const props = defineProps<Props>()

function formatHours(hours: number): string {
  if (hours === 0) return '-'
  const h = Math.floor(hours)
  const m = Math.round((hours - h) * 60)
  return m > 0 ? `${h}h ${m}m` : `${h}h`
}

function getStatus(record: AttendanceRecord): 'present' | 'in_progress' | 'absent' {
  if (record.hours_worked > 0) return 'present'
  if (record.check_in)         return 'in_progress'
  return 'absent'
}

const circumference = 2 * Math.PI * 54

const TARGET_HOURS = 14.5

const progressPercentage = computed(() =>
  Math.min((props.hours_worked / TARGET_HOURS) * 100, 100)
)

const strokeDashoffset = computed(() =>
  circumference - (progressPercentage.value / 100) * circumference
)

const filteredRecords = computed(() =>
  props.records.filter(record => {
    const day = record.day.toLowerCase()
    return day !== 'minggu' && day !== 'sunday'
  })
)
</script>

<template>
  <AppLayout>
    <div class="page-root">

      <!-- Header -->
      <div class="page-header">
        <h1>Dashboard</h1>
        <p>Pantau kehadiran dan jam kerja minggu ini.</p>
      </div>

      <div class="dashboard-grid">

        <!-- Attendance Table -->
        <div class="table-card">
          <div class="card-header">
            <h3>Attendance History</h3>
            <p>Catatan check-in dan check-out minggu ini</p>
          </div>

          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Hari / Tanggal</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th>Jam Kerja</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(record, index) in filteredRecords"
                  :key="record.id"
                  class="table-row"
                  :style="{ '--row-i': index }"
                >
                  <td>
                    <p class="day-name">{{ record.day }}</p>
                    <p class="day-date">{{ record.date }}</p>
                  </td>
                  <td>
                    <span v-if="record.check_in" class="time-text">{{ record.check_in }}</span>
                    <span v-else class="empty-text">—</span>
                  </td>
                  <td>
                    <span v-if="record.check_out" class="time-text">{{ record.check_out }}</span>
                    <span v-else class="empty-text">—</span>
                  </td>
                  <td>
                    <span class="hours-text">{{ formatHours(record.hours_worked) }}</span>
                  </td>
                  <td>
                    <span class="badge" :class="`badge--${getStatus(record)}`">
                      <template v-if="getStatus(record) === 'present'">Present</template>
                      <template v-else-if="getStatus(record) === 'in_progress'">In Progress</template>
                      <template v-else>Absent</template>
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right Column -->
        <div class="right-col">

          <!-- Donut Progress -->
          <div class="chart-card">
            <div class="card-header">
              <h3>Work Progress</h3>
              <p>Target jam kerja minggu ini</p>
            </div>
            <div class="chart-body">
              <div class="donut-wrap">
                <svg viewBox="0 0 120 120" class="donut-svg">
                  <circle cx="60" cy="60" r="54" fill="none" stroke="#1c1c1f" stroke-width="10" />
                  <circle
                    cx="60" cy="60" r="54"
                    fill="none"
                    stroke="#ffffff"
                    stroke-width="10"
                    stroke-linecap="round"
                    :stroke-dasharray="circumference"
                    :stroke-dashoffset="strokeDashoffset"
                    transform="rotate(-90 60 60)"
                    class="donut-progress"
                  />
                </svg>
                <div class="donut-label">
                  <span class="donut-pct">{{ progressPercentage.toFixed(0) }}%</span>
                  <span class="donut-sub">Completed</span>
                </div>
              </div>
              <div class="chart-meta">
                <p class="chart-meta-value">
                  {{ hours_worked.toFixed(1) }}h
                 <span>/ {{ props.target_hours }}h</span>
                </p>
                <p class="chart-meta-label">Jam minggu ini</p>
              </div>
            </div>
          </div>

          <!-- Present Today -->
          <div class="stat-card" style="--i: 0">
            <div class="stat-icon" :class="present_today ? 'stat-icon--green' : 'stat-icon--muted'">
              <CheckCircle class="stat-svg" :class="present_today ? 'icon--green' : 'icon--muted'" />
            </div>
            <div>
              <p class="stat-label">Present Today</p>
              <p class="stat-value">{{ present_today ? 'Ya' : 'Tidak' }}</p>
            </div>
          </div>

          <!-- Avg Hours -->
          <div class="stat-card" style="--i: 1">
            <div class="stat-icon stat-icon--blue">
              <Clock class="stat-svg icon--blue" />
            </div>
            <div>
              <p class="stat-label">Rata-rata Jam/Minggu</p>
              <p class="stat-value">{{ avg_hours.toFixed(1) }}h</p>
            </div>
          </div>

          <!-- Hours This Week -->
          <div class="stat-card" style="--i: 2">
            <div class="stat-icon stat-icon--amber">
              <Clock class="stat-svg icon--amber" />
            </div>
            <div>
              <p class="stat-label">Jam Minggu Ini</p>
              <p class="stat-value">
                {{ hours_worked.toFixed(1) }}h
                <span class="stat-value-sub">/ {{ props.target_hours }}h</span>
              </p>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>

.page-root {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  animation: pageIn 0.45s cubic-bezier(.22,1,.36,1) both;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

.page-header h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 0.25rem;
}
.page-header p { color: #71717a; font-size: 0.9rem; }

/* Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 1.5rem;
  align-items: start;
}
@media (max-width: 1024px) {
  .dashboard-grid { grid-template-columns: 1fr; }
}

/* Card base */
.table-card,
.chart-card {
  background: #09090b;
  border: 1px solid #27272a;
  border-radius: 14px;
  overflow: hidden;
}
.card-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #1c1c1f;
}
.card-header h3 {
  font-size: 1rem;
  font-weight: 600;
  color: #ffffff;
  margin-bottom: 0.2rem;
}
.card-header p { font-size: 0.8rem; color: #71717a; }

/* Table */
.table-wrapper { overflow-x: auto; }
.data-table {
  width: 100%;
  min-width: 500px;
  border-collapse: collapse;
}
.data-table thead tr {
  background: #111113;
  border-bottom: 1px solid #1c1c1f;
}
.data-table th {
  padding: 0.75rem 1.25rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #52525b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.data-table td {
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid #111113;
  vertical-align: middle;
}
.table-row {
  transition: background 0.18s;
  animation: rowIn 0.4s cubic-bezier(.22,1,.36,1) calc(var(--row-i, 0) * 40ms) both;
}
.table-row:hover { background: #0d0d10; }
.table-row:last-child td { border-bottom: none; }
@keyframes rowIn {
  from { opacity: 0; transform: translateX(-6px); }
  to   { opacity: 1; transform: translateX(0); }
}

.day-name  { color: #ffffff; font-size: 0.875rem; font-weight: 500; }
.day-date  { color: #71717a; font-size: 0.75rem; margin-top: 1px; }
.time-text { color: #ffffff; font-size: 0.875rem; font-variant-numeric: tabular-nums; }
.empty-text { color: #3f3f46; }
.hours-text { color: #a1a1aa; font-size: 0.875rem; font-variant-numeric: tabular-nums; }

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid transparent;
}
.badge--present     { background: #052e16; color: #4ade80; border-color: #166534; }
.badge--in_progress { background: #1c1206; color: #fbbf24; border-color: #78350f; }
.badge--absent      { background: #111113; color: #52525b; border-color: #27272a; }

/* Right col */
.right-col {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Donut */
.chart-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
}
.donut-wrap {
  position: relative;
  width: 130px;
  height: 130px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.donut-svg { width: 100%; height: 100%; }
.donut-progress {
  transition: stroke-dashoffset 0.9s cubic-bezier(.22,1,.36,1);
}
.donut-label {
  position: absolute;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.donut-pct { font-size: 1.5rem; font-weight: 700; color: #ffffff; line-height: 1.1; }
.donut-sub { font-size: 0.68rem; color: #71717a; }
.chart-meta { text-align: center; }
.chart-meta-value { font-size: 1rem; font-weight: 600; color: #ffffff; }
.chart-meta-value span { font-size: 0.825rem; font-weight: 400; color: #71717a; }
.chart-meta-label { font-size: 0.75rem; color: #71717a; margin-top: 2px; }

/* Stat cards */
.stat-card {
  background: #09090b;
  border: 1px solid #27272a;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.9rem;
  transition: border-color 0.25s, transform 0.25s;
  animation: cardIn 0.45s cubic-bezier(.22,1,.36,1) calc(var(--i, 0) * 60ms + 0.12s) both;
  cursor: default;
}
.stat-card:hover { border-color: #3f3f46; transform: translateY(-1px); }
@keyframes cardIn {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-icon--green { background: #052e16; }
.stat-icon--muted { background: #1c1c1f; }
.stat-icon--blue  { background: #0c1a2e; }
.stat-icon--amber { background: #1c1206; }

.stat-svg { width: 20px; height: 20px; }
.icon--green { color: #4ade80; }
.icon--muted { color: #52525b; }
.icon--blue  { color: #60a5fa; }
.icon--amber { color: #fbbf24; }

.stat-label { font-size: 0.775rem; color: #71717a; margin-bottom: 0.15rem; }
.stat-value { font-size: 1.2rem; font-weight: 600; color: #ffffff; line-height: 1.2; }
.stat-value-sub { font-size: 0.78rem; font-weight: 400; color: #71717a; }

</style>