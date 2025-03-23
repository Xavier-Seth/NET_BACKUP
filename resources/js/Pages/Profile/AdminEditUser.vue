<template>
    <div class="app-layout">
      <Sidebar />
      <div class="main-content">
        <Header />
        <div class="profile-settings">
          <h2 v-if="!loading && user">Editing Profile of {{ form.last_name }}, {{ form.first_name }}</h2>
  
          <!-- ✅ Show loading spinner while fetching -->
          <div v-if="loading" class="loading">Loading user profile...</div>
  
          <!-- ✅ Show error message if fetching fails -->
          <div v-if="errorMessage" class="error-message">
            {{ errorMessage }}
          </div>
  
          <div v-if="!loading && user" class="profile-container">
            <div class="profile-image">
              <img :src="user.profilePicture || '/images/user-avatar.png'" alt="Profile Picture" />
              <button class="change-photo" @click="changePhoto">Change Photo</button>
            </div>
            <form @submit.prevent="updateProfile" class="profile-form">
              <div class="form-row">
                <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" v-model="form.last_name" :disabled="!isEditing" />
                </div>
                <div class="form-group">
                  <label>First Name</label>
                  <input type="text" v-model="form.first_name" :disabled="!isEditing" />
                </div>
                <div class="form-group">
                  <label>Middle Name</label>
                  <input type="text" v-model="form.middle_name" :disabled="!isEditing" />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Sex</label>
                  <select v-model="form.sex" :disabled="!isEditing">
                    <option>Male</option>
                    <option>Female</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Civil Status</label>
                  <select v-model="form.civil_status" :disabled="!isEditing">
                    <option>Single</option>
                    <option>Married</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Date of Birth</label>
                  <input type="date" v-model="form.date_of_birth" :disabled="!isEditing" />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Religion</label>
                  <input type="text" v-model="form.religion" :disabled="!isEditing" />
                </div>
                <div class="form-group">
                  <label>Phone Number</label>
                  <input type="text" v-model="form.phone_number" :disabled="!isEditing" />
                </div>
                <div class="form-group">
                  <label>Email Address</label>
                  <input type="email" v-model="form.email" :disabled="!isEditing" />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" v-model="form.password" :disabled="!isEditing" placeholder="Leave blank if unchanged" />
                </div>
                <div class="form-group">
                  <label>Role</label>
                  <select v-model="form.role" :disabled="!isEditing">
                    <option>Admin</option>
                    <option>LIS</option>
                    <option>User</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Status</label>
                  <select v-model="form.status" :disabled="!isEditing">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
              </div>
              <div class="button-group">
                <button type="button" class="edit-btn" @click="toggleEdit">
                  {{ isEditing ? 'Cancel' : 'Edit' }}
                </button>
                <button type="submit" class="update-btn" v-if="isEditing">
                  Update
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router'; // ✅ Import Vue Router
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from 'axios';

const users = ref([]);
const searchQuery = ref("");
const loading = ref(true);
const router = useRouter(); // ✅ Define `router`
const selectedStatus = ref("");
const selectedRoles = ref({ admin: false, lis: false });

// ✅ Confirm Edit & Redirect Admin to Edit Page
const confirmEdit = (user) => {
    console.log("Selected User:", user); // ✅ Debugging Log

    if (!user || !user.id) {
        alert("Invalid user selected.");
        return;
    }

    if (confirm(`Do you want to edit the profile of ${user.last_name}, ${user.first_name}?`)) {
        console.log("Redirecting to AdminEditUser.vue for ID:", user.id); // ✅ Debugging Log
        router.push({ name: 'admin.edit-user', params: { id: user.id } }); // ✅ Fix Undefined Error
    }
};
</script>

  
  <style scoped>
  .loading {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    color: #007bff;
  }
  .error-message {
    color: #ff0000;
    font-weight: bold;
    text-align: center;
    margin-bottom: 15px;
  }
  .app-layout {
    display: flex;
    height: 100vh;
    overflow: hidden;
  }
  .main-content {
    flex: 1;
    padding: 20px;
    margin-left: 220px;
    overflow-y: auto;
  }
  .profile-settings {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    width: 90%;
    max-width: 1200px;
    margin: auto;
    margin-top: 20px;
  }
  .profile-container {
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .profile-image img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
  }
  .change-photo {
    margin-top: 10px;
    padding: 10px 15px;
    border: none;
    background: #007bff;
    color: white;
    border-radius: 6px;
    cursor: pointer;
  }
  </style>
  