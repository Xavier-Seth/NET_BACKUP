<template>
  <div class="page-wrapper">
    <!-- ✅ Top Header -->
    <header class="top-bar">
      <div class="brand">
        <img src="/images/school_logo.png" alt="School Logo" class="school-logo" />
        <h1>Rizal Central School</h1>
      </div>

      <div class="profile dropdown">
        <div class="dropdown-toggle" data-bs-toggle="dropdown">
          <img src="/images/user-avatar.png" alt="User Avatar" class="avatar" />
          <div class="user-info">
            <span class="user-name">{{ userName }}</span>
            <small class="user-role">{{ userRole }}</small>
          </div>
        </div>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><Link class="dropdown-item" :href="route('profile.edit')">Profile Settings</Link></li>
          <li><button @click="checkRole" class="dropdown-item">Register New User</button></li>
          <li><button @click="checkRoleStudent" class="dropdown-item">Register Student</button></li>
          <li><Link class="dropdown-item text-danger" href="/logout" method="post" as="button">Logout</Link></li>
        </ul>
      </div>
    </header>

    <div class="container mt-4 p-4 form-wrapper rounded shadow">
      <button class="btn btn-link text-decoration-none back-arrow" @click="goBack">← Back to Dashboard</button>
      <h4 class="fw-bold text-center mb-4">Student Record</h4>

      <form @submit.prevent="submit">
        <!-- ✅ LRN -->
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">LRN:</label>
            <input v-model="form.lrn" type="text" inputmode="numeric" pattern="\d*" class="form-control" :class="{ 'is-invalid': form.errors.lrn }" @input="form.lrn = form.lrn.replace(/\D/g, '')" @paste="e => e.clipboardData.getData('text').match(/^\d+$/) ? null : e.preventDefault()" />
            <div v-if="form.errors.lrn" class="invalid-feedback d-block">{{ form.errors.lrn }}</div>
          </div>
        </div>

        <!-- ✅ Name Info -->
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">First Name:</label>
            <input v-model="form.first_name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.first_name }" />
            <div v-if="form.errors.first_name" class="invalid-feedback d-block">{{ form.errors.first_name }}</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Middle Name:</label>
            <input v-model="form.middle_name" type="text" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label">Last Name:</label>
            <input v-model="form.last_name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.last_name }" />
            <div v-if="form.errors.last_name" class="invalid-feedback d-block">{{ form.errors.last_name }}</div>
          </div>
        </div>

        <!-- ✅ Personal Info -->
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Birthdate:</label>
            <input v-model="form.birthdate" type="date" class="form-control" :class="{ 'is-invalid': form.errors.birthdate }" />
            <div v-if="form.errors.birthdate" class="invalid-feedback d-block">{{ form.errors.birthdate }}</div>
          </div>
          <div class="col-md-2">
            <label class="form-label">Sex:</label>
            <select v-model="form.sex" class="form-select" :class="{ 'is-invalid': form.errors.sex }">
              <option value="" disabled>Sex</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
            <div v-if="form.errors.sex" class="invalid-feedback d-block">{{ form.errors.sex }}</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Civil Status:</label>
            <input v-model="form.civil_status" type="text" class="form-control" />
          </div>
        </div>

        <!-- ✅ Citizenship & Birth -->
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Citizenship:</label>
            <input v-model="form.citizenship" type="text" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label">Place of Birth:</label>
            <input v-model="form.place_of_birth" type="text" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label">S/Y:</label>
            <input v-model="form.school_year" type="text" class="form-control" />
          </div>
        </div>

        <!-- ✅ Contact -->
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Guardian Phone No:</label>
            <input v-model="form.guardian_phone" type="text" inputmode="numeric" pattern="\d*" class="form-control" :class="{ 'is-invalid': form.errors.guardian_phone }" @input="form.guardian_phone = form.guardian_phone.replace(/\D/g, '')" @paste="e => e.clipboardData.getData('text').match(/^\d+$/) ? null : e.preventDefault()" />
            <div v-if="form.errors.guardian_phone" class="invalid-feedback d-block">{{ form.errors.guardian_phone }}</div>
          </div>
        </div>

        <!-- ✅ Address & Grade -->
        <div class="row mb-3">
          <div class="col-md-8">
            <label class="form-label">Permanent Address:</label>
            <input v-model="form.address" type="text" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label">Grade Level:</label>
            <select v-model="form.grade_level" class="form-select" :class="{ 'is-invalid': form.errors.grade_level }">
              <option value="" disabled>Select Grade Level</option>
              <option value="Grade 1">Grade 1</option>
              <option value="Grade 2">Grade 2</option>
              <option value="Grade 3">Grade 3</option>
              <option value="Grade 4">Grade 4</option>
              <option value="Grade 5">Grade 5</option>
              <option value="Grade 6">Grade 6</option>
            </select>
            <div v-if="form.errors.grade_level" class="invalid-feedback d-block">{{ form.errors.grade_level }}</div>
          </div>
        </div>

        <!-- ✅ Parents -->
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Father's Name:</label>
            <input v-model="form.father_name" type="text" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label">Mother's Name:</label>
            <input v-model="form.mother_name" type="text" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label">Guardian's Name:</label>
            <input v-model="form.guardian_name" type="text" class="form-control" />
          </div>
        </div>

        <!-- ✅ File Uploads (No Form 137) -->
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">PSA:</label>
            <input ref="psaRef" type="file" class="form-control" @change="handleFileUpload($event, 'psa')" />
          </div>
          <div class="col-md-6">
            <label class="form-label">ECCRD:</label>
            <input ref="eccrdRef" type="file" class="form-control" @change="handleFileUpload($event, 'eccrd')" />
          </div>
        </div>

        <!-- ✅ Submit -->
        <div class="text-end">
          <button class="btn btn-primary" :disabled="form.processing">Submit</button>
        </div>
      </form>

      <!-- ✅ Success Modal -->
      <Teleport to="body">
        <Transition name="fade">
          <div v-if="showSuccessModal" class="modal-backdrop">
            <div class="modal-content-box">
              <h5>Success</h5>
              <p>{{ successMessage }}</p>
              <button class="btn btn-success mt-2" @click="showSuccessModal = false">OK</button>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, usePage, router, Link } from '@inertiajs/vue3'

const user = usePage().props.auth.user
const userName = `${user.last_name}, ${user.first_name}`
const userRole = user?.role ? `(${user.role})` : ''

const successMessage = ref(null)
const showSuccessModal = ref(false)

const psaRef = ref(null)
const eccrdRef = ref(null)

const currentYear = new Date().getFullYear()
const nextYear = currentYear + 1

const form = useForm({
  lrn: '',
  first_name: '',
  middle_name: '',
  last_name: '',
  birthdate: '',
  sex: '',
  civil_status: '',
  citizenship: '',
  place_of_birth: '',
  school_year: `${currentYear}-${nextYear}`,
  guardian_phone: '',
  address: '',
  grade_level: '',
  father_name: '',
  mother_name: '',
  guardian_name: '',
  psa: null,
  eccrd: null,
})

const handleFileUpload = (event, field) => {
  form[field] = event.target.files[0]
}

const submit = () => {
  const formData = new FormData()
  Object.entries(form.data()).forEach(([key, value]) => {
    if (!['psa', 'eccrd'].includes(key)) {
      formData.append(key, value)
    }
  })

  const fileFields = ['psa', 'eccrd']
  const categories = ['PSA', 'ECCRD']

  fileFields.forEach((field, index) => {
    if (form[field]) {
      formData.append('files[]', form[field])
      formData.append('categories[]', categories[index])
      formData.append('lrns[]', form.lrn)
      formData.append('types[]', 'student')
    }
  })

  router.post(route('students.store'), formData, {
    forceFormData: true,
    onSuccess: () => {
      form.reset()
      psaRef.value.value = ''
      eccrdRef.value.value = ''
      successMessage.value = '✅ Student and documents uploaded successfully.'
      showSuccessModal.value = true
    },
    onError: (errors) => {
      form.errors = errors
    }
  })
}

const goBack = () => router.visit(route('dashboard'))

const checkRole = () => {
  const allowed = ['Admin', 'Admin Staff']
  allowed.includes(user.role) ? router.visit(route('register')) : alert('❌ Only Admin or Admin Staff can register new users.')
}

const checkRoleStudent = () => {
  const allowed = ['Admin', 'Admin Staff']
  allowed.includes(user.role) ? router.visit(route('students.register')) : alert('❌ Only Admin or Admin Staff can register students.')
}
</script>

<style scoped>
.page-wrapper {
  background-color: #ffffff;
  min-height: 100vh;
  padding-bottom: 50px;
}
.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  background: white;
  width: 100%;
  border-radius: 0 0 10px 10px;
  flex-wrap: wrap;
  gap: 10px;
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
  justify-content: flex-end;
}
.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #ccc;
  object-fit: cover;
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
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}
.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 15px;
}
.form-wrapper {
  background-color: #ffffff;
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
}
.back-arrow {
  font-size: 16px;
  color: #0d6efd;
  padding-left: 0;
}
.back-arrow:hover {
  text-decoration: underline;
  color: #0a58ca;
}
@media (max-width: 768px) {
  .top-bar {
    flex-direction: column;
    align-items: flex-start;
    padding: 10px 15px;
    gap: 15px;
  }
  .brand h1 {
    font-size: 18px;
  }
  .dropdown-toggle {
    max-width: 100%;
  }
  .form-wrapper {
    padding: 15px;
  }
  .row {
    flex-direction: column;
  }
  .col-md-4, .col-md-6, .col-md-2, .col-md-8 {
    width: 100% !important;
    max-width: 100%;
  }
  .text-end {
    text-align: center !important;
    margin-top: 20px;
  }
  .btn {
    width: 100%;
  }
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content-box {
  background: white;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
  width: 320px;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.4s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

</style>
