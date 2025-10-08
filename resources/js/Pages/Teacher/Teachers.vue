<template>
  <MainLayout activeMenu="teachers">
    <div class="teacher-container">
      <!-- Header: Title + Search -->
      <div class="d-flex justify-between align-items-center mb-3 header-row">
        <h2 class="teacher-title">Teachers</h2>
        <div class="d-flex align-items-center gap-2">
          <input
            type="text"
            v-model="searchQuery"
            class="form-control search-input"
            placeholder="Search teacher..."
          />
        </div>
      </div>

      <!-- Loading Placeholder -->
      <div v-if="!teachers || teachers.length === 0" class="text-center text-muted py-4">
        Loading...
      </div>

      <!-- Teacher Cards -->
      <transition-group name="fade" tag="div" class="card-list">
        <div
          v-for="teacher in filteredTeachers"
          :key="teacher.id"
          class="teacher-card"
        >
          <!-- Left: Profile + Name -->
          <div class="profile-info">
            <img
              :src="teacher.photo_path ? `/storage/${teacher.photo_path}` : '/images/user-avatar.png'"
              class="profile-img"
              alt="Teacher Photo"
              loading="lazy"
            />
            <span class="teacher-name">{{ teacher.full_name }}</span>
          </div>

          <!-- Center: Status -->
          <div class="status-block">
            <span
              class="status-dot"
              :class="teacher.status === 'Active' ? 'bg-green-500' : 'bg-yellow-600'"
            ></span>
            <span :class="teacher.status === 'Active' ? 'text-green-600' : 'text-yellow-700'">
              {{ teacher.status }}
            </span>
          </div>

          <!-- Right: Actions -->
          <div class="action-icons">
            <i class="bi bi-eye" title="View Profile" @click="viewTeacher(teacher)"></i>
            <i class="bi bi-pencil-square" title="Edit" @click="editTeacher(teacher)"></i>
            <i class="bi bi-trash text-danger" title="Delete" @click="openDeleteModal(teacher)"></i>
          </div>
        </div>
      </transition-group>
    </div>

    <!-- Confirm Delete Modal -->
    <div
      v-if="showConfirm"
      class="modal-overlay"
      @click.self="closeConfirm"
      @keydown.esc="closeConfirm"
      tabindex="0"
    >
      <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="modal-header">
          <h3 id="confirmTitle">Delete teacher?</h3>
          <button class="btn-icon" @click="closeConfirm" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <p class="mb-2">
            <span class="warn">⚠️</span>
            Are you sure you want to delete
            <strong>{{ teacherToDelete?.full_name }}</strong>?
          </p>
          <p class="text-muted small">
            This will also delete all of their uploaded documents.
          </p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" @click="closeConfirm">Cancel</button>
          <button class="btn btn-danger" :disabled="deleting" @click="confirmDelete">
            <span v-if="!deleting">OK, delete</span>
            <span v-else>Deleting…</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Result Toast -->
    <transition name="toast">
      <div v-if="resultToast.show" :class="['toast', resultToast.type === 'success' ? 'toast-success' : 'toast-error']">
        {{ resultToast.message }}
      </div>
    </transition>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
  teachers: { type: Array, default: () => [] },
})

const searchQuery = ref('')

// filtering
const filteredTeachers = computed(() => {
  const q = (searchQuery.value || '').toLowerCase().trim()
  if (!q) return props.teachers
  return props.teachers.filter(t => (t.full_name || '').toLowerCase().includes(q))
})

// modal + delete state
const showConfirm = ref(false)
const teacherToDelete = ref(null)
const deleting = ref(false)

// toast
const resultToast = ref({ show: false, type: 'success', message: '' })
const showToast = (message, type = 'success') => {
  resultToast.value = { show: true, type, message }
  setTimeout(() => (resultToast.value.show = false), 2200)
}

// actions
const openDeleteModal = (teacher) => {
  teacherToDelete.value = teacher
  showConfirm.value = true
  // focus trap entry
  setTimeout(() => {
    const el = document.querySelector('.modal-card')
    el && el.focus?.()
  }, 0)
}

const closeConfirm = () => {
  if (deleting.value) return
  showConfirm.value = false
  teacherToDelete.value = null
}

const confirmDelete = () => {
  if (!teacherToDelete.value) return
  deleting.value = true
  router.delete(`/teachers/${teacherToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showToast('Teacher and associated documents deleted.')
    },
    onError: () => {
      showToast('Failed to delete teacher.', 'error')
    },
    onFinish: () => {
      deleting.value = false
      closeConfirm()
    },
  })
}

const editTeacher = (teacher) => router.get(`/teachers/${teacher.id}/edit`)
const viewTeacher = (teacher) => router.get(`/teachers/${teacher.id}`)
</script>

<style scoped>
/* Container and header */
.teacher-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 24px 20px;
}

.header-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.teacher-title {
  font-size: 24px;
  font-weight: bold;
  margin: 0;
}

/* Search input */
.search-input {
  width: 250px;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #ccc;
  transition: all 0.2s ease;
}
.search-input:focus {
  border-color: #007bff;
  box-shadow: 0 0 4px rgba(0, 123, 255, 0.5);
}

/* Teacher card list */
.card-list {
  display: flex;
  flex-direction: column;
  gap: 1px;
  margin-top: 16px;
}

/* Transition animation */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* Teacher card */
.teacher-card {
  position: relative;
  display: flex;
  align-items: center;
  background: #fff;
  padding: 12px 24px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  height: 90px;
}

.profile-info {
  display: flex;
  align-items: center;
  gap: 14px;
}

.profile-img {
  width: 54px;
  height: 54px;
  border-radius: 9999px;
  object-fit: cover;
  border: 2px solid #ddd;
}

.teacher-name {
  font-size: 15px;
  font-weight: 500;
  line-height: 1;
}

/* Status badge */
.status-block {
  position: absolute;
  left: 85%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  line-height: 1;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 9999px;
}

.text-green-600 {
  color: #16a34a;
}

.bg-green-500 {
  background: #22c55e;
}

.text-yellow-700 {
  color: #a16207;
}

.bg-yellow-600 {
  background: #ca8a04;
}

/* Action icons */
.action-icons {
  margin-left: auto;
  display: flex;
  gap: 15px;
  font-size: 18px;
}
.action-icons i {
  cursor: pointer;
}

/* Modal overlay */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: grid;
  place-items: center;
  z-index: 60;
}

/* Modal card */
.modal-card {
  width: 100%;
  max-width: 460px;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 10px 35px rgba(0, 0, 0, 0.25);
  outline: none;
}

/* Modal header */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  border-bottom: 1px solid #eee;
}

/* Modal body */
.modal-body {
  padding: 14px 18px;
}

/* Modal footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 18px;
  border-top: 1px solid #eee;
}

/* Buttons */
.btn {
  padding: 8px 14px;
  border-radius: 8px;
  border: 1px solid transparent;
  font-weight: 600;
}
.btn-light {
  background: #f3f4f6;
  border-color: #e5e7eb;
}
.btn-danger {
  background: #dc2626;
  color: #fff;
}
.btn-danger:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.btn-icon {
  background: transparent;
  border: none;
  font-size: 16px;
  cursor: pointer;
}

/* Toast animation */
.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.25s, transform 0.25s;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

/* Toast box */
.toast {
  position: fixed;
  right: 18px;
  bottom: 18px;
  z-index: 70;
  padding: 10px 14px;
  border-radius: 8px;
  color: #fff;
  min-width: 260px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}
.toast-success {
  background: #16a34a;
}
.toast-error {
  background: #dc2626;
}
</style>
