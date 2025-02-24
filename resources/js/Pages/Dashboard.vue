<template>
  <div class="dashboard-layout">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Bar -->
      <header class="top-bar">
        <div class="brand">
          <img src="/images/school_logo.png" alt="School Logo" class="school-logo" />
          <h1>DocuNet</h1>
        </div>
        <div class="search-container">
          <input type="text" placeholder="Search..." class="search-bar" />
          <i class="bi bi-search search-icon"></i>
        </div>
        <div class="profile">
          <img src="/images/user-avatar.png" alt="User Avatar" class="avatar" />
          <span>Xavier Noynay</span>
        </div>
      </header>

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
  methods: {
    logout() {
      router.post("/logout", {}, {
        onFinish: () => {
          localStorage.removeItem("token");
          window.location.href = "/login";
        },
      });
    },
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
.sidebar {
  width: 220px;
}
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
  background: #007bff;
  color: white;
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
  align-items: center;
  position: relative;
}

.search-bar {
  padding: 8px;
  border-radius: 20px;
  border: 1px solid #ccc;
  outline: none;
  width: 220px;
  color: black;
}

.search-icon {
  position: absolute;
  right: 10px;
  color: gray;
  cursor: pointer;
}

/* Profile Section */
.profile {
  display: flex;
  align-items: center;
  gap: 10px;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid white;
}

/* Stat Cards */
.stats {
  margin-top: 35px;
  margin-left: 50px;
  display: flex;
  justify-content: flex-start;
  gap: 100px;
}

.stat-card {
  width: 300px;
  height: 180px;
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
  background: #1D1B42;
}
.green {
  background: #0B3D02;
}
.red {
  background: #8B0000;
}
</style>
