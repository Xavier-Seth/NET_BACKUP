<template>
    <MainLayout>
        <div class="stats d-flex align-items-center">
            <button class="btn active-user btn-success me-2">Active Users</button>
            <button class="btn total-user btn-primary me-3">Total Users</button>

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

            <input type="text" v-model="searchQuery" class="form-control search-bar ms-auto" placeholder="Search Users" />
            <select v-model="selectedStatus" class="form-select status-select ms-3">
                <option value="">All Users</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div v-if="loading" class="loading">Loading users...</div>

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
                            <button class="btn btn-edit" @click="confirmEdit(user)">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-delete ms-2"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from 'axios';

// Variables
const users = ref([]);
const searchQuery = ref("");
const loading = ref(true);
const router = useRouter();
const selectedStatus = ref("");
const selectedRoles = ref({ admin: false, lis: false });

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

// Computed Property for Filtering Users
const filteredUsers = computed(() => {
    return users.value.filter(user => {
        const matchesSearch = (`${user.first_name} ${user.last_name}`.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            user.email.toLowerCase().includes(searchQuery.value.toLowerCase()));

        const matchesStatus = selectedStatus.value === "" || user.status.toLowerCase() === selectedStatus.value.toLowerCase();

        const matchesRole = (!selectedRoles.value.admin && !selectedRoles.value.lis) ||
                            (selectedRoles.value.admin && user.role.toLowerCase() === 'admin') ||
                            (selectedRoles.value.lis && user.role.toLowerCase() === 'lis');

        return matchesSearch && matchesStatus && matchesRole;
    });
});

// Redirect Admin to User Profile Page
const confirmEdit = (user) => {
    console.log("Selected User:", user); // ✅ Debug log to check selected user

    if (!user || !user.id) {
        alert("Invalid user selected.");
        return;
    }

    if (confirm(`Do you want to edit the profile of ${user.last_name}, ${user.first_name}?`)) {
        console.log("Redirecting to AdminEditUser.vue for ID:", user.id); // ✅ Debug log to check redirect
        router.push({ name: 'admin.edit-user', params: { id: user.id } });
    }
};


// Fetch users when the component loads
onMounted(fetchUsers);
</script>


<style scoped>
/* Main Content */
.stats {
    display: flex;
    align-items: center;
    margin-top: 10px;
    gap: 8px;
}

/* Role Filter */
.form-select{
    width: 160px;
}
.role-filter {
    display: flex;
    align-items: center;
    margin-left: 10px;
}

.form-check {
    display: flex;
    align-items: center;
}

.form-check input[type="checkbox"] {
    width: 16px;
    height: 16px;
    margin-right: 5px;
    cursor: pointer;
}
.search-bar{
    width: 300px;
}
.search-bar, .status-select {
    padding: 8px;
    border-radius: 8px;
    border: 1px solid #ccc;
    outline: none;
    transition: 0.3s;
}

.search-bar:focus, .status-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

/* Table */
.table-container {
    max-height: 500px;
    overflow-y: auto;
    border-radius: 10px;
    background: white;
    padding: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.users-table {
    width: 100%;
    border-collapse: collapse;
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

/* Action Buttons */
.btn-edit, .btn-delete {
    border: none;
    color: white;
    padding: 6px 10px;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.3s;
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
</style>
