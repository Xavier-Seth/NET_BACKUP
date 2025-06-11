<template>
  <div class="flex min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Page Content -->
    <div class="flex-1">
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
              <tr v-for="log in logs" :key="log.id">
                <td class="p-3">{{ formatDate(log.created_at) }}</td>
                <td class="p-3">
                  {{ log.user?.role || 'N/A' }} ({{ getUserName(log.user) }})
                </td>
                <td class="p-3">{{ log.activity }}</td>
              </tr>
              <tr v-if="logs.length === 0">
                <td class="p-3 text-center text-muted" colspan="3">
                  No log entries found.
                </td>
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

const props = defineProps({
  logs: Array,
});

function formatDate(datetime) {
  return new Date(datetime).toLocaleString();
}

function getUserName(user) {
  if (!user) return "Unknown";
  return `${user.first_name} ${user.last_name}`;
}
</script>

<style scoped>
.flex-1 {
  margin-left: 200px;
  padding: 20px;
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
