<template>
  <Head title="Penalty Management" />
  <AppLayout>
    <div class="page-container px-6 py-8">

      <!-- Header -->
      <div class="page-header">
        <div class="header-info">
          <h2>Penalty Management</h2>
          <p class="header-subtitle">{{ counts.total }} hukuman ditemukan</p>
        </div>
        <div class="header-actions">
          <button class="btn btn-secondary" @click="manualRefresh">
            <RefreshCw class="icon" />
            Refresh
          </button>
          <button class="btn btn-warning" @click="openExemptModal">
            <CalendarOff class="icon" />
            Minggu Libur
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-mini">
        <div class="stat-card-mini" style="--i:0">
          <div class="stat-icon-mini stat-icon-mini--neutral">
            <AlertTriangle />
          </div>
          <div class="stat-content-mini">
            <h4>{{ counts.total }}</h4>
            <p>Total Hukuman</p>
          </div>
        </div>
        <div class="stat-card-mini" style="--i:1">
          <div class="stat-icon-mini stat-icon-mini--yellow">
            <Clock />
          </div>
          <div class="stat-content-mini">
            <h4>{{ counts.pending }}</h4>
            <p>Menunggu</p>
          </div>
        </div>
        <div class="stat-card-mini" style="--i:2">
          <div class="stat-icon-mini stat-icon-mini--blue">
            <Upload />
          </div>
          <div class="stat-content-mini">
            <h4>{{ counts.uploaded }}</h4>
            <p>Perlu Review</p>
          </div>
        </div>
        <div class="stat-card-mini" style="--i:3">
          <div class="stat-icon-mini stat-icon-mini--green">
            <CheckCircle />
          </div>
          <div class="stat-content-mini">
            <h4>{{ counts.approved }}</h4>
            <p>Disetujui</p>
          </div>
        </div>
        <div class="stat-card-mini" style="--i:4">
          <div class="stat-icon-mini stat-icon-mini--red">
            <XCircle />
          </div>
          <div class="stat-content-mini">
            <h4>{{ counts.rejected }}</h4>
            <p>Ditolak</p>
          </div>
        </div>
      </div>

      <!-- Filter -->
      <div class="filter-bar">
        <div class="filter-group">
          <Search class="filter-icon" />
          <input
            v-model="search"
            type="text"
            placeholder="Cari nama user..."
            class="filter-input"
          />
        </div>
        <div class="filter-tabs">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            class="filter-tab"
            :class="{ 'filter-tab--active': activeTab === tab.value }"
            @click="activeTab = tab.value"
          >
            {{ tab.label }}
            <span class="tab-count">{{ tab.count }}</span>
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="table-container">
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th><div class="th-content"><Hash class="th-icon" />ID</div></th>
                <th><div class="th-content"><UserIcon class="th-icon" />User</div></th>
                <th><div class="th-content"><Calendar class="th-icon" />Minggu</div></th>
                <!-- <th><div class="th-content"><Clock class="th-icon" />Total Menit</div></th> -->
                <th><div class="th-content"><AlertTriangle class="th-icon" />Status</div></th>
                <th><div class="th-content"><ImageIcon class="th-icon" />Bukti</div></th>
                <th><div class="th-content"><Settings class="th-icon" />Aksi</div></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(penalty, index) in filteredPenalties"
                :key="penalty.id"
                class="table-row"
                :style="{ '--row-i': index }"
              >
                <td><span class="row-id">#{{ penalty.id }}</span></td>
                <td>
                  <div class="user-info">
                    <div class="user-avatar">{{ getInitials(penalty.user.name) }}</div>
                    <span class="username">{{ penalty.user.name }}</span>
                  </div>
                </td>
                <td>
                  <span class="week-text">
                    {{ formatWeek(penalty.weekly_report.week_start) }}
                  </span>
                </td>
                <!-- <td>
                  <span class="minutes-text">
                    {{ formatMinutes(penalty.weekly_report.total_minutes) }}
                  </span>
                </td> -->
                <td>
                  <span class="status-badge" :class="statusClass(penalty.status)">
                    {{ statusLabel(penalty.status) }}
                  </span>
                </td>
                <td>
                  <button
                    v-if="penalty.proof_path"
                    class="btn-proof"
                    @click="openProofModal(penalty)"
                  >
                    <Eye class="icon" />
                    Lihat Bukti
                  </button>
                  <span v-else class="no-proof">—</span>
                </td>
                <td>
                  <div class="actions">
                    <button
                      v-if="penalty.status === 'uploaded'"
                      class="btn-action btn-approve"
                      title="Setujui"
                      @click="approvePenalty(penalty)"
                    >
                      <Check class="icon" />
                    </button>
                    <button
                      v-if="penalty.status === 'uploaded'"
                      class="btn-action btn-reject"
                      title="Tolak"
                      @click="openRejectModal(penalty)"
                    >
                      <X class="icon" />
                    </button>
                    <span v-if="penalty.status !== 'uploaded'" class="no-action">—</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="filteredPenalties.length === 0" class="empty-state">
            <div class="empty-icon">
              <AlertTriangle />
            </div>
            <h3>Tidak ada hukuman</h3>
            <p>Tidak ada hukuman yang cocok dengan filter saat ini.</p>
          </div>
        </div>
      </div>

      <!-- Modal: Lihat Bukti -->
      <div v-if="showProofModal" ref="proofModalRef" class="modal-overlay" @click="closeProofModal">
        <div class="modal-content modal-content--wide" @click.stop>
          <div class="modal-header">
            <h3>Bukti Lari — {{ selectedPenalty?.user?.name }}</h3>
            <button class="modal-close" @click="closeProofModal"><X /></button>
          </div>
          <div class="proof-body">
            <div class="proof-meta">
              <span class="proof-meta-item">
                <Calendar class="icon" />
                {{ selectedPenalty ? formatWeek(selectedPenalty.weekly_report.week_start) : '' }}
              </span>
              <span class="status-badge" :class="statusClass(selectedPenalty?.status ?? '')">
                {{ statusLabel(selectedPenalty?.status ?? '') }}
              </span>
            </div>
            <div class="proof-image-wrapper">
              <img
                :src="`/storage/${selectedPenalty?.proof_path}`"
                alt="Bukti lari"
                class="proof-image"
              />
            </div>
            <div v-if="selectedPenalty?.rejection_reason" class="rejection-note">
              <XCircle class="icon" />
              <span>Alasan ditolak sebelumnya: {{ selectedPenalty.rejection_reason }}</span>
            </div>
            <div v-if="selectedPenalty?.status === 'uploaded'" class="proof-actions">
              <button class="btn btn-danger" @click="openRejectModal(selectedPenalty!)">
                <X class="icon" />
                Tolak
              </button>
              <button class="btn btn-success" @click="approvePenalty(selectedPenalty!)">
                <Check class="icon" />
                Setujui
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal: Tolak Bukti -->
      <div v-if="showRejectModal" ref="rejectModalRef" class="modal-overlay" @click="closeRejectModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header modal-header--red">
            <h3>Tolak Bukti</h3>
            <button class="modal-close" @click="closeRejectModal"><X /></button>
          </div>
          <div class="modal-form">
            <p class="reject-desc">
              Tolak bukti lari dari <strong>{{ selectedPenalty?.user?.name }}</strong>?
              User perlu mengupload ulang buktinya.
            </p>
            <div class="form-group">
              <label>Alasan Penolakan:</label>
              <textarea
                v-model="rejectReason"
                class="form-control"
                rows="3"
                placeholder="Contoh: Gambar tidak jelas, bukan dari Strava, jarak kurang dari 10km..."
              />
              <span v-if="rejectError" class="error">{{ rejectError }}</span>
            </div>
            <div class="form-actions">
              <button class="btn btn-secondary" @click="closeRejectModal">Batal</button>
              <button class="btn btn-danger" :disabled="rejectProcessing" @click="submitReject">
                {{ rejectProcessing ? 'Memproses...' : 'Tolak Bukti' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal: Minggu Libur -->
      <div v-if="showExemptModal" ref="exemptModalRef" class="modal-overlay" @click="closeExemptModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Pengaturan Minggu Libur</h3>
            <button class="modal-close" @click="closeExemptModal"><X /></button>
          </div>
          <div class="modal-form">
            <p class="exempt-desc">
              Tambahkan minggu yang dikecualikan dari hukuman. User tidak akan mendapat hukuman
              meskipun tidak memenuhi jam kerja pada minggu tersebut.
            </p>

            <!-- Form tambah minggu libur -->
            <div class="exempt-form">
              <div class="form-group">
                <label>Tanggal Senin (awal minggu):</label>
                <input v-model="exemptForm.week_start" type="date" class="form-control" />
                <span v-if="exemptErrors.week_start" class="error">{{ exemptErrors.week_start }}</span>
              </div>
              <div class="form-group">
                <label>Keterangan (opsional):</label>
                <input
                  v-model="exemptForm.reason"
                  type="text"
                  class="form-control"
                  placeholder="Contoh: Libur Idul Fitri, Libur Nasional..."
                />
              </div>
              <button class="btn btn-primary" :disabled="exemptProcessing" @click="addExemptWeek">
                <Plus class="icon" />
                {{ exemptProcessing ? 'Menyimpan...' : 'Tambah Minggu Libur' }}
              </button>
            </div>

            <!-- List minggu libur -->
            <div class="exempt-list">
              <h4 class="exempt-list-title">Minggu yang Dikecualikan</h4>
              <div v-if="exemptWeeks.length === 0" class="exempt-empty">
                Belum ada minggu libur yang ditambahkan.
              </div>
              <div
                v-for="week in exemptWeeks"
                :key="week.id"
                class="exempt-item"
              >
                <div class="exempt-item-info">
                  <CalendarOff class="icon" />
                  <div>
                    <span class="exempt-week">{{ formatWeek(week.week_start) }}</span>
                    <span v-if="week.reason" class="exempt-reason">{{ week.reason }}</span>
                  </div>
                </div>
                <button class="btn-action btn-delete" @click="deleteExemptWeek(week.id)">
                  <Trash class="icon" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive, nextTick, Ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import {
  RefreshCw, X, Check, Eye, Plus, Trash,
  AlertTriangle, Clock, Upload, CheckCircle, XCircle,
  Hash, Calendar, Settings, Search, CalendarOff,
  User as UserIcon, Image as ImageIcon,
} from 'lucide-vue-next'
import { useInitials } from '@/composables/useInitials'
import AppLayout from '@/layouts/AppLayout.vue'
import { Penalty, ExemptWeek } from '@/types'

interface Props {
  penalties: Penalty[]
  exemptWeeks: ExemptWeek[]
}

const props = defineProps<Props>()
const { getInitials } = useInitials()

// ── State ────────────────────────────────────────────────────────────────────
const search = ref('')
const activeTab = ref('all')
const showProofModal = ref(false)
const showRejectModal = ref(false)
const showExemptModal = ref(false)
const proofModalRef = ref<HTMLElement | null>(null)
const rejectModalRef = ref<HTMLElement | null>(null)
const exemptModalRef = ref<HTMLElement | null>(null)
const selectedPenalty = ref<Penalty | null>(null)
const rejectReason = ref('')
const rejectError = ref('')
const rejectProcessing = ref(false)
const exemptProcessing = ref(false)
const exemptErrors = ref<Record<string, string>>({})

const exemptForm = reactive({ week_start: '', reason: '' })

// ── Computed ──────────────────────────────────────────────────────────────────
const counts = computed(() => ({
  pending:  props.penalties.filter(p => p.status === 'pending').length,
  uploaded: props.penalties.filter(p => p.status === 'uploaded').length,
  approved: props.penalties.filter(p => p.status === 'approved').length,
  rejected: props.penalties.filter(p => p.status === 'rejected').length,
  total: props.penalties.filter(p => p.status !== 'exempted').length,
}))

const statusTabs = computed(() => [
  { value: 'all',      label: 'Semua',       count: counts.value.total },
  { value: 'pending',  label: 'Menunggu',    count: counts.value.pending },
  { value: 'uploaded', label: 'Perlu Review', count: counts.value.uploaded },
  { value: 'approved', label: 'Disetujui',   count: counts.value.approved },
  { value: 'rejected', label: 'Ditolak',     count: counts.value.rejected },
])

const filteredPenalties = computed(() => {
  let result = props.penalties.filter(p => p.status !== 'exempted')
  if (activeTab.value !== 'all') {
    result = result.filter(p => p.status === activeTab.value)
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    result = result.filter(p => p.user.name.toLowerCase().includes(q))
  }
  return result
})

// ── Helpers ───────────────────────────────────────────────────────────────────
const formatWeek = (weekStart: string) => {
  const start = new Date(weekStart)
  const end = new Date(weekStart)
  end.setDate(end.getDate() + 5)
  const fmt = (d: Date) => d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  return `${fmt(start)} – ${fmt(end)}`
}

const formatMinutes = (minutes: number) => {
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return `${h}j ${m}m`
}

const statusLabel = (status: string) => ({
  pending:  'Menunggu',
  uploaded: 'Perlu Review',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}[status] ?? status)

const statusClass = (status: string) => ({
  pending:  'status--yellow',
  uploaded: 'status--blue',
  approved: 'status--green',
  rejected: 'status--red',
}[status] ?? '')

// ── Actions ───────────────────────────────────────────────────────────────────
const manualRefresh = () => router.reload()
const scrollIntoView = async (elRef: Ref<HTMLElement | null>) => {
  await nextTick()

  elRef.value?.scrollIntoView({
    behavior: 'smooth',
    block: 'center',
  })
}

const openProofModal = (penalty: Penalty) => {
  selectedPenalty.value = penalty
  showProofModal.value = true

  scrollIntoView(proofModalRef)
}
const closeProofModal = () => {
  showProofModal.value = false
  selectedPenalty.value = null
}

const openRejectModal = (penalty: Penalty) => {
  selectedPenalty.value = penalty
  rejectReason.value = ''
  rejectError.value = ''
  showRejectModal.value = true
  showProofModal.value = false

  scrollIntoView(rejectModalRef)
}
const closeRejectModal = () => {
  showRejectModal.value = false
  rejectReason.value = ''
}

const openExemptModal =  () => { 
  showExemptModal.value = true 

  scrollIntoView(exemptModalRef)
}
const closeExemptModal = () => { showExemptModal.value = false }

const approvePenalty = (penalty: Penalty) => {
  if (!confirm(`Setujui bukti lari dari ${penalty.user.name}?`)) return
  router.post(`/admin/penalties/${penalty.id}/approve`, {}, {
    onSuccess: () => { closeProofModal(); router.reload() },
    onError: () => alert('Gagal menyetujui bukti.'),
  })
}

const submitReject = () => {
  if (!rejectReason.value.trim()) {
    rejectError.value = 'Alasan penolakan wajib diisi.'
    return
  }
  rejectProcessing.value = true
  router.post(`/admin/penalties/${selectedPenalty.value!.id}/reject`, {
    reason: rejectReason.value,
  }, {
    onSuccess: () => { closeRejectModal(); router.reload() },
    onError: () => alert('Gagal menolak bukti.'),
    onFinish: () => { rejectProcessing.value = false },
  })
}

const addExemptWeek = () => {
  if (!exemptForm.week_start) {
    exemptErrors.value = { week_start: 'Tanggal wajib diisi.' }
    return
  }
  exemptProcessing.value = true
  router.post('/admin/penalty-exempt-weeks', exemptForm, {
    onSuccess: () => {
      exemptForm.week_start = ''
      exemptForm.reason = ''
      exemptErrors.value = {}
      router.reload()
    },
    onError: (err) => { exemptErrors.value = err },
    onFinish: () => { exemptProcessing.value = false },
  })
}

const deleteExemptWeek = (id: number) => {
  if (!confirm('Hapus minggu libur ini?')) return
  router.delete(`/admin/penalty-exempt-weeks/${id}`, {
    onSuccess: () => router.reload(),
  })
}
</script>

<style scoped>
/* ── Page ── */
.page-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  background: var(--penalty-bg-page);
  animation: pageIn 0.45s cubic-bezier(.22, 1, .36, 1) both;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Header ── */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.5rem;
  flex-wrap: wrap;
}
.header-info h2 {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--penalty-text-primary);
  margin-bottom: 0.25rem;
}
.header-subtitle { color: var(--penalty-text-secondary); font-size: 0.9rem; }
.header-actions { display: flex; gap: 0.75rem; }

/* ── Buttons ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.65rem 1.25rem;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.875rem;
  border: none;
  transition: background 0.2s, transform 0.2s;
}
.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.icon { width: 16px; height: 16px; }

.btn-primary { background: var(--penalty-blue-bg); color: var(--penalty-text-primary); }
.dark .btn-primary { background: var(--penalty-blue-bg); color: var(--penalty-blue-text); }
.btn-primary:hover { background: var(--penalty-blue-border); transform: translateY(-1px); }

.btn-secondary {
  background: var(--penalty-blue-bg);
  color: var(--penalty-blue-text);
  border: 1px solid var(--penalty-blue-border);
}
.btn-secondary:hover {
  background: var(--penalty-blue-border);
  color: var(--penalty-blue-text);
  transform: translateY(-1px);
}

.btn-warning {
  background: var(--penalty-yellow-bg);
  color: var(--penalty-yellow-text);
  border: 1px solid var(--penalty-yellow-border);
}
.btn-warning:hover {
  background: var(--penalty-yellow-border);
  color: var(--penalty-yellow-text);
  transform: translateY(-1px);
}
.btn-success { background: var(--penalty-green-bg); color: var(--penalty-green-text); border: 1px solid var(--penalty-green-border); }
.btn-success:hover { background: var(--penalty-green-border); transform: translateY(-1px); }

.btn-danger { background: var(--penalty-red-bg); color: var(--penalty-red-text); border: 1px solid var(--penalty-red-border); }
.btn-danger:hover { background: var(--penalty-red-border); transform: translateY(-1px); }

/* ── Stats ── */
.stats-mini {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 1rem;
}
.stat-card-mini {
  background: var(--penalty-bg-surface);
  border: 1px solid var(--penalty-border);
  border-radius: 12px;
  padding: 1.1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.9rem;
  animation: cardIn 0.45s cubic-bezier(.22, 1, .36, 1) calc(var(--i, 0) * 60ms) both;
  transition: border-color 0.25s, transform 0.25s;
}
.stat-card-mini:hover { border-color: var(--penalty-text-muted); transform: translateY(-2px); }
@keyframes cardIn {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}
.stat-icon-mini {
  width: 38px; height: 38px;
  background: var(--penalty-bg-page);
  border: 1px solid var(--penalty-border);
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--penalty-text-secondary);
  flex-shrink: 0;
}
.stat-icon-mini--neutral { background: var(--penalty-bg-page); border-color: var(--penalty-border); color: var(--penalty-text-secondary); }
.stat-icon-mini--yellow { background: var(--penalty-yellow-bg); border-color: var(--penalty-yellow-border); color: var(--penalty-yellow-text); }
.stat-icon-mini--blue   { background: var(--penalty-blue-bg); border-color: var(--penalty-blue-border); color: var(--penalty-blue-text); }
.stat-icon-mini--green  { background: var(--penalty-green-bg); border-color: var(--penalty-green-border); color: var(--penalty-green-text); }
.stat-icon-mini--red    { background: var(--penalty-red-bg); border-color: var(--penalty-red-border); color: var(--penalty-red-text); }

.stat-content-mini h4 { font-size: 1.4rem; font-weight: 700; color: var(--penalty-text-primary); line-height: 1.2; }
.stat-content-mini p { color: var(--penalty-text-secondary); font-size: 0.8rem; }

/* ── Filter Bar ── */
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
  background: var(--penalty-bg-surface);
  border: 1px solid var(--penalty-border);
  border-radius: 12px;
  padding: 0.85rem 1.25rem;
}
.filter-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
  min-width: 200px;
}
.filter-icon { width: 16px; height: 16px; color: var(--penalty-text-secondary); flex-shrink: 0; }
.filter-input {
  background: transparent;
  border: none;
  outline: none;
  color: var(--penalty-text-primary);
  font-size: 0.875rem;
  width: 100%;
}
.filter-input::placeholder { color: var(--penalty-text-secondary); }
.filter-tabs { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.filter-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.85rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 500;
  border: 1px solid var(--penalty-border);
  background: var(--penalty-bg-page);
  color: var(--penalty-text-secondary);
  cursor: pointer;
  transition: all 0.18s;
}
.filter-tab:hover { border-color: var(--penalty-text-muted); color: var(--penalty-text-primary); }
.filter-tab--active { 
    background: var(--penalty-blue-text);      /* menggunakan biru dari status */
    color: white; 
    border-color: var(--penalty-blue-text); 
}
.dark .filter-tab--active { 
    background: var(--penalty-blue-text); 
    color: #0a0a0a;       /* teks gelap di dark mode agar kontras */
    border-color: var(--penalty-blue-text); 
}
.tab-count {
  background: rgba(255,255,255,0.2);
  padding: 0.05rem 0.45rem;
  border-radius: 99px;
  font-size: 0.75rem;
}
.filter-tab--active .tab-count { background: rgba(255,255,255,0.25); }
.dark .filter-tab--active .tab-count { background: rgba(0,0,0,0.15); }

/* ── Table ── */
.table-container {
  background: var(--penalty-bg-surface);
  border: 1px solid var(--penalty-border);
  border-radius: 14px;
  overflow: hidden;
}
.table-wrapper { overflow-x: auto; scrollbar-width: thin; }
.data-table { width: 100%; min-width: 820px; border-collapse: collapse; }
.data-table thead tr { background: var(--penalty-table-header-bg); border-bottom: 1px solid var(--penalty-border); }
.data-table th { padding: 0.9rem 1.25rem; text-align: left; }
.th-content {
  display: flex; align-items: center; gap: 0.4rem;
  color: var(--penalty-table-header-text); font-size: 0.8rem; font-weight: 600;
  text-transform: uppercase; letter-spacing: 0.04em;
}
.th-icon { width: 14px; height: 14px; color: var(--penalty-blue-border); }
.dark .th-icon { color: #52525b; }
.data-table td { padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--penalty-bg-page); vertical-align: middle; }
.table-row {
  transition: background 0.18s;
  animation: rowIn 0.4s cubic-bezier(.22, 1, .36, 1) calc(var(--row-i, 0) * 30ms) both;
}
.table-row:hover { background: var(--penalty-table-row-hover); }
@keyframes rowIn {
  from { opacity: 0; transform: translateX(-6px); }
  to   { opacity: 1; transform: translateX(0); }
}
.row-id { color: var(--penalty-text-muted); font-size: 0.85rem; font-weight: 500; }
.user-info { display: flex; align-items: center; gap: 0.65rem; }
.user-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: var(--penalty-avatar-bg);
  border: 1px solid var(--penalty-avatar-border);
  display: flex; align-items: center; justify-content: center;
  color: var(--penalty-avatar-text); font-weight: 600; font-size: 0.75rem; flex-shrink: 0;
}
.username { color: var(--penalty-text-primary); font-size: 0.875rem; font-weight: 500; }
.week-text { color: var(--penalty-text-secondary); font-size: 0.85rem; }
.minutes-text { color: var(--penalty-text-primary); font-size: 0.875rem; font-weight: 500; }

/* ── Status Badge ── */
.status-badge {
  display: inline-flex; align-items: center;
  padding: 0.2rem 0.65rem; border-radius: 9999px;
  font-size: 0.775rem; font-weight: 500; border: 1px solid transparent;
}
.status--yellow { background: var(--penalty-yellow-bg); border-color: var(--penalty-yellow-border); color: var(--penalty-yellow-text); }
.status--blue   { background: var(--penalty-blue-bg); border-color: var(--penalty-blue-border); color: var(--penalty-blue-text); }
.status--green  { background: var(--penalty-green-bg); border-color: var(--penalty-green-border); color: var(--penalty-green-text); }
.status--red    { background: var(--penalty-red-bg); border-color: var(--penalty-red-border); color: var(--penalty-red-text); }

/* ── Proof button ── */
.btn-proof {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.3rem 0.75rem; border-radius: 7px;
  background: var(--penalty-blue-bg); color: var(--penalty-blue-text);
  border: 1px solid var(--penalty-blue-border);
  font-size: 0.8rem; font-weight: 500; cursor: pointer;
  transition: all 0.18s;
}
.btn-proof:hover { background: var(--penalty-blue-border); color: var(--penalty-text-primary); transform: translateY(-1px); }
.btn-proof .icon { width: 14px; height: 14px; }
.no-proof, .no-action { color: var(--penalty-text-muted); font-size: 0.85rem; }

/* ── Action buttons ── */
.actions { display: flex; gap: 0.4rem; }
.btn-action {
    width: 32px; height: 32px;
    border: 1px solid var(--penalty-action-btn-border);
    border-radius: 8px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: background 0.2s, border-color 0.2s, transform 0.2s;
    background: var(--penalty-action-btn-bg);
    color: var(--penalty-action-btn-text);
}
.btn-action .icon { width: 14px; height: 14px; }
.btn-approve:hover { 
    background: var(--penalty-green-bg); 
    color: var(--penalty-green-text); 
    border-color: var(--penalty-green-border); 
    transform: translateY(-1px); 
}
.btn-reject:hover { 
    background: var(--penalty-red-bg); 
    color: var(--penalty-red-text); 
    border-color: var(--penalty-red-border); 
    transform: translateY(-1px); 
}
.btn-delete:hover { 
    background: var(--penalty-red-bg); 
    color: var(--penalty-red-text); 
    border-color: var(--penalty-red-border); 
    transform: translateY(-1px); 
}
/* ── Empty state ── */
.empty-state {
  display: flex; flex-direction: column; align-items: center;
  padding: 4rem 2rem; text-align: center; gap: 0.75rem;
}
.empty-icon { color: var(--penalty-text-muted); width: 56px; height: 56px; }
.empty-state h3 { font-size: 1.25rem; font-weight: 600; color: var(--penalty-text-primary); }
.empty-state p { color: var(--penalty-text-secondary); font-size: 0.9rem; max-width: 360px; }

/* ── Modal ── */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; animation: overlayIn 0.2s ease both;
}
@keyframes overlayIn { from { opacity: 0; } to { opacity: 1; } }

.modal-content {
  background: var(--penalty-bg-surface);
  border: 1px solid var(--penalty-border);
  border-radius: 16px; min-width: 480px; max-width: 90vw;
  max-height: 90vh; overflow-y: auto; scrollbar-width: thin;
  animation: modalIn 0.3s cubic-bezier(.22, 1, .36, 1) both;
  box-shadow: 0 20px 60px rgba(0,0,0,0.4);
}
.modal-content--wide { min-width: 600px; }
@keyframes modalIn {
  from { opacity: 0; transform: scale(0.95) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.25rem 1.75rem; border-bottom: 1px solid var(--penalty-border-dark);
  background: var(--penalty-bg-elevated);
}
.modal-header--red { border-bottom-color: var(--penalty-red-border); }
.modal-header h3 { font-size: 1.1rem; font-weight: 600; color: var(--penalty-text-primary); }
.modal-close {
  background: none; border: none; color: var(--penalty-text-secondary);
  cursor: pointer; padding: 0.4rem; border-radius: 7px;
  transition: background 0.2s, color 0.2s;
}
.modal-close:hover { background: var(--penalty-border-dark); color: var(--penalty-text-primary); }
.modal-form { padding: 1.75rem; }
.form-group { margin-bottom: 1.25rem; }
.form-group label { display: block; margin-bottom: 0.45rem; color: var(--penalty-text-secondary); font-size: 0.825rem; font-weight: 500; }
.form-control {
  width: 100%; padding: 0.65rem 0.9rem;
  background: var(--penalty-bg-elevated);
  border: 1px solid var(--penalty-border);
  border-radius: 8px;
  color: var(--penalty-text-primary);
  font-size: 0.875rem;
  transition: border-color 0.2s; box-sizing: border-box;
  resize: vertical;
}
.form-control:focus { outline: none; border-color: var(--penalty-text-muted); box-shadow: 0 0 0 3px rgba(82,82,91,0.2); }
.form-control::placeholder { color: var(--penalty-text-muted); }
.error { color: var(--penalty-red-text); font-size: 0.775rem; margin-top: 0.35rem; display: block; }
.form-actions {
  display: flex; gap: 0.75rem; justify-content: flex-end;
  margin-top: 1.75rem; padding-top: 1.25rem;
  border-top: 1px solid var(--penalty-border-dark);
}

/* ── Proof Modal body ── */
.proof-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem; }
.proof-meta { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
.proof-meta-item { display: flex; align-items: center; gap: 0.4rem; color: var(--penalty-text-secondary); font-size: 0.875rem; }
.proof-meta-item .icon { width: 14px; height: 14px; }
.proof-image-wrapper {
  border-radius: 10px; overflow: hidden;
  border: 1px solid var(--penalty-modal-border);
  background: var(--penalty-bg-elevated);
}
.proof-image { width: 100%; max-height: 480px; object-fit: contain; display: block; }
.rejection-note {
  display: flex; align-items: flex-start; gap: 0.5rem;
  padding: 0.75rem 1rem; border-radius: 8px;
  background: var(--penalty-red-bg);
  border: 1px solid var(--penalty-red-border);
  color: var(--penalty-red-text);
  font-size: 0.85rem;
}
.rejection-note .icon { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
.proof-actions {
  display: flex; gap: 0.75rem; justify-content: flex-end;
  padding-top: 1rem; border-top: 1px solid var(--penalty-border-dark);
}

/* ── Reject Modal ── */
.reject-desc { color: var(--penalty-text-secondary); font-size: 0.875rem; margin-bottom: 1.25rem; line-height: 1.6; }
.reject-desc strong { color: var(--penalty-text-primary); }

/* ── Exempt Modal ── */
.exempt-desc { color: var(--penalty-text-secondary); font-size: 0.875rem; margin-bottom: 1.5rem; line-height: 1.6; }
.exempt-form { display: flex; flex-direction: column; gap: 0; margin-bottom: 1.5rem; }
.exempt-list { border-top: 1px solid var(--penalty-border-dark); padding-top: 1.25rem; }
.exempt-list-title { font-size: 0.875rem; font-weight: 600; color: var(--penalty-text-primary); margin-bottom: 0.75rem; }
.exempt-empty { color: var(--penalty-text-muted); font-size: 0.85rem; }
.exempt-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.75rem 0; border-bottom: 1px solid var(--penalty-border-dark);
}
.exempt-item:last-child { border-bottom: none; }
.exempt-item-info { display: flex; align-items: center; gap: 0.65rem; color: var(--penalty-text-secondary); }
.exempt-item-info .icon { width: 16px; height: 16px; color: var(--penalty-yellow-text); flex-shrink: 0; }
.exempt-week { display: block; color: var(--penalty-text-primary); font-size: 0.875rem; font-weight: 500; }
.exempt-reason { display: block; color: var(--penalty-text-secondary); font-size: 0.8rem; margin-top: 0.1rem; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .page-header { flex-direction: column; }
  .header-actions { width: 100%; }
  .stats-mini { grid-template-columns: repeat(2, 1fr); }
  .filter-bar { flex-direction: column; align-items: stretch; }
  .modal-content { min-width: 90vw; margin: 1rem; }
  .modal-content--wide { min-width: 90vw; }
}
</style>