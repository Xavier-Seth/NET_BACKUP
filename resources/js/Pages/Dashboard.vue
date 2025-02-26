<template>
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
          <img src="/images/user-avatar.png" alt="User Avatar" class="avatar dropdown-toggle" data-bs-toggle="dropdown" />
          <span class="dropdown-toggle" data-bs-toggle="dropdown">Xavier Noynay</span>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#">Profile Settings</a></li>
            <li><a class="dropdown-item" @click="navigateToRegister">Register New User</a></li>
            <li><a class="dropdown-item text-danger" href="#">Logout</a></li>
          </ul>
        </div>
      </header>

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
    </div>
  </div>
</template>

<script>
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue"; // Import Sidebar Component

export default {
  components: {
    Sidebar, // Register Sidebar Component
  },
  data() {
    return {
      activeMenu: "dashboard", // Default active menu
    };
  },
  methods: {
    setActive(menuItem) {
      this.activeMenu = menuItem;
    },
    navigateToRegister() {
      router.visit(route('register'));
    }
  },
};
</script>

<style scoped>
/* Overall Layout */
.dashboard-layout {
  display: flex;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: #f5f5f5;
}

/* Sidebar */

/* Main Content */
.main-content {
  flex: 1;
  margin-left: 220px; /* Ensure content is not hidden behind sidebar */
  display: flex;
  flex-direction: column;
  padding: 20px;
}

/* Top Bar */
.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: rgb(0, 0, 0);
  padding: 10px 20px;
  border-radius: 10px;
}

/* Brand */
.brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.school-logo {
  width: 50px;
  height: auto;
}

/* Search Bar */
.search-container {
  display: flex;
  position: relative;
  margin-top: 20px;
  margin-left: 4%;
}

.search-bar {
  padding: 8px;
  border-radius: 20px;
  border: 2px solid #3d3b3b;
  outline: none;
  width: 400px;
  height: 30px;
  color: black;
}

.search-icon {
  position: absolute;
  top: 4px;
  left: 350px;
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
  width: 40px;
  height: 40px;
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
  margin-left: 60px;
  display: flex;
  justify-content: flex-start;
  gap: 80px;
}

.stat-card {
  width: 300px;
  height: 150px;
  background: #1D1B42;
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

/* Colors for Each Card */
.navy {
  background: #19184F;
}
.green {
  background: #002500;
}
.red {
  background: #7B0828;
}
</style>
