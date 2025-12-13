<template>
  <MainLayout activeMenu="logs">
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
            <tr v-for="log in paginatedLogs" :key="log.id">
              <td class="p-3">{{ formatDate(log.created_at) }}</td>
              
              <td class="p-3">
                <span v-if="isMe(log.user_id)" class="text-success fw-bold">
                  You
                </span>
                <span v-else>
                  {{ log.user?.role || 'System' }} ({{ getUserName(log.user) }})
                </span>
              </td>

              <td class="p-3">{{ log.activity }}</td>
            </tr>
            <tr v-if="paginatedLogs.length === 0">
              <td class="p-3 text-center text-muted" colspan="3">
                No log entries found.
              </td>
            </tr>
          </tbody>
        </table>

        <div class="mt-4 flex justify-center items-center space-x-2" v-if="totalPages > 1">
          <button
            class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400"
            :disabled="currentPage === 1"
            @click="currentPage--"
          >
            Prev
          </button>

          <span class="font-medium text-sm">
            Page {{ currentPage }} of {{ totalPages }}
          </span>

          <button
            class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400"
            :disabled="currentPage === totalPages"
            @click="currentPage++"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import MainLayout from '@/Layouts/MainLayout.vue'; // Ensures consistent layout

const props = defineProps({
  logs: Array,
});

const page = usePage();
const currentUser = page.props.auth.user;

const currentPage = ref(1);
const perPage = 20;

const totalPages = computed(() =>
  Math.ceil(props.logs.length / perPage)
);

const paginatedLogs = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return props.logs.slice(start, start + perPage);
});

function formatDate(datetime) {
  return new Date(datetime).toLocaleString();
}

function getUserName(user) {
  if (!user) return "Unknown";
  return `${user.first_name} ${user.last_name}`;
}

// Check if the log belongs to the currently logged-in user
function isMe(logUserId) {
  return currentUser && currentUser.id === logUserId;
}
</script>

<style scoped>
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

/* Helper class for "You" text color (Bootstrap-like green) */
.text-success {
  color: #198754;
}
.fw-bold {
  font-weight: 700;
}
</style>