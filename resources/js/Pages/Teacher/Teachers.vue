<template>
  <MainLayout activeMenu="teachers">
    <div class="teacher-container">
      <!-- Header: Title + Search + Add New Button -->
      <div class="d-flex justify-between align-items-center mb-3 header-row">
        <h2 class="teacher-title">Teachers</h2>
        <div class="d-flex align-items-center gap-2">
          <input
            type="text"
            v-model="searchQuery"
            class="form-control search-input"
            placeholder="Search teacher..."
          />
          <button class="btn btn-primary" @click="addNewTeacher">+ Add New</button>
        </div>
      </div>

      <!-- Loading Placeholder -->
      <div v-if="!teachers || teachers.length === 0" class="text-center text-muted py-4">
        Loading...
      </div>

      <!-- Teacher Cards with smooth animation -->
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
            <i class="bi bi-trash text-danger" title="Delete" @click="confirmDelete(teacher)"></i>
          </div>
        </div>
      </transition-group>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
  teachers: Array,
})

const searchQuery = ref('')

const filteredTeachers = computed(() => {
  const query = searchQuery.value.toLowerCase()
  return props.teachers.filter(t =>
    t.full_name.toLowerCase().includes(query)
  )
})

const confirmDelete = (teacher) => {
  if (confirm(`⚠️ Are you sure you want to delete ${teacher.full_name}?\nThis will also delete all their uploaded documents.`)) {
    router.delete(`/teachers/${teacher.id}`, {
      onSuccess: () => alert('✅ Teacher and associated documents deleted successfully.'),
      onError: () => alert('❌ Failed to delete teacher.'),
    })
  }
}

const editTeacher = (teacher) => {
  router.get(`/teachers/${teacher.id}/edit`)
}

const viewTeacher = (teacher) => {
  router.get(`/teachers/${teacher.id}`)
}

const addNewTeacher = () => {
  router.get('/teachers/register')
}
</script>

<style scoped>
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

.search-input {
  width: 250px;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #ccc;
}
.search-input:focus {
  border-color: #007bff;
  box-shadow: 0 0 4px rgba(0, 123, 255, 0.5);
}

.card-list {
  display: flex;
  flex-direction: column;
  gap: 1px;
  margin-top: 16px;
}

/* Transition animations */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

.teacher-card {
  position: relative;
  display: flex;
  align-items: center;
  background: white;
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

.action-icons {
  margin-left: auto;
  display: flex;
  gap: 15px;
  font-size: 18px;
}

.action-icons i {
  cursor: pointer;
}
</style>
