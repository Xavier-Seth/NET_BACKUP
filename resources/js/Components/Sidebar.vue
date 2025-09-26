<script setup>
import { ref, onMounted, computed, defineProps, nextTick } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const props = defineProps({
  activeMenu: { type: String, default: '' }
})

const page = usePage()
const branding = computed(() => page.props.branding || { school_name: 'Rizal Central School', logo_url: null })
const user = page.props.auth.user

const isOpen = ref(true)
const isMobile = window.innerWidth < 768
const openDocs = ref(false)
const currentPath = computed(() => usePage().url)

// ===== Logout modal state =====
const showLogoutModal = ref(false)
const cancelBtn = ref(null)

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
    return route().current(nameOrPath) || props.activeMenu === nameOrPath
  }
  return url === nameOrPath
}

// Open pretty modal
function openLogoutModal () {
  showLogoutModal.value = true
  nextTick(() => cancelBtn.value?.focus())
}

// Perform logout
function doLogout () {
  router.post(route('logout'), { preserveScroll: true })
  showLogoutModal.value = false
}

function closeOnBackdrop (e) {
  if (e.target.classList.contains('ux-modal')) showLogoutModal.value = false
}

onMounted(() => {
  window.addEventListener('keydown', (e) => {
    if (e.altKey && e.key.toLowerCase() === 's') {
      isOpen.value = !isOpen.value
    }
  })
})
</script>

<template>
  <div>
    <aside :class="['sidebar', { open: isOpen }]">
      <!-- Logo / Branding -->
      <div class="logo" :title="branding.school_name">
        <img
          :src="branding.logo_url || '/images/school_logo.png'"
          :alt="branding.school_name"
          class="school-logo"
          @error="e => e.target.src = '/images/school_logo.png'"
        />
        <div class="school-caption" v-text="branding.school_name" />
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

          <!-- Documents -->
          <li>
            <a class="nav-link" @click="toggle('Documents')">
              <i class="bi bi-folder2-open"></i> Documents
              <i class="bi bi-caret-left-fill caret-icon" :class="{ rotated: openDocs }"></i>
            </a>

            <ul v-if="openDocs" class="dropdown">
              <li>
                <a
                  @click="go('documents.teachers-profile')"
                  :class="['dropdown-link', { active: isActive('documents.teachers-profile') }]"
                >
                  <i class="bi bi-folder"></i> Teachers Profile
                </a>
              </li>

              <li class="section-title">DTR's</li>
              <li>
                <a
                  @click="go('/documents/dtr')"
                  :class="['dropdown-link', { active: currentPath.startsWith('/documents/dtr') }]"
                >
                  <i class="bi bi-file-earmark-text"></i> DTR's
                </a>
              </li>

              <li class="section-title">School Properties</li>
              <li>
                <a
                  @click="go('/documents/school-properties?category=ICS')"
                  :class="['dropdown-link', { active: currentPath.includes('ICS') }]"
                >
                  <i class="bi bi-file-earmark-text"></i> ICS
                </a>
              </li>
              <li>
                <a
                  @click="go('/documents/school-properties?category=RIS')"
                  :class="['dropdown-link', { active: currentPath.includes('RIS') }]"
                >
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

          <li>
            <a
              @click="go('settings.index')"
              :class="['nav-link', { active: isActive('settings.index') || props.activeMenu === 'settings' }]"
            >
              <i class="bi bi-gear"></i> Settings
            </a>
          </li>
        </ul>
      </nav>

      <!-- Logout -->
      <div class="logout" @click="openLogoutModal">
        <i class="bi bi-box-arrow-right"></i> Logout
      </div>
    </aside>

    <!-- Overlay for mobile -->
    <div v-if="isOpen && isMobile" class="overlay" @click="isOpen = false"></div>

    <!-- ===== Pretty Logout Modal ===== -->
    <transition name="fade">
      <div
        v-if="showLogoutModal"
        class="ux-modal"
        @click="closeOnBackdrop"
        @keyup.esc="showLogoutModal = false"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
      >
        <transition name="pop">
          <div class="ux-modal-card" role="document">
            <div class="ux-modal-header">
              <div class="ux-modal-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm0 5a1.25 1.25 0 1 1-1.25 1.25A1.25 1.25 0 0 1 12 7Zm1.25 10h-2.5v-7h2.5Z"/>
                </svg>
              </div>
              <h5 class="ux-modal-title">Confirm Logout</h5>
              <button class="btn-close" @click="showLogoutModal = false" aria-label="Close"></button>
            </div>

            <div class="ux-modal-body">
              Are you sure you want to log out?
            </div>

            <div class="ux-modal-footer">
              <button ref="cancelBtn" class="btn btn-light" @click="showLogoutModal = false">Cancel</button>
              <button class="btn btn-danger" @click="doLogout">Logout</button>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </div>
</template>

<style scoped>
/* ===== Sidebar (unchanged) ===== */
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

.logo { display: grid; place-items: center; gap: 8px; padding: 10px 0; margin-bottom: 10px; text-align: center; }
.school-logo { width: 100px; height: auto; object-fit: contain; }
.school-caption { max-width: 160px; line-height: 1.2; font-size: 12px; color: #d8dcff; word-break: break-word; }

.menu { flex-grow: 1; }
.menu ul { list-style: none; padding: 0 0 0 10px; margin: 0; }

.nav-link {
  display: flex; align-items: center; padding: 10px 16px;
  gap: 10px; font-size: 15px; color: white; text-decoration: none;
  transition: background 0.2s, color 0.2s; border-radius: 12px;
}
.nav-link:hover { background: rgba(255,255,255,0.1); }
.nav-link.active { background: white; color: #12172b; font-weight: bold; border-radius: 12px; }
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
.dropdown-link.active { background: white; color: #12172b; font-weight: bold; border-radius: 12px; }

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

/* ===== Pretty Modal (same style as header modal) ===== */
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.pop-enter-active, .pop-leave-active { transition: transform .16s ease, opacity .16s ease; }
.pop-enter-from, .pop-leave-to { transform: translateY(10px) scale(.98); opacity: 0; }

.ux-modal {
  position: fixed; inset: 0;
  background: rgba(17,24,39,.55);
  display: grid; place-items: center;
  z-index: 2000;  /* above sidebar */
}

.ux-modal-card {
  width: 420px; max-width: 92%;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 20px 50px rgba(0,0,0,.25);
  overflow: hidden;
  border: 1px solid rgba(0,0,0,.06);
}

.ux-modal-header {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 16px;
  border-bottom: 1px solid #f1f1f1;
}
.ux-modal-icon {
  width: 36px; height: 36px; border-radius: 9999px; display: grid; place-items: center;
  background: #fee2e2; color: #dc2626;
}
.ux-modal-title { margin: 0; font-size: 16px; font-weight: 700; color: #111827; flex: 1; }
.btn-close {
  appearance: none; border: 0; background: transparent; width: 30px; height: 30px;
  mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="%23000" d="M1.146 1.854 7.293 8l-6.147 6.146.708.708L8 8.707l6.146 6.147.708-.708L8.707 8l6.147-6.146-.708-.708L8 7.293 1.854 1.146z"/></svg>') center / 16px 16px no-repeat;
  background-color: #6b7280; opacity: .8; cursor: pointer; border-radius: 6px;
}
.btn-close:hover { opacity: 1; }

.ux-modal-body { padding: 16px; color: #374151; font-size: 14px; }
.ux-modal-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 16px; background: #fafafa; border-top: 1px solid #f1f1f1; }

.btn { padding: 8px 14px; border-radius: 10px; font-weight: 600; }
.btn-light { background: #f3f4f6; border: 1px solid #e5e7eb; color: #374151; }
.btn-light:hover { background: #e5e7eb; }
.btn-danger { background: #dc2626; border: 1px solid #dc2626; }
.btn-danger:hover { background: #b91c1c; border-color: #b91c1c; }
</style>
