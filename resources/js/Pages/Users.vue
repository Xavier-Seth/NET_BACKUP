<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

const users = ref([])
const loading = ref(true)
const searchQuery = ref('')
const statusFilter = ref('active')
const selectedRoles = ref({ admin: false, adminStaff: false })
const sortKey = ref('full_name')
const sortAsc = ref(true)

const showDeleteModal = ref(false)
const selectedUser = ref(null)

const currentUserId = usePage().props.auth.user.id

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

const toggleStatusFilter = () => {
  statusFilter.value = statusFilter.value === 'active' ? 'inactive' : 'active'
}

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const fullName = `${user.last_name} ${user.first_name} ${user.middle_name}`.toLowerCase()
    const search = searchQuery.value.toLowerCase()
    const matchesSearch = fullName.includes(search)
    const matchesStatus = user.status.toLowerCase() === statusFilter.value
    const matchesRole =
      (!selectedRoles.value.admin && !selectedRoles.value.adminStaff) ||
      (selectedRoles.value.admin && user.role.toLowerCase() === 'admin') ||
      (selectedRoles.value.adminStaff && user.role.toLowerCase() === 'admin staff')
    return matchesSearch && matchesStatus && matchesRole
  })
})

const sortedUsers = computed(() => {
  return [...filteredUsers.value].sort((a, b) => {
    const fieldA = sortKey.value === 'full_name'
      ? `${a.last_name} ${a.first_name} ${a.middle_name}`.toLowerCase()
      : a[sortKey.value]?.toLowerCase()
    const fieldB = sortKey.value === 'full_name'
      ? `${b.last_name} ${b.first_name} ${b.middle_name}`.toLowerCase()
      : b[sortKey.value]?.toLowerCase()
    if (fieldA < fieldB) return sortAsc.value ? -1 : 1
    if (fieldA > fieldB) return sortAsc.value ? 1 : -1
    return 0
  })
})

const sortUsers = (key) => {
  if (sortKey.value === key) {
    sortAsc.value = !sortAsc.value
  } else {
    sortKey.value = key
    sortAsc.value = true
  }
}

const confirmEdit = (user) => {
  if (!user?.id) {
    alert('Invalid user selected.')
    return
  }
  if (confirm(`Do you want to edit the profile of ${user.last_name}, ${user.first_name}?`)) {
    router.get(route('admin.edit-user', { id: user.id }))
  }
}

const confirmDelete = (user) => {
  if (user.id === currentUserId) {
    alert("⚠️ You cannot delete your own account while logged in.")
    return
  }
  selectedUser.value = user
  showDeleteModal.value = true
}

const deleteUser = async () => {
  if (!selectedUser.value?.id) return

  try {
    await axios.delete(`/api/users/${selectedUser.value.id}`)
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
    <!-- Filters -->
    <div class="filter-container">
      <div class="filters">
        <button
          class="btn toggle-status-btn"
          :class="statusFilter === 'active' ? 'btn-success' : 'btn-danger'"
          @click="toggleStatusFilter"
        >
          {{ statusFilter === 'active' ? 'Active Users' : 'Inactive Users' }}
        </button>

        <div class="role-filter">
          <label class="form-check">
            <input type="checkbox" class="form-check-input" v-model="selectedRoles.admin" />
            <span>Admin</span>
          </label>
          <label class="form-check">
            <input type="checkbox" class="form-check-input" v-model="selectedRoles.adminStaff" />
            <span>Admin Staff</span>
          </label>
        </div>
      </div>

      <input
        type="text"
        v-model="searchQuery"
        class="form-control search-bar"
        placeholder="Search by full name (Last or First Name)"
      />
    </div>

    <!-- Loader -->
    <div v-if="loading" class="loading">Loading users...</div>

    <!-- User Table -->
    <div v-else class="table-container mt-3">
      <table class="table users-table">
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
            <td>{{ user.last_name }}, {{ user.first_name }} {{ user.middle_name }}</td>
            <td>{{ user.role }}</td>
            <td>{{ user.email }}</td>
            <td>
              <span :class="['badge', user.status === 'active' ? 'bg-success' : 'bg-secondary']">
                {{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}
              </span>
            </td>
            <td>
              <button class="btn btn-edit" @click="confirmEdit(user)">
                <i class="bi bi-pencil-square"></i>
              </button>
              <button class="btn btn-delete ms-2" @click="confirmDelete(user)">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-backdrop">
      <div class="modal-box">
        <h5>Confirm Deletion</h5>
        <p>
          Are you sure you want to delete
          <strong>{{ selectedUser?.last_name }}, {{ selectedUser?.first_name }}</strong>?
        </p>
        <div class="modal-actions">
          <button class="btn btn-secondary" @click="showDeleteModal = false">Cancel</button>
          <button class="btn btn-danger" @click="deleteUser">Yes, Delete</button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
/* Keep your full CSS as it already looks great */
.filter-container {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  margin-top: 10px;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

.toggle-status-btn {
  min-width: 150px;
}

.role-filter {
  display: flex;
  gap: 10px;
}

.form-check {
  display: flex;
  align-items: center;
  gap: 5px;
}

.search-bar {
  flex-grow: 1;
  max-width: 300px;
  padding: 8px;
  border-radius: 8px;
  border: 1px solid #ccc;
}
.search-bar:focus {
  border-color: #007bff;
  box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

.table-container {
  background: white;
  padding: 10px;
  border-radius: 10px;
  overflow-x: auto;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 800px;
}

.users-table th {
  background: #19184F;
  color: white;
  padding: 12px;
  cursor: pointer;
}
.users-table th:hover {
  background: #3a35c4;
}

.users-table td {
  padding: 10px;
  border-bottom: 1px solid #ddd;
}

.btn-edit,
.btn-delete {
  border: none;
  padding: 6px 10px;
  border-radius: 5px;
  font-size: 14px;
  color: white;
  cursor: pointer;
}
.btn-edit {
  background: #ffc107;
  color: black;
}
.btn-edit:hover {
  background: #e0a800;
}
.btn-delete {
  background: #dc3545;
}
.btn-delete:hover {
  background: #c82333;
}

.badge {
  font-size: 12px;
  padding: 6px 12px;
  border-radius: 20px;
  text-transform: capitalize;
  display: inline-block;
  text-align: center;
  min-width: 90px;
  font-weight: 600;
}

@media (max-width: 768px) {
  .filter-container {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .filters {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .search-bar {
    width: 100%;
    max-width: 100%;
  }

  .users-table,
  .users-table thead,
  .users-table tbody,
  .users-table th,
  .users-table td,
  .users-table tr {
    display: block;
  }

  .users-table thead {
    display: none;
  }

  .users-table tr {
    margin-bottom: 15px;
    border: 1px solid #eee;
    border-radius: 10px;
    padding: 10px;
    background: #f9f9f9;
  }

  .users-table td {
    position: relative;
    padding-left: 50%;
    text-align: left;
    border: none;
    border-bottom: 1px solid #ddd;
  }

  .users-table td::before {
    position: absolute;
    top: 10px;
    left: 10px;
    width: 45%;
    font-weight: bold;
    white-space: nowrap;
    color: #333;
  }

  .users-table td:nth-of-type(1)::before { content: "Name"; }
  .users-table td:nth-of-type(2)::before { content: "Role"; }
  .users-table td:nth-of-type(3)::before { content: "Email"; }
  .users-table td:nth-of-type(4)::before { content: "Status"; }
  .users-table td:nth-of-type(5)::before { content: "Actions"; }
}

/* Modal Styling */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.modal-box {
  background: white;
  padding: 20px 30px;
  border-radius: 10px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
  text-align: center;
}

/* Updated for horizontal button layout */
.modal-actions {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 20px;
}

.modal-actions .btn {
  min-width: 110px;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}
.btn-secondary:hover {
  background: #5a6268;
}
</style>
