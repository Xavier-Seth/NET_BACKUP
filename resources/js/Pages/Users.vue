<script setup>
import { ref, computed, onMounted } from 'vue';
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from 'axios';

// Users List (Initially empty)
const users = ref([]);
const searchQuery = ref("");
const loading = ref(true);
const sortKey = ref(null);
const sortOrder = ref(1);
const selectedStatus = ref(""); // For selecting Active or Inactive status
const selectedRoles = ref({ admin: false, lis: false }); // For selecting roles

// Fetch users from Laravel API
const fetchUsers = async () => {
    try {
        const response = await axios.get('/api/users');
        users.value = response.data;
    } catch (error) {
        console.error("Error fetching users:", error);
    } finally {
        loading.value = false;
    }
};

// Computed: Filter users based on search, status, and selected roles
const filteredUsers = computed(() => {
    return users.value.filter(user => {
        // Filter by search query
        const matchesSearch = (`${user.first_name} ${user.last_name}`.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            user.email.toLowerCase().includes(searchQuery.value.toLowerCase()));

        // Filter by selected status
        const matchesStatus = selectedStatus.value === "" || user.status.toLowerCase() === selectedStatus.value.toLowerCase();

        // Filter by selected roles: Show all users if no role is selected
        const matchesRole = (!selectedRoles.value.admin && !selectedRoles.value.lis) ||
                            (selectedRoles.value.admin && user.role.toLowerCase() === 'admin') ||
                            (selectedRoles.value.lis && user.role.toLowerCase() === 'lis');

        return matchesSearch && matchesStatus && matchesRole;
    });
});

// Sort users dynamically
const sortUsers = (key) => {
    if (sortKey.value === key) {
        sortOrder.value *= -1;
    } else {
        sortKey.value = key;
        sortOrder.value = 1;
    }
    users.value.sort((a, b) => {
        return (a[key] > b[key] ? 1 : -1) * sortOrder.value;
    });
};

// Fetch users when the component loads
onMounted(fetchUsers);
</script>


<template>
    <MainLayout>
        <!-- User Stats -->
        <div class="stats d-flex align-items-center">
            <button class="btn active-user btn-success me-2">Active Users</button>
            <button class="btn total-user btn-primary me-3">Total Users</button>
            
            <!-- Role Filter Checkboxes (Moved beside the Total Users button) -->
            <div class="role-filter">
                <label class="form-check me-3">
                    <input type="checkbox" class="form-check-input" v-model="selectedRoles.admin" />
                    <span>Admin</span>
                </label>
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" v-model="selectedRoles.lis" />
                    <span>LIS</span>
                </label>
            </div>

            <!-- Search Bar -->
            <input type="text" v-model="searchQuery" class="form-control search-bar ms-auto" placeholder="Search Users" />

            <!-- Status Filter Dropdown -->
            <select v-model="selectedStatus" class="form-select status-select ms-3">
                <option value="">All Users</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <!-- Loading Indicator -->
        <div v-if="loading" class="loading">Loading users...</div>

        <!-- User Table -->
        <div v-else class="table-container mt-3">
            <table class="table users-table">
                <thead>
                    <tr>
                        <th @click="sortUsers('id')">ID</th>
                        <th @click="sortUsers('first_name')">First Name</th>
                        <th @click="sortUsers('last_name')">Last Name</th>
                        <th @click="sortUsers('role')">Role</th>
                        <th @click="sortUsers('email')">Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in filteredUsers" :key="user.id">
                        <td>{{ user.id }}</td>
                        <td>{{ user.first_name }}</td>
                        <td>{{ user.last_name }}</td>
                        <td>{{ user.role }}</td>
                        <td>{{ user.email }}</td>
                        <td>{{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}</td>
                        <td>
                            <button class="btn btn-save"><i class="bi bi-check2"></i> Save</button>
                            <button class="btn btn-edit ms-2"><i class="bi bi-pencil-square"></i></button>
                            <button class="btn btn-delete ms-2"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </MainLayout>
</template>

<style scoped>
/* Main Content */
.stats {
    display: flex;
    align-items: center;
    margin-top: 10px;
    gap: 8px;
}

/* Role Filter */
.role-filter {
    display: flex;
    align-items: center;
    margin-left: 10px;
}

.form-check {
    position: relative;
    display: flex;
    align-items: center;
}

.form-check input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    width: 20px;
    height: 20px;
    border: 2px solid #ccc;
    border-radius: 4px;
    background-color: #fff;
    cursor: pointer;
    transition: background-color 0.3s, border-color 0.3s;
    margin-right: 10px;
}

.form-check input[type="checkbox"]:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.form-check input[type="checkbox"]:checked::before {
    content: '✔';
    font-size: 14px;
    color: white;
    position: absolute;
    top: 0;
    left: 2px;
}

.form-check label {
    font-size: 14px;
    color: #555;
    cursor: pointer;
    transition: color 0.3s;
}

.form-check input[type="checkbox"]:hover {
    border-color: #007bff;
}

.form-check input[type="checkbox"]:checked:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}

.form-check label:hover {
    color: #007bff;
}

/* Search Bar */
.search-bar {
    padding: 8px;
    border-radius: 8px;
    border: 2px solid #707070;
    outline: none;
    width: 350px;
    height: 45px;
    color: black;
    transition: border-color 0.3s;
}

.search-bar:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

/* Status Filter Dropdown */
.status-select {
    padding: 8px;
    border-radius: 8px;
    border: 2px solid #707070;
    outline: none;
    width: 150px;
}

/* Table */
.table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 10px;
    overflow: hidden;
}

/* Table Header */
.users-table thead {
    background: #19184F;
    color: white;
}

.users-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
}

.users-table th:hover {
    background: #4540c5;
}

/* Table Rows */
.users-table td {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    font-size: 15px;
}

/* Action Buttons */
.btn-save, .btn-edit, .btn-delete {
    border: none;
    color: white;
    padding: 6px 10px;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.3s;
}

/* Save (green) */
.btn-save {
    background: #28a745;
}

.btn-save:hover {
    background: #218838;
}

/* Edit (orange) */
.btn-edit {
    background: #ffc107;
    color: black;
}

.btn-edit:hover {
    background: #e0a800;
}

/* Delete (red) */
.btn-delete {
    background: #dc3545;
}

.btn-delete:hover {
    background: #c82333;
}

.table-container {
    max-height: 500px; /* Adjust based on preference */
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 10px;
}
</style>
