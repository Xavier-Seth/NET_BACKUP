<template>
  <header class="top-bar">
    <div class="brand">
      <img src="/images/school_logo.png" alt="School Logo" class="school-logo" />
      <h1>DocuNet</h1>
    </div>

    <!-- Profile Dropdown -->
    <div class="profile dropdown">
      <div class="dropdown-toggle" data-bs-toggle="dropdown">
        <img src="/images/user-avatar.png" alt="User Avatar" class="avatar" />
        <div class="user-info">
          <span class="user-name">{{ userName }}</span>
          <small class="user-role">{{ userRole }}</small>
        </div>
      </div>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="#">Profile Settings</a></li>
        <li>
          <button @click="checkRole" class="dropdown-item">Register New User</button>
        </li>
        <li>
          <Link class="dropdown-item text-danger" href="/logout" method="post" as="button">
            Logout
          </Link>
        </li>
      </ul>
    </div>
  </header>
</template>

<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";

const user = usePage().props.auth.user;
const userName = user ? `${user.last_name}, ${user.first_name}` : "User"; // Display Lastname, Firstname
const userRole = user?.role ? `(${user.role})` : ""; // Display user role

// Check role before navigating to register page
const checkRole = () => {
  if (user.role !== "Admin") {
    alert("❌ Access Denied: Only Admin users can register new users.");
  } else {
    router.visit("/register"); // ✅ Proceed if LIS
  }
};
</script>

<style scoped>
.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.school-logo {
  width: 60px;
  height: auto;
}

.profile {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  min-width: 180px; /* Ensures enough space for name */
  justify-content: flex-end; /* Keeps profile content aligned */
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #ccc;
  object-fit: cover;
  flex-shrink: 0; /* Prevents avatar from shrinking */
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

.dropdown-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap; /* Prevents name from wrapping */
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px; /* Prevents long names from expanding the element */
}
</style>
