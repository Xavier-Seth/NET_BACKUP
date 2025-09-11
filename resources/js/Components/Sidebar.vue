<template>
  <div>
    <aside :class="['sidebar', { open: isOpen }]">
      <!-- Logo -->
      <div class="logo">
        <img src="/images/school_logo.png" alt="School Logo" class="school-logo" />
      </div>

      <!-- Navigation Menu -->
      <nav class="menu">
        <ul>
          <li>
            <a @click="go('dashboard')" :class="['nav-link', { active: isActive('dashboard') }]">
              <i class="bi bi-grid"></i> Dashboard
            </a>
          </li>

          <li v-if="user.role === 'Admin'">
            <a @click="go('users.index')" :class="['nav-link', { active: isActive('users.index') }]">
              <i class="bi bi-people"></i> Users
            </a>
          </li>

          <li>
            <a @click="go('teachers.index')" :class="['nav-link', { active: isActive('teachers.index') }]">
              <i class="bi bi-person-lines-fill"></i> Teachers
            </a>
          </li>

          <!-- 🔧 Documents dropdown -->
          <li>
            <a class="nav-link" @click="toggle('Documents')">
              <i class="bi bi-folder2-open"></i> Documents
              <i class="bi bi-caret-left-fill caret-icon" :class="{ rotated: openDocs }"></i>
            </a>

            <ul v-if="openDocs" class="dropdown">
              <li>
                <a @click="go('documents.teachers-profile')" :class="['dropdown-link', { active: isActive('documents.teachers-profile') }]">
                  <i class="bi bi-folder"></i> Teachers Profile
                </a>
              </li>

              <li class="section-title">DTR's</li>
              <li>
                <a @click="go('/documents/dtr')" :class="['dropdown-link', { active: currentPath.startsWith('/documents/dtr') }]">
                  <i class="bi bi-file-earmark-text"></i> DTR's
                </a>
              </li>

              <li class="section-title">School Properties</li>
              <li>
                <a @click="go('/documents/school-properties?category=ICS')" :class="['dropdown-link', { active: currentPath.includes('ICS') }]">
                  <i class="bi bi-file-earmark-text"></i> ICS
                </a>
              </li>
              <li>
                <a @click="go('/documents/school-properties?category=RIS')" :class="['dropdown-link', { active: currentPath.includes('RIS') }]">
                  <i class="bi bi-file-earmark-text"></i> RIS
                </a>
              </li>
            </ul>
          </li>

          <li>
            <a @click="go('teachers.register')" :class="['nav-link', { active: isActive('teachers.register') }]">
              <i class="bi bi-person-badge"></i> Register Teachers
            </a>
          </li>

          <li>
            <a @click="go('upload')" :class="['nav-link', { active: isActive('upload') }]">
              <i class="bi bi-upload"></i> Upload Files
            </a>
          </li>

          <li>
            <a @click="go('logs.index')" :class="['nav-link', { active: isActive('logs.index') }]">
              <i class="bi bi-clock-history"></i> Logs
            </a>
          </li>

          <!-- ⚙️ Settings -->
          <li>
            <a @click="go('settings.index')" :class="['nav-link', { active: isActive('settings.index') || activeMenu === 'settings' }]">
              <i class="bi bi-gear"></i> Settings
            </a>
          </li>
        </ul>
      </nav>

      <!-- Logout -->
      <div class="logout" @click="logout">
        <i class="bi bi-box-arrow-right"></i> Logout
      </div>
    </aside>

    <!-- Overlay for mobile -->
    <div v-if="isOpen && isMobile" class="overlay" @click="isOpen = false"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, defineProps } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const props = defineProps({
  activeMenu: { type: String, default: '' } // ← from MainLayout/pages
})

const user = usePage().props.auth.user
const isOpen = ref(true)
const isMobile = window.innerWidth < 768
const openDocs = ref(false)
const currentPath = computed(() => usePage().url)

const toggle = (section) => {
  if (section === 'Documents') openDocs.value = !openDocs.value
}

const go = (pathOrRoute) => {
  if (typeof pathOrRoute === 'string' && pathOrRoute.startsWith('/')) {
    router.visit(pathOrRoute)
  } else {
    router.visit(route(pathOrRoute))
  }
  if (isMobile) isOpen.value = false
}

const isActive = (nameOrPath) => {
  const url = currentPath.value
  if (!nameOrPath.startsWith('/')) {
    // Prefer real route check; fall back to prop for manual highlight
    return route().current(nameOrPath) || props.activeMenu === nameOrPath
  }
  return url === nameOrPath
}

const logout = () => {
  if (confirm('Are you sure you want to log out?')) {
    router.post(route('logout'), { preserveScroll: true })
  }
}

onMounted(() => {
  window.addEventListener('keydown', (e) => {
    if (e.altKey && e.key.toLowerCase() === 's') {
      isOpen.value = !isOpen.value
    }
  })
})
</script>

<style scoped>
.sidebar {
  width: 210px;
  height: 100vh;
  background: linear-gradient(to bottom, #12172b, #1a1f3a);
  color: white;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1000;
  padding: 20px 0;
  overflow-y: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.sidebar::-webkit-scrollbar { display: none; }
.logo {
  display: flex; justify-content: center; align-items: center;
  padding: 10px 0; margin-bottom: 10px;
}
.logo img { width: 100px; height: auto; object-fit: contain; }
.menu { flex-grow: 1; }
.menu ul { list-style: none; padding: 0 0 0 10px; margin: 0; }
.nav-link {
  display: flex; align-items: center; padding: 10px 16px;
  gap: 10px; font-size: 15px; color: white; text-decoration: none;
  transition: background 0.2s, color 0.2s; border-radius: 12px;
}
.nav-link:hover { background: rgba(255,255,255,0.1); }
.nav-link.active {
  background: white; color: #12172b; font-weight: bold; border-radius: 12px;
}
.nav-link.disabled { opacity: .6; cursor: not-allowed; pointer-events: auto; }
.caret-icon { margin-left: auto; transition: transform .3s ease; }
.caret-icon.rotated { transform: rotate(-90deg); }
.dropdown { padding-left: 10px; margin-top: 5px; }
.dropdown-link {
  display: flex; align-items: center; gap: 8px; padding: 6px 16px;
  font-size: 14px; color: #ccc; text-decoration: none;
  transition: background .2s, color .2s; border-radius: 12px;
}
.dropdown-link:hover { background: rgba(255,255,255,0.1); color: white; }
.dropdown-link.active {
  background: white; color: #12172b; font-weight: bold; border-radius: 12px;
}
.section-title {
  font-size: 13px; font-weight: 600; color: #a1a1a1;
  margin-top: 10px; padding-left: 16px; text-transform: uppercase;
}
.logout {
  margin-top: 20px; padding: 12px 16px; font-size: 14px;
  color: white; cursor: pointer; display: flex; align-items: center; gap: 10px;
}
.logout:hover { background: rgba(255,255,255,0.1); }
.overlay {
  position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.3); z-index: 900;
}
</style>
