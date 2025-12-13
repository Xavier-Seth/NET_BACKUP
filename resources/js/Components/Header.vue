<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, nextTick, onMounted, onUnmounted, watch } from 'vue'

const page = usePage()
const user = page.props.auth?.user || {}

// --- SMART NOTIFICATION LOGIC ---
const notifications = computed(() => page.props.auth.notifications || [])

// 1. Load "seen" IDs from browser storage
const getStoredReadIds = () => {
  try {
    return new Set(JSON.parse(localStorage.getItem('read_notification_ids') || '[]'))
  } catch (e) {
    return new Set()
  }
}

const locallyReadIds = ref(getStoredReadIds())
const localHasUnread = ref(false)

// 2. Check logic
const checkUnreadStatus = () => {
  const serverNotifications = notifications.value
  const hasTrulyNew = serverNotifications.some(n => !locallyReadIds.value.has(n.id))
  localHasUnread.value = hasTrulyNew
}

watch(notifications, () => {
  checkUnreadStatus()
}, { immediate: true })

let pollTimer = null

onMounted(() => {
  checkUnreadStatus()
  if (user.role === 'Teacher') {
    pollTimer = setInterval(() => {
      router.reload({ only: ['auth'] })
    }, 10000)
  }
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})

function markRead() {
  const currentIds = notifications.value.map(n => n.id)
  currentIds.forEach(id => locallyReadIds.value.add(id))
  localStorage.setItem('read_notification_ids', JSON.stringify([...locallyReadIds.value]))
  localHasUnread.value = false

  if (notifications.value.length > 0) {
    router.post(route('notifications.read'), {}, { preserveScroll: true })
  }
}
// ----------------------------------------------

const showLogoutModal = ref(false)
const cancelBtn = ref(null)
const isLoggingOut = ref(false) // <--- NEW: Track logout state

const branding = computed(() => page.props.branding || { school_name: 'Rizal Central School', logo_url: null })

const avatarUrl = computed(() => {
  return user.photo_path
    ? `/storage/${user.photo_path}?t=${Date.now()}`
    : '/images/user-avatar.png'
})

const userName = computed(() => {
  return user.last_name && user.first_name
    ? `${user.last_name}, ${user.first_name}`
    : 'User'
})
const userRole = computed(() => (user.role ? `(${user.role})` : ''))

function checkRole() {
  if (user.role !== 'Admin') {
    alert('❌ Access Denied: Only Admin users can register new users.')
  } else {
    router.visit(route('register'))
  }
}

function openLogoutModal() {
  showLogoutModal.value = true
  nextTick(() => cancelBtn.value?.focus())
}

// --- UPDATED LOGOUT FUNCTION ---
function logout() {
  if (isLoggingOut.value) return // Prevent double clicks
  isLoggingOut.value = true

  // Clear local storage on logout
  localStorage.removeItem('read_notification_ids')
  
  router.post(route('logout'), {}, {
    preserveScroll: true,
    onFinish: () => {
        isLoggingOut.value = false
        showLogoutModal.value = false
    },
    onError: () => {
        // If error (like 419 expired), force reload to login
        window.location.href = '/';
    }
  })
}

function handleImgError(e) {
  e.target.src = '/images/user-avatar.png'
}

function closeOnBackdrop(e) {
  if (e.target.classList.contains('ux-modal')) showLogoutModal.value = false
}
</script>

<template>
  <header class="top-bar">
    <div class="container">
      <div class="brand">
        <img
          v-if="branding.logo_url"
          :src="branding.logo_url"
          alt="Logo"
          class="brand-logo"
          @error="e => (e.target.style.display = 'none')"
        />
        <span class="school-name">{{ branding.school_name }}</span>
      </div>

      <div class="ms-auto d-flex align-items-center gap-3">

        <template v-if="user.role === 'Teacher'">
          
          <div class="dropdown">
            <button 
              class="btn-icon position-relative" 
              title="Notifications"
              data-bs-toggle="dropdown" 
              aria-expanded="false"
              @click="markRead"
            >
              <i class="bi bi-bell-fill"></i>
              <span v-if="localHasUnread" class="badge-dot"></span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end p-0 shadow border-0" style="width: 320px; max-height: 400px; overflow-y: auto;">
              <li class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark">Notifications</h6>
                <small v-if="localHasUnread" class="text-muted">New</small>
              </li>
              
              <li v-if="notifications.length === 0" class="p-4 text-center text-muted small">
                <i class="bi bi-bell-slash d-block fs-4 mb-2 opacity-50"></i>
                No new notifications
              </li>

              <li v-for="note in notifications" :key="note.id">
                <Link :href="route('logs.index')" class="dropdown-item p-3 border-bottom" style="white-space: normal;">
                  <div class="d-flex align-items-start">
                    <div class="me-3 mt-1 text-primary"><i class="bi bi-info-circle-fill"></i></div>
                    <div>
                      <div class="fw-semibold text-dark" style="font-size: 0.9rem;">
                        {{ note.data.message }}
                      </div>
                      <div class="text-muted small mt-1">
                        By {{ note.data.by_user }} • <span class="fst-italic">{{ new Date(note.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                      </div>
                    </div>
                  </div>
                </Link>
              </li>
            </ul>
          </div>

          <div class="profile-static d-flex align-items-center gap-2">
            <img :src="avatarUrl" alt="User Avatar" class="avatar" @error="handleImgError" />
            <div class="user-info">
              <span class="user-name">{{ userName }}</span>
              <small class="user-role">{{ userRole }}</small>
            </div>
          </div>
        </template>

        <div v-else class="profile dropdown">
          <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" type="button">
            <img :src="avatarUrl" alt="User Avatar" class="avatar" @error="handleImgError" />
            <div class="user-info">
              <span class="user-name">{{ userName }}</span>
              <small class="user-role">{{ userRole }}</small>
            </div>
          </button>

          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <Link class="dropdown-item" :href="route('profile.edit')">Profile Settings</Link>
            </li>
            <li v-if="user.role === 'Admin'">
              <button @click="checkRole" class="dropdown-item">Register New User</button>
            </li>
            <li>
              <button @click="openLogoutModal" class="dropdown-item text-danger">Logout</button>
            </li>
          </ul>
        </div>

      </div>
    </div>
  </header>

  <transition name="fade">
    <div v-if="showLogoutModal" class="ux-modal" @click="closeOnBackdrop" @keyup.esc="showLogoutModal = false" tabindex="-1" role="dialog" aria-modal="true">
      <transition name="pop">
        <div class="ux-modal-card" role="document">
          <div class="ux-modal-header">
            <div class="ux-modal-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
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
            <button ref="cancelBtn" class="btn btn-light" @click="showLogoutModal = false" :disabled="isLoggingOut">Cancel</button>
            <button class="btn btn-danger" @click="logout" :disabled="isLoggingOut">
                <span v-if="isLoggingOut">Logging out...</span>
                <span v-else>Logout</span>
            </button>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>

<style scoped>
/* ===== Header ===== */
.top-bar {
  background: white;
  padding: 12px 20px;
  box-shadow: 0 4px 8px rgba(0,0,0,.08);
  border-radius: 12px;
  margin-bottom: 1rem;
  width: 100%;
}
.container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.brand { display: flex; align-items: center; gap: 10px; }
.brand-logo { height: 28px; width: auto; object-fit: contain; }
.school-name { font-size: 18px; font-weight: 700; white-space: nowrap; }

/* --- Teacher Bell Icon --- */
.btn-icon {
  background: #f3f4f6;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #12172b;
  font-size: 1.25rem;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}
.btn-icon:hover {
  background: #e5e7eb;
  color: #000;
  transform: translateY(-1px);
}
.badge-dot {
  position: absolute;
  top: 10px;
  right: 12px;
  width: 10px;
  height: 10px;
  background: #dc2626;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 0 0 1px #fff;
}

/* --- Common User Info Styles --- */
.avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #eee; object-fit: cover; }
.user-info { display: flex; flex-direction: column; align-items: flex-start; }
.user-name { font-weight: 700; color: #111827; }
.user-role { font-size: 12px; color: #6b7280; }

/* --- Admin Dropdown Specifics --- */
.profile { display: flex; align-items: center; gap: 10px; min-width: 180px; justify-content: flex-end; }
.dropdown-toggle { display: flex; align-items: center; gap: 8px; background: none; border: none; cursor: pointer; }
.dropdown-menu { background: white; border-radius: 10px; box-shadow: 0 8px 20px rgba(0,0,0,0.12); border: 1px solid #f0f0f0; }
.dropdown-item { color: #111827; padding: 10px 14px; }
.dropdown-item:hover { background: #f7f7f7; }
.ms-auto { margin-left: auto; }

/* ===== Modal Styles (Unchanged) ===== */
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.pop-enter-active, .pop-leave-active { transition: transform .16s ease, opacity .16s ease; }
.pop-enter-from, .pop-leave-to { transform: translateY(10px) scale(.98); opacity: 0; }
.ux-modal { position: fixed; inset: 0; background: rgba(17,24,39,.55); display: grid; place-items: center; z-index: 1050; }
.ux-modal-card { width: 420px; max-width: 92%; background: #fff; border-radius: 14px; box-shadow: 0 20px 50px rgba(0,0,0,.25); overflow: hidden; border: 1px solid rgba(0,0,0,.06); }
.ux-modal-header { display: flex; align-items: center; gap: 10px; padding: 14px 16px; border-bottom: 1px solid #f1f1f1; }
.ux-modal-icon { width: 36px; height: 36px; border-radius: 9999px; display: grid; place-items: center; background: #fee2e2; color: #dc2626; }
.ux-modal-title { margin: 0; font-size: 16px; font-weight: 700; color: #111827; flex: 1; }
.btn-close { appearance: none; border: 0; background: transparent; width: 30px; height: 30px; mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="%23000" d="M1.146 1.854 7.293 8l-6.147 6.146.708.708L8 8.707l6.146 6.147.708-.708L8.707 8l6.147-6.146-.708-.708L8 7.293 1.854 1.146z"/></svg>') center / 16px 16px no-repeat; background-color: #6b7280; opacity: .8; cursor: pointer; border-radius: 6px; }
.btn-close:hover { opacity: 1; }
.ux-modal-body { padding: 16px; color: #374151; font-size: 14px; }
.ux-modal-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 16px; background: #fafafa; border-top: 1px solid #f1f1f1; }
.btn { padding: 8px 14px; border-radius: 10px; font-weight: 600; }
.btn-light { background: #f3f4f6; border: 1px solid #e5e7eb; color: #374151; }
.btn-light:hover { background: #e5e7eb; }
.btn-danger { background: #dc2626; border: 1px solid #dc2626; }
.btn-danger:hover { background: #b91c1c; border-color: #b91c1c; }
</style>