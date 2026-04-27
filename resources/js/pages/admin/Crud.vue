<template>
  <Head title="User Management" />

  <AppLayout>
    <div class="crud-container">
      <!-- Header Section -->
      <div class="crud-header">
        <div class="header-info">
          <h2>Daftar User</h2>
          <p class="header-subtitle">{{ users.length }} user ditemukan</p>
        </div>
        <div class="crud-actions">
          <button 
            class="btn btn-primary" 
            @click="() => {
              openCreateModal();
              onCollapsed?.();
            }"
          >
            <Plus class="icon" />
            Tambah User
          </button>
          <button 
            class="btn btn-secondary" 
            @click="manualRefresh"
          >
            <RefreshCw class="icon" />
            Refresh
          </button>
        </div>
      </div>
  
      <!-- Stats Cards -->
      <div class="stats-mini">
        <div class="stat-card-mini">
          <div class="stat-icon-mini">
            <Users />
          </div>
          <div class="stat-content-mini">
            <h4>{{ users.length }}</h4>
            <p>Total Users</p>
          </div>
        </div>
        <div class="stat-card-mini">
          <div class="stat-icon-mini">
            <UserCheck />
          </div>
          <div class="stat-content-mini">
            <h4>{{ users.filter(u => u.is_admin).length }}</h4>
            <!-- <h4>{{ users.filter(u => u.admin_role === 'admin').length }}</h4> -->
            <p>Admin</p>
          </div>
        </div>
        <div class="stat-card-mini">
          <div class="stat-icon-mini">
            <UserIcon />
          </div>
          <div class="stat-content-mini">
            <h4>{{ users.filter(u => !u.is_admin).length }}</h4>
            <!-- <h4>{{ users.filter(u => u.admin_role === 'user').length }}</h4> -->
            <p>User</p>
          </div>
        </div>
      </div>
  
      <!-- Create Modal -->
      <div v-if="showCreateModal" class="modal-overlay" @click="closeCreateModal">
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
              <input 
                v-model="createForm.name" 
                type="text" 
                required 
                class="form-control"
                placeholder="Masukkan nama"
              />
              <span v-if="createErrors.name" class="error">{{ createErrors.name }}</span>
            </div>
            
            <div class="form-group">
              <label>Email:</label>
              <input 
                v-model="createForm.email" 
                type="email" 
                required 
                class="form-control"
                placeholder="Masukkan email"
              />
              <span v-if="createErrors.email" class="error">{{ createErrors.email }}</span>
            </div>
            
            <div class="form-group">
              <label>Password:</label>
              <input 
                v-model="createForm.password" 
                type="password" 
                required 
                class="form-control"
                placeholder="Masukkan password"
              />
              <span v-if="createErrors.password" class="error">{{ createErrors.password }}</span>
            </div>
  
            <div class="form-group">
              <label>Konfirmasi Password:</label>
              <input 
                v-model="createForm.password_confirmation" 
                type="password" 
                required 
                class="form-control"
                placeholder="Konfirmasi password"
              />
            </div>
  
            <!-- <div class="form-group">
              <label>Tanggal Lahir:</label>
              <input 
                v-model="createForm.birthdate" 
                type="date" 
                required 
                class="form-control"
              />
              <span v-if="createErrors.birthdate" class="error">{{ createErrors.birthdate }}</span>
            </div> -->
  
            <!-- <div class="form-group">
              <label>Berita:</label>
              <select v-model="createForm.newsletter" required class="form-control">
                <option :value=true>Iya</option>
                <option :value=false>Tidak</option>
              </select>
              <span v-if="createErrors.newsletter" class="error">{{ createErrors.newsletter }}</span>
            </div> -->
  
            <div class="form-group">
              <label>Admin:</label>
              <select v-model="createForm.is_admin" required class="form-control">
                <option :value=true>Iya</option>
                <option :value=false>Tidak</option>
              </select>
              <span v-if="createErrors.is_admin" class="error">{{ createErrors.is_admin }}</span>
            </div>

            <div class="form-group">
              <label>Role:</label>
              <input 
                v-model="createForm.role" 
                type="text" 
                required 
                class="form-control"
                placeholder="Masukkan role"
              />
              <span v-if="createErrors.role" class="error">{{ createErrors.role }}</span>
            </div>
  
            <!-- <div class="checkbox-group">
              <label>Berita:</label>
              <input 
                v-model="createForm.newsletter" 
                type="checkbox" 
                class="checkbox-control"
              />
              <span v-if="createErrors.newsletter" class="error">{{ createErrors.newsletter }}</span>
            </div>
  
            <div class="checkbox-group">
              <label>Admin:</label>
              <input 
                v-model="createForm.is_admin" 
                type="checkbox" 
                class="checkbox-control"
              />
              <span v-if="createErrors.is_admin" class="error">{{ createErrors.is_admin }}</span>
            </div> -->
  
            <!-- <div class="form-group">
              <label>Role:</label>
              <select v-model="createForm.admin_role" required class="form-control">
                <option value="">Pilih Role</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
              </select>
              <span v-if="createErrors.admin_role" class="error">{{ createErrors.admin_role }}</span>
            </div> -->
            
            <div class="form-actions">
              <button type="button" @click="closeCreateModal" class="btn btn-secondary">
                Batal
              </button>
              <button type="submit" :disabled="createProcessing" class="btn btn-primary">
                {{ createProcessing ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
  
      <!-- Edit Modal -->
      <div v-if="showEditModal" class="modal-overlay" @click="closeEditModal">
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
              <input 
                v-model="editForm.name" 
                type="text" 
                required 
                class="form-control"
              />
              <span v-if="editErrors.name" class="error">{{ editErrors.name }}</span>
            </div>
            
            <div class="form-group">
              <label>Email:</label>
              <input 
                v-model="editForm.email" 
                type="email" 
                required 
                class="form-control"
              />
              <span v-if="editErrors.email" class="error">{{ editErrors.email }}</span>
            </div>
  
            <div class="form-group">
              <label>Password Baru (kosongkan jika tidak ingin mengubah):</label>
              <input 
                v-model="editForm.password" 
                type="password" 
                class="form-control"
                placeholder="Masukkan password baru"
              />
              <span v-if="editErrors.password" class="error">{{ editErrors.password }}</span>
            </div>
  
            <div class="form-group" v-if="editForm.password">
              <label>Konfirmasi Password Baru:</label>
              <input 
                v-model="editForm.password_confirmation" 
                type="password" 
                class="form-control"
                placeholder="Konfirmasi password baru"
              />
            </div>
  
            <!-- <div class="form-group">
              <label>Tanggal Lahir:</label>
              <input 
                v-model="editForm.birthdate" 
                type="date" 
                required 
                class="form-control"
              />
              <span v-if="editErrors.birthdate" class="error">{{ editErrors.birthdate }}</span>
            </div> -->
  
            <!-- <div class="form-group">
              <label>Berita:</label>
              <select v-model="editForm.newsletter" required class="form-control">
                <option :value=true>Iya</option>
                <option :value=false>Tidak</option>
              </select>
              <span v-if="editErrors.newsletter" class="error">{{ editErrors.newsletter }}</span>
            </div> -->
  
            <div class="form-group">
              <label>Admin:</label>
              <select v-model="editForm.is_admin" required class="form-control">
                <option :value=true>Iya</option>
                <option :value=false>Tidak</option>
              </select>
              <span v-if="editErrors.is_admin" class="error">{{ editErrors.is_admin }}</span>
            </div>

            <div class="form-group">
              <label>Role:</label>
              <input 
                v-model="editForm.role" 
                type="text" 
                required 
                class="form-control"
              />
              <span v-if="editErrors.role" class="error">{{ editErrors.role }}</span>
            </div>
            
            <div class="form-actions">
              <button type="button" @click="closeEditModal" class="btn btn-secondary">
                Batal
              </button>
              <button type="submit" :disabled="editProcessing" class="btn btn-primary">
                {{ editProcessing ? 'Menyimpan...' : 'Update' }}
              </button>
            </div>
          </form>
        </div>
      </div>
  
      <!-- Users Table -->
      <div class="table-container">
        <!-- <div class="table-header">
          <h3>Daftar User</h3>
          <div class="table-info">
            <span>{{ users.length }} user ditemukan</span>
          </div>
        </div> -->
        
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>
                  <div class="th-content">
                    <Hash class="th-icon" />
                    ID
                  </div>
                </th>
                <th>
                  <div class="th-content">
                    <UserIcon class="th-icon" />
                    Name
                  </div>
                </th>
                <th>
                  <div class="th-content">
                    <Mail class="th-icon" />
                    Email
                  </div>
                </th>
                <th>
                  <div class="th-content">
                    <Shield class="th-icon" />
                    Admin
                  </div>
                </th>
                <th>
                  <div class="th-content">
                    <UserCheck class="th-icon" />
                    Role
                  </div>
                </th>
                <!-- <th>
                  <div class="th-content">
                    <Calendar class="th-icon" />
                    Birthdate
                  </div>
                </th> -->
                <th>
                  <div class="th-content">
                    <Calendar class="th-icon" />
                    Dibuat
                  </div>
                </th>
                <!-- <th>
                  <div class="th-content">
                    <Newspaper class="th-icon" />
                    Berita
                  </div>
                </th> -->
                <th>
                  <div class="th-content">
                    <Settings class="th-icon" />
                    Aksi
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" class="table-row">
                <td>
                  <div class="cell-content">
                    <span class="user-id">#{{ user.id }}</span>
                  </div>
                </td>
                <td>
                  <div class="cell-content">
                    <div class="user-info">
                      <div class="user-avatar">
                        {{ getInitials(user.name) }}
                      </div>
                      <div class="user-details">
                        <span class="username">{{ user.name }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="cell-content">
                    <span class="email">{{ user.email }}</span>
                  </div>
                </td>
                <td>
                  <div class="cell-content">
                    <span class="role-badge" :class="'role-' + user.role">
                      {{ user.is_admin }}
                      <!-- <span v-if="user.admin_assigned_at">
                        , {{ formatDate(user.admin_assigned_at) }}
                      </span> -->
                    </span>
                  </div>
                </td>
                <td>
                  <div class="cell-content">
                    <span class="role-badge" :class="'role-' + user.role">
                      {{ user.role }}
                    </span>
                  </div>
                </td>
                <!-- <td>
                  <div class="cell-content">
                    <span class="date">{{ formatDate(user.birthdate) }}</span>
                  </div>
                </td> -->
                <td>
                  <div class="cell-content">
                    <span class="date">{{ formatDate(user.created_at) }}</span>
                  </div>
                </td>
                <!-- <td>
                  <div class="cell-content">
                    <span class="role-badge" :class="'role-' + user.role">
                      {{ user.newsletter }}
                    </span>
                  </div>
                </td> -->
                <td>
                  <div class="cell-content">
                    <div class="actions">
                      <button 
                        @click="openEditModal(user)" 
                        class="btn-action btn-edit"
                        title="Edit user"
                      >
                        <Edit class="icon" />
                      </button>
                      <button 
                        @click="deleteUser(user)" 
                        class="btn-action btn-delete"
                        title="Hapus user"
                      >
                        <Trash class="icon" />
                      </button>
                    </div>
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
import { ref, reactive, onMounted } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { 
  Plus,
  RefreshCw,
  Edit,
  Trash,
  X,
  Users,
  UserCheck,
  User as UserIcon,
  Hash,
  Mail,
  Shield,
  Calendar,
  Settings,
  Newspaper,
} from 'lucide-vue-next'
import type { User } from '@/types'
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';

interface Props {
  users: User[]
  errors?: Record<string, string>
  onCollapsed?: () => void
}

const props = defineProps<Props>()

// Define emits
const emit = defineEmits<{
  refresh: []
}>()

// State management
const { getInitials } = useInitials()
const showCreateModal = ref(false)
const showEditModal = ref(false)
const createProcessing = ref(false)
const editProcessing = ref(false)
const createErrors = ref<Record<string, string>>({})
const editErrors = ref<Record<string, string>>({})
const editingUserId = ref<number | null>(null)
// const adminRoles = ['kanade', 'mafuyu', 'ena', 'mizuki']

// Form data
// const createForm = useForm({
//   username: '',
//   email: '',
//   password: '',
//   password_confirmation: '',
//   birthdate: '',
//   newsletter: false,
//   is_admin: false,
// })

const createForm = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  is_admin: false,
  role: '',
})

const editForm = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
  is_admin: false,
})

// Methods
const openCreateModal = () => {
  resetCreateForm()
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
  resetCreateForm()
  createErrors.value = {}
}

const openEditModal = (user: User) => {
  props.onCollapsed?.()
  resetEditForm()
  editForm.name = user.name
  editForm.email = user.email
  editForm.is_admin = user.is_admin
  editForm.role = user.role
  editingUserId.value = user.id
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  resetEditForm()
  editErrors.value = {}
}

const resetCreateForm = () => {
  createForm.name = ''
  createForm.email = ''
  createForm.password = ''
  createForm.password_confirmation = ''
  createForm.role = ''
  createForm.is_admin = false
}

const resetEditForm = () => {
  editForm.name = ''
  editForm.email = ''
  editForm.password = ''
  editForm.password_confirmation = ''
  // editForm.birthdate = ''
  // editForm.newsletter = false
  editForm.role = ''
  editForm.is_admin = false
  editingUserId.value = null
}

const createUser = () => {
  createProcessing.value = true
  createErrors.value = {}
  
  router.post('/admin/users', createForm, {
    onSuccess: () => {
      closeCreateModal()
      createProcessing.value = false
      emit('refresh')
    },
    onError: (err) => {
      createErrors.value = err
      createProcessing.value = false
    }
  })
}

const updateUser = () => {
  if (!editingUserId.value) return
  
  editProcessing.value = true
  editErrors.value = {}
  
  const updateData: any = {
    name: editForm.name,
    email: editForm.email,
    // birthdate: editForm.birthdate,
    // newsletter: editForm.newsletter,
    is_admin: editForm.is_admin,
    role: editForm.role,
  }

  // Only include password if it's provided
  if (editForm.password) {
    updateData.password = editForm.password
    updateData.password_confirmation = editForm.password_confirmation
  }
  
  router.put(`/admin/users/${editingUserId.value}`, updateData, {
    onSuccess: () => {
      closeEditModal()
      editProcessing.value = false
      emit('refresh')
    },
    onError: (err) => {
      editErrors.value = err
      editProcessing.value = false
    }
  })
}

const deleteUser = (user: User) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus user "${user.name}"?`)) return

  router.delete(`/admin/users/${user.id}`, {
    onSuccess: () => {
      emit('refresh')
    },
    onError: (err) => {
      alert('Gagal menghapus user: ' + Object.values(err).join(', '))
    }
  })
}

const manualRefresh = () => {
  emit('refresh')
}

// const getInitials = (name: string) => {
//   return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase().slice(0, 2)
// }

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

function toDateInputValue(dateString: string): string {
  if (!dateString) return ''
  const date = new Date(dateString)
  // Ambil bagian tanggal saja (YYYY-MM-DD)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}
</script>

<style scoped>
.crud-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.crud-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.header-info h2 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 0.5rem;
}

.header-subtitle {
  color: rgba(255, 255, 255, 0.7);
  font-size: 1rem;
}

.crud-actions {
  display: flex;
  gap: 1rem;
}

.btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn-primary {
  background: linear-gradient(45deg, #9d4edd, #c77dff);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(157, 78, 221, 0.4);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.2);
}

.icon {
  width: 18px;
  height: 18px;
}

/* Stats Cards */
.stats-mini {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.stat-card-mini {
  background: rgba(157, 78, 221, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(157, 78, 221, 0.3);
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s ease;
}

.stat-card-mini:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(157, 78, 221, 0.2);
}

.stat-icon-mini {
  width: 40px;
  height: 40px;
  background: linear-gradient(45deg, #9d4edd, #c77dff);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.stat-content-mini h4 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 0.25rem;
}

.stat-content-mini p {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.85rem;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: linear-gradient(135deg, #1a0825 0%, #2d1b3d 100%);
  border: 1px solid rgba(157, 78, 221, 0.3);
  border-radius: 16px;
  min-width: 500px;
  max-width: 90vw;
  max-height: 90vh;
  scrollbar-width: thin;
  scrollbar-color: #9d4edd #2d1b3d;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid rgba(157, 78, 221, 0.2);
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #ffffff;
}

.modal-close {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.modal-close:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.1);
}

.modal-form {
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #ffffff;
  font-weight: 500;
  font-size: 0.9rem;
}

.form-control {
  width: 100%;
  padding: 0.75rem 1rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(157, 78, 221, 0.3);
  border-radius: 8px;
  color: white;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: #9d4edd;
  box-shadow: 0 0 0 3px rgba(157, 78, 221, 0.1);
}

.form-control::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

.checkbox-group {
  margin: 0.5rem 0;
}

.checkbox-group label {
  /* gap: 0.75rem;
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.9rem;
  cursor: pointer;
  line-height: 1.4; */
  margin-bottom: 0.5rem;
  color: #ffffff;
  font-weight: 500;
  font-size: 0.9rem;
}

.checkbox-control {
  margin: 0;
  margin-top: 0.1rem;
}

.error {
  color: #ef4444;
  font-size: 0.8rem;
  margin-top: 0.5rem;
  display: block;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
}

/* Table Styles */
.table-container {
  background: rgba(157, 78, 221, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(157, 78, 221, 0.3);
  border-radius: 16px;
  width: 100%;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid rgba(157, 78, 221, 0.2);
}

.table-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #ffffff;
}

.table-info {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.9rem;
}

.table-wrapper {
  overflow-x: auto;
  scrollbar-width: thin;
  scrollbar-color: #9d4edd #2d1b3d;
}

.data-table {
  width: 100%;
  min-width: 900px;
  border-collapse: collapse;
}

.table-wrapper::-webkit-scrollbar {
  height: 10px;
  background: transparent;
}
.table-wrapper::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #9d4edd 30%, #c77dff 100%);
  border-radius: 8px;
}
.table-wrapper::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #a855f7 30%, #c77dff 100%);
}
.table-wrapper::-webkit-scrollbar-track {
  background: rgba(157, 78, 221, 0.07);
  border-radius: 8px;
}

.data-table th {
  background: rgba(0, 0, 0, 0.3);
  padding: 1rem 1.5rem;
  text-align: left;
  border-bottom: 1px solid rgba(157, 78, 221, 0.2);
}

.th-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #ffffff;
  font-size: 0.9rem;
}

.th-icon {
  width: 16px;
  height: 16px;
  color: #9d4edd;
}

.data-table td {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid rgba(157, 78, 221, 0.1);
}

.table-row {
  transition: all 0.3s ease;
}

.table-row:hover {
  background: rgba(157, 78, 221, 0.05);
}

.cell-content {
  display: flex;
  align-items: center;
}

.user-id {
  color: rgba(255, 255, 255, 0.7);
  font-weight: 500;
  font-size: 0.9rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 36px;
  height: 36px;
  background: linear-gradient(45deg, #9d4edd, #c77dff);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.8rem;
}

.username {
  color: #ffffff;
  font-weight: 500;
  font-size: 0.9rem;
}

.email {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.9rem;
}

.role-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: capitalize;
}

.role-admin {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.role-user {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.date {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.9rem;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.btn-edit {
  background: rgba(59, 130, 246, 0.2);
  color: #3b82f6;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.btn-edit:hover {
  background: rgba(59, 130, 246, 0.3);
  transform: translateY(-1px);
}

.btn-delete {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-delete:hover {
  background: rgba(239, 68, 68, 0.3);
  transform: translateY(-1px);
}

.btn-action .icon {
  width: 16px;
  height: 16px;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.empty-icon {
  width: 80px;
  height: 80px;
  color: rgba(157, 78, 221, 0.5);
  margin-bottom: 1.5rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #ffffff;
  margin-bottom: 1rem;
}

.empty-state p {
  color: rgba(255, 255, 255, 0.7);
  font-size: 1rem;
  margin-bottom: 2rem;
  max-width: 400px;
}

/* Responsive */
@media (max-width: 768px) {
  .crud-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .crud-actions {
    width: 100%;
    justify-content: flex-start;
  }
  
  .stats-mini {
    grid-template-columns: 1fr;
  }
  
  .modal-content {
    min-width: 90vw;
    margin: 1rem;
  }
  
  .table-wrapper {
    overflow-x: auto;
  }
  
  .data-table {
    min-width: 600px;
  }
}
</style>