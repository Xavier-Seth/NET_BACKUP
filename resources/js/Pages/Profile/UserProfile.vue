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

          <div v-if="successMessage" class="success-message">
            {{ successMessage }}
          </div>

          <form @submit.prevent="updateProfile" class="profile-form">
            <div class="form-row">
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" v-model="form.last_name" :disabled="!isEditing" :class="{ 'input-error': form.errors.last_name }" />
                <span class="error" v-if="form.errors.last_name">{{ form.errors.last_name }}</span>
              </div>
              <div class="form-group">
                <label>First Name</label>
                <input type="text" v-model="form.first_name" :disabled="!isEditing" :class="{ 'input-error': form.errors.first_name }" />
                <span class="error" v-if="form.errors.first_name">{{ form.errors.first_name }}</span>
              </div>
              <div class="form-group">
                <label>Middle Name</label>
                <input type="text" v-model="form.middle_name" :disabled="!isEditing" :class="{ 'input-error': form.errors.middle_name }" />
                <span class="error" v-if="form.errors.middle_name">{{ form.errors.middle_name }}</span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Sex</label>
                <select v-model="form.sex" :disabled="!isEditing" :class="{ 'input-error': form.errors.sex }">
                  <option>Male</option>
                  <option>Female</option>
                </select>
                <span class="error" v-if="form.errors.sex">{{ form.errors.sex }}</span>
              </div>
              <div class="form-group">
                <label>Civil Status</label>
                <select v-model="form.civil_status" :disabled="!isEditing" :class="{ 'input-error': form.errors.civil_status }">
                  <option>Single</option>
                  <option>Married</option>
                </select>
                <span class="error" v-if="form.errors.civil_status">{{ form.errors.civil_status }}</span>
              </div>
              <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" v-model="form.date_of_birth" :disabled="!isEditing" :class="{ 'input-error': form.errors.date_of_birth }" />
                <span class="error" v-if="form.errors.date_of_birth">{{ form.errors.date_of_birth }}</span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Religion</label>
                <input type="text" v-model="form.religion" :disabled="!isEditing" :class="{ 'input-error': form.errors.religion }" />
                <span class="error" v-if="form.errors.religion">{{ form.errors.religion }}</span>
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <input
                  type="text"
                  inputmode="numeric"
                  maxlength="11"
                  pattern="[0-9]*"
                  v-model="form.phone_number"
                  @input="form.phone_number = form.phone_number.replace(/\D/g, '')"
                  :disabled="!isEditing"
                  :class="{ 'input-error': form.errors.phone_number }"
                />
                <span class="error" v-if="form.errors.phone_number">{{ form.errors.phone_number }}</span>
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" v-model="form.email" :disabled="!isEditing" :class="{ 'input-error': form.errors.email }" />
                <span class="error" v-if="form.errors.email">{{ form.errors.email }}</span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Password</label>
                <input
                  type="password"
                  v-model="form.password"
                  :disabled="!isEditing"
                  placeholder="Leave blank if unchanged"
                  :class="{ 'input-error': form.errors.password }"
                />
                <span class="error" v-if="form.errors.password">{{ form.errors.password }}</span>
              </div>
              <div class="form-group">
                <label>Confirm Password</label>
                <input
                  type="password"
                  v-model="form.password_confirmation"
                  :disabled="!isEditing"
                  placeholder="Re-type new password"
                  :class="{ 'input-error': form.errors.password_confirmation }"
                />
                <span class="error" v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</span>
              </div>
              <div class="form-group">
                <label>Status</label>
                <input type="text" :value="capitalize(user.status)" disabled />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group role-field">
                <label>Role</label>
                <input type="text" v-model="user.role" disabled />
              </div>
            </div>

            <div class="button-group">
              <button type="button" class="edit-btn" @click="toggleEdit">
                {{ isEditing ? 'Cancel' : 'Edit' }}
              </button>
              <button type="submit" class="update-btn" v-if="isEditing">Update</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Header from '@/Components/Header.vue';

const props = usePage().props;
const user = ref(props.user || {});
const successMessage = ref("");

const form = useForm({
  first_name: user.value.first_name || "",
  last_name: user.value.last_name || "",
  middle_name: user.value.middle_name || "",
  sex: user.value.sex || "",
  civil_status: user.value.civil_status || "",
  date_of_birth: user.value.date_of_birth ? user.value.date_of_birth.substring(0, 10) : "",
  religion: user.value.religion || "",
  phone_number: user.value.phone_number || "",
  email: user.value.email || "",
  password: "",
  password_confirmation: "",
});

const isEditing = ref(false);

const toggleEdit = () => {
  isEditing.value = !isEditing.value;
  if (!isEditing.value) {
    form.reset();
    form.date_of_birth = user.value.date_of_birth ? user.value.date_of_birth.substring(0, 10) : "";
  }
};

const capitalize = (val) => {
  if (!val) return '';
  return val.charAt(0).toUpperCase() + val.slice(1);
};

const updateProfile = () => {
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(form.email)) {
    form.setError('email', 'Please enter a valid email address.');
    return;
  }

  if (form.password && form.password !== form.password_confirmation) {
    form.setError('password_confirmation', 'Passwords do not match.');
    return;
  }

  form.patch(route('profile.update'), {
    preserveScroll: true,
    onSuccess: () => {
      isEditing.value = false;
      form.reset("password", "password_confirmation");
      successMessage.value = "Profile updated successfully! ✅";
      setTimeout(() => successMessage.value = "", 3000);
    },
    onError: () => {
      nextTick(() => {
        const errorField = document.querySelector('.input-error');
        if (errorField) errorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    },
  });
};

const changePhoto = () => {
  console.log('Change photo clicked');
};
</script>

<style scoped>
.app-layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  flex-direction: row;
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
  margin: 20px auto;
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
  object-fit: cover;
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

.success-message {
  background: #28a745;
  color: white;
  padding: 10px;
  border-radius: 6px;
  text-align: center;
  font-weight: bold;
  margin: 15px 0;
}

.form-row {
  display: flex;
  gap: 30px;
  margin-top: 15px;
  flex-wrap: wrap;
}

.form-group {
  flex: 1 1 250px;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
}

.input-error {
  border-color: #dc3545 !important;
  background-color: #fff0f0;
}

.error {
  color: #dc3545;
  font-size: 13px;
  margin-top: 5px;
  display: block;
}

.button-group {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 25px;
  flex-wrap: wrap;
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

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: 15px;
  }

  .profile-settings {
    padding: 20px;
    width: 100%;
    margin: 10px auto;
    box-shadow: none;
    border-radius: 0;
  }

  .form-row {
    flex-direction: column;
    gap: 10px;
  }

  .profile-image img {
    width: 120px;
    height: 120px;
  }

  .button-group {
    flex-direction: column;
    align-items: center;
  }

  .edit-btn,
  .update-btn {
    width: 100%;
    padding: 10px;
  }
}

.role-field input {
  max-width: 200px;
  margin-left: 5px;
}
</style>
