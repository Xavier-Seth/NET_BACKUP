<script setup>
import { ref } from 'vue';

// Dummy users list
const users = ref([
    { id: '01', name: 'Kent Bingco', role: 'Staff', email: 'BingcoKent@gmail.com', status: 'Active' },
    { id: '02', name: 'Jane Doe', role: 'Admin', email: 'janedoe@example.com', status: 'Inactive' },
]);

</script>

<template>
    <div class="d-flex">
        <!-- Sidebar -->
        <Sidebar/>
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h2>DocuNet</h2>
                <div class="user-profile">
                    <img src="/path-to-profile.png" alt="User Profile" class="profile-img" />
                    <span>Xavier Noynay</span>
                </div>
            </div>

            <!-- User Stats -->
            <div class="stats d-flex">
                <button class="btn btn-success me-2">Active Users</button>
                <button class="btn btn-primary">Total Users</button>
                <input type="text" class="form-control ms-auto" placeholder="Search" />
            </div>

            <!-- User Table -->
            <table class="table mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id">
                        <td>{{ user.id }}</td>
                        <td>{{ user.name }}</td>
                        <td>{{ user.role }}</td>
                        <td>{{ user.email }}</td>
                        <td>
                            <span :class="user.status === 'Active' ? 'badge bg-success' : 'badge bg-secondary'">
                                {{ user.status }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success">Save</button>
                            <button class="btn btn-sm btn-warning ms-2"><i class="bi bi-pencil-square"></i></button>
                            <button class="btn btn-sm btn-danger ms-2"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import Sidebar from "@/Components/Sidebar.vue";
export default {
  components: {
    Sidebar, // Register Sidebar Component
  }
}
</script>
<style scoped>

/* Main Content */
.main-content {
    flex-grow: 1;
    padding: 20px;
    background: #f4f4f4;
    min-height: 100vh;
    margin-left: 220px;
    width: calc(100% - 220px);
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.user-profile {
    display: flex;
    align-items: center;
}

.profile-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

.stats {
    display: flex;
    align-items: center;
    margin-top: 10px;
}

/* Table */
.table {
    background: white;
    border-radius: 10px;
}

.badge {
    padding: 5px 10px;
}
</style>
