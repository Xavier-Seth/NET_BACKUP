<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar />

    <div class="flex-1 ml-[200px]">
      <!-- Toast Alert -->
      <transition name="fade">
        <div
          v-if="showToast"
          class="fixed top-0 left-0 right-0 z-50 bg-green-600 text-white text-center py-2 shadow"
        >
          User updated successfully.
        </div>
      </transition>

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
              <input ref="photoRef" type="file" accept="image/*" class="d-none" @change="handlePhotoUpload" />
            </label>
            <h5 class="mt-2 fw-bold">Edit User Profile</h5>
          </div>
        </div>
      </div>

      <!-- Success Modal -->
      <transition name="fade">
        <div v-if="showSuccessModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div class="bg-white p-6 rounded shadow-lg text-center max-w-sm w-full">
            <h4 class="text-xl font-semibold mb-2">✅ User Updated!</h4>
            <p class="text-gray-700 mb-4">Go back to Users list?</p>
            <div class="d-flex justify-content-center gap-3">
              <button class="btn btn-success" @click="goToUsers">Yes</button>
              <button class="btn btn-secondary" @click="showSuccessModal = false">No</button>
            </div>
          </div>
        </div>
      </transition>

      <!-- ERROR Modal -->
      <transition name="fade">
        <div v-if="showErrorModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div class="bg-white p-6 rounded shadow-lg max-w-md w-full">
            <div class="flex items-start gap-3">
              <div class="text-red-600 text-2xl">⚠️</div>
              <div class="flex-1">
                <h4 class="text-lg font-semibold mb-2">{{ errorTitle }}</h4>
                <ul class="list-disc pl-5 space-y-1 text-gray-700">
                  <li v-for="(msg, i) in errorMessages" :key="i">{{ msg }}</li>
                </ul>
              </div>
            </div>
            <div class="text-right mt-4">
              <button class="btn btn-danger" @click="showErrorModal = false">Close</button>
            </div>
          </div>
        </div>
      </transition>

      <!-- Form -->
      <div class="container d-flex justify-content-center mt-1">
        <div class="form-wrapper bg-white shadow p-4 w-100" style="max-width: 1000px; border-radius: 0 0 10px 10px;">
          <form @submit.prevent="submitForm">
            <!-- Name -->
            <div class="row mb-3" style="margin-top: 6rem;">
              <div class="col-md-4">
                <label class="form-label">Last Name *</label>
                <input v-model="form.last_name" class="form-control" required />
              </div>
              <div class="col-md-4">
                <label class="form-label">First Name *</label>
                <input v-model="form.first_name" class="form-control" required />
              </div>
              <div class="col-md-4">
                <label class="form-label">Middle Name</label>
                <input v-model="form.middle_name" class="form-control" />
              </div>
            </div>

            <!-- Sex / Civil Status / DOB -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Sex *</label>
                <select v-model="form.sex" class="form-control" required>
                  <option value="">Select</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Civil Status *</label>
                <select v-model="form.civil_status" class="form-control" required>
                  <option value="">Select</option>
                  <option value="Single">Single</option>
                  <option value="Married">Married</option>
                  <option value="Widowed">Widowed</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Date of Birth *</label>
                <input type="date" v-model="form.date_of_birth" class="form-control" required />
              </div>
            </div>

            <!-- Contact -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Religion</label>
                <input v-model="form.religion" class="form-control" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Phone Number *</label>
                <input type="tel" v-model="form.phone_number" class="form-control" required />
              </div>
              <div class="col-md-4">
                <label class="form-label">Email *</label>
                <input type="email" v-model="form.email" class="form-control" required />
              </div>
            </div>

            <!-- Password / Status / Role -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">New Password</label>
                <input type="password" v-model="form.password" class="form-control" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Confirm Password</label>
                <input type="password" v-model="form.password_confirmation" class="form-control" />
              </div>
              <div class="col-md-2">
                <label class="form-label">Status *</label>
                <select v-model="form.status" class="form-control" required>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Role *</label>
                <select v-model="form.role" class="form-control" required>
                  <option value="Admin">Admin</option>
                  <option value="Admin Staff">Admin Staff</option>
                  <option value="User">User</option>
                </select>
              </div>
            </div>

            <!-- Buttons -->
            <div class="text-end mt-4 d-flex justify-content-between">
              <div>
                <button type="button" class="btn btn-outline-secondary me-2" @click="cancelChanges">Cancel Changes</button>
                <button type="button" class="btn btn-outline-dark" @click="goToUsers">Back to Users List</button>
              </div>
              <button type="submit" class="btn btn-success">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import Sidebar from '@/Components/Sidebar.vue'

const user = usePage().props.user
const photoPreview = ref(null)
const showSuccessModal = ref(false)
const showToast = ref(false)

// Error modal state
const showErrorModal = ref(false)
const errorTitle = ref('Action blocked')
const errorMessages = ref([])

const form = useForm({
  first_name: user.first_name ?? '',
  last_name: user.last_name ?? '',
  middle_name: user.middle_name ?? '',
  sex: user.sex ?? '',
  civil_status: user.civil_status ?? '',
  date_of_birth: user.date_of_birth?.substring(0, 10) ?? '',
  religion: user.religion ?? '',
  phone_number: user.phone_number ?? '',
  email: user.email ?? '',
  role: user.role ?? '',
  status: user.status ?? '',
  password: '',
  password_confirmation: '',
  photo: null,
})

function openErrorModal(messages, title = 'Action blocked') {
  errorTitle.value = title
  errorMessages.value = Array.isArray(messages) ? messages : [messages]
  showErrorModal.value = true
}

function handlePhotoUpload(e) {
  const file = e.target.files[0]
  if (file) {
    form.photo = file
    photoPreview.value = URL.createObjectURL(file)
  }
}

function submitForm() {
  // client-side guard for password mismatch
  if (form.password && form.password !== form.password_confirmation) {
    openErrorModal('Passwords do not match!')
    return
  }

  form.post(route('admin.update-user.post', { id: user.id }), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      photoPreview.value = null
      showSuccessModal.value = true
      showTemporaryToast()
    },
    onError: (errors) => {
      // errors is an object like { role: '...', status: '...' }
      const msgs = Object.values(errors).filter(Boolean)
      openErrorModal(msgs.length ? msgs : 'An error occurred while updating the user.')
    }
  })
}

function goToUsers() {
  router.visit(route('users.index'))
}

function cancelChanges() {
  form.reset()
  photoPreview.value = null
}

function showTemporaryToast() {
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 3000)
}
</script>

<style scoped>
.form-wrapper {
  background-color: #fff;
  padding: 25px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
  border-radius: 10px;
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
