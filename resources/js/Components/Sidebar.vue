<template>
  <div>
    <!-- Sidebar -->
    <aside :class="['sidebar', { open: isOpen }]">
      <!-- Toggle button (Mobile only) -->
      <button class="toggle-btn" @click="toggleSidebar">
        <i class="bi bi-list"></i>
      </button>

      <!-- Menu -->
      <nav class="menu">
        <ul>
          <li v-for="item in menuItems" :key="item.label" class="menu-parent">
            <a
              href="javascript:void(0);"
              @click="handleMenuClick(item)"
              :class="{ active: isActive(item.path) }"
              class="menu-item"
            >
              <div class="icon-box" :class="{ 'active-icon': isActive(item.path) }">
                <i :class="item.icon"></i>
              </div>
              <span>{{ item.label }}</span>
            </a>
          </li>
        </ul>
      </nav>

      <!-- Exit -->
      <div class="exit" @click="logout">
        <div class="icon-box exit-icon">
          <i class="bi bi-box-arrow-right"></i>
        </div>
        <span>Exit</span>
      </div>
    </aside>

    <!-- Overlay for mobile -->
    <div v-if="isOpen && isMobile" class="overlay" @click="isOpen = false"></div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const user = usePage().props.auth.user
const isOpen = ref(true)

const isMobile = window.innerWidth < 768

const toggleSidebar = () => {
  isOpen.value = !isOpen.value
}

const menuItems = computed(() => [
  { path: route('dashboard'), label: 'Dashboard', icon: 'bi bi-grid' },
  { path: route('users.index'), label: 'Users', icon: 'bi bi-people' },
  { path: route('documents'), label: 'Documents', icon: 'bi bi-file-earmark-text' },
  { path: route('students.records'), label: 'Student Records', icon: 'bi bi-folder2-open' },
  { path: route('upload'), label: 'Upload Files', icon: 'bi bi-upload' },
])

const handleMenuClick = (item) => {
  if (item.label === 'Users' && user.role !== 'Admin') {
    alert('❌ Access Denied: Only Admin users can access this page.')
  } else {
    router.visit(item.path)
    if (isMobile) isOpen.value = false
  }
}

const isActive = (path) => {
  return window.location.pathname === new URL(path, window.location.origin).pathname
}

const logout = () => {
  if (confirm('Are you sure you want to log out?')) {
    router.post(route('logout'), {
      preserveScroll: true,
    })
  }
}

onMounted(() => {
  // Keyboard shortcut: Alt + S to toggle sidebar
  window.addEventListener('keydown', (e) => {
    if (e.altKey && e.key.toLowerCase() === 's') {
      isOpen.value = !isOpen.value
    }
  })
})
</script>

<style scoped>
.sidebar {
  width: 200px;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  background: linear-gradient(to bottom, #12172B, #1a1f3a);
  color: white;
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  z-index: 1000;
  overflow-y: auto;
  scroll-behavior: smooth;
  transition: transform 0.3s ease-in-out;
}

/* Mobile Hidden State */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
  .sidebar.open {
    transform: translateX(0);
  }
}

/* Toggle Button */
.toggle-btn {
  display: none;
}

@media (max-width: 768px) {
  .toggle-btn {
    display: block;
    position: fixed;
    top: 10px;
    left: 10px;
    z-index: 1100;
    background: #12172b;
    color: white;
    border: none;
    font-size: 24px;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
  }
}

/* Menu Layout */
.menu {
  margin-top: 15px;
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
.menu-parent {
  position: relative;
  width: 100%;
}
.menu-item {
  text-decoration: none;
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  padding: 10px;
  transition: 0.3s ease-in-out;
  cursor: pointer;
}
.menu-item.active {
  font-weight: bold;
}
.icon-box {
  width: 40px;
  height: 40px;
  background: #2c2f48;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  margin-bottom: 4px;
  transition: 0.3s ease-in-out;
}
.active-icon {
  background: white !important;
  color: #1a1f3a !important;
}
.menu-item:hover .icon-box {
  background: white;
  color: #1a1f3a;
}

/* Exit Button */
.exit {
  margin-right: 12px;
  margin-bottom: 20px;
  text-align: center;
  cursor: pointer;
}
.exit-icon {
  margin-left: 80px;
  background: #d9534f;
}
.exit:hover {
  opacity: 0.8;
}

/* Overlay for mobile */
.overlay {
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  width: 100vw;
  background: rgba(0, 0, 0, 0.3);
  z-index: 900;
}
</style>
