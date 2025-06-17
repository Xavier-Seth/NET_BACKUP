<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar />

    <div class="flex-1 ml-[200px]">
      <!-- Banner -->
      <div class="container d-flex justify-content-center mt-3">
        <div class="position-relative w-100" style="max-width: 1000px;">
          <img src="/images/rizal.jpg" class="w-100 rounded-top" style="height: 200px; object-fit: cover;" />
          <div class="position-absolute top-100 start-50 translate-middle text-center">
            <label style="cursor: pointer;">
              <img
                :src="photoPreview || (user.profilePicture ? `${user.profilePicture}?t=${Date.now()}` : '/images/user-avatar.png')"
                class="rounded-circle border border-white shadow"
                style="width: 160px; height: 160px; object-fit: cover;"
                alt="Profile"
              />
              <input
                ref="photoRef"
                type="file"
                accept="image/*"
                class="d-none"
                @change="handlePhotoUpload"
                :disabled="!isEditing"
              />
            </label>
            <h5 class="mt-2 fw-bold">Profile Settings</h5>
          </div>
        </div>
      </div>

      <!-- Success Modal -->
      <transition name="fade">
        <div v-if="showSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white p-6 rounded shadow-lg text-center max-w-sm w-full">
            <h4 class="text-xl font-semibold mb-2">✅ Profile Updated!</h4>
            <p class="text-gray-700 mb-4">Your changes have been saved successfully.</p>
            <button @click="showSuccessModal = false" class="btn btn-success px-4">OK</button>
          </div>
        </div>
      </transition>

      <!-- Form -->
      <div class="container d-flex justify-content-center mt-1">
        <div class="form-wrapper bg-white shadow p-4 w-100" style="max-width: 1000px; border-radius: 0 0 10px 10px;">
          <form @submit.prevent="updateProfile">
            <!-- Name -->
            <div class="row mb-3" style="margin-top: 6rem;">
              <div class="col-md-4">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input
                  v-model="form.last_name"
                  @input="filterText(form, 'last_name')"
                  class="form-control"
                  :class="inputClass"
                  :readonly="!isEditing"
                  required
                />
                <div v-if="inputWarnings.last_name" class="text-warning mt-1">{{ inputWarnings.last_name }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input
                  v-model="form.first_name"
                  @input="filterText(form, 'first_name')"
                  class="form-control"
                  :class="inputClass"
                  :readonly="!isEditing"
                  required
                />
                <div v-if="inputWarnings.first_name" class="text-warning mt-1">{{ inputWarnings.first_name }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Middle Name</label>
                <input
                  v-model="form.middle_name"
                  @input="filterText(form, 'middle_name')"
                  class="form-control"
                  :class="inputClass"
                />
                <div v-if="inputWarnings.middle_name" class="text-warning mt-1">{{ inputWarnings.middle_name }}</div>
              </div>
            </div>

            <!-- Sex / Civil Status / DOB -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Sex <span class="text-danger">*</span></label>
                <select
                  v-model="form.sex"
                  class="form-control"
                  :class="inputClass"
                  :disabled="!isEditing"
                  required
                >
                  <option value="">Select</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                <select
                  v-model="form.civil_status"
                  class="form-control"
                  :class="inputClass"
                  :disabled="!isEditing"
                  required
                >
                  <option value="">Select</option>
                  <option value="Single">Single</option>
                  <option value="Married">Married</option>
                  <option value="Widowed">Widowed</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Date of Birth</label>
                <input
                  type="date"
                  v-model="form.date_of_birth"
                  class="form-control"
                  :class="inputClass"
                  :readonly="!isEditing"
                />
              </div>
            </div>

            <!-- Contact -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Religion</label>
                <input
                  v-model="form.religion"
                  @input="filterText(form, 'religion')"
                  class="form-control"
                  :class="inputClass"
                />
                <div v-if="inputWarnings.religion" class="text-warning mt-1">{{ inputWarnings.religion }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Phone Number</label>
                <input
                  type="tel"
                  v-model="form.phone_number"
                  @input="filterPhone(form, 'phone_number')"
                  class="form-control"
                  :class="inputClass"
                />
                <div v-if="inputWarnings.phone_number" class="text-warning mt-1">{{ inputWarnings.phone_number }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input
                  type="email"
                  v-model="form.email"
                  class="form-control"
                  :class="inputClass"
                  :readonly="!isEditing"
                  required
                />
                <div v-if="emailError" class="text-danger mt-1">{{ emailError }}</div>
              </div>
            </div>

            <!-- Password -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">New Password</label>
                <input
                  type="password"
                  v-model="form.password"
                  class="form-control"
                  :readonly="!isEditing"
                  placeholder="Leave blank if unchanged"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Confirm Password</label>
                <input
                  type="password"
                  v-model="form.password_confirmation"
                  class="form-control"
                  :readonly="!isEditing"
                  placeholder="Retype password"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Status</label>
                <input
                  type="text"
                  :value="capitalize(user.status)"
                  readonly
                  class="form-control"
                />
              </div>
            </div>

            <!-- Role -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Role</label>
                <input
                  type="text"
                  :value="user.role"
                  readonly
                  class="form-control"
                />
              </div>
            </div>

            <!-- Buttons -->
            <div class="text-end mt-4">
              <button type="button" class="btn btn-secondary me-2" @click="toggleEdit">
                {{ isEditing ? 'Cancel' : 'Edit Profile' }}
              </button>
              <button v-if="isEditing" type="submit" class="btn btn-success">Update</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import Sidebar from '@/Components/Sidebar.vue'

const user = computed(() => usePage().props.user || {})
const isEditing = ref(false)
const photoPreview = ref(null)
const showSuccessModal = ref(false)
const emailError = ref('')

const inputWarnings = ref({
  first_name: '',
  last_name: '',
  middle_name: '',
  religion: '',
  phone_number: '',
})

function showTemporaryWarning(field, message, duration = 2500) {
  inputWarnings.value[field] = message
  setTimeout(() => {
    inputWarnings.value[field] = ''
  }, duration)
}

const allowedNameChars = /[^a-zA-ZñÑ\s-]/g
const allowedPhoneChars = /[^0-9]/g

function filterText(obj, field) {
  const original = obj[field]
  const filtered = original.replace(allowedNameChars, '')
  if (original !== filtered) showTemporaryWarning(field, 'Only letters, spaces, and hyphens allowed.')
  obj[field] = filtered
}

function filterPhone(obj, field) {
  const original = obj[field]
  const filtered = original.replace(allowedPhoneChars, '')
  if (original !== filtered) showTemporaryWarning(field, 'Only numbers are allowed.')
  obj[field] = filtered
}

const form = useForm({
  first_name: user.value.first_name ?? '',
  last_name: user.value.last_name ?? '',
  middle_name: user.value.middle_name ?? '',
  sex: user.value.sex ?? '',
  civil_status: user.value.civil_status ?? '',
  date_of_birth: user.value.date_of_birth?.substring(0, 10) ?? '',
  religion: user.value.religion ?? '',
  phone_number: user.value.phone_number ?? '',
  email: user.value.email ?? '',
  password: '',
  password_confirmation: '',
  photo: null,
})

watch(() => form.email, (val) => {
  if (val.trim() === '') {
    emailError.value = 'Email is required.'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
    emailError.value = 'Invalid email format.'
  } else {
    emailError.value = ''
  }
})

const inputClass = computed(() => (isEditing.value ? '' : 'bg-light pointer-events-none'))

function toggleEdit() {
  isEditing.value = !isEditing.value
  if (!isEditing.value) {
    form.reset('password', 'password_confirmation')
    emailError.value = ''
  }
}

function capitalize(val) {
  return val ? val.charAt(0).toUpperCase() + val.slice(1) : ''
}

function handlePhotoUpload(e) {
  const file = e.target.files[0]
  if (file) {
    form.photo = file
    photoPreview.value = URL.createObjectURL(file)
  }
}

function updateProfile() {
  if (form.password && form.password !== form.password_confirmation) {
    alert('Passwords do not match!')
    return
  }

  if (emailError.value) {
    alert(emailError.value)
    return
  }

  form.post(route('profile.update'), {
    preserveScroll: true,
    preserveState: true,
    forceFormData: true,
    onSuccess: () => {
      toggleEdit()
      photoPreview.value = null
      router.reload({ only: ['user'] })
      showSuccessModal.value = true
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      alert(firstError || 'An error occurred while updating your profile.')
    },
  })
}
</script>

<style scoped>
.form-wrapper {
  background-color: #fff;
  padding: 25px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
  border-radius: 10px;
}
.pointer-events-none {
  pointer-events: none;
}
.bg-light {
  background-color: #e9ecef;
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
.text-warning {
  font-size: 0.875rem;
  color: #d39e00;
}
.text-danger {
  font-size: 0.875rem;
  color: #dc3545;
}
</style>
