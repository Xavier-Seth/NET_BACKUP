<template>
  <keep-alive>
    <div class="dashboard-layout">
      <!-- Sidebar -->
      <Sidebar :activeMenu="activeMenu" @update-active="setActive" />

      <!-- Main Content -->
      <div class="main-content">
        <!-- Top Bar -->  
        <header class="top-bar">
          <div class="brand">
            <img src="/images/school_logo.png" alt="School Logo" class="school-logo" />
            <h1>DocuNet</h1>
          </div>

          <!-- Profile Dropdown -->
          <div class="profile dropdown">
            <div class="dropdown-toggle" data-bs-toggle="dropdown">
              <img src="/images/user-avatar.png" alt="User Avatar" class="avatar" />
              <span>Xavier Noynay</span>
            </div>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#">Profile Settings</a></li>
              <li><a class="dropdown-item" @click="navigateToRegister">Register New User</a></li>
              <li>
                <Link class="dropdown-item text-danger" href="/logout" method="post" as="button">
                  Logout
                </Link>
              </li>
            </ul>
          </div>
        </header>

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
            <p>3,467</p>
          </div>
          <div class="stat-card green">
            <i class="bi bi-people stat-icon"></i>
            <h3>Active Users</h3>
            <p>30</p>
          </div>
          <div class="stat-card red">
            <i class="bi bi-hdd-stack stat-icon"></i>
            <h3>Storage Usage</h3>
            <p>6.7GB</p>
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
                <!-- Three blank rows to represent recent uploads -->
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
      </div>
    </div>
  </keep-alive>
</template>

<script>
import { router } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";

export default {
  components: {
    Sidebar,
    Link,
  },
  data() {
    return {
      activeMenu: "dashboard",
      // Three dummy rows for now
      recentUploads: [
        { name: "", uploadedFile: "" },
        { name: "", uploadedFile: "" },
        { name: "", uploadedFile: "" },
      ],
    };
  },
  methods: {
    setActive(menuItem) {
      this.activeMenu = menuItem;
    },
    navigateToRegister() {
      router.visit("/register");
    },
  },
};
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: #f5f5f5;
}

.main-content {
  flex: 1;
  margin-left: 220px; /* Reserve space for the sidebar */
  display: flex;
  flex-direction: column;
  padding: 20px;
}

.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: rgb(0, 0, 0);
  padding: 10px 20px;
  border-radius: 10px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.school-logo {
  margin-top: -10px;
  width: 70px;
  height: auto;
  margin-left: 5px;
}

/* Search Container */
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

/* Profile Dropdown */
.profile {
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
  cursor: pointer;
}

.avatar {
  margin-top: -10px;
  width: 40px;
  height: 40px;
  margin-left: 30px;
  border-radius: 50%;
  border: 2px solid white;
}

.dropdown-menu {
  background: white;
  border-radius: 8px;
  box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);
}

.dropdown-item {
  color: black;
  padding: 10px;
}

.dropdown-item:hover {
  background: #f1f1f1;
}

/* Stat Cards */
.stats {
  margin-top: 30px;
  margin-left: 80px;
  display: flex;
  justify-content: flex-start;
  gap: 75px;
}

.stat-card {
  width: 300px;
  height: 150px;
  background: #1d1b42;
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

/* Table Section */
.files-table-section {
  margin-top: 10px;
  margin-left: 80px;
}

/* Table Heading */
.table-heading {
  margin-bottom: 15px;
}

.table-heading h2 {
  font-size: 20px;
  font-weight: bold;
  margin: 0;
}

/* Table Container */
.table-container {
  background: white;
  border-radius: 10px;
  padding: 15px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Dashboard Table */
.dashboard-table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 10px;
  overflow: hidden;
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
  font-weight: 600;
}

/* Action Buttons in Table */
.btn-action {
  border: none;
  background: none;
  cursor: pointer;
  margin-right: 10px;
  font-size: 18px;
  color: #333;
  transition: color 0.3s;
}

.btn-action:hover {
  color: #007bff;
}

.edit i {
  color: #ffa500; /* orange */
}

.delete i {
  color: #dc3545; /* red */
}
</style>
