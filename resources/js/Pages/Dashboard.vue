<template>
  <MainLayout activeMenu="dashboard">
    <!-- Search Bar -->
    <div class="search-container">
      <input type="text" placeholder="Search..." class="search-bar" />
      <i class="bi bi-search search-icon"></i>
    </div>

    <!-- Stat Cards -->
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

    <!-- Table of Recently Uploaded -->
    <section class="files-table-section">
      <div class="table-heading">
        <h2>Recently Uploaded</h2>
      </div>
      <div class="table-container">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Uploaded File</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(file, index) in recentUploads" :key="index">
              <td>{{ file.name }}</td>
              <td>{{ file.uploadedFile }}</td>
              <td>
                <button class="btn-action edit">
                  <i class="bi bi-pencil-square"></i>
                </button>
                <button class="btn-action delete">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const totalDocuments = page.props.totalDocuments;
const totalUsers = page.props.totalUsers;
const totalStorage = page.props.totalStorage;

const recentUploads = [
  { name: 'Document 1', uploadedFile: 'file1.pdf' },
  { name: 'Document 2', uploadedFile: 'file2.docx' }
];
</script>

<style scoped>
.search-container {
  display: flex;
  position: relative;
  margin-top: 10px;
  margin-left: 4%;
  width: 400px;
}
.search-bar {
  width: 100%;
  padding: 8px;
  border-radius: 20px;
  border: 2px solid #3d3b3b;
  outline: none;
  height: 30px;
  color: black;
  padding-right: 30px;
}
.search-icon {
  position: absolute;
  top: 50%;
  right: 10px;
  transform: translateY(-50%);
  color: gray;
  cursor: pointer;
}
.stats {
  margin-top: 30px;
  margin-left: 80px;
  display: flex;
  justify-content: flex-start;
  gap: 75px;
}
.stat-card {
  width: 280px;
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
.navy { background: #19184f; }
.green { background: #002500; }
.red { background: #7b0828; }

.files-table-section {
  margin-top: 5px;
  margin-left: 80px;
}
.table-container {
  background: white;
  border-radius: 10px;
  padding: 15px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.dashboard-table {
  width: 100%;
  border-collapse: collapse;
}
.dashboard-table th, .dashboard-table td {
  padding: 15px;
  border-bottom: 1px solid #ddd;
  font-size: 15px;
}
.dashboard-table thead th {
  background: #19184f;
  color: white;
}
.btn-action {
  border: none;
  background: none;
  cursor: pointer;
  margin-right: 10px;
  font-size: 18px;
  color: #333;
}
.btn-action:hover { color: #007bff; }
.edit i { color: #ffa500; }
.delete i { color: #dc3545; }
</style>
