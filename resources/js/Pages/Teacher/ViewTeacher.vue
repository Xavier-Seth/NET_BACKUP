<template>
  <MainLayout activeMenu="teachers">
    <div class="teacher-container">
      <!-- Header with Back + Edit -->
      <div class="d-flex justify-between align-items-center mb-4">
        <h2 class="teacher-title">Teacher Profile</h2>
        <div class="d-flex gap-2">
          <button class="btn btn-secondary" @click="goBack">← Back to List</button>
          <button class="btn btn-warning" @click="editTeacher">✎ Edit</button>
        </div>
      </div>

      <!-- Profile Section -->
      <div class="bg-white rounded shadow p-4 mb-4">
        <div class="d-flex align-items-center">
          <img
            :src="teacher.photo_path ? `/storage/${teacher.photo_path}` : '/images/user-avatar.png'"
            class="rounded-circle border"
            style="width: 120px; height: 120px; object-fit: cover;"
            alt="Teacher Photo"
          />
          <div class="ms-4">
            <h4>{{ teacher.full_name }}</h4>
            <p class="mb-0"><strong>Position:</strong> {{ teacher.position }}</p>
            <p class="mb-0"><strong>Department:</strong> {{ teacher.department }}</p>
            <p class="mb-0"><strong>Email:</strong> {{ teacher.email }}</p>
            <p class="mb-0"><strong>Contact:</strong> {{ teacher.contact }}</p>
            <p class="mb-0">
              <strong>Status:</strong>
              <span :class="statusColor">{{ teacher.status }}</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Documents Section -->
      <div class="bg-white rounded shadow p-4">
        <h5 class="mb-3">Uploaded Documents</h5>
        <table class="table table-bordered">
          <thead class="table-light">
            <tr>
              <th>Filename</th>
              <th>Category</th>
              <th>Uploaded At</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in documents" :key="doc.id">
              <td class="filename-cell" :title="doc.name">{{ doc.name }}</td>
              <td>{{ doc.category?.name || 'N/A' }}</td>
              <td>{{ formatDate(doc.created_at) }}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-outline-primary me-2" @click="preview(doc)">Preview</button>
                <a class="btn btn-sm btn-outline-success" :href="`/documents/${doc.id}/download`" target="_blank">Download</a>
              </td>
            </tr>
            <tr v-if="documents.length === 0">
              <td colspan="4" class="text-center text-muted">No documents found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Preview Modal -->
      <div v-if="previewUrl" class="preview-modal">
        <div class="preview-content">
          <button class="close-preview" @click="closePreview">&times;</button>
          <iframe v-if="previewType === 'pdf'" :src="previewUrl" frameborder="0"></iframe>
          <img v-else :src="previewUrl" alt="Document Preview" />
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({
  teacher: Object,
  documents: Array,
})

const previewUrl = ref(null)
const previewType = ref('pdf')

const preview = (document) => {
  const extension = document.name.split('.').pop().toLowerCase()

  if (extension === 'pdf' || ['jpg', 'jpeg', 'png'].includes(extension)) {
    previewType.value = extension === 'pdf' ? 'pdf' : 'image'
    previewUrl.value = `/documents/${document.id}/preview`
  } else if (['doc', 'docx', 'xls', 'xlsx'].includes(extension)) {
    if (document.pdf_preview_path) {
      previewType.value = 'pdf'
      previewUrl.value = `/storage/${document.pdf_preview_path}`
    } else {
      alert('❗ No PDF preview available for this document.')
    }
  } else {
    alert('❗ Unsupported file format for preview.')
  }
}

const closePreview = () => {
  previewUrl.value = null
  previewType.value = null
}

watch(previewUrl, (val) => {
  document.body.style.overflow = val ? 'hidden' : ''
})

const goBack = () => {
  router.get('/teachers')
}

const editTeacher = () => {
  router.get(`/teachers/${props.teacher.id}/edit`)
}

const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const statusColor = computed(() => {
  return props.teacher.status === 'Active'
    ? 'text-success fw-bold'
    : 'text-warning fw-bold'
})
</script>

<style scoped>
.teacher-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 24px 20px;
}

/* Wrap or truncate long filenames gracefully */
.filename-cell {
  max-width: 300px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.preview-modal {
  position: fixed;
  inset: 0;
  z-index: 999;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
}

.preview-content {
  position: relative;
  background: white;
  width: 90vw;
  max-width: 700px;
  height: 500px;
  border-radius: 10px;
  overflow-y: auto;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 15px;
}

.preview-content iframe,
.preview-content img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 6px;
}

.close-preview {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #dc3545;
  color: white;
  border: none;
  padding: 4px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  z-index: 1;
}
</style>
