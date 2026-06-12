<template>

  <Head title="Hukuman Saya" />
  <AppLayout>
    <div class="page-container px-6 py-8">

      <!-- Header -->
      <div class="page-header">
        <div class="header-info">
          <h2>Aktivitas Pengembangan</h2>
          <p class="header-subtitle">
            {{ counts.total }} aktivitas lari tercatat
          </p>
        </div>
        <!-- <button class="btn btn-secondary" @click="manualRefresh">
          <RefreshCw class="icon" />
          Refresh
        </button> -->
      </div>

      <!-- Stats -->
      <div class="stats-mini">
        <div v-for="(stat, index) in statCards" :key="stat.value" class="stat-card-mini" :style="{ '--i': index }"
          :class="{ 'filter-tab--active': activeTab === stat.value }" @click="activeTab = stat.value">
          <div class="stat-icon-mini" :class="stat.iconClass">
            <component :is="stat.icon" />
          </div>

          <div class="stat-content-mini">
            <h4>{{ stat.count }}</h4>
            <p>{{ stat.label }}</p>
          </div>
        </div>
      </div>

      <!-- Info Banner -->
      <div class="info-banner">
        <div class="info-banner-icon">
          <Info />
        </div>
        <div class="info-banner-text">
          <strong>Panduan Penyelesaian:</strong>
          Upload screenshot hasil lari 10 km dari aplikasi Strava dan pap lari.
          Maksimal ukuran file <strong>1 MB</strong>. Aktivitas akan ditinjau oleh admin sebelum dinyatakan selesai.
        </div>
      </div>

      <!-- Penalty Cards -->
      <div v-if="filteredPenalties.length !== 0" class="penalty-list">
        <div v-for="(penalty, index) in filteredPenalties" :key="penalty.id" class="penalty-card"
          :style="{ '--card-i': index }" :class="cardClass(penalty.status)">
          <!-- Card Header -->
          <div class="card-header">
            <div class="card-header-left">
              <div class="card-icon" :class="iconClass(penalty.status)">
                <component :is="statusIcon(penalty.status)" />
              </div>
              <div>
                <p class="card-week">{{ formatWeek(penalty.weekly_report.week_start) }}</p>
                <p class="card-minutes">{{ formatMinutes(penalty.weekly_report.total_minutes) }} kerja minggu itu</p>
              </div>
            </div>
            <span class="status-badge" :class="statusBadgeClass(penalty.status)">
              {{ statusLabel(penalty.status) }}
            </span>
          </div>

          <!-- Rejection reason -->
          <div v-if="penalty.status === 'rejected' && penalty.rejection_reason" class="rejection-note">
            <XCircle class="rn-icon" />
            <div>
              <p class="rn-title">Alasan Penolakan:</p>
              <p class="rn-reason">{{ penalty.rejection_reason }}</p>
            </div>
          </div>

          <!-- Uploaded proof preview -->
          <div v-if="penalty.proof_path && penalty.status !== 'rejected'" class="proof-preview">
            <img :src="`/storage/${penalty.proof_path}`" alt="Bukti lari" class="proof-thumb" />
            <div class="proof-preview-info">
              <CheckCircle v-if="penalty.status === 'approved'" class="proof-check proof-check--green" />
              <Clock v-else class="proof-check proof-check--blue" />
              <span>{{ penalty.status === 'approved' ? 'Bukti disetujui' : 'Bukti sedang direview' }}</span>
            </div>
          </div>

          <!-- Upload area -->
          <div v-if="penalty.status === 'pending' || penalty.status === 'rejected'" class="upload-area"
            :class="{ 'upload-area--dragover': isDragging[penalty.id] }"
            @dragover.prevent="isDragging[penalty.id] = true" @dragleave="isDragging[penalty.id] = false"
            @drop.prevent="onDrop($event, penalty)" @click="triggerFileInput(penalty.id)">
            <input :ref="el => { fileInputs[penalty.id] = el as HTMLInputElement }" type="file" accept="image/*"
              class="file-input-hidden" @change="onFileChange($event, penalty)" />

            <div v-if="!selectedFiles[penalty.id]" class="upload-placeholder">
              <ImageUp class="upload-icon" />
              <p class="upload-title">Klik atau drag foto di sini</p>
              <p class="upload-hint">PNG, JPG, JPEG — maks. 1 MB</p>
            </div>

            <div v-else class="upload-preview">
              <img :src="previewUrls[penalty.id]" alt="Preview" class="preview-img" />
              <div class="preview-info">
                <p class="preview-name">{{ selectedFiles[penalty.id]?.name }}</p>
                <p class="preview-size">{{ formatSize(selectedFiles[penalty.id]?.size ?? 0) }}</p>
                <button class="btn-remove" @click.stop="removeFile(penalty.id)">
                  <X class="icon" /> Ganti foto
                </button>
              </div>
            </div>
          </div>

          <!-- Error file -->
          <p v-if="fileErrors[penalty.id]" class="file-error">
            <XCircle class="icon" /> {{ fileErrors[penalty.id] }}
          </p>

          <!-- Upload button -->
          <div v-if="penalty.status === 'pending' || penalty.status === 'rejected'" class="card-footer">
            <button class="btn btn-upload" :disabled="!selectedFiles[penalty.id] || uploadingId === penalty.id"
              @click="submitUpload(penalty)">
              <Upload class="icon" />
              {{ uploadingId === penalty.id ? 'Mengupload...' : 'Upload ScreenShot' }}
            </button>
          </div>

        </div>
      </div>

      <!-- Empty state -->
      <div v-else-if="counts.total > 0" class="empty-state">
        <div class="empty-icon">
          <CheckCircle />
        </div>
        <h3>Tidak ada aktivitas pengembangan</h3>
        <p>Tidak ada aktivitas pengembangan yang cocok dengan filter saat ini.</p>
      </div>
       
      <div v-else class="empty-state">
        <div class="empty-icon">
          <CheckCircle />
        </div>
        <h3>Luar biasa! 🎉</h3>
        <p>Saat ini tidak ada aktivitas pengembangan yang perlu diselesaikan. Terus pertahankan konsistensi jam kerja
          mingguanmu!</p>
      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import {
  RefreshCw, X, Upload, CheckCircle, XCircle,
  AlertTriangle, Clock, Info, ImageUp,
} from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { Penalty } from '@/types'


interface Props {
  penalties: Penalty[]
}

const props = defineProps<Props>()

// ── State ─────────────────────────────────────────────────────────────────────
const selectedFiles = reactive<Record<number, File | null>>({})
const previewUrls = reactive<Record<number, string>>({})
const fileErrors = reactive<Record<number, string>>({})
const isDragging = reactive<Record<number, boolean>>({})
const fileInputs = reactive<Record<number, HTMLInputElement | null>>({})
const uploadingId = ref<number | null>(null)
const activeTab = ref('pending')

// ── Computed ──────────────────────────────────────────────────────────────────
const counts = computed(() => ({
  pending: props.penalties.filter(p => p.status === 'pending').length,
  uploaded: props.penalties.filter(p => p.status === 'uploaded').length,
  approved: props.penalties.filter(p => p.status === 'approved').length,
  rejected: props.penalties.filter(p => p.status === 'rejected').length,
  total: props.penalties.filter(p => p.status !== 'exempted').length,
}))

const statCards = computed(() => [
  {
    value: 'all',
    count: counts.value.total,
    label: 'Total Aktivitas',
    icon: AlertTriangle,
    iconClass: 'stat-icon-mini--neutral',
  },
  {
    value: 'pending',
    count: counts.value.pending,
    label: 'Belum Upload',
    icon: Clock,
    iconClass: 'stat-icon-mini--yellow',
  },
  {
    value: 'uploaded',
    count: counts.value.uploaded,
    label: 'Menunggu Review',
    icon: Upload,
    iconClass: 'stat-icon-mini--blue',
  },
  {
    value: 'approved',
    count: counts.value.approved,
    label: 'Selesai',
    icon: CheckCircle,
    iconClass: 'stat-icon-mini--green',
  },
  {
    value: 'rejected',
    count: counts.value.rejected,
    label: 'Perlu Perbaikan',
    icon: XCircle,
    iconClass: 'stat-icon-mini--red',
  },
])

const filteredPenalties = computed(() => {
  let result = props.penalties.filter(p => p.status !== 'exempted')
  if (activeTab.value !== 'all') {
    result = result.filter(p => p.status === activeTab.value)
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
  const m = Math.floor(minutes % 60)
  return `${h} jam ${m} menit`
}

const formatSize = (bytes: number) => {
  return (bytes / 1024 / 1024).toFixed(2) + ' MB'
}

const statusLabel = (status: string) => ({
  pending: 'Belum Upload',
  uploaded: 'Menunggu Review',
  approved: 'Selesai',
  rejected: 'Ditolak',
}[status] ?? status)

const statusIcon = (status: string) => ({
  pending: Clock,
  uploaded: Upload,
  approved: CheckCircle,
  rejected: XCircle,
}[status] ?? AlertTriangle)

const statusBadgeClass = (status: string) => ({
  pending: 'status--yellow',
  uploaded: 'status--blue',
  approved: 'status--green',
  rejected: 'status--red',
}[status] ?? '')

const cardClass = (status: string) => ({
  pending: 'card--yellow',
  uploaded: 'card--blue',
  approved: 'card--green',
  rejected: 'card--red',
}[status] ?? '')

const iconClass = (status: string) => ({
  pending: 'card-icon--yellow',
  uploaded: 'card-icon--blue',
  approved: 'card-icon--green',
  rejected: 'card-icon--red',
}[status] ?? '')

// ── File Handling ─────────────────────────────────────────────────────────────
const triggerFileInput = (id: number) => {
  fileInputs[id]?.click()
}

const validateAndSet = (file: File, penaltyId: number) => {
  fileErrors[penaltyId] = ''

  if (!file.type.startsWith('image/')) {
    fileErrors[penaltyId] = 'File harus berupa gambar (PNG, JPG, JPEG).'
    return
  }

  if (file.size > 1 * 1024 * 1024) {
    fileErrors[penaltyId] = 'Ukuran file melebihi 1 MB.'
    return
  }

  selectedFiles[penaltyId] = file
  previewUrls[penaltyId] = URL.createObjectURL(file)
}

const onFileChange = (event: Event, penalty: Penalty) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) validateAndSet(file, penalty.id)
}

const onDrop = (event: DragEvent, penalty: Penalty) => {
  isDragging[penalty.id] = false
  const file = event.dataTransfer?.files?.[0]
  if (file) validateAndSet(file, penalty.id)
}

const removeFile = (id: number) => {
  selectedFiles[id] = null
  previewUrls[id] = ''
  fileErrors[id] = ''
  if (fileInputs[id]) fileInputs[id]!.value = ''
}

// ── Submit ────────────────────────────────────────────────────────────────────
const submitUpload = (penalty: Penalty) => {
  const file = selectedFiles[penalty.id]
  if (!file) return

  uploadingId.value = penalty.id

  const formData = new FormData()
  formData.append('proof', file)
  formData.append('_method', 'POST')

  router.post(`/penalties/${penalty.id}/upload`, formData, {
    onSuccess: () => {
      removeFile(penalty.id)
      router.reload()
    },
    onError: (err) => {
      fileErrors[penalty.id] = Object.values(err).join(', ')
    },
    onFinish: () => {
      uploadingId.value = null
    },
  })
}

// const manualRefresh = () => router.reload()
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
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
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

.header-subtitle {
  color: var(--penalty-text-secondary);
  font-size: 0.9rem;
}

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

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.icon {
  width: 16px;
  height: 16px;
}

.btn-secondary {
  background: #1c1c1f;
  color: #a1a1aa;
  border: 1px solid #27272a;
}

.btn-secondary:hover {
  background: #27272a;
  color: #ffffff;
  transform: translateY(-1px);
}

.btn-upload {
  background: var(--penalty-green-bg);
  color: var(--penalty-green-text);
  border: 1px solid var(--penalty-green-border);
  width: 100%;
  justify-content: center;
}

.btn-upload:hover:not(:disabled) {
  background: var(--penalty-green-border);
  transform: translateY(-1px);
}

/* ── Stats ── */
.stats-mini {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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

.stat-card-mini:hover {
  border-color: var(--penalty-text-muted);
  transform: translateY(-2px);
}

.filter-tab--active {
  border: 3px solid var(--penalty-blue-text);
}

@keyframes cardIn {
  from {
    opacity: 0;
    transform: translateY(14px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stat-icon-mini {
  width: 38px;
  height: 38px;
  background: var(--penalty-bg-page);
  border: 1px solid var(--penalty-border);
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--penalty-text-secondary);
  flex-shrink: 0;
}

.stat-icon-mini--neutral {
  background: var(--penalty-bg-page);
  border-color: var(--penalty-border);
  color: var(--penalty-text-secondary);
}

.stat-icon-mini--yellow {
  background: var(--penalty-yellow-bg);
  border-color: var(--penalty-yellow-border);
  color: var(--penalty-yellow-text);
}

.stat-icon-mini--blue {
  background: var(--penalty-blue-bg);
  border-color: var(--penalty-blue-border);
  color: var(--penalty-blue-text);
}

.stat-icon-mini--green {
  background: var(--penalty-green-bg);
  border-color: var(--penalty-green-border);
  color: var(--penalty-green-text);
}

.stat-icon-mini--red {
  background: var(--penalty-red-bg);
  border-color: var(--penalty-red-border);
  color: var(--penalty-red-text);
}

.stat-content-mini h4 {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--penalty-text-primary);
  line-height: 1.2;
}

.stat-content-mini p {
  color: var(--penalty-text-secondary);
  font-size: 0.8rem;
}

/* ── Info Banner ── */
.info-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-radius: 10px;
  background: var(--penalty-blue-bg);
  border: 1px solid var(--penalty-blue-border);
  color: var(--penalty-blue-text);
  font-size: 0.875rem;
  line-height: 1.6;
}

.info-banner-icon {
  flex-shrink: 0;
  margin-top: 1px;
}

.info-banner-icon svg {
  width: 18px;
  height: 18px;
}

.info-banner-text strong {
  color: var(--penalty-text-primary);
}

/* ── Penalty Cards ── */
.penalty-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.penalty-card {
  background: var(--penalty-bg-surface);
  border: 1px solid var(--penalty-border);
  border-radius: 14px;
  overflow: hidden;
  animation: cardIn 0.45s cubic-bezier(.22, 1, .36, 1) calc(var(--card-i, 0) * 80ms) both;
  transition: border-color 0.2s;
}

.card--yellow {
  border-left: 3px solid var(--penalty-yellow-border);
}

.card--blue {
  border-left: 3px solid var(--penalty-blue-border);
}

.card--green {
  border-left: 3px solid var(--penalty-green-border);
}

.card--red {
  border-left: 3px solid var(--penalty-red-border);
}

/* ── Card Header ── */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.card-header-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.card-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.card-icon svg {
  width: 20px;
  height: 20px;
}

.card-icon--yellow {
  background: var(--penalty-yellow-bg);
  color: var(--penalty-yellow-text);
}

.card-icon--blue {
  background: var(--penalty-blue-bg);
  color: var(--penalty-blue-text);
}

.card-icon--green {
  background: var(--penalty-green-bg);
  color: var(--penalty-green-text);
}

.card-icon--red {
  background: var(--penalty-red-bg);
  color: var(--penalty-red-text);
}

.card-week {
  color: var(--penalty-text-primary);
  font-weight: 600;
  font-size: 0.95rem;
  margin-bottom: 0.2rem;
}

.card-minutes {
  color: var(--penalty-text-secondary);
  font-size: 0.8rem;
}

/* ── Status Badge ── */
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.775rem;
  font-weight: 500;
  border: 1px solid transparent;
  white-space: nowrap;
}

.status--yellow {
  background: var(--penalty-yellow-bg);
  color: var(--penalty-yellow-text);
  border-color: var(--penalty-yellow-border);
}

.status--blue {
  background: var(--penalty-blue-bg);
  color: var(--penalty-blue-text);
  border-color: var(--penalty-blue-border);
}

.status--green {
  background: var(--penalty-green-bg);
  color: var(--penalty-green-text);
  border-color: var(--penalty-green-border);
}

.status--red {
  background: var(--penalty-red-bg);
  color: var(--penalty-red-text);
  border-color: var(--penalty-red-border);
}

/* ── Rejection Note ── */
.rejection-note {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  margin: 0 1.5rem 1rem;
  padding: 0.85rem 1rem;
  background: var(--penalty-red-bg);
  border: 1px solid var(--penalty-red-border);
  border-radius: 8px;
  color: var(--penalty-red-text);
}

.rn-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  margin-top: 1px;
}

.rn-title {
  font-size: 0.8rem;
  font-weight: 600;
  margin-bottom: 0.2rem;
}

.rn-reason {
  font-size: 0.825rem;
  color: #f87171;
  line-height: 1.5;
}

/* ── Proof Preview ── */
.proof-preview {
  margin: 0 1.5rem 1rem;
  border: 1px solid var(--penalty-border);
  border-radius: 10px;
  overflow: hidden;
}

.proof-thumb {
  width: 100%;
  max-height: 200px;
  object-fit: cover;
  display: block;
}

.proof-preview-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1rem;
  background: var(--penalty-bg-page);
  color: var(--penalty-text-secondary);
  font-size: 0.825rem;
}

.proof-check {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.proof-check--green {
  color: var(--penalty-green-text);
}

.proof-check--blue {
  color: var(--penalty-blue-text);
}

/* ── Upload Area ── */
.upload-area {
  margin: 0 1.5rem 1rem;
  border: 2px dashed var(--penalty-border);
  border-radius: 10px;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
  overflow: hidden;
}

.upload-area:hover {
  border-color: var(--penalty-text-muted);
  background: var(--penalty-bg-page);
}

.upload-area--dragover {
  border-color: var(--penalty-green-text);
  background: var(--penalty-green-bg);
}

.file-input-hidden {
  display: none;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2rem;
  text-align: center;
}

.upload-icon {
  width: 36px;
  height: 36px;
  color: var(--penalty-text-muted);
}

.upload-title {
  color: var(--penalty-text-muted);
  font-size: 0.875rem;
  font-weight: 500;
}

.upload-hint {
  color: var(--penalty-text-secondary);
  font-size: 0.8rem;
}

.upload-preview {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
}

.preview-img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--penalty-border);
  flex-shrink: 0;
}

.preview-info {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.preview-name {
  color: var(--penalty-text-primary);
  font-size: 0.85rem;
  font-weight: 500;
}

.preview-size {
  color: var(--penalty-text-secondary);
  font-size: 0.8rem;
}

.btn-remove {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.3rem 0.65rem;
  border-radius: 6px;
  background: var(--penalty-red-bg);
  color: var(--penalty-red-text);
  border: 1px solid var(--penalty-red-border);
  font-size: 0.775rem;
  cursor: pointer;
  transition: background 0.2s;
  width: fit-content;
}

.btn-remove:hover {
  background: var(--penalty-red-border);
}

.btn-remove .icon {
  width: 12px;
  height: 12px;
}

.file-error {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin: -0.5rem 1.5rem 1rem;
  color: var(--penalty-red-text);
  font-size: 0.825rem;
}

.file-error .icon {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

/* ── Card Footer ── */
.card-footer {
  padding: 0 1.5rem 1.5rem;
}

/* ── Empty State ── */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4rem 2rem;
  text-align: center;
  gap: 0.75rem;
  background: var(--penalty-bg-surface);
  border: 1px solid var(--penalty-border);
  border-radius: 14px;
}

.empty-icon {
  color: var(--penalty-green-text);
  width: 56px;
  height: 56px;
}

.empty-state h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--penalty-text-primary);
}

.empty-state p {
  color: var(--penalty-text-secondary);
  font-size: 0.9rem;
  max-width: 360px;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
  }

  .stats-mini {
    grid-template-columns: repeat(2, 1fr);
  }

  .upload-preview {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>