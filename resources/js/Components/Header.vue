<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, nextTick } from 'vue'

const page = usePage()
const user = page.props.auth?.user || {}
const showLogoutModal = ref(false)
const cancelBtn = ref(null)

// Branding
const branding = computed(() => page.props.branding || { school_name: 'Rizal Central School', logo_url: null })

// Avatar
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

// Logout action (called after confirming in modal)
function logout() {
  router.post(route('logout'), { preserveScroll: true })
  showLogoutModal.value = false
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
      <!-- Left: Branding -->
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

      <!-- Right: User Profile Dropdown -->
      <div class="profile dropdown ms-auto">
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
            <!-- Open the pretty modal -->
            <button @click="openLogoutModal" class="dropdown-item text-danger">Logout</button>
          </li>
        </ul>
      </div>
    </div>
  </header>

  <!-- Pretty Logout Confirmation Modal (Vue-only) -->
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
              <!-- circle exclamation icon -->
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
            <button ref="cancelBtn" class="btn btn-light" @click="showLogoutModal = false">Cancel</button>
            <button class="btn btn-danger" @click="logout">Logout</button>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>

<style scoped>
/* ===== Header (unchanged) ===== */
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
.profile { display: flex; align-items: center; gap: 10px; min-width: 180px; justify-content: flex-end; }
.avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #eee; object-fit: cover; }
.user-info { display: flex; flex-direction: column; align-items: flex-start; }
.user-name { font-weight: 700; }
.user-role { font-size: 12px; color: #6b7280; }
.dropdown-toggle { display: flex; align-items: center; gap: 8px; background: none; border: none; cursor: pointer; }
.dropdown-menu { background: white; border-radius: 10px; box-shadow: 0 8px 20px rgba(0,0,0,0.12); border: 1px solid #f0f0f0; }
.dropdown-item { color: #111827; padding: 10px 14px; }
.dropdown-item:hover { background: #f7f7f7; }
.ms-auto { margin-left: auto; }

/* ===== Pretty Modal ===== */
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.pop-enter-active, .pop-leave-active { transition: transform .16s ease, opacity .16s ease; }
.pop-enter-from, .pop-leave-to { transform: translateY(10px) scale(.98); opacity: 0; }

.ux-modal {
  position: fixed; inset: 0;
  background: rgba(17,24,39,.55); /* slate-900/55 */
  display: grid; place-items: center;
  z-index: 1050;
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
  width: 36px; height: 36px;
  border-radius: 9999px;
  display: grid; place-items: center;
  background: #fee2e2;       /* red-100 */
  color: #dc2626;             /* red-600 */
}
.ux-modal-title {
  margin: 0; font-size: 16px; font-weight: 700; color: #111827;
  flex: 1;
}
.btn-close {
  appearance: none; border: 0; background: transparent; width: 30px; height: 30px;
  mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="%23000" d="M1.146 1.854 7.293 8l-6.147 6.146.708.708L8 8.707l6.146 6.147.708-.708L8.707 8l6.147-6.146-.708-.708L8 7.293 1.854 1.146z"/></svg>') center / 16px 16px no-repeat;
  background-color: #6b7280; opacity: .8; cursor: pointer; border-radius: 6px;
}
.btn-close:hover { opacity: 1; }

.ux-modal-body { padding: 16px; color: #374151; font-size: 14px; }

.ux-modal-footer {
  display: flex; justify-content: flex-end; gap: 8px;
  padding: 12px 16px;
  background: #fafafa; border-top: 1px solid #f1f1f1;
}

/* Buttons (use your Bootstrap classes; extra polish below) */
.btn { padding: 8px 14px; border-radius: 10px; font-weight: 600; }
.btn-light { background: #f3f4f6; border: 1px solid #e5e7eb; color: #374151; }
.btn-light:hover { background: #e5e7eb; }
.btn-danger { background: #dc2626; border: 1px solid #dc2626; }
.btn-danger:hover { background: #b91c1c; border-color: #b91c1c; }
</style>
