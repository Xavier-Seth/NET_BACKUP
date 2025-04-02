<template>
  <transition name="fade">
    <div v-if="show" class="modal-overlay" @click.self="closeModal">
      <transition name="modal">
        <div class="modal-content">
          <button class="close-icon" @click="closeModal" aria-label="Close">&times;</button>

          <h5 class="modal-title mb-3">{{ student?.full_name }}</h5>
          <p class="mb-2">LRN: {{ student?.lrn }}</p>
          <p class="mb-3">Current Grade Level: {{ student?.grade_level }}</p>

          <form @submit.prevent="saveGrades">
            <div v-for="level in gradeLevels" :key="level" class="grade-row">
              <label>{{ level }} GWA:</label>
              <input
                type="number"
                step="0.01"
                :readonly="!editingEnabled"
                @click="requestEditAccess"
                :class="{ changed: hasChanged(level), 'read-only': !editingEnabled }"
                v-model="grades[level]"
                min="0"
                max="100"
                placeholder="Enter GWA"
              />
            </div>

            <!-- ✅ Success Message -->
            <p v-if="successMessage" class="text-success mt-2 text-center">
              {{ successMessage }}
            </p>

            <div class="btn-group mt-3">
              <button class="btn btn-success" type="submit" :disabled="isSaving || !editingEnabled">
                {{ isSaving ? 'Saving...' : 'Save Grades' }}
              </button>
              <button class="btn btn-secondary" type="button" @click="closeModal">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </transition>

      <!-- 🔒 Confirm Edit Modal -->
      <transition name="fade">
        <div v-if="showConfirmModal" class="confirm-overlay" @click.self="cancelEdit">
          <div class="confirm-modal">
            <h5>Edit Student Grade?</h5>
            <div class="confirm-buttons">
              <button class="btn btn-danger" @click="cancelEdit">Cancel</button>
              <button class="btn btn-success" @click="confirmEdit">Proceed</button>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  show: Boolean,
  student: Object,
})
const emit = defineEmits(['close', 'updated'])

const gradeLevels = [
  'Grade 1', 'Grade 2', 'Grade 3',
  'Grade 4', 'Grade 5', 'Grade 6'
]

const grades = ref({})
const originalGrades = ref({})
const isSaving = ref(false)
const successMessage = ref("") // ✅ New
let successTimeout = null       // ✅ Timer reference

const editingEnabled = ref(false)
const showConfirmModal = ref(false)

watch(() => props.show, (visible) => {
  if (visible && props.student) {
    initializeForm(props.student)
  }
}, { immediate: true })

function initializeForm(student) {
  grades.value = {}
  originalGrades.value = {}
  editingEnabled.value = false
  showConfirmModal.value = false
  successMessage.value = ""
  clearTimeout(successTimeout)

  const existingGrades = student.grades || []
  gradeLevels.forEach((level) => {
    const found = existingGrades.find((g) => g.grade_level === level)
    grades.value[level] = found ? found.gwa : ''
    originalGrades.value[level] = found ? found.gwa : ''
  })
}

function hasChanged(level) {
  return grades.value[level] !== originalGrades.value[level]
}

function requestEditAccess() {
  if (!editingEnabled.value) {
    showConfirmModal.value = true
  }
}

function confirmEdit() {
  editingEnabled.value = true
  showConfirmModal.value = false
}

function cancelEdit() {
  showConfirmModal.value = false
}

async function saveGrades() {
  if (!editingEnabled.value) return
  isSaving.value = true
  successMessage.value = ""
  clearTimeout(successTimeout)

  try {
    const payload = {}
    gradeLevels.forEach((level) => {
      payload[level] = grades.value[level] === '' ? null : parseFloat(grades.value[level])
    })

    const response = await axios.post(
      `/students/${props.student.lrn}/update-grades`,
      { grades: payload }
    )

    successMessage.value = "✅ Grades saved successfully!"
    emit('updated', response.data.student)
    originalGrades.value = { ...grades.value }

    // ✅ Auto-dismiss after 3 seconds
    successTimeout = setTimeout(() => {
      successMessage.value = ""
    }, 3000)
  } catch (error) {
    console.error('Failed to save grades:', error)
  } finally {
    isSaving.value = false
  }
}

function closeModal() {
  grades.value = {}
  originalGrades.value = {}
  editingEnabled.value = false
  showConfirmModal.value = false
  successMessage.value = ""
  clearTimeout(successTimeout)
  emit('close')
}
</script>

<style scoped>
/* Animations */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}
.modal-enter-from {
  opacity: 0;
  transform: scale(0.95);
}
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Modal Styling */
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 999;
}

.modal-content {
  position: relative;
  background: white;
  padding: 25px 30px;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
}

.close-icon {
  position: absolute;
  top: 10px;
  right: 15px;
  background: transparent;
  border: none;
  font-size: 24px;
  color: #888;
  cursor: pointer;
}
.close-icon:hover {
  color: #000;
}

/* Grade Fields */
.grade-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

input[type='number'] {
  padding: 6px 10px;
  width: 150px;
  border-radius: 6px;
  border: 1px solid #ccc;
}
input[type='number'].changed {
  background-color: #fff7c4;
}
input[type='number'].read-only {
  background-color: #f0f0f0;
  cursor: pointer;
}

/* Buttons */
.btn-group {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* Confirm Modal */
.confirm-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1001;
  display: flex;
  justify-content: center;
  align-items: center;
}

.confirm-modal {
  background: white;
  padding: 25px 30px;
  border-radius: 12px;
  max-width: 300px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.confirm-buttons {
  margin-top: 20px;
  display: flex;
  justify-content: space-between;
  gap: 10px;
}
</style>
