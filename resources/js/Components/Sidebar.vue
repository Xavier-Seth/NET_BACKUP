<template>
  <aside class="sidebar">
    <nav class="menu">
      <ul>
        <li v-for="item in menuItems" :key="item.path">
          <a :href="item.path" :class="{ active: isActive(item.path) }">
            <div class="icon-circle" :class="{ 'active-icon': isActive(item.path) }">
              <i :class="item.icon"></i>
            </div>
            <span>{{ item.label }}</span>
          </a>
        </li>
        <li class="exit" @click="logout">
          <div class="icon-circle exit-icon">
            <i class="bi bi-box-arrow-right"></i>
          </div>
          <span>Exit</span>
        </li>
      </ul>
    </nav>
  </aside>
</template>

<script>
export default {
  name: "Sidebar",
  data() {
    return {
      menuItems: [
        { path: "/dashboard", label: "Dashboard", icon: "bi bi-grid" },
        { path: "/users", label: "Users", icon: "bi bi-people" },
        { path: "/documents", label: "Documents", icon: "bi bi-file-earmark-text" },
        { path: "/active-files", label: "Active Files", icon: "bi bi-folder2-open" },
        { path: "/upload", label: "Upload Files", icon: "bi bi-upload" }
      ],
    };
  },
  methods: {
    isActive(path) {
      return window.location.pathname === path;
    },
    logout() {
      console.log("User logged out");
      // Implement logout functionality here
    }
  }
};
</script>

<style scoped>
/* Sidebar */
.sidebar {
  width: 220px;
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  background: linear-gradient(to bottom, #12172B, #1a1f3a);
  color: white;
  padding: 10px 0;
  z-index: 1000;
}

/* Menu Styling */
.menu {
  margin-top: 35px;
}

.menu ul {
  list-style: none;
  padding: 0;
  margin: 0;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.menu li {
  margin: 10px 0;
}

.menu li a {
  text-decoration: none;
  color: white; /* Keep text white */
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: 0.3s ease-in-out;
}

/* Active Link Styling */
.menu li a.active {
  font-weight: bold;
}

/* Icon Circle */
.icon-circle {
  width: 45px;
  height: 45px;
  background: #2c2f48;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  margin-bottom: 3px;
  transition: 0.3s ease-in-out;
}

/* Active Icon (Only the circle turns white, text remains white) */
.active-icon {
  background: white !important;
  color: #1a1f3a !important;
}

/* Hover Effect */
.menu li a:hover .icon-circle {
  background: white;
  color: #1a1f3a; /* Change icon color when hovered */
  transition: 0.3s ease-in-out;
}

/* Exit Button */
.exit {
  margin-top: auto;
  cursor: pointer;
}

.exit:hover {
  opacity: 0.8;
}
</style>
