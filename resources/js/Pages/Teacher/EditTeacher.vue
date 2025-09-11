<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar />

    <div class="flex-1">
      <!-- Banner -->
      <div class="container d-flex justify-content-center">
        <div class="position-relative w-100" style="max-width: 1000px;">
          <img
            src="/images/rizal.jpg"
            class="w-100 rounded-top"
            style="height: 200px; object-fit: cover;"
          />

          <div class="position-absolute top-100 start-50 translate-middle text-center">
            <label for="photoInput" style="cursor: pointer;">
              <img
                :src="photoPreview || (form.photo_path ? `/storage/${form.photo_path}` : '/images/user-avatar.png')"
                class="rounded-circle border border-white shadow"
                style="width: 160px; height: 160px; object-fit: cover;"
                alt="Photo"
              />
            </label>

            <input
              id="photoInput"
              type="file"
              accept="image/*"
              class="d-none"
              @change="handlePhotoChange"
            />

            <h5 class="mt-2 fw-bold">Edit Teacher Profile</h5>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="container d-flex justify-content-center">
        <div
          class="form-wrapper mt-1 bg-white shadow p-4"
          style="max-width: 1000px; width: 100%; border-radius: 0 0 10px 10px;"
        >
          <form @submit.prevent="openUpdateConfirm">
            <!-- Name Fields -->
            <div class="row mb-3" style="margin-top: 5rem;">
              <div class="col-md-4">
                <label class="form-label">First Name:</label>
                <input
                  v-model="form.first_name"
                  @input="form.first_name = sanitizeName(form.first_name)"
                  class="form-control"
                  :class="{ 'is-invalid': errors.first_name }"
                />
                <div v-if="errors.first_name" class="text-danger mt-1">{{ errors.first_name }}</div>
              </div>

              <div class="col-md-4">
                <label class="form-label">Middle Name:</label>
                <input
                  v-model="form.middle_name"
                  @input="form.middle_name = sanitizeName(form.middle_name)"
                  class="form-control"
                  placeholder="(optional)"
                />
              </div>

              <div class="col-md-4">
                <label class="form-label">Last Name:</label>
                <input
                  v-model="form.last_name"
                  @input="form.last_name = sanitizeName(form.last_name)"
                  class="form-control"
                  :class="{ 'is-invalid': errors.last_name }"
                />
                <div v-if="errors.last_name" class="text-danger mt-1">{{ errors.last_name }}</div>
              </div>
            </div>

            <!-- Employment Fields -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Name Extension:</label>
                <input
                  v-model="form.name_extension"
                  @input="form.name_extension = sanitizeName(form.name_extension)"
                  class="form-control"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Employee ID:</label>
                <input v-model="form.employee_id" class="form-control" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Position:</label>
                <input v-model="form.position" class="form-control" />
              </div>
            </div>

            <!-- Other Info -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Birthdate:</label>
                <input v-model="form.birth_date" type="date" class="form-control" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Department:</label>
                <input v-model="form.department" class="form-control" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Date Hired:</label>
                <input v-model="form.date_hired" type="date" class="form-control" />
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Contact:</label>
                <input
                  v-model="form.contact"
                  @input="form.contact = sanitizeNumbers(form.contact)"
                  class="form-control"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label">Email:</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="form-control"
                  :class="{ 'is-invalid': errors.email }"
                />
                <div v-if="errors.email" class="text-danger mt-1">{{ errors.email }}</div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Address:</label>
                <input v-model="form.address" class="form-control" />
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Remarks:</label>
                <textarea
                  v-model="form.remarks"
                  @input="form.remarks = sanitizeRemarks(form.remarks)"
                  class="form-control"
                  rows="3"
                ></textarea>
              </div>
            </div>

            <!-- Status -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Status:</label>
                <select v-model="form.status" class="form-control">
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-between mt-4">
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" @click="goBack">
                  ← Back to List
                </button>
                <button type="button" class="btn btn-secondary" @click="openCancelConfirm">
                  Cancel
                </button>
              </div>
              <button type="submit" class="btn btn-primary">
                Update Teacher
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Confirm Update Modal -->
    <div v-if="modals.updateConfirm" class="overlay" @keydown.esc="closeAll" tabindex="0">
      <div class="modal-card">
        <button class="close-btn" @click="closeAll">✕</button>
        <h5 class="mb-2">Confirm Update</h5>
        <p class="text-gray-600">Are you sure you want to update this profile?</p>
        <div class="mt-4 d-flex justify-content-end gap-2">
          <button class="btn btn-success" @click="submitUpdate">Yes, update</button>
          <button class="btn btn-outline-secondary" @click="closeAll">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Confirm Cancel Modal -->
    <div v-if="modals.cancelConfirm" class="overlay" @keydown.esc="closeAll" tabindex="0">
      <div class="modal-card">
        <button class="close-btn" @click="closeAll">✕</button>
        <h5 class="mb-2">Discard Changes?</h5>
        <p class="text-gray-600">All unsaved edits will be lost.</p>
        <div class="mt-4 d-flex justify-content-end gap-2">
          <button class="btn btn-danger" @click="proceedCancel">Discard</button>
          <button class="btn btn-outline-secondary" @click="closeAll">Keep editing</button>
        </div>
      </div>
    </div>

    <!-- Info Modal (success / error) -->
    <div v-if="info.show" class="overlay" @keydown.esc="closeAll" tabindex="0">
      <div class="modal-card">
        <button class="close-btn" @click="closeAll">✕</button>
        <div class="d-flex align-items-center mb-2">
          <span
            v-if="info.type !== 'default'"
            :class="['rounded-circle me-2 d-inline-flex align-items-center justify-content-center',
                    info.type==='success' ? 'bg-success' : 'bg-danger']"
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
import Sidebar from '@/Components/Sidebar.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({ teacher: Object })
const errors = ref({})
const photoPreview = ref(null)

const form = useForm({
  ...props.teacher,
  status: props.teacher.status || 'Active',
  photo: null,
})

/* --- sanitizers --- */
const sanitizeName    = v => (v || '').replace(/[^a-zA-ZñÑ.\s-]/g, '').trimStart()
const sanitizeNumbers = v => (v || '').replace(/\D/g, '')
const sanitizeRemarks = v => (v || '').replace(/[^a-zA-Z0-9ñÑ\s.,!?()\-]/g, '')

/* --- modal state --- */
const modals = ref({
  updateConfirm: false,
  cancelConfirm: false,
})
const info = ref({ show: false, title: '', message: '', type: 'default' })
const showInfo = (title, message, type = 'default') => { info.value = { show: true, title, message, type } }
const closeAll = () => { modals.value.updateConfirm = false; modals.value.cancelConfirm = false; info.value.show = false }

/* --- file --- */
const handlePhotoChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.photo = file
    photoPreview.value = URL.createObjectURL(file)
  }
  e.target.value = '' // allow picking the same file again
}

/* --- submit flow --- */
const openUpdateConfirm = () => {
  // basic front-end checks
  if (!form.first_name?.trim() || !form.last_name?.trim()) {
    showInfo('Missing Fields', 'Please provide at least First Name and Last Name.', 'error')
    return
  }
  modals.value.updateConfirm = true
}

const submitUpdate = () => {
  modals.value.updateConfirm = false

  form.first_name  = (form.first_name || '').trim()
  form.middle_name = (form.middle_name || '').trim()
  form.last_name   = (form.last_name || '').trim()
  form.full_name   = [form.first_name, form.middle_name, form.last_name].filter(Boolean).join(' ')

  form.post(`/teachers/${form.id}/update`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      showInfo('Success', 'Teacher profile updated successfully!', 'success')
      form.clearErrors()
    },
    onError: (errs) => {
      errors.value = errs
      const firstKey = Object.keys(errs || {})[0]
      const msg = firstKey ? String(errs[firstKey]) : 'Update failed. Please review the form.'
      showInfo('Update Failed', msg, 'error')
    },
  })
}

/* --- cancel flow --- */
const openCancelConfirm = () => { modals.value.cancelConfirm = true }
const proceedCancel = () => { modals.value.cancelConfirm = false; router.get('/teachers') }
const goBack = () => { router.get('/teachers') }
</script>

<style scoped>
.flex-1 {
  margin-left: 200px;
  padding: 20px;
}
img.rounded-circle { transition: all 0.3s ease; cursor: pointer; }
.form-wrapper {
  background: #fff;
  padding: 25px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
}
.is-invalid { border-color: #dc3545; }
.text-danger { font-size: 0.875rem; }

/* --- modal styles --- */
.overlay {
  position: fixed; inset: 0; z-index: 50;
  display: flex; align-items: center; justify-content: center;
  background: rgba(31,41,55,.5); /* gray-800/50 */
}
.modal-card {
  position: relative; width: 24rem; /* w-96 */
  background: #fff; padding: 1.25rem; border-radius: .5rem; box-shadow: 0 10px 25px rgba(0,0,0,.15);
}
.close-btn {
  position: absolute; top: .5rem; right: .5rem;
  border: 0; background: #f8f9fa; padding: .25rem .5rem; border-radius: .25rem;
}
</style>
