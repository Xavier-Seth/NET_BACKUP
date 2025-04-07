<template>
  <MainLayout activeMenu="dashboard">
    <!-- ✅ Success Notification -->
    <div v-if="success" class="notification-bar">
      {{ success }}
    </div>

    <!-- ✅ Stat Cards -->
    <section class="stats">
      <div class="stat-card navy">
        <i class="bi bi-file-earmark-text stat-icon"></i>
        <h3>Total Documents</h3>
        <p>{{ totalDocuments }}</p>
      </div>
      <div class="stat-card green">
        <i class="bi bi-people stat-icon"></i>
        <h3>Active Users</h3>
        <p>{{ totalUsers }}</p>
      </div>
      <div class="stat-card red">
        <i class="bi bi-hdd-stack stat-icon"></i>
        <h3>Storage Usage</h3>
        <p>{{ totalStorage }}</p>
      </div>
    </section>

    <!-- ✅ Recently Uploaded -->
    <section class="files-table-section">
      <div class="table-heading">
        <h2>Recently Uploaded</h2>
      </div>
      <div class="table-container">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Filename</th>
              <th>Type</th>
              <th>Uploaded By</th>
              <th>Uploaded At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="file in recentUploads.slice(0, 5)" :key="file.id">
              <td>{{ file.filename }}</td>
              <td class="text-capitalize">{{ file.type || 'N/A' }}</td>
              <td>{{ file.user ? file.user.name : 'N/A' }}</td>
              <td>{{ new Date(file.created_at).toLocaleString() }}</td>
              <td>
                <a
                  :href="`/storage/${file.pdf_preview_path || file.file_path}`"
                  target="_blank"
                  class="btn-action view"
                >
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const totalDocuments = page.props.totalDocuments
const totalUsers = page.props.totalUsers
const totalStorage = page.props.totalStorage
const recentUploads = page.props.recentUploads || []
const success = page.props.success
</script>

<style scoped>
.notification-bar {
  background-color: #d1e7dd;
  color: #0f5132;
  padding: 12px;
  text-align: center;
  border-radius: 6px;
  margin: 10px 80px;
  font-weight: bold;
  border: 1px solid #badbcc;
}

.stats {
  margin-top: 30px;
  margin-left: 80px;
  display: flex;
  justify-content: flex-start;
  gap: 75px;
  flex-wrap: wrap;
}
.stat-card {
  flex: 1;
  min-width: 240px;
  max-width: 280px;
  height: 150px;
  border-radius: 15px;
  box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.1);
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}
.stat-icon {
  font-size: 2rem;
  margin-bottom: 10px;
}
.navy {
  background: #19184f;
}
.green {
  background: #002500;
}
.red {
  background: #7b0828;
}

.files-table-section {
  margin-top: 20px;
  margin-left: 80px;
  margin-right: 20px;
}
.table-heading {
  margin-bottom: 10px;
}
.table-container {
  background: white;
  border-radius: 10px;
  padding: 15px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow-x: auto;
}
.dashboard-table {
  width: 100%;
  border-collapse: collapse;
}
.dashboard-table th,
.dashboard-table td {
  padding: 15px;
  border-bottom: 1px solid #ddd;
  font-size: 15px;
}
.dashboard-table thead th {
  background: #19184f;
  color: white;
  white-space: nowrap;
}

.btn-action {
  border: none;
  background: none;
  cursor: pointer;
  font-size: 18px;
  color: #333;
}
.btn-action.view i {
  color: #0d6efd;
}
.btn-action:hover {
  color: #007bff;
}

@media (max-width: 768px) {
  .stats {
    margin-left: 20px;
    justify-content: center;
    gap: 20px;
  }
  .files-table-section {
    margin-left: 20px;
    margin-right: 20px;
  }
  .stat-card {
    max-width: 100%;
  }
  .dashboard-table th,
  .dashboard-table td {
    font-size: 14px;
    padding: 10px;
  }
}
</style>
