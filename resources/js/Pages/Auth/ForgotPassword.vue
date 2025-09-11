<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const form = useForm({
  email: '',
})

const sent = ref(false)

const submit = () => {
  form.post(route('password.email'), {
    onSuccess: () => {
      sent.value = true
      setTimeout(() => (sent.value = false), 4000) // auto-hide after 4s
    },
  })
}
</script>

<template>
  <Head title="Forgot Password" />

  <div class="d-flex justify-content-center align-items-center vh-100"
       style="background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);">
    <div class="card p-4 text-white shadow-lg border-0"
         style="width: 400px; background: #191645; border-radius: 20px;">
      <div class="text-center">
        <!-- ✅ Centered logo -->
        <img src="/images/school_logo.png" alt="School Logo" width="80" class="mb-3 d-block mx-auto" />
        <h4 class="fw-bold">Forgot Password</h4>
        <p class="text-light" style="font-size: 14px;">Enter your email to receive a reset link.</p>
      </div>

      <form @submit.prevent="submit">
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
          <div v-if="form.errors.email" class="text-danger small mt-1">
            {{ form.errors.email }}
          </div>
        </div>

        <button type="submit" class="btn w-100 fw-bold mt-2"
                style="background: #c19f4e; color: #fff; border-radius: 8px;">
          Send Reset Link
        </button>
      </form>

      <!-- ✅ Success alert -->
      <div v-if="sent" class="alert alert-success mt-3 mb-0 py-2 px-3 text-center">
        Reset link sent to your email!
      </div>
    </div>
  </div>
</template>

<style scoped>
.input-group-text {
  border-radius: 50px 0 0 50px;
  padding: 10px 15px;
}
.form-control {
  border-radius: 0 50px 50px 0;
  padding: 10px;
  background-color: #212529;
  color: white;
}
.form-control::placeholder {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}
.card { backdrop-filter: blur(10px); }
</style>
