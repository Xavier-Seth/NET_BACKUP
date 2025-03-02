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
        <span>{{ userName }}</span>
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
import { ref } from "vue";

const user = usePage().props.auth.user;
const userName = user?.name || "User";

// Check role before navigating to register page
const checkRole = () => {
  if (user.role !== "LIS") {
    alert("❌ Access Denied: Only LIS users can register new users.");
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
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #ccc;
  object-fit: cover;
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
