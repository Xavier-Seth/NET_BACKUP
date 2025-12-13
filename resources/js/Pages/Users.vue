<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

// --- State Variables ---
const users = ref([])
const loading = ref(true)
const searchQuery = ref('')
const statusFilter = ref('active') // 'active' or 'inactive'
const roleFilter = ref('All')      // 'All', 'Teacher', 'Admin', 'Admin Staff'

const sortKey = ref('full_name')
const sortAsc = ref(true)

// Modals
const showDeleteModal = ref(false)
const selectedUser = ref(null)
const showEditModal = ref(false)
const editModalUser = ref(null)

const currentUserId = usePage().props.auth.user.id

// --- API Calls ---
const fetchUsers = async () => {
  try {
    const response = await axios.get('/api/users')
    users.value = response.data
  } catch (err) {
    console.error('Fetch error:', err)
  } finally {
    loading.value = false
  }
}

// --- Filtering & Sorting ---
const toggleStatusFilter = () => {
  statusFilter.value = statusFilter.value === 'active' ? 'inactive' : 'active'
}

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    // 1. Search Logic
    const fullName = `${user.last_name} ${user.first_name} ${user.middle_name}`.toLowerCase()
    const search = searchQuery.value.toLowerCase()
    const matchesSearch = fullName.includes(search)

    // 2. Status Logic (Case-Insensitive Fix)
    // This ensures 'Inactive' (capitalized) matches 'inactive' (lowercase)
    const userStatus = (user.status || '').toLowerCase()
    const currentFilter = statusFilter.value.toLowerCase()
    const matchesStatus = userStatus === currentFilter

    // 3. Role Logic
    let matchesRole = true
    if (roleFilter.value !== 'All') {
        matchesRole = user.role === roleFilter.value
    }

    return matchesSearch && matchesStatus && matchesRole
  })
})

const sortedUsers = computed(() => {
  return [...filteredUsers.value].sort((a, b) => {
    const fieldA =
      sortKey.value === 'full_name'
        ? `${a.last_name} ${a.first_name} ${a.middle_name}`.toLowerCase()
        : a[sortKey.value]?.toLowerCase()

    const fieldB =
      sortKey.value === 'full_name'
        ? `${b.last_name} ${b.first_name} ${b.middle_name}`.toLowerCase()
        : b[sortKey.value]?.toLowerCase()

    if (fieldA < fieldB) return sortAsc.value ? -1 : 1
    if (fieldA > fieldB) return sortAsc.value ? 1 : -1
    return 0
  })
})

const sortUsers = key => {
  if (sortKey.value === key) {
    sortAsc.value = !sortAsc.value
  } else {
    sortKey.value = key
    sortAsc.value = true
  }
}

// --- Actions (Edit & Delete) ---

const confirmEdit = user => {
  if (!user?.id) return
  editModalUser.value = user
  showEditModal.value = true
}

const proceedToEdit = () => {
  if (!editModalUser.value?.id) return
  
  // Close the modal first
  showEditModal.value = false
  const user = editModalUser.value

  if (user.role === 'Teacher') {
    // If Teacher, go to the specialized Teacher Edit Page
    // We use teacher_id if available (from your updated controller), otherwise user.id
    const targetId = user.teacher_id || user.id
    if (!user.teacher_id) console.warn("Warning: Using User ID instead of Teacher ID. Check Controller.")
    
    router.get(route('teachers.edit', { teacher: targetId }))
  } else {
    // If Admin/Staff, go to the generic Admin Edit User Page
    router.get(route('admin.edit-user', { id: user.id }))
  }
}

const confirmDelete = user => {
  if (user.id === currentUserId) {
    alert('⚠️ You cannot delete your own account while logged in.')
    return
  }
  selectedUser.value = user
  showDeleteModal.value = true
}

const deleteUser = async () => {
  if (!selectedUser.value?.id) return

  try {
    await axios.delete(`/api/users/${selectedUser.value.id}`)
    // Remove deleted user from list locally
    users.value = users.value.filter(u => u.id !== selectedUser.value.id)
    showDeleteModal.value = false
    alert(`${selectedUser.value.last_name}, ${selectedUser.value.first_name} was successfully deleted.`)
  } catch (error) {
    console.error('Delete failed:', error)
    alert('Failed to delete user.')
  }
}

onMounted(fetchUsers)
</script>

<template>
  <MainLayout>
    
    <div class="filter-header d-flex justify-content-between align-items-center mb-3 mt-3">
        
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <button
                class="btn toggle-status-btn text-white fw-bold"
                :class="statusFilter === 'active' ? 'btn-danger' : 'btn-success'"
                @click="toggleStatusFilter"
            >
                {{ statusFilter === 'active' ? 'Inactive Users' : 'Active Users' }}
            </button>

            <div class="role-pills d-flex gap-2">
                <button 
                    class="pill-btn" 
                    :class="{ active: roleFilter === 'All' }" 
                    @click="roleFilter = 'All'"
                >
                    All Users
                </button>
                <button 
                    class="pill-btn" 
                    :class="{ active: roleFilter === 'Teacher' }" 
                    @click="roleFilter = 'Teacher'"
                >
                    Teachers
                </button>
                <button 
                    class="pill-btn" 
                    :class="{ active: roleFilter === 'Admin' }" 
                    @click="roleFilter = 'Admin'"
                >
                    Admins
                </button>
                <button 
                    class="pill-btn" 
                    :class="{ active: roleFilter === 'Admin Staff' }" 
                    @click="roleFilter = 'Admin Staff'"
                >
                    Staff
                </button>
            </div>
        </div>

        <div class="search-wrapper">
            <input
                type="text"
                v-model="searchQuery"
                class="form-control search-bar"
                placeholder="Search by full name..."
            />
        </div>
    </div>

    <div v-if="loading" class="loading">Loading users...</div>

    <div v-else class="table-container shadow-sm">
      <table class="table users-table mb-0">
        <thead>
          <tr>
            <th @click="sortUsers('full_name')">Name</th>
            <th @click="sortUsers('role')">Role</th>
            <th @click="sortUsers('email')">Email</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="user in sortedUsers" :key="user.id">
            <td class="fw-bold text-dark">
                {{ user.last_name }}, {{ user.first_name }} {{ user.middle_name }}
            </td>
            
            <td>
                {{ user.role }}
            </td>

            <td>{{ user.email }}</td>

            <td>
              <span
                :class="['badge', user.status === 'active' ? 'bg-success' : 'bg-secondary']"
              >
                {{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}
              </span>
            </td>

            <td>
              <button class="btn btn-edit me-2" @click="confirmEdit(user)">
                <i class="bi bi-pencil-square"></i>
              </button>

              <button class="btn btn-delete" @click="confirmDelete(user)">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
          <tr v-if="sortedUsers.length === 0">
              <td colspan="5" class="text-center text-muted py-4">
                  No {{ statusFilter }} users found matching criteria.
              </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showDeleteModal" class="modal-backdrop">
      <div class="modal-box">
        <div class="text-danger mb-2 text-center">
            <i class="bi bi-exclamation-triangle-fill fs-1"></i>
        </div>
        <h5 class="text-center fw-bold">Confirm Deletion</h5>
        
        <p class="mb-3 text-center">
          Are you sure you want to delete
          <strong>{{ selectedUser?.last_name }}, {{ selectedUser?.first_name }}</strong>?
        </p>

        <div v-if="selectedUser?.role === 'Teacher'" class="alert alert-warning text-start" style="font-size: 0.9rem;">
            <strong>⚠️ Critical Warning:</strong> This user is a <strong>Teacher</strong>. 
            Deleting this account will also permanently delete:
            <ul class="mb-0 mt-1 ps-3">
                <li>Their <strong>Teacher Profile</strong></li>
                <li>All <strong>Uploaded Documents</strong></li>
                <li>All Logs associated with them</li>
            </ul>
        </div>

        <div class="modal-actions d-flex justify-content-center gap-2 mt-4">
          <button class="btn btn-secondary" @click="showDeleteModal = false">
            Cancel
          </button>
          <button class="btn btn-danger" @click="deleteUser">
            Yes, Delete
          </button>
        </div>
      </div>
    </div>

    <div v-if="showEditModal" class="modal-backdrop">
      <div class="modal-box">
        <h5 class="fw-bold mb-3">Edit User</h5>
        <p>
          Do you want to edit the profile of
          <strong>{{ editModalUser?.last_name }}, {{ editModalUser?.first_name }}</strong>?
        </p>
        <div class="modal-actions d-flex justify-content-end gap-2 mt-4">
          <button class="btn btn-secondary" @click="showEditModal = false">
            Cancel
          </button>
          <button class="btn btn-primary" @click="proceedToEdit">
            Edit
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
/* Filter Header Layout */
.filter-header {
    flex-wrap: wrap; 
    gap: 15px;
}

/* Toggle Button */
.toggle-status-btn {
    min-width: 140px;
    border-radius: 8px;
    font-size: 0.9rem;
}

/* Pill Buttons (The tabs) */
.pill-btn {
    border: 1px solid #e0e0e0;
    background: white;
    color: #6b7280;
    padding: 6px 16px;
    border-radius: 50px; /* Fully rounded pills */
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}
.pill-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}
.pill-btn.active {
    background: #19184f; /* Your theme dark blue */
    color: white;
    border-color: #19184f;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(25, 24, 79, 0.2);
}

/* Search Bar */
.search-bar {
    width: 300px; 
    padding: 8px 12px; 
    border-radius: 8px; 
    border: 1px solid #ccc;
}
.search-bar:focus {
    border-color: #19184f;
    box-shadow: 0 0 0 3px rgba(25, 24, 79, 0.1);
}

/* Table Styling */
.table-container { 
    background: white; 
    padding: 0; 
    border-radius: 10px; 
    overflow: hidden; /* Clips the table corners */
}
.users-table { 
    width: 100%; 
    border-collapse: collapse; 
    min-width: 800px; 
}
.users-table th { 
    background: #19184f; 
    color: white; 
    padding: 14px 16px; 
    cursor: pointer; 
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}
.users-table th:hover { background: #2a286e; }
.users-table td { 
    padding: 12px 16px; 
    border-bottom: 1px solid #f0f0f0; 
    vertical-align: middle; 
    color: #374151;
}
.users-table tr:last-child td { border-bottom: none; }
.users-table tr:hover { background-color: #f9fafb; }

/* Buttons */
.btn-edit { background: #ffc107; color: #212529; border: none; padding: 6px 10px; border-radius: 6px; }
.btn-edit:hover { background: #e0a800; }
.btn-delete { background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 6px; }
.btn-delete:hover { background: #bb2d3b; }

/* Status Badge */
.badge { 
    font-size: 12px; 
    padding: 6px 12px; 
    border-radius: 20px; 
    text-transform: capitalize; 
    text-align: center; 
    min-width: 80px; 
    font-weight: 600; 
    display: inline-block;
}

/* Mobile Responsive */
@media (max-width: 992px) {
  .filter-header {
      flex-direction: column;
      align-items: stretch !important;
  }
  .search-wrapper { width: 100%; }
  .search-bar { width: 100%; }
  
  .role-pills {
      flex-wrap: wrap;
      justify-content: start;
  }
  
  /* Scrollable table on mobile */
  .table-container { overflow-x: auto; }
}

/* Modal Styles */
.modal-backdrop { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-box { background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 420px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.alert { padding: 12px; border-radius: 8px; border: 1px solid transparent; }
.alert-warning { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }
</style>