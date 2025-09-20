<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

// Props come from the GET /reset-password/{token} route
const props = defineProps({
  token: String,
  email: String
})

const form = useForm({
  token: props.token || '',
  email: props.email || '',
  password: '',
  password_confirmation: ''
})

const submitting = ref(false)
const success = ref(false)

const submit = () => {
  submitting.value = true
  form.post(route('password.store'), {
    onSuccess: () => {
      success.value = true
      // Optionally redirect after a moment
      // setTimeout(() => window.location = route('login'), 1200)
    },
    onFinish: () => (submitting.value = false)
  })
}
</script>

<template>
  <Head title="Reset Password" />

  <div class="d-flex justify-content-center align-items-center vh-100"
       style="background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);">
    <div class="card p-4 text-white shadow-lg border-0"
         style="width: 420px; background: #191645; border-radius: 20px;">
      <div class="text-center">
        <!-- Centered logo -->
        <img src="/images/school_logo.png" alt="School Logo" width="80" class="mb-3 d-block mx-auto" />
        <h4 class="fw-bold">Reset Password</h4>
        <p class="text-light" style="font-size: 14px;">Create a new password for your account.</p>
      </div>

      <form @submit.prevent="submit">
        <!-- Email -->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-0">
              <i class="bi bi-envelope-fill"></i>
            </span>
            <input
              type="email"
              v-model="form.email"
              class="form-control bg-dark text-white border-0 placeholder-light"
              placeholder="Email"
              required
            />
          </div>
          <div v-if="form.errors.email" class="text-danger small mt-1">{{ form.errors.email }}</div>
        </div>

        <!-- New password -->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-0">
              <i class="bi bi-lock-fill"></i>
            </span>
            <input
              type="password"
              v-model="form.password"
              class="form-control bg-dark text-white border-0 placeholder-light"
              placeholder="New password"
              required
              minlength="8"
            />
          </div>
          <div v-if="form.errors.password" class="text-danger small mt-1">{{ form.errors.password }}</div>
        </div>

        <!-- Confirm password -->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-0">
              <i class="bi bi-shield-lock-fill"></i>
            </span>
            <input
              type="password"
              v-model="form.password_confirmation"
              class="form-control bg-dark text-white border-0 placeholder-light"
              placeholder="Confirm password"
              required
            />
          </div>
          <div v-if="form.errors.password_confirmation" class="text-danger small mt-1">
            {{ form.errors.password_confirmation }}
          </div>
        </div>

        <button type="submit" class="btn w-100 fw-bold mt-2"
                :disabled="submitting"
                style="background: #c19f4e; color: #fff; border-radius: 8px;">
          {{ submitting ? 'Updating…' : 'Update Password' }}
        </button>
      </form>

      <!-- Success -->
      <div v-if="success" class="alert alert-success mt-3 mb-0 py-2 px-3 text-center">
        Password updated! You can now log in with your new password.
      </div>
    </div>
  </div>
</template>

<style scoped>
.input-group-text { border-radius: 50px 0 0 50px; padding: 10px 15px; }
.form-control { border-radius: 0 50px 50px 0; padding: 10px; background-color: #212529; color: white; }
.form-control::placeholder { color: rgba(255,255,255,.6); font-size: 14px; }
.card { backdrop-filter: blur(10px); }
</style>
