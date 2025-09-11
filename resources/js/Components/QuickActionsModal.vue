<template>
  <div v-if="show" class="fixed inset-0 z-50 d-flex align-items-center justify-content-center">
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" @click="$emit('close')"></div>

    <div class="position-relative bg-white rounded-3 shadow p-4" style="width: 28rem;">
      <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2" @click="$emit('close')">✕</button>

      <div class="d-flex gap-3 mb-3">
        <img :src="teacher.photo" class="rounded-circle" style="width:64px;height:64px;object-fit:cover" />
        <div class="flex-grow-1">
          <div class="fw-semibold text-truncate">{{ teacher.name }}</div>
          <div class="text-muted small">
            {{ teacher.department || '—' }}
            ·
            <span :class="['badge', teacher.status==='active' ? 'bg-success' : 'bg-secondary']">
              {{ (teacher.status || 'inactive').toUpperCase() }}
            </span>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mb-3">
        <button class="btn btn-primary btn-sm" @click="$emit('open')">Open Profile</button>
        <button class="btn btn-outline-primary btn-sm" @click="$emit('upload')">Upload</button>
        <button class="btn btn-outline-secondary btn-sm" :disabled="!latest" @click="$emit('preview')">
          Preview Latest
        </button>
      </div>

      <div>
        <div class="fw-semibold mb-2">Recent Documents</div>
        <div v-if="recent?.length" class="list-group small">
          <button
            v-for="doc in recent"
            :key="doc.id"
            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
            @click="$emit('preview-doc', doc.id)"
          >
            <span class="text-truncate me-2">{{ doc.name }}</span>
            <span class="text-muted">{{ doc.created_at_human || '' }}</span>
          </button>
        </div>
        <div v-else class="text-muted small">No recent documents.</div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show:   { type: Boolean, default: false },
  teacher:{ type: Object,  default: () => ({}) },
  recent: { type: Array,   default: () => [] },
  latest: { type: Object,  default: null },
})
defineEmits(['close','open','upload','preview','preview-doc'])
</script>

<style scoped>
.fixed { position: fixed; }
.inset-0 { inset: 0; }
</style>
