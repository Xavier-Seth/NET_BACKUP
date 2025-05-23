<template>
  <header class="top-bar">
    <div class="container">
      <!-- Left: Brand -->
      <div class="brand">
        <img src="/images/school_logo.png" alt="School Logo" class="school-logo" />
        <h1 class="school-name">Rizal Central School</h1>
      </div>

      <!-- Right: User Profile Dropdown -->
      <div class="profile dropdown">
        <button
          class="dropdown-toggle"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          type="button"
        >
          <img src="/images/user-avatar.png" alt="User Avatar" class="avatar" />
          <div class="user-info">
            <span class="user-name">{{ userName }}</span>
            <small class="user-role">{{ userRole }}</small>
          </div>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <Link class="dropdown-item" :href="route('profile.edit')">
              Profile Settings
            </Link>
          </li>
          <li>
            <button @click="checkRole" class="dropdown-item">
              Register New User
            </button>
          </li>
          <li>
            <button @click="checkRoleStudent" class="dropdown-item">
              Register Student
            </button>
          </li>
          <li>
            <button @click="logout" class="dropdown-item text-danger">
              Logout
            </button>
          </li>
        </ul>
      </div>
    </div>
  </header>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = page.props.auth.user

const userName = user ? `${user.last_name}, ${user.first_name}` : 'User'
const userRole = user?.role ? `(${user.role})` : ''

const checkRole = () => {
  if (user.role !== 'Admin') {
    alert('❌ Access Denied: Only Admin users can register new users.')
  } else {
    router.visit(route('register'))
  }
}

// ✅ Allow Admin and Admin Staff to register students
const checkRoleStudent = () => {
  if (user.role !== 'Admin' && user.role !== 'Admin Staff') {
    alert('❌ Access Denied: Only Admin and Admin Staff can register students.')
  } else {
    router.visit(route('students.register'))
  }
}

const logout = () => {
  if (confirm('Are you sure you want to log out?')) {
    router.post(route('logout'), {
      preserveScroll: true,
    })
  }
}
</script>

<style scoped>
.top-bar {
  background: white;
  padding: 12px 20px;
  box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
  border-radius: 10px;
  margin-bottom: 1rem;
  width: 100%;
}

.container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
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

.school-name {
  font-size: 18px;
  font-weight: bold;
  white-space: nowrap;
}

.profile {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 180px;
  justify-content: flex-end;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #ccc;
  object-fit: cover;
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.user-name {
  font-weight: bold;
}

.user-role {
  font-size: 12px;
  color: gray;
}

.dropdown-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: none;
  cursor: pointer;
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
</style>
