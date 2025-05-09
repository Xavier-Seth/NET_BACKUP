<template>
    <div class="flex min-h-screen bg-gray-100">
      <!-- Sidebar -->
      <Sidebar />
  
      <!-- Page Content -->
      <div class="flex-1">
        <!-- Header -->

  
        <!-- Logs Table -->
        <div class="container p-6">
          <h2 class="text-2xl font-bold mb-4">System Logs</h2>
  
          <div class="logs-table-wrapper bg-white rounded shadow p-4">
            <table class="w-full logs-table">
              <thead>
                <tr>
                  <th class="p-3 text-left">Date</th>
                  <th class="p-3 text-left">User</th>
                  <th class="p-3 text-left">Activity</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="p-3">2025-05-01 08:15 AM</td>
                  <td>Admin (Xavier Seth)</td>
                  <td>Uploaded "Personal Data Sheet.pdf"</td>
                </tr>
                <tr>
                  <td class="p-3">2025-05-01 08:45 AM</td>
                  <td>Admin Staff (Kent)</td>
                  <td>Added Teacher "Maria Dela Cruz"</td>
                </tr>
                <tr>
                  <td class="p-3">2025-05-01 09:20 AM</td>
                  <td>Admin (Xavier Seth)</td>
                  <td>Downloaded "DTR April.pdf"</td>
                </tr>
                <tr>
                  <td class="p-3">2025-05-01 10:00 AM</td>
                  <td>Admin Staff (Anna)</td>
                  <td>Updated User "Kent Admin Staff"</td>
                </tr>
                <tr>
                  <td class="p-3">2025-05-01 11:30 AM</td>
                  <td>Admin (Xavier Seth)</td>
                  <td>Added new user "John Doe (Admin Staff)"</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import Sidebar from "@/Components/Sidebar.vue";
  import { usePage } from "@inertiajs/vue3";
  
  const user = usePage().props.auth.user;
  const userName = `${user.last_name}, ${user.first_name}`;
  const userRole = user?.role ? `(${user.role})` : '';
  </script>
  
  <style scoped>
  .flex-1 {
    margin-left: 200px;
    padding: 20px;
  }
  
  .top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: white;
    width: 100%;
    border-radius: 0 0 10px 10px;
    flex-wrap: wrap;
    gap: 10px;
  }
  
  .brand {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .school-logo {
    width: 60px;
    height: auto;
  }
  
  .profile {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #ccc;
    object-fit: cover;
  }
  
  .user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
  
  .user-name {
    font-weight: bold;
  }
  
  .user-role {
    font-size: 12px;
    color: gray;
  }
  
  .logs-table-wrapper {
    overflow-x: auto;
  }
  
  .logs-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .logs-table th {
    background-color: #0d0c37;
    color: white;
    padding: 10px;
    font-size: 14px;
  }
  
  .logs-table td {
    padding: 10px;
    font-size: 14px;
    border-bottom: 1px solid #ddd;
  }
  
  .logs-table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
  }
  </style>
  