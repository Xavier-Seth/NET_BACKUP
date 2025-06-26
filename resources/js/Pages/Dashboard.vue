<template>
  <MainLayout activeMenu="dashboard">
    <!-- Top Section: Avatars + Widgets -->
    <div class="d-flex justify-content-between align-items-center px-4 py-3 bg-light flex-wrap">
      <!-- Avatar Carousel -->
      <div class="d-flex align-items-center">
        <!-- Left Arrow -->
        <button
          class="carousel-arrow"
          @click="scrollAvatars(-1)"
          :disabled="avatarIndex === 0"
        >
          ‹
        </button>

        <!-- 6-Slot Avatar Row -->
        <div class="d-flex gap-3 fixed-avatar-row">
          <div
            v-for="teacher in visibleAvatars"
            :key="teacher.id"
            class="avatar-wrapper text-center"
            :title="teacher.name"
            @click="viewDocuments(teacher.id)"
          >
            <div class="story-ring">
              <img :src="teacher.photo" class="rounded-circle avatar-img" />
            </div>
            <small class="d-block mt-1 text-truncate text-muted">{{ teacher.name }}</small>
          </div>
        </div>

        <!-- Right Arrow -->
        <button
          class="carousel-arrow"
          @click="scrollAvatars(1)"
          :disabled="avatarIndex + 6 >= avatars.length"
        >
          ›
        </button>
      </div>

      <!-- Widgets -->
      <div class="d-flex gap-3 ms-auto mt-3 mt-md-0">
        <!-- Storage Widget -->
        <div class="storage-card p-3 border rounded text-center">
          <h6 class="text-primary mb-2">Total Storage</h6>
          <div class="donut-chart mx-auto">
            <svg viewBox="0 0 36 36" class="donut-svg">
              <circle class="donut-bg" cx="18" cy="18" r="15" />
              <circle
                class="donut-fg"
                cx="18"
                cy="18"
                r="15"
                :stroke-dasharray="`${percentage},100`"
              />
              <text x="18" y="20" text-anchor="middle" class="donut-text">{{ percentage }}%</text>
            </svg>
          </div>
          <p class="mt-2 mb-0">{{ totalStorage }}</p>
        </div>

        <!-- Teachers Widget -->
        <div class="teacher-widget p-3 border rounded text-center">
          <h6 class="text-primary mb-2">Total Teachers</h6>
          <div>
            <i class="bi bi-person-lines-fill fs-2 text-info"></i>
            <p class="mt-2 mb-0 fs-5 fw-bold">{{ totalTeachers }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Teacher List Cards -->
    <div class="teacher-list p-4 bg-white">
      <div
        v-for="teacher in teachers"
        :key="teacher.id"
        class="d-flex align-items-center border-bottom py-3"
      >
        <img :src="teacher.photo" alt="Teacher" class="rounded-circle avatar-img me-3" />
        <div class="flex-grow-1">
          <div class="fw-semibold">{{ teacher.name }}</div>
        </div>
        <div class="d-flex align-items-center me-4">
          <span :class="['status-dot', teacher.status]"></span>
          <span class="ms-1 status-label text-capitalize">{{ teacher.status }}</span>
        </div>
        <button class="btn btn-outline-primary btn-sm" @click="viewDocuments(teacher.id)">
          View Documents
        </button>
      </div>

      <!-- Pagination -->
      <div v-if="pagination?.links?.length" class="text-center mt-4">
        <button
          v-for="(link, index) in pagination.links"
          :key="index"
          class="btn btn-sm mx-1"
          :class="{ 'btn-primary': link.active, 'btn-outline-secondary': !link.active }"
          :disabled="!link.url"
          v-html="link.label"
          @click="goToPage(link.url)"
        />
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'

const page = usePage()

// Props
const avatars = page.props.avatars || []
const teachers = page.props.teachers?.data || []
const pagination = page.props.teachers
const totalStorage = page.props.totalStorage || '0.00 MB'
const totalTeachers = page.props.totalTeachers || 0

// Avatar carousel state
const avatarIndex = ref(0)
const visibleAvatars = computed(() => avatars.slice(avatarIndex.value, avatarIndex.value + 6))

const scrollAvatars = (direction) => {
  const newIndex = avatarIndex.value + direction
  if (newIndex >= 0 && newIndex <= avatars.length - 6) {
    avatarIndex.value = newIndex
  }
}

const viewDocuments = (id) => router.visit(`/teachers/${id}`)

const goToPage = (url) => {
  if (url) router.visit(url, { preserveScroll: true })
}

const percentage = computed(() => {
  const used = parseFloat(totalStorage)
  return Math.min(Math.round((used / 1000) * 100), 100)
})
</script>

<style scoped>
/* Avatar Carousel */
.fixed-avatar-row {
  width: auto;
  flex-wrap: nowrap;
  gap: 1rem;
}
.fixed-avatar-row {
  overflow-x: auto;
}
.avatar-wrapper {
  flex: 0 0 auto;
  width: 70px;
  text-align: center;
  cursor: pointer;
}
.avatar-img {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 50%;
}
.story-ring {
  padding: 2px;
  background: linear-gradient(45deg, #0d6efd, #ffc107);
  border-radius: 50%;
  display: inline-block;
}
.story-ring img {
  border: 2px solid #fff;
}
.carousel-arrow {
  background: none;
  border: none;
  font-size: 2rem;
  font-weight: bold;
  color: #0d6efd;
  cursor: pointer;
  padding: 0 10px;
}
.carousel-arrow:disabled {
  color: #ccc;
  cursor: not-allowed;
}

/* Widgets */
.storage-card,
.teacher-widget {
  width: 180px;
  background: #fff;
}
.donut-chart {
  width: 100px;
  height: 100px;
}
.donut-svg {
  transform: rotate(-90deg);
  width: 100%;
  height: 100%;
}
.donut-bg {
  fill: none;
  stroke: #e9ecef;
  stroke-width: 4;
}
.donut-fg {
  fill: none;
  stroke: #28a745;
  stroke-width: 4;
  stroke-linecap: round;
  transition: stroke-dasharray 0.6s ease;
}
.donut-text {
  font-size: 0.5em;
  fill: #28a745;
  font-weight: bold;
  transform: rotate(90deg);
}

/* Teacher List */
.teacher-list {
  background: #fff;
}
.status-dot {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 50%;
}
.status-dot.active {
  background: #28a745;
}
.status-dot.inactive {
  background: #ffc107;
}
.status-label {
  font-size: 0.9rem;
  color: #6c757d;
}
</style>
