<template>
  <aside class="sidebar">
    <nav class="menu">
      <ul>
        <li v-for="item in menuItems" :key="item.path">
          <!-- Using Inertia's Link component with explicit URL string -->
          <Link :href="`/${item.path}`" :class="{ active: isActive(item.path) }">
            <div class="icon-circle" :class="{ 'active-icon': isActive(item.path) }">
              <i :class="item.icon"></i>
            </div>
            <span>{{ item.label }}</span>
          </Link>
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
import { Link, router } from '@inertiajs/vue3';

export default {
  name: 'Sidebar',
  components: { Link },
  computed: {
    menuItems() {
      return [
        { path: 'dashboard', label: 'Dashboard', icon: 'bi bi-grid' },
        { path: 'users', label: 'Users', icon: 'bi bi-people' },
        { path: 'documents', label: 'Documents', icon: 'bi bi-file-earmark-text' },
        { path: 'active-files', label: 'Active Files', icon: 'bi bi-folder2-open' },
        { path: 'upload', label: 'Upload Files', icon: 'bi bi-upload' },
      ];
    },
  },
  methods: {
    isActive(path) {
      // Use window.location.pathname for active checking
      return window.location.pathname.startsWith(`/${path}`);
    },
    logout() {
      // Use a simple string URL for logout
      router.post('/logout');
    },
  },
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
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: 0.3s ease-in-out;
}

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

/* Active Icon */
.active-icon {
  background: white !important;
  color: #1a1f3a !important;
}

/* Hover Effect */
.menu li a:hover .icon-circle {
  background: white;
  color: #1a1f3a;
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
