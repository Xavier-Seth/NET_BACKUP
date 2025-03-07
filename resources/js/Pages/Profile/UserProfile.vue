<template>
  <div class="app-layout">
    <Sidebar />
    <div class="main-content">
      <Header />
      <div class="profile-settings">
        <h2>Profile Settings</h2>
        <div class="profile-container">
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
                <input type="text" v-model="user.role" disabled />
              </div>
              <div class="form-group">
                <label>Status</label>
                <input type="text" v-model="user.status" disabled />
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
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Header from '@/Components/Header.vue';

const props = usePage().props;

// Define editing toggle variable only once
const isEditing = ref(false);

// Extract user props
const user = props.user;

// Correctly format the date for the HTML input (YYYY-MM-DD)
const formattedDob = user.date_of_birth ? user.date_of_birth.split('T')[0] : '';

const form = useForm({
  first_name: user.first_name,
  last_name: user.last_name,
  middle_name: user.middle_name,
  sex: user.sex,
  civil_status: user.civil_status,
  date_of_birth: formattedDate(user.date_of_birth),
  religion: user.religion,
  phone_number: user.phone_number,
  email: user.email,
  password: '',
  password_confirmation: '',
});


function toggleEdit() {
  isEditing.value = !isEditing.value;
  if (!isEditing.value) {
    form.reset();
    form.date_of_birth = formattedDate(user.date_of_birth);
  }
}

function formattedDate(date) {
  return date ? date.substring(0, 10) : '';
}

function updateProfile() {
  form.patch(route('profile.update'), {
    preserveScroll: true,
    onSuccess: () => {
      isEditing.value = false;
      form.reset('password', 'password_confirmation');
    },
  });
}

function changePhoto() {
  console.log('Change photo clicked');
}
</script>






<style scoped>
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
.form-row {
  display: flex;
  gap: 30px;
  margin-top: 15px;
}
.form-group {
  flex: 1;
}
.form-group input,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
}
.button-group {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 25px;
}
.edit-btn,
.update-btn {
  background: #007bff;
  color: white;
  border: none;
  padding: 12px 25px;
  cursor: pointer;
  border-radius: 6px;
}
.update-btn {
  background: #28a745;
}
</style>
