<script setup>
import { ref } from 'vue';
import Sidebar from "@/Components/Sidebar.vue";

// Dummy users list
const users = ref([
    { id: '01', name: 'Kent Bingco', role: 'Staff', email: 'BingcoKent@gmail.com', status: 'Active' },
    { id: '02', name: 'Jane Doe', role: 'Admin', email: 'janedoe@example.com', status: 'Inactive' },
]);
</script>

<template>
    <div class="d-flex">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header (Logo & Title, User Profile) -->
            <div class="header">
                <div class="logo-title">
                    <img src="/images/school_logo.png" alt="Logo" class="logo" />
                    <h2 class="docunet-title">DocuNet</h2>
                </div>
                <div class="user-profile">
                    <img src="/images/user-avatar.png" alt="User Profile" class="profile-img" />
                    <span>Xavier Noynay</span>
                </div>
            </div>

            <!-- User Stats -->
            <div class="stats d-flex">
                <button class="btn active-user btn-success me-2">Active Users</button>
                <button class="btn total-user btn-primary">Total Users</button>
                <input type="text" class="form-control search-bar ms-auto" placeholder="Search" />
            </div>

            <!-- User Table -->
            <div class="table-container mt-3">
                <table class="table users-table">
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
                                <span :class="user.status === 'Active' ? 'badge status-active' : 'badge status-inactive'">
                                    {{ user.status }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-save"><i class="bi bi-check2"></i> Save</button>
                                <button class="btn btn-edit ms-2"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-delete ms-2"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

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

/* Header */
.header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 10px 20px;
}

.logo-title {
    display: flex;
    align-items: center;
}

.logo {
    width: 80px;
    height: auto;
    margin-top: -15px;
}

.docunet-title {
    margin-left: 15px;
    font-size: 24px;
    font-weight: bold;
}

/* User Profile */
.user-profile {
    display: flex;
    align-items: center;
    margin-top: -10px;
}

.profile-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

/* Stats Section */
.stats {
    display: flex;
    align-items: center;
    margin-top: 10px;
    gap: 8px;
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

/* User Buttons */
.active-user {
    height: 60px;
    margin-top: 5px;
    background: #002500;
    font-size: 17px;
}

.total-user {
    height: 60px;
    margin-top: 5px;
    background: #19184F;
    font-size: 17px;
}

/* Table Container */
.table-container {
    margin-top: 20px;
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
    background: #19184F; /* Dark Blue Header */
    color: white;
}

.users-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 16px;
}

/* Table Rows */
.users-table td {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    font-size: 15px;
}

/* Status Badges */
.status-active {
    background-color: #28a745; /* or your preferred green */
    color: white;
    border-radius: 15px;
    padding: 5px 10px;
    font-weight: 500;
}

.status-inactive {
    background-color: #6c757d; /* or your preferred gray */
    color: white;
    border-radius: 15px;
    padding: 5px 10px;
    font-weight: 500;
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
</style>
