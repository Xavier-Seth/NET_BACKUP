<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar />

    <div class="flex-1">
      <div class="container d-flex justify-content-center">
        <div class="position-relative w-100" style="max-width: 1000px;">
          <img src="/images/rizal.jpg" class="w-100 rounded-top" style="height: 200px; object-fit: cover;" />
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
              <input ref="photoRef" type="file" accept="image/*" class="d-none" @change="handlePhotoUpload" />
            </label>
            <h5 class="mt-2 fw-bold">Register Teacher</h5>
          </div>
        </div>
      </div>

      <div class="container d-flex justify-content-center">
        <div class="mt-1 form-wrapper bg-white shadow p-4" style="margin-top: 0px; max-width: 1000px; width: 100%; border-radius: 0 0 10px 10px;">
          <form @submit.prevent="handleSubmit">
            <div class="row mb-3" style="margin-top: 5rem;">
              <div class="col-md-4">
                <label class="form-label">First Name: <span class="text-danger">*</span></label>
                <input
                  v-model="form.first_name"
                  data-field="first_name"
                  type="text"
                  class="form-control"
                  @input="restrictToLettersWithDot"
                  :class="{'is-invalid': localErrors.first_name}"
                />
                <div v-if="localErrors.first_name" class="text-danger mt-1">{{ localErrors.first_name }}</div>
                <div v-if="inputWarnings.first_name" class="text-warning mt-1">{{ inputWarnings.first_name }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Middle Name:</label>
                <input
                  v-model="form.middle_name"
                  data-field="middle_name"
                  type="text"
                  class="form-control"
                  @input="restrictToLettersWithDot"
                  placeholder="(optional)"
                />
                <div v-if="inputWarnings.middle_name" class="text-warning mt-1">{{ inputWarnings.middle_name }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Last Name: <span class="text-danger">*</span></label>
                <input
                  v-model="form.last_name"
                  data-field="last_name"
                  type="text"
                  class="form-control"
                  @input="restrictToLettersWithDot"
                  :class="{'is-invalid': localErrors.last_name}"
                />
                <div v-if="localErrors.last_name" class="text-danger mt-1">{{ localErrors.last_name }}</div>
                <div v-if="inputWarnings.last_name" class="text-warning mt-1">{{ inputWarnings.last_name }}</div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Name Extension:</label>
                <input
                  v-model="form.name_extension"
                  data-field="name_extension"
                  type="text"
                  class="form-control"
                  @input="restrictToLettersWithDot"
                  placeholder="Jr., Sr., III (optional)"
                />
                <div v-if="inputWarnings.name_extension" class="text-warning mt-1">{{ inputWarnings.name_extension }}</div>
              </div>
              
              <div class="col-md-4">
                <label class="form-label">Employee ID: <span class="text-danger">*</span></label>
                <input
                  v-model="form.employee_id"
                  data-field="employee_id"
                  type="text"
                  class="form-control"
                  @input="restrictToAlphaNumericDash"
                  :class="{'is-invalid': localErrors.employee_id}"
                />
                <div v-if="localErrors.employee_id" class="text-danger mt-1">{{ localErrors.employee_id }}</div>
              </div>

              <div class="col-md-4">
                <label class="form-label">Position: <span class="text-danger">*</span></label>
                <input
                  v-model="form.position"
                  data-field="position"
                  type="text"
                  class="form-control"
                  @input="restrictToAlphaNumericDash"
                  :class="{'is-invalid': localErrors.position}"
                />
                <div v-if="localErrors.position" class="text-danger mt-1">{{ localErrors.position }}</div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Birthdate: <span class="text-danger">*</span></label>
                <input 
                  v-model="form.birth_date" 
                  type="date" 
                  class="form-control" 
                  :class="{'is-invalid': localErrors.birth_date}"
                />
                <div v-if="localErrors.birth_date" class="text-danger mt-1">{{ localErrors.birth_date }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Department / Unit: <span class="text-danger">*</span></label>
                <input
                  v-model="form.department"
                  data-field="department"
                  type="text"
                  class="form-control"
                  @input="restrictToAlphaNumericDash"
                  :class="{'is-invalid': localErrors.department}"
                />
                <div v-if="localErrors.department" class="text-danger mt-1">{{ localErrors.department }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Date Hired: <span class="text-danger">*</span></label>
                <input 
                  v-model="form.date_hired" 
                  type="date" 
                  class="form-control"
                  :class="{'is-invalid': localErrors.date_hired}"
                />
                <div v-if="localErrors.date_hired" class="text-danger mt-1">{{ localErrors.date_hired }}</div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Contact Number: <span class="text-danger">*</span></label>
                <input
                  v-model="form.contact"
                  data-field="contact"
                  type="text"
                  class="form-control"
                  @input="restrictToNumbers"
                  :class="{'is-invalid': localErrors.contact}"
                />
                <div v-if="localErrors.contact" class="text-danger mt-1">{{ localErrors.contact }}</div>
                <div v-if="inputWarnings.contact" class="text-warning mt-1">{{ inputWarnings.contact }}</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email Address: <span class="text-danger">*</span></label>
                <input
                  v-model="form.email"
                  type="email"
                  class="form-control"
                  :class="{'is-invalid': localErrors.email}"
                />
                <div v-if="localErrors.email" class="text-danger mt-1">{{ localErrors.email }}</div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Permanent Address: <span class="text-danger">*</span></label>
                <input 
                  v-model="form.address" 
                  type="text" 
                  class="form-control"
                  :class="{'is-invalid': localErrors.address}"
                />
                <div v-if="localErrors.address" class="text-danger mt-1">{{ localErrors.address }}</div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Remarks:</label>
                <textarea
                  v-model="form.remarks"
                  class="form-control"
                  rows="3"
                  placeholder="Additional notes or info..."
                  @input="restrictRemarks"
                ></textarea>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Upload PDS File:</label>
                <input
                  ref="pdsRef"
                  type="file"
                  accept=".pdf,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                  class="form-control"
                  @change="handleFileUpload"
                />
                <p v-if="form.pds" class="text-success mt-1">
                  Selected file: {{ form.pds.name }}
                </p>
              </div>
            </div>

            <div class="text-end">
              <button class="btn btn-primary">Register Teacher</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div v-if="modals.confirm" class="overlay" @keydown.esc="closeAll" tabindex="0">
      <div class="modal-card">
        <button class="close-btn" @click="closeAll">✕</button>
        <h5 class="mb-2">Confirm Registration</h5>
        <p class="text-gray-600">Are you sure you want to register this teacher?</p>
        <div class="mt-4 d-flex justify-content-end gap-2">
          <button class="btn btn-success" @click="submit">Yes, register</button>
          <button class="btn btn-outline-secondary" @click="closeAll">Cancel</button>
        </div>
      </div>
    </div>

    <div v-if="info.show" class="overlay" @keydown.esc="closeAll" tabindex="0">
      <div class="modal-card">
        <button class="close-btn" @click="closeAll">✕</button>
        <div class="d-flex align-items-center mb-2">
          <span
            v-if="info.type !== 'default'"
            :class="[
              'rounded-circle me-2 d-inline-flex align-items-center justify-content-center',
              info.type==='success' ? 'bg-success' : (info.type==='error' ? 'bg-danger' : 'bg-secondary')
            ]"
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
</template>

<script setup>
import Sidebar from "@/Components/Sidebar.vue"
import { ref, watch, nextTick } from "vue" // Added nextTick import
import { useForm } from "@inertiajs/vue3"

const pdsRef = ref(null)
const photoRef = ref(null)
const photoPreview = ref(null)

// Flag to pause validation during reset
const isResetting = ref(false)

const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  full_name: '',
  name_extension: '',
  employee_id: '',
  position: '',
  birth_date: '',
  department: '',
  date_hired: '',
  contact: '',
  email: '',
  address: '',
  remarks: '',
  pds: null,
  photo: null,
})

const localErrors = ref({
  first_name: '',
  last_name: '',
  email: '',
  employee_id: '',
  position: '',
  department: '',
  birth_date: '',
  date_hired: '',
  contact: '',
  address: ''
})

const inputWarnings = ref({
  first_name: '',
  middle_name: '',
  last_name: '',
  name_extension: '',
  contact: '',
})

/* --- modal state --- */
const modals = ref({ confirm: false })
const info = ref({ show: false, title: '', message: '', type: 'default' })
const showInfo = (title, message, type='default') => { info.value = { show: true, title, message, type } }
const closeAll = () => { modals.value.confirm = false; info.value.show = false }

/* --- temp warning helper --- */
const showTemporaryWarning = (field, message, duration = 2500) => {
  inputWarnings.value[field] = message
  setTimeout(() => { inputWarnings.value[field] = '' }, duration)
}

/* --- LIVE VALIDATION (Watchers) --- */
/* Note: check isResetting to ensure errors don't appear when form is reset programmatically */

watch(() => form.first_name, (val) => {
  if (isResetting.value) return; 
  localErrors.value.first_name = (val || '').trim() === '' ? 'First name is required.' : ''
})

watch(() => form.last_name, (val) => {
  if (isResetting.value) return;
  localErrors.value.last_name = (val || '').trim() === '' ? 'Last name is required.' : ''
})

watch(() => form.email, (val) => {
  if (isResetting.value) return;
  if ((val || '').trim() === '') {
    localErrors.value.email = 'Email is required.'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
    localErrors.value.email = 'Invalid email format.'
  } else {
    localErrors.value.email = ''
  }
})

watch(() => form.employee_id, (val) => {
  if (isResetting.value) return;
  localErrors.value.employee_id = (val || '').trim() === '' ? 'Employee ID is required.' : ''
})

watch(() => form.position, (val) => {
  if (isResetting.value) return;
  localErrors.value.position = (val || '').trim() === '' ? 'Position is required.' : ''
})

watch(() => form.department, (val) => {
  if (isResetting.value) return;
  localErrors.value.department = (val || '').trim() === '' ? 'Department is required.' : ''
})

watch(() => form.contact, (val) => {
  if (isResetting.value) return;
  localErrors.value.contact = (val || '').trim() === '' ? 'Contact number is required.' : ''
})

watch(() => form.birth_date, (val) => {
  if (isResetting.value) return;
  localErrors.value.birth_date = (val || '') === '' ? 'Birth date is required.' : ''
})

watch(() => form.date_hired, (val) => {
  if (isResetting.value) return;
  localErrors.value.date_hired = (val || '') === '' ? 'Date hired is required.' : ''
})

watch(() => form.address, (val) => {
  if (isResetting.value) return;
  localErrors.value.address = (val || '').trim() === '' ? 'Permanent address is required.' : ''
})


/* --- restrictions --- */
const restrictToLettersWithDot = (e) => {
  const value = e.target.value
  const filtered = value.replace(/[^a-zA-ZñÑ.\s]/g, '')
  const field = e.target.dataset.field
  if (value !== filtered) showTemporaryWarning(field, 'Only letters, spaces, and dots allowed.')
  e.target.value = filtered
  if (field) form[field] = filtered
}
const restrictToAlphaNumericDash = (e) => {
  const value = e.target.value
  const filtered = value.replace(/[^a-zA-Z0-9-]/g, '')
  const field = e.target.dataset.field
  e.target.value = filtered
  if (field) form[field] = filtered
}
const restrictToNumbers = (e) => {
  const value = e.target.value
  const filtered = value.replace(/\D/g, '')
  const field = e.target.dataset.field
  if (value !== filtered) showTemporaryWarning(field, 'Only numbers are allowed.')
  e.target.value = filtered
  if (field) form[field] = filtered
}
const restrictRemarks = (e) => {
  const value = e.target.value
  e.target.value = value.replace(/[^a-zA-Z0-9ñÑ\s.,!?()\-]/g, '')
  form.remarks = e.target.value
}

/* --- file handlers --- */
const handleFileUpload = (event) => { form.pds = event.target.files[0] }
const handlePhotoUpload = (event) => {
  const file = event.target.files[0]
  form.photo = file
  photoPreview.value = file && file.type.startsWith("image/") ? URL.createObjectURL(file) : null
}

/* --- submit flow --- */
const handleSubmit = () => {
  let isValid = true;

  // 1. Check Names & Email (Required)
  if (!form.first_name) { localErrors.value.first_name = 'First name is required.'; isValid = false; }
  if (!form.last_name) { localErrors.value.last_name = 'Last name is required.'; isValid = false; }
  
  // Email Validation
  if (!form.email) { 
    localErrors.value.email = 'Email is required.'; 
    isValid = false; 
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    localErrors.value.email = 'Invalid email format.'; 
    isValid = false;
  }

  // 2. Check Newly Required Fields
  if (!form.employee_id) { localErrors.value.employee_id = 'Employee ID is required.'; isValid = false; }
  if (!form.position) { localErrors.value.position = 'Position is required.'; isValid = false; }
  if (!form.contact) { localErrors.value.contact = 'Contact number is required.'; isValid = false; }
  if (!form.department) { localErrors.value.department = 'Department is required.'; isValid = false; }
  if (!form.birth_date) { localErrors.value.birth_date = 'Birth date is required.'; isValid = false; }
  if (!form.date_hired) { localErrors.value.date_hired = 'Date hired is required.'; isValid = false; }
  if (!form.address) { localErrors.value.address = 'Permanent address is required.'; isValid = false; }

  // Stop if errors
  if (!isValid) {
    showInfo('Missing Fields', 'Please fill out all the required fields.', 'error')
    return
  }

  // Proceed if valid
  modals.value.confirm = true
}

const submit = () => {
  modals.value.confirm = false

  form.full_name = [form.first_name, form.middle_name, form.last_name].filter(Boolean).join(" ")

  form.post("/teachers", {
    forceFormData: true,
    onSuccess: () => {
      // 1. Enable reset flag to pause watchers
      isResetting.value = true

      showInfo('Success', 'Teacher registered successfully!', 'success')
      form.reset()
      
      // 2. Explicitly clear errors just in case
      Object.keys(localErrors.value).forEach(key => localErrors.value[key] = '')
      
      if (pdsRef.value)  pdsRef.value.value = ''
      if (photoRef.value) photoRef.value.value = ''
      photoPreview.value = null

      // 3. Disable reset flag after DOM update
      nextTick(() => {
        isResetting.value = false
      })
    },
    onError: (errors) => {
      const firstKey = Object.keys(errors || {})[0]
      const msg = firstKey ? String(errors[firstKey]) : 'Registration failed. Please review the form.'
      showInfo('Registration Failed', msg, 'error')
      console.error("Server validation error:", errors)
    },
  })
}
</script>

<style scoped>
.flex-1 { margin-left: 200px; padding: 20px; }
img.rounded-circle { transition: all 0.3s ease; cursor: pointer; }
.form-wrapper { background-color: #ffffff; padding: 25px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08); }
.is-invalid { border-color: #dc3545; }
.text-danger { font-size: 0.875rem; }
.text-warning { font-size: 0.875rem; color: #d39e00; }

/* modal styles */
.overlay {
  position: fixed; inset: 0; z-index: 50;
  display: flex; align-items: center; justify-content: center;
  background: rgba(31,41,55,.5);
}
.modal-card {
  position: relative; width: 24rem; background: #fff;
  padding: 1.25rem; border-radius: .5rem; box-shadow: 0 10px 25px rgba(0,0,0,.15);
}
.close-btn {
  position: absolute; top: .5rem; right: .5rem;
  border: 0; background: #f8f9fa; padding: .25rem .5rem; border-radius: .25rem;
}
</style>