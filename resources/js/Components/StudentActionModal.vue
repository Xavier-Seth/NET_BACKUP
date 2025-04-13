<template>
  <div v-if="show" class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <h5 class="modal-title mb-3 text-center">{{ student.full_name }}</h5>
      <p class="mb-2 text-center">LRN: {{ student.lrn }}</p>

      <div class="btn-group">
        <button class="btn btn-warning" @click="go('record')">Student Record</button>
        <button class="btn btn-success" @click="goToDocuments">Documents</button>
      </div>

      <button class="btn btn-secondary mt-3 w-100" @click="close">Close</button>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ show: Boolean, student: Object })
const emit = defineEmits(['close'])

const go = (page) => {
  router.get(`/students/${props.student.id}/${page}`)
  emit('close')
}

const goToDocuments = () => {
  router.visit('/documents', {
    method: 'get',
    data: {
      search: props.student.lrn,
      type: 'student'
    },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => emit('close')
  })
}

const close = () => emit('close')

watch(() => props.show, (visible) => {
  if (visible) {
    document.body.classList.add('no-scroll')
  } else {
    document.body.classList.remove('no-scroll')
  }
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 999;
  padding: 1rem;
}
.modal-content {
  background: white;
  padding: 20px;
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
  box-sizing: border-box;
}
.btn-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 10px;
}
.btn {
  width: 100%;
  padding: 10px;
  font-size: 14px;
  border-radius: 6px;
  font-weight: 500;
  text-align: center;
}
</style>
