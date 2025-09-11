<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar />

    <div class="flex-1 ml-[200px]">
      <!-- Banner -->
      <div class="container d-flex justify-content-center mt-3">
        <div class="position-relative w-100" style="max-width: 1000px;">
          <img
            src="/images/rizal.jpg"
            class="w-100 rounded-top"
            style="height: 200px; object-fit: cover;"
          />
          <div class="position-absolute top-100 start-50 translate-middle text-center">
            <label style="cursor: pointer;">
              <img
                v-if="photoPreview"
                :src="photoPreview"
                class="rounded-circle border border-white shadow"
                style="width: 160px; height: 160px; object-fit: cover;"
                alt="Preview"
              />
              <img
                v-else
                src="/images/user-avatar.png"
                class="rounded-circle border border-white shadow"
                style="width: 160px; height: 160px; object-fit: cover;"
                alt="Default"
              />
              <input
                ref="photoRef"
                type="file"
                accept="image/*"
                class="d-none"
                @change="handlePhotoUpload($event)"
              />
            </label>
            <h5 class="mt-2 fw-bold">Register User</h5>
          </div>
        </div>
      </div>

      <!-- Form Section -->
      <div class="container d-flex justify-content-center mt-1">
        <div class="form-wrapper bg-white shadow p-4 w-100" style="max-width: 1000px; border-radius: 0 0 10px 10px;">
          <form @submit.prevent="handleSubmit" ref="formRef" autocomplete="off">
            <div class="row mb-3" style="margin-top: 6rem;">
              <div class="col-md-4">
                <label class="form-label">Last Name</label>
                <input type="text" v-model="form.last_name" @input="filterText(form, 'last_name')" class="form-control" required />
                <div v-if="inputWarnings.last_name" class="text-warning mt-1">{{ inputWarnings.last_name }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">First Name</label>
                <input type="text" v-model="form.first_name" @input="filterText(form, 'first_name')" class="form-control" required />
                <div v-if="inputWarnings.first_name" class="text-warning mt-1">{{ inputWarnings.first_name }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Middle Name</label>
                <input type="text" v-model="form.middle_name" @input="filterText(form, 'middle_name')" class="form-control" />
                <div v-if="inputWarnings.middle_name" class="text-warning mt-1">{{ inputWarnings.middle_name }}</div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Sex</label>
                <select v-model="form.sex" class="form-control" required>
                  <option value="">Select</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Civil Status</label>
                <select v-model="form.civil_status" class="form-control" required>
                  <option value="">Select</option>
                  <option value="Single">Single</option>
                  <option value="Married">Married</option>
                  <option value="Widowed">Widowed</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Date of Birth</label>
                <input type="date" v-model="form.date_of_birth" class="form-control" required />
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Religion</label>
                <input type="text" v-model="form.religion" @input="filterText(form, 'religion')" class="form-control" />
                <div v-if="inputWarnings.religion" class="text-warning mt-1">{{ inputWarnings.religion }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Email</label>
                <input
                  type="email"
                  v-model="form.email"
                  class="form-control"
                  :class="{'is-invalid': localErrors.email}"
                  required
                />
                <div v-if="localErrors.email" class="text-danger mt-1">{{ localErrors.email }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Phone Number</label>
                <input type="text" v-model="form.phone_number" @input="filterPhone(form, 'phone_number')" class="form-control" required />
                <div v-if="inputWarnings.phone_number" class="text-warning mt-1">{{ inputWarnings.phone_number }}</div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Password</label>
                <input type="password" v-model="form.password" class="form-control" required />
              </div>
              <div class="col-md-4">
                <label class="form-label">Confirm Password</label>
                <input type="password" v-model="form.password_confirmation" class="form-control" required />
              </div>
              <div class="col-md-4">
                <label class="form-label">Role</label>
                <select v-model="form.role" class="form-control" required>
                  <option value="Admin Staff">Admin Staff</option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Status</label>
                <select v-model="form.status" class="form-control" required>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>

            <div class="text-end">
              <button type="submit" class="btn btn-primary">Register</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Confirm Modal -->
      <div
        v-if="showConfirm"
        class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
        @keydown.esc="closeAll"
        tabindex="0"
      >
        <div class="bg-white p-6 rounded-lg shadow-lg w-96 relative">
          <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2" @click="closeAll">✕</button>
          <h5 class="mb-3">Confirm Registration</h5>
          <p class="text-gray-600">Are you sure you want to register this user?</p>
          <div class="mt-4 d-flex justify-content-end gap-2">
            <button class="btn btn-success" @click="registerUser">Yes</button>
            <button class="btn btn-outline-secondary" @click="closeAll">Cancel</button>
          </div>
        </div>
      </div>

      <!-- Info Modal (success / error / validation) -->
      <div
        v-if="info.show"
        class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
        @keydown.esc="closeAll"
        tabindex="0"
      >
        <div class="bg-white p-6 rounded-lg shadow-lg w-96 relative">
          <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2" @click="closeAll">✕</button>
          <div class="d-flex align-items-center mb-2">
            <span
              v-if="info.type !== 'default'"
              :class="['rounded-circle me-2 d-inline-flex align-items-center justify-content-center',
                       info.type==='success' ? 'bg-success' : (info.type==='error' ? 'bg-danger' : 'bg-secondary')]"
              style="width:28px;height:28px;"
            >
              <span class="text-white fw-bold">{{ info.type==='success' ? '✓' : '!' }}</span>
            </span>
            <h5 class="m-0">{{ info.title }}</h5>
          </div>
          <p class="text-gray-700">{{ info.message }}</p>
          <div class="mt-4 d-flex justify-content-end">
            <button class="btn btn-primary" @click="closeAll">OK</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

export default {
  name: 'RegisterUser',
  components: { Sidebar },

  setup() {
    const showConfirm = ref(false);
    const info = ref({ show: false, title: '', message: '', type: 'default' });

    const showInfo = (title, message, type = 'default') => {
      info.value = { show: true, title, message, type };
    };
    const closeAll = () => {
      showConfirm.value = false;
      info.value.show = false;
    };

    const photoRef = ref(null);
    const photoPreview = ref(null);
    const formRef = ref(null);

    const inputWarnings = ref({
      first_name: '',
      last_name: '',
      middle_name: '',
      religion: '',
      phone_number: '',
    });

    const localErrors = ref({
      email: '',
    });

    const showTemporaryWarning = (field, message, duration = 2500) => {
      inputWarnings.value[field] = message;
      setTimeout(() => (inputWarnings.value[field] = ''), duration);
    };

    const form = useForm({
      last_name: '',
      first_name: '',
      middle_name: '',
      sex: '',
      civil_status: '',
      date_of_birth: '',
      religion: '',
      email: '',
      phone_number: '',
      password: '',
      password_confirmation: '',
      role: 'Admin Staff',
      status: 'active',
      photo: null,
    });

    watch(() => form.email, (val) => {
      if (val.trim() === '') {
        localErrors.value.email = 'Email is required.';
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        localErrors.value.email = 'Invalid email format.';
      } else {
        localErrors.value.email = '';
      }
    });

    const handlePhotoUpload = (event) => {
      const file = event.target.files[0];
      form.photo = file;
      photoPreview.value = file && file.type.startsWith('image/')
        ? URL.createObjectURL(file)
        : null;
    };

    const handleSubmit = () => {
      const el = formRef.value;
      if (el && el.checkValidity()) {
        showConfirm.value = true;
      } else {
        showInfo('Incomplete Form', 'Please complete all required fields before submitting.', 'error');
        el && el.reportValidity();
      }
    };

    const registerUser = () => {
      showConfirm.value = false;

      if (form.password !== form.password_confirmation) {
        showInfo('Password Mismatch', 'Passwords do not match. Please re-enter matching passwords.', 'error');
        return;
      }

      if (localErrors.value.email) {
        showInfo('Invalid Email', localErrors.value.email, 'error');
        return;
      }

      form.post(route('register'), {
        forceFormData: true,
        onSuccess: () => {
          showInfo('Success', 'User registered successfully!', 'success');
          form.reset();
          form.clearErrors();
          if (photoRef.value) photoRef.value.value = '';
          photoPreview.value = null;
        },
        onError: (errors) => {
          const firstKey = Object.keys(errors)[0];
          const msg = firstKey ? String(errors[firstKey]) : 'An error occurred during registration.';
          showInfo('Registration Failed', msg, 'error');
        },
      });
    };

    const allowedNameChars = /[^a-zA-ZñÑ\s-]/g;
    const allowedPhoneChars = /[^0-9]/g;

    const filterText = (obj, field) => {
      const original = obj[field] ?? '';
      const filtered = original.replace(allowedNameChars, '');
      if (original !== filtered) showTemporaryWarning(field, 'Only letters, spaces, and hyphens allowed.');
      obj[field] = filtered;
    };

    const filterPhone = (obj, field) => {
      const original = obj[field] ?? '';
      const filtered = original.replace(allowedPhoneChars, '');
      if (original !== filtered) showTemporaryWarning(field, 'Only numbers are allowed.');
      obj[field] = filtered;
    };

    return {
      form,
      formRef,
      registerUser,
      photoRef,
      photoPreview,
      handlePhotoUpload,
      handleSubmit,
      filterText,
      filterPhone,
      inputWarnings,
      localErrors,
      showConfirm,
      info,
      closeAll,
    };
  },
};
</script>

<style scoped>
.form-wrapper {
  background-color: #ffffff;
  padding: 25px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
}
.text-warning {
  font-size: 0.875rem;
  color: #d39e00;
}
.text-danger {
  font-size: 0.875rem;
  color: #dc3545;
}
.is-invalid {
  border-color: #dc3545;
}
</style>
