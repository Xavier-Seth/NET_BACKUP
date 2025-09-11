<template>
  <MainLayout activeMenu="dashboard">
    <!-- Top: Avatars + Widgets -->
    <div class="d-flex justify-content-between align-items-center px-4 py-3 bg-light flex-wrap">
      <!-- Avatars: open Quick Actions -->
      <div class="d-flex align-items-center">
        <button class="carousel-arrow" @click="scrollAvatars(-1)" :disabled="avatarIndex === 0">‹</button>

        <div class="d-flex gap-3 fixed-avatar-row">
          <div
            v-for="teacher in visibleAvatars"
            :key="teacher.id"
            class="avatar-wrapper text-center"
            :title="teacher.name"
            @click="openQuickActions(teacher)"
          >
            <div class="story-ring">
              <img :src="teacher.photo" class="rounded-circle avatar-img" />
            </div>
            <small class="d-block mt-1 text-truncate text-muted">{{ teacher.name }}</small>
          </div>
        </div>

        <button class="carousel-arrow" @click="scrollAvatars(1)" :disabled="avatarIndex + 6 >= avatars.length">›</button>
      </div>

      <!-- Widgets -->
      <div class="d-flex gap-3 ms-auto mt-3 mt-md-0">
        <!-- Storage -->
        <div class="storage-card p-3 border rounded text-center">
          <h6 class="text-primary mb-2">Total Storage</h6>
          <div class="donut-chart mx-auto">
            <svg viewBox="0 0 36 36" class="donut-svg">
              <circle class="donut-bg" cx="18" cy="18" r="15" />
              <circle class="donut-fg" cx="18" cy="18" r="15" :stroke-dasharray="`${percentage},100`" />
              <text x="18" y="20" text-anchor="middle" class="donut-text">{{ percentage }}%</text>
            </svg>
          </div>
          <p class="mt-2 mb-0">{{ totalStorage }}</p>
          <div class="small text-muted">{{ storageBreakdown }}</div>
        </div>

        <!-- Teachers -->
        <div class="teacher-widget p-3 border rounded text-center">
          <h6 class="text-primary mb-2">Total Teachers</h6>
          <div>
            <i class="bi bi-person-lines-fill fs-2 text-info"></i>
            <p class="mt-2 mb-0 fs-5 fw-bold">{{ totalTeachers }}</p>
            <div class="small text-muted">{{ activeCount }} active · {{ inactiveCount }} inactive</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Teacher List -->
    <div class="teacher-list p-4 bg-white">
      <div
        v-for="teacher in teachers"
        :key="teacher.id"
        class="d-flex align-items-center border-bottom py-3"
      >
        <img :src="teacher.photo" alt="Teacher" class="rounded-circle avatar-img me-3" />
        <div class="flex-grow-1">
          <div class="fw-semibold">{{ teacher.name }}</div>
          <div class="text-muted small">{{ teacher.department || '—' }}</div>
        </div>

        <!-- Status (text only) -->
        <span class="me-3 text-muted text-capitalize">{{ teacher.status }}</span>

        <!-- Single action -->
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

    <!-- Quick Actions Modal (for avatar clicks) -->
    <QuickActionsModal
      :show="qaOpen"
      :teacher="qaTeacher || {}"
      :recent="qaRecent"
      :latest="qaLatest"
      @close="closeQuickActions"
      @open="goOpenProfile"
      @upload="goUpload"
      @preview="previewLatest"
      @preview-doc="previewDoc"
    />
  </MainLayout>
</template>

<script setup>
import { usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import QuickActionsModal from '@/Components/QuickActionsModal.vue'

/** ------- Inertia props (from controller) ------- */
const page = usePage()
const avatars          = page.props.avatars || []                 // [{id,name,photo,status,department, recent?:[]}]
const teachers         = page.props.teachers?.data || []          // paginated list
const pagination       = page.props.teachers
const totalStorage     = page.props.totalStorage || '0.00 MB'     // e.g., "2.50 GB"
const totalTeachers    = page.props.totalTeachers || 0
const activeCount      = page.props.activeCount   || 0
const inactiveCount    = page.props.inactiveCount || 0
const storageBreakdown = page.props.storageBreakdown || ''        // e.g., "PDF 65% · Images 25% · XLS 10%"

/** ------- Quick Actions state (avatars) ------- */
const qaOpen    = ref(false)
const qaTeacher = ref(null)
const qaRecent  = ref([])
const qaLatest  = ref(null)

const openQuickActions = (teacher) => {
  qaTeacher.value = teacher
  qaRecent.value  = teacher.recent || []
  qaLatest.value  = qaRecent.value[0] || null
  qaOpen.value    = true
}
const closeQuickActions = () => { qaOpen.value = false }
const goOpenProfile = () => { qaOpen.value = false; router.visit(`/teachers/${qaTeacher.value.id}`) }
const goUpload = () => { qaOpen.value = false; router.visit(`/upload?teacher_id=${qaTeacher.value.id}`) }

/** ------- Preview (opens /documents/{id}/preview) ------- */
const previewLatest = () => {
  if (!qaLatest.value) return
  qaOpen.value = false
  window.open(`/documents/${qaLatest.value.id}/preview`, '_blank', 'noopener')
}
const previewDoc = (id) => {
  qaOpen.value = false
  window.open(`/documents/${id}/preview`, '_blank', 'noopener')
}

/** ------- Avatar carousel ------- */
const avatarIndex = ref(0)
const visibleAvatars = computed(() => avatars.slice(avatarIndex.value, avatarIndex.value + 6))
const scrollAvatars = (direction) => {
  const newIndex = avatarIndex.value + direction
  if (newIndex >= 0 && newIndex <= Math.max(avatars.length - 6, 0)) avatarIndex.value = newIndex
}

/** ------- Navigation helpers ------- */
const viewDocuments = (id) => router.visit(`/teachers/${id}`)
const goToPage = (url) => { if (url) router.visit(url, { preserveScroll: true }) }

/** ------- Storage donut percentage ------- */
const percentage = computed(() => {
  const [numStr, unit = 'MB'] = (totalStorage || '0 MB').split(' ')
  const used = parseFloat(numStr || '0')
  const usedMB =
    unit?.toUpperCase().startsWith('G') ? used * 1024 :
    unit?.toUpperCase().startsWith('K') ? used / 1024 :
    used
  return Math.min(Math.round((usedMB / 1000) * 100), 100) // assumes 1000 MB quota
})
</script>

<style scoped>
/* Avatar Carousel */
.fixed-avatar-row { width: auto; flex-wrap: nowrap; gap: 1rem; overflow-x: auto; }
.avatar-wrapper { flex: 0 0 auto; width: 70px; text-align: center; cursor: pointer; }
.avatar-img { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; }
.story-ring { padding: 2px; background: linear-gradient(45deg, #0d6efd, #ffc107); border-radius: 50%; display: inline-block; }
.story-ring img { border: 2px solid #fff; }
.carousel-arrow { background: none; border: none; font-size: 2rem; font-weight: bold; color: #0d6efd; cursor: pointer; padding: 0 10px; }
.carousel-arrow:disabled { color: #ccc; cursor: not-allowed; }

/* Widgets */
.storage-card, .teacher-widget { width: 180px; background: #fff; }
.donut-chart { width: 100px; height: 100px; }
.donut-svg { transform: rotate(-90deg); width: 100%; height: 100%; }
.donut-bg { fill: none; stroke: #e9ecef; stroke-width: 4; }
.donut-fg { fill: none; stroke: #28a745; stroke-width: 4; stroke-linecap: round; transition: stroke-dasharray 0.6s ease; }
.donut-text { font-size: 0.5em; fill: #28a745; font-weight: bold; transform: rotate(90deg); }

/* Teacher List */
.teacher-list { background: #fff; }
.status-label { font-size: 0.9rem; color: #6c757d; }
</style>
