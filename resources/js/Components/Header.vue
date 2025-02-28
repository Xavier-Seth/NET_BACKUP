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
        <span>Xavier Noynay</span>
      </div>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="#">Profile Settings</a></li>
        <li><a class="dropdown-item" @click="confirmRegister">Register New User</a></li>
        <li>
          <Link class="dropdown-item text-danger" href="/logout" method="post" as="button">
            Logout
          </Link>
        </li>
      </ul>
    </div>
  </header>
</template>

<script>
import { Link, router } from "@inertiajs/vue3";

export default {
  components: { Link },
  methods: {
    confirmRegister() {
      if (confirm("Registering a new user will log you out of your current account. Do you want to continue?")) {
        this.logoutAndRedirect();
      }
    },
    logoutAndRedirect() {
      router.post("/logout", {}, {
        onSuccess: () => {
          router.visit("/register"); // Redirect to register page after logging out
        }
      });
    }
  }
};
</script>

<style scoped>
.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: rgb(0, 0, 0);
  padding: 10px 20px;
  border-radius: 10px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.school-logo {
  margin-top: -10px;
  width: 70px;
  height: auto;
  margin-left: 5px;
}

/* Profile Dropdown */
.profile {
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
  cursor: pointer;
}

.avatar {
  margin-top: -10px;
  width: 40px;
  height: 40px;
  margin-left: 30px;
  border-radius: 50%;
  border: 2px solid white;
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
