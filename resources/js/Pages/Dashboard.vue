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
              <li><Link class="dropdown-item text-danger" href="/logout" method="post" as="button">Logout</Link></li>
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
  margin-left: 220px;
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
  width: 50px;
  height: auto;
}

.search-container {
  display: flex;
  position: relative;
  margin-top: 20px;
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
