<template>

  <Head title="User Management" />

  <AppLayout>
    <div class="crud-container px-6 py-8">

      <!-- Header -->
      <div class="crud-header">
        <div class="header-info">
          <h2>Daftar User</h2>
          <p class="header-subtitle">{{ users.length }} user ditemukan</p>
        </div>
        <div class="crud-actions">
          <button class="btn btn-primary" @click="() => { openCreateModal(); onCollapsed?.() }">
            <Plus class="icon" />
            Tambah User
          </button>
          <button class="btn btn-secondary" @click="manualRefresh">
            <RefreshCw class="icon" />
            Refresh
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-mini">
        <div class="stat-card-mini" style="--i:0">
          <div class="stat-icon-mini">
            <Users />
          </div>
          <div class="stat-content-mini">
            <h4>{{ users.length }}</h4>
            <p>Total Users</p>
          </div>
        </div>
        <div class="stat-card-mini" style="--i:1">
          <div class="stat-icon-mini stat-icon-mini--blue">
            <UserCheck />
          </div>
          <div class="stat-content-mini">
            <h4>{{ users.filter(u => u.is_admin).length }}</h4>
            <p>Admin</p>
          </div>
        </div>
        <div class="stat-card-mini" style="--i:2">
          <div class="stat-icon-mini stat-icon-mini--mint">
            <UserIcon />
          </div>
          <div class="stat-content-mini">
            <h4>{{ users.filter(u => !u.is_admin).length }}</h4>
            <p>User</p>
          </div>
        </div>
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" ref="createModalRef" class="modal-overlay" @click="closeCreateModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Tambah User Baru</h3>
            <button class="modal-close" @click="closeCreateModal">
              <X />
            </button>
          </div>
          <form @submit.prevent="createUser" class="modal-form">
            <div class="form-group">
              <label>Name:</label>
              <input v-model="createForm.name" type="text" required class="form-control" placeholder="Masukkan nama" />
              <span v-if="createErrors.name" class="error">{{ createErrors.name }}</span>
            </div>
            <div class="form-group">
              <label>Email:</label>
              <input v-model="createForm.email" type="email" required class="form-control" placeholder="Masukkan email" />
              <span v-if="createErrors.email" class="error">{{ createErrors.email }}</span>
            </div>
            <div class="form-group">
              <label>Password:</label>
              <input v-model="createForm.password" type="password" required class="form-control" placeholder="Masukkan password" />
              <span v-if="createErrors.password" class="error">{{ createErrors.password }}</span>
            </div>
            <div class="form-group">
              <label>Konfirmasi Password:</label>
              <input v-model="createForm.password_confirmation" type="password" required class="form-control" placeholder="Konfirmasi password" />
            </div>
            <div class="form-group">
              <label>Nomor HP (Opsional):</label>
              <input v-model="createForm.phone" type="text" required class="form-control" placeholder="62895xxx" />
              <span v-if="createErrors.phone" class="error">{{ createErrors.phone }}</span>
            </div>
            <div class="form-group">
              <label>Admin:</label>
              <select v-model="createForm.is_admin" required class="form-control">
                <option :value="true">Iya</option>
                <option :value="false">Tidak</option>
              </select>
              <span v-if="createErrors.is_admin" class="error">{{ createErrors.is_admin }}</span>
            </div>
            <div class="form-group">
              <label>Role:</label>
              <input v-model="createForm.role" type="text" required class="form-control" placeholder="Masukkan role" />
              <span v-if="createErrors.role" class="error">{{ createErrors.role }}</span>
            </div>
            <div class="form-actions">
              <button type="button" @click="closeCreateModal" class="btn btn-secondary">Batal</button>
              <button type="submit" :disabled="createProcessing" class="btn btn-primary">
                {{ createProcessing ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="showEditModal" ref="editModalRef" class="modal-overlay" @click="closeEditModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Edit User</h3>
            <button class="modal-close" @click="closeEditModal">
              <X />
            </button>
          </div>
          <form @submit.prevent="updateUser" class="modal-form">
            <div class="form-group">
              <label>Name:</label>
              <input v-model="editForm.name" type="text" required class="form-control" />
              <span v-if="editErrors.name" class="error">{{ editErrors.name }}</span>
            </div>
            <div class="form-group">
              <label>Email:</label>
              <input v-model="editForm.email" type="email" required class="form-control" />
              <span v-if="editErrors.email" class="error">{{ editErrors.email }}</span>
            </div>
            <div class="form-group">
              <label>Password Baru (kosongkan jika tidak ingin mengubah):</label>
              <input v-model="editForm.password" type="password" class="form-control" placeholder="Masukkan password baru" />
              <span v-if="editErrors.password" class="error">{{ editErrors.password }}</span>
            </div>
            <div class="form-group" v-if="editForm.password">
              <label>Konfirmasi Password Baru:</label>
              <input v-model="editForm.password_confirmation" type="password" class="form-control" placeholder="Konfirmasi password baru" />
            </div>
            <div class="form-group">
              <label>Nomor HP:</label>
              <input v-model="editForm.phone" type="text" class="form-control" placeholder="62895xxx" />
              <span v-if="editErrors.phone" class="error">{{ editErrors.phone }}</span>
            </div>
            <div class="form-group">
              <label>Admin:</label>
              <select v-model="editForm.is_admin" required class="form-control">
                <option :value="true">Iya</option>
                <option :value="false">Tidak</option>
              </select>
              <span v-if="editErrors.is_admin" class="error">{{ editErrors.is_admin }}</span>
            </div>
            <div class="form-group">
              <label>Role:</label>
              <input v-model="editForm.role" type="text" required class="form-control" />
              <span v-if="editErrors.role" class="error">{{ editErrors.role }}</span>
            </div>
            <div class="form-actions">
              <button type="button" @click="closeEditModal" class="btn btn-secondary">Batal</button>
              <button type="submit" :disabled="editProcessing" class="btn btn-primary">
                {{ editProcessing ? 'Menyimpan...' : 'Update' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Table -->
      <div class="table-container">
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th><div class="th-content"><Hash class="th-icon" />ID</div></th>
                <th><div class="th-content"><UserIcon class="th-icon" />Name</div></th>
                <th><div class="th-content"><Mail class="th-icon" />Email</div></th>
                <th><div class="th-content"><Shield class="th-icon" />Admin</div></th>
                <th><div class="th-content"><UserCheck class="th-icon" />Role</div></th>
                <th><div class="th-content"><Smartphone class="th-icon" />Device</div></th>
                <th><div class="th-content"><Calendar class="th-icon" />Dibuat</div></th>
                <th><div class="th-content"><Settings class="th-icon" />Aksi</div></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(user, index) in users" :key="user.id" class="table-row" :style="{ '--row-i': index }">
                <td><span class="user-id">#{{ user.id }}</span></td>
                <td>
                  <div class="user-info">
                    <div class="user-avatar">{{ getInitials(user.name) }}</div>
                    <span class="username">{{ user.name }}</span>
                  </div>
                </td>
                <td><span class="email">{{ user.email }}</span></td>
                <td>
                  <span class="badge-admin" :class="user.is_admin ? 'badge--yes' : 'badge--no'">
                    {{ user.is_admin ? 'Admin' : 'Bukan Admin' }}
                  </span>
                </td>
                <td>
                  <span class="badge-role">{{ user.role }}</span>
                </td>
                <td>
                  <span class="badge-admin" :class="user.device_id ? 'badge--yes' : 'badge--no'">
                    {{ user.device_id ? 'Terhubung' : 'Belum terhubung' }}
                  </span>
                </td>
                <td><span class="date">{{ formatDate(user.created_at) }}</span></td>
                <td>
                  <div class="actions">
                    <button @click="openEditModal(user)" class="btn-action btn-edit" title="Edit user">
                      <Edit class="icon" />
                    </button>
                    <button @click="resetDevice(user)" class="btn-action btn-reset" title="Reset Device">
                      <RotateCcw class="icon" />
                    </button>
                    <button @click="deleteUser(user)" class="btn-action btn-delete" title="Hapus user">
                      <Trash class="icon" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="users.length === 0" class="empty-state">
            <div class="empty-icon">
              <Users />
            </div>
            <h3>Tidak ada user</h3>
            <p>Belum ada user yang terdaftar dalam sistem.</p>
            <button class="btn btn-primary" @click="openCreateModal">
              <Plus class="icon" />
              Tambah User Pertama
            </button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, nextTick } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import {
  Plus, RefreshCw, Edit, Trash, X,
  Users, UserCheck, User as UserIcon,
  Hash, Mail, Shield, Calendar, Settings,
  RotateCcw, Smartphone
} from 'lucide-vue-next'
import type { User } from '@/types'
import { useInitials } from '@/composables/useInitials'
import AppLayout from '@/layouts/AppLayout.vue'

interface Props {
  users: User[]
  errors?: Record<string, string>
  onCollapsed?: () => void
}

const props = defineProps<Props>()
const emit = defineEmits<{ refresh: [] }>()

const { getInitials } = useInitials()
const showCreateModal = ref(false)
const showEditModal = ref(false)
const createModalRef = ref<HTMLElement | null>(null)
const editModalRef = ref<HTMLElement | null>(null)
const createProcessing = ref(false)
const editProcessing = ref(false)
const createErrors = ref<Record<string, string>>({})
const editErrors = ref<Record<string, string>>({})
const editingUserId = ref<number | null>(null)

const createForm = reactive({
  name: '', email: '', password: '', password_confirmation: '',
  phone: '', is_admin: false, role: '',
})

const editForm = reactive({
  name: '', email: '', password: '', password_confirmation: '',
  phone: '', role: '', is_admin: false,
})

const openCreateModal = async () => { 
  resetCreateForm(); showCreateModal.value = true

  await nextTick()

  createModalRef.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    })
 }
const closeCreateModal = () => { showCreateModal.value = false; resetCreateForm(); createErrors.value = {} }

const openEditModal = async (user: User) => {
  props.onCollapsed?.()
  resetEditForm()
  editForm.name = user.name
  editForm.email = user.email
  editForm.phone = user.phone
  editForm.is_admin = user.is_admin
  editForm.role = user.role
  editingUserId.value = user.id
  showEditModal.value = true

  await nextTick()

  editModalRef.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    })
}
const closeEditModal = () => { showEditModal.value = false; resetEditForm(); editErrors.value = {} }

const resetCreateForm = () => {
  createForm.name = ''; createForm.email = ''; createForm.password = ''
  createForm.password_confirmation = ''; createForm.role = ''; createForm.is_admin = false; createForm.phone = ''
}
const resetEditForm = () => {
  editForm.name = ''; editForm.email = ''; editForm.password = ''
  editForm.password_confirmation = ''; editForm.role = ''; editForm.is_admin = false; editForm.phone = ''
  editingUserId.value = null
}

const createUser = () => {
  createProcessing.value = true; createErrors.value = {}
  router.post('/admin/users', createForm, {
    onSuccess: () => { closeCreateModal(); createProcessing.value = false; emit('refresh') },
    onError: (err) => { createErrors.value = err; createProcessing.value = false }
  })
}

const updateUser = () => {
  if (!editingUserId.value) return
  editProcessing.value = true; editErrors.value = {}
  const data: any = {
    name: editForm.name, email: editForm.email, phone: editForm.phone,
    is_admin: editForm.is_admin, role: editForm.role,
  }
  if (editForm.password) {
    data.password = editForm.password
    data.password_confirmation = editForm.password_confirmation
  }
  router.put(`/admin/users/${editingUserId.value}`, data, {
    onSuccess: () => { closeEditModal(); editProcessing.value = false; emit('refresh') },
    onError: (err) => { editErrors.value = err; editProcessing.value = false }
  })
}

const resetDevice = (user: User) => {
  if (!confirm(`Apakah Anda yakin ingin mereset device user "${user.name}"?`)) return
  router.post(`/admin/users/${user.id}/reset-device`, {}, {
    onSuccess: () => emit('refresh'),
    onError: (err) => alert('Gagal mereset device user: ' + Object.values(err).join(', '))
  })
}

const deleteUser = (user: User) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus user "${user.name}"?`)) return
  router.delete(`/admin/users/${user.id}`, {
    onSuccess: () => emit('refresh'),
    onError: (err) => alert('Gagal menghapus user: ' + Object.values(err).join(', '))
  })
}

const manualRefresh = () => emit('refresh')

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<style scoped>

/* ── Page load ── */
.crud-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  background: #f4f4f5;
  animation: pageIn 0.45s cubic-bezier(.22, 1, .36, 1) both;
}

.dark .crud-container {
  background: #0a0a0a;
}

@keyframes pageIn {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Header ── */
.crud-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.header-info h2 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0a0a0a;
  margin-bottom: 0.25rem;
}

.dark .header-info h2 {
  color: #ffffff;
}

.header-subtitle {
  color: #71717a;
  font-size: 0.9rem;
}

.crud-actions {
  display: flex;
  gap: 0.75rem;
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
  transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
}

.btn-primary {
  background: #BAD5FF;
  color: #0a0a0a;
}

.dark .btn-primary {
  background: #ffffff;
  color: #09090b;
}

.btn-primary:hover {
  background: #a8c8ff;
  transform: translateY(-1px);
}

.dark .btn-primary:hover {
  background: #e4e4e7;
}

.btn-secondary {
  background: #FFF0BA;
  color: #92680a;
  border: 1px solid #ffe88a;
}

.dark .btn-secondary {
  background: #1c1c1f;
  color: #a1a1aa;
  border: 1px solid #27272a;
}

.btn-secondary:hover {
  background: #ffe88a;
  transform: translateY(-1px);
}

.dark .btn-secondary:hover {
  background: #27272a;
  color: #ffffff;
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

/* ── Stat cards ── */
.stats-mini {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}

.stat-card-mini {
  background: #ffffff;
  border: 1px solid #e4e4e7;
  border-radius: 12px;
  padding: 1.1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.9rem;
  animation: cardIn 0.45s cubic-bezier(.22, 1, .36, 1) calc(var(--i, 0) * 60ms) both;
  transition: border-color 0.25s, transform 0.25s;
  cursor: default;
}

.dark .stat-card-mini {
  background: #09090b;
  border: 1px solid #27272a;
}

.stat-card-mini:hover {
  border-color: #a1a1aa;
  transform: translateY(-2px);
}

.dark .stat-card-mini:hover {
  border-color: #52525b;
}

@keyframes cardIn {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

.stat-icon-mini {
  width: 38px;
  height: 38px;
  background: #f4f4f5;
  border: 1px solid #e4e4e7;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #71717a;
  flex-shrink: 0;
  transition: background 0.25s, color 0.25s;
}

.dark .stat-icon-mini {
  background: #1c1c1f;
  border: 1px solid #27272a;
}

/* Aksen warna per stat card */
.stat-icon-mini--blue {
  background: #EEF5FF;
  border-color: #BAD5FF;
  color: #3b6ea8;
}

.stat-icon-mini--mint {
  background: #EDFFF7;
  border-color: #BAFFE5;
  color: #1a7a52;
}

.stat-card-mini:hover .stat-icon-mini {
  background: #e4e4e7;
  color: #0a0a0a;
}

.dark .stat-icon-mini--blue, .dark .stat-icon-mini--mint {
  color: #71717a;
}

.dark .stat-card-mini:hover .stat-icon-mini {
  background: #27272a;
  color: #ffffff; 
}

.stat-card-mini:hover .stat-icon-mini--blue {
  background: #BAD5FF;
  color: #0a0a0a;
}

.stat-card-mini:hover .stat-icon-mini--mint {
  background: #BAFFE5;
  color: #0a0a0a;
}

.stat-content-mini h4 {
  font-size: 1.4rem;
  font-weight: 700;
  color: #0a0a0a;
  line-height: 1.2;
}

.dark .stat-content-mini h4 {
  color: #ffffff;
}


.stat-content-mini p {
  color: #71717a;
  font-size: 0.8rem;
}

/* ── Modal overlay ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: overlayIn 0.2s ease both;
}

@keyframes overlayIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

.modal-content {
  background: #ffffff;
  border: 1px solid #e4e4e7;
  border-radius: 16px;
  min-width: 480px;
  max-width: 90vw;
  max-height: 90vh;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #d4d4d8 #f4f4f5;
  animation: modalIn 0.3s cubic-bezier(.22, 1, .36, 1) both;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12), 0 4px 16px rgba(0, 0, 0, 0.06);
}

.dark .modal-content {
  background: #09090b;
  border: 1px solid #27272a; 
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.95) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.75rem;
  border-bottom: 1px solid #e4e4e7;
  background: #fafafa;
}

.dark .modal-header {
  background: #09090b;
  border-bottom: 1px solid #1c1c1f;
}

.modal-header h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #0a0a0a;
}

.dark .modal-header h3 {
  color: #ffffff;
}

.modal-close {
  background: none;
  border: none;
  color: #a1a1aa;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, color 0.2s;
}

.dark .modal-close {
  color: #71717a;
}

.modal-close:hover {
  background: #f4f4f5;
  color: #0a0a0a;
}

.dark .modal-close:hover {
  background: #1c1c1f;
  color: #ffffff;
}

.modal-form {
  padding: 1.75rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.45rem;
  color: #3f3f46;
  font-size: 0.825rem;
  font-weight: 500;
}

.dark .form-group label {
  color: #a1a1aa;
}

.form-control {
  width: 100%;
  padding: 0.65rem 0.9rem;
  background: #fafafa;
  border: 1px solid #e4e4e7;
  border-radius: 8px;
  color: #0a0a0a;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.dark .form-control {
  background: #111113;
  border: 1px solid #27272a;
  color: #ffffff;
}

.form-control:focus {
  outline: none;
  border-color: #BAD5FF;
  box-shadow: 0 0 0 3px rgba(186, 213, 255, 0.35);
}

.dark .form-control:focus {
  border-color: #52525b;
  box-shadow: 0 0 0 3px rgba(82, 82, 91, 0.2);
}

.form-control::placeholder {
  color: #a1a1aa;
}

.dark .form-control::placeholder {
  color: #52525b;
}

.form-control option {
  background: #ffffff;
  color: #0a0a0a;
}

.dark .form-control option {
  background: #09090b;
  color: #ffffff;
}

.error {
  color: #dc2626;
  font-size: 0.775rem;
  margin-top: 0.35rem;
  display: block;
}

.dark .error {
  color: #f87171;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.75rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e4e4e7;
}

.dark .form-actions {
  border-top: 1px solid #1c1c1f;
}

/* ── Table ── */
.table-container {
  background: #ffffff;
  border: 1px solid #e4e4e7;
  border-radius: 14px;
  overflow: hidden;
}

.dark .table-container {
  background: #09090b;
  border: 1px solid #27272a;
}

.table-wrapper {
  overflow-x: auto;
  scrollbar-width: thin;
  scrollbar-color: #d4d4d8 #f4f4f5;
}

.dark .table-wrapper {
  scrollbar-color: #3f3f46 #09090b;
}

.table-wrapper::-webkit-scrollbar {
  height: 6px;
}

.table-wrapper::-webkit-scrollbar-thumb {
  background: #d4d4d8;
  border-radius: 6px;
}

.dark .table-wrapper::-webkit-scrollbar-thumb {
  background: #3f3f46;
}

.table-wrapper::-webkit-scrollbar-track {
  background: #f4f4f5;
}

.dark .table-wrapper::-webkit-scrollbar-track {
  background: #09090b;
}

.data-table {
  width: 100%;
  min-width: 860px;
  border-collapse: collapse;
}

.data-table thead tr {
  background: #EEF5FF;
  border-bottom: 1px solid #e4e4e7;
}

.dark .data-table thead tr {
  background: #111113;
  border-bottom: 1px solid #1c1c1f;
}

.data-table th {
  padding: 0.9rem 1.25rem;
  text-align: left;
}

.th-content {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: #3b6ea8;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.dark .th-content {
  color: #a1a1aa;
}

.th-icon {
  width: 14px;
  height: 14px;
  color: #7aaee0;
}

.dark .th-icon {
  color: #52525b;
}

.data-table td {
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid #f4f4f5;
  vertical-align: middle;
}

.dark .data-table td {
  border-bottom: 1px solid #111113;
}

/* Row animation */
.table-row {
  transition: background 0.18s;
  animation: rowIn 0.4s cubic-bezier(.22, 1, .36, 1) calc(var(--row-i, 0) * 30ms) both;
}

.table-row:hover {
  background: #f8f8f9;
}

.dark .table-row:hover {
  background: #0d0d10;
}

.table-row:last-child td {
  border-bottom: none;
}

@keyframes rowIn {
  from { opacity: 0; transform: translateX(-6px); }
  to   { opacity: 1; transform: translateX(0); }
}

/* Cells */
.user-id {
  color: #a1a1aa;
  font-size: 0.85rem;
  font-weight: 500;
}

.dark .user-id {
  color: #52525b;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #f0f6ff;
  border: 1px solid #BAD5FF;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3b6ea8;
  font-weight: 600;
  font-size: 0.75rem;
  flex-shrink: 0;
  transition: background 0.2s, border-color 0.2s;
}

.dark .user-avatar {
  background: #1c1c1f;
  border: 1px solid #3f3f46;
  color: #d4d4d8;
}

.table-row:hover .user-avatar {
  background: #BAD5FF;
  border-color: #a8c8ff;
  color: #0a0a0a;
}

.dark .table-row:hover .user-avatar {
  background: #27272a;
  border-color: #52525b;
  color: #d4d4d8;
}

.username {
  color: #0a0a0a;
  font-size: 0.875rem;
  font-weight: 500;
}

.dark .username {
  color: #ffffff;  
}
.email {
  color: #71717a;
  font-size: 0.875rem;
}

.dark .email {
  color: #a1a1aa;
}

.date {
  color: #a1a1aa;
  font-size: 0.825rem;
}

.dark .date {
  color: #71717a;
}

/* ── Badge Admin ── */
.badge-admin {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.775rem;
  font-weight: 500;
  border: 1px solid transparent;
}

.badge--yes {
  background: #f0fdf4;
  color: #16a34a;
  border-color: #bbf7d0;
}

.dark .badge--yes {
  background: #052e16;
  color: #4ade80;
  border-color: #166534;
}

.badge--no {
  background: #f4f4f5;
  color: #71717a;
  border-color: #e4e4e7;
}

.dark .badge--no {
  background: #111113;
  color: #71717a;
  border-color: #27272a;
}

/* ── Badge Role ── */
.badge-role {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.775rem;
  font-weight: 500;
  background: #EDFFF7;
  color: #1a7a52;
  border: 1px solid #BAFFE5;
  text-transform: capitalize;
}

.dark .badge-role {
  background: #052e16;
  color: #86efac;
  border: 1px solid #166534;
}

/* ── Action buttons ── */
.actions {
  display: flex;
  gap: 0.4rem;
}

.btn-action {
  width: 32px;
  height: 32px;
  border: 1px solid transparent;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, border-color 0.2s, transform 0.2s;
}

.btn-action .icon {
  width: 14px;
  height: 14px;
}

.btn-edit {
  background: #f4f4f5;
  color: #71717a;
  border-color: #e4e4e7;
}

.dark .btn-edit {
  background: #1c1c1f;
  color: #a1a1aa;
  border-color: #27272a;
}

.btn-edit:hover {
  background: #EEF5FF;
  color: #3b6ea8;
  border-color: #BAD5FF;
  transform: translateY(-1px);
}

.dark .btn-edit:hover {
  background: rgba(59, 110, 168, 0.1);
  color: #55a0f4;
  border-color: #3b6ea8;

}

.btn-reset {
  background: #f4f4f5;
  color: #71717a;
  border-color: #e4e4e7;
}

.dark .btn-reset {
  background: #1c1c1f;
  color: #a1a1aa;
  border-color: #27272a;
}

.btn-reset:hover {
  background: #fffbeb;
  color: #d97706;
  border-color: #fde68a;
  transform: translateY(-1px);
}

.dark .btn-reset:hover {
  background: rgba(250, 204, 21, 0.1);
  color: #facc15;
  border-color: rgba(250, 204, 21, 0.4);
}

.btn-delete {
  background: #f4f4f5;
  color: #71717a;
  border-color: #e4e4e7;
}

.dark .btn-delete {
  background: #1c1c1f;
  color: #71717a;
  border-color: #27272a;
}

.btn-delete:hover {
  background: #fef2f2;
  color: #dc2626;
  border-color: #fecaca;
  transform: translateY(-1px);
}

.dark .btn-delete:hover {
  background: #3f0d0d;
  color: #f87171;
  border-color: #7f1d1d;
}

/* ── Empty state ── */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4rem 2rem;
  text-align: center;
  gap: 0.75rem;
  animation: fadeIn 0.4s ease both;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

.empty-icon {
  color: #d4d4d8;
  width: 56px;
  height: 56px;
}

.dark .empty-icon {
  color: #27272a;
}

.empty-state h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #0a0a0a;
}

.dark .empty-state h3 {
  color: #ffffff;
}

.empty-state p {
  color: #71717a;
  font-size: 0.9rem;
  max-width: 360px;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .crud-header { flex-direction: column; }
  .crud-actions { width: 100%; }
  .stats-mini { grid-template-columns: 1fr; }
  .modal-content { min-width: 90vw; margin: 1rem; }
}
</style>