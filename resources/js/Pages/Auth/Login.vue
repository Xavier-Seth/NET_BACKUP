<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const showError = ref(false); 

const submit = () => {
  form.post(route('login'), {
    onSuccess: () => {
      router.visit(route('dashboard'));
    },
    onError: () => {
      showError.value = true;

      // Auto-hide after 3 seconds
      setTimeout(() => {
        showError.value = false;
        form.clearErrors();
      }, 3000);
    },
    onFinish: () => form.reset('password'),
  });
};

const clearErrors = () => {
  form.clearErrors();
  showError.value = false;
};
</script>

<template>
  <Head title="Login Page" />

  <div class="d-flex justify-content-center align-items-center vh-100"
       style="background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);">
    <div class="card p-4 text-white shadow-lg border-0"
         style="width: 400px; background: #191645; border-radius: 20px;">
      <div class="text-center">
        <div class="d-flex justify-content-center">
          <img src="/images/school_logo.png" alt="School Logo" width="90" class="mb-3" />
        </div>
        <h4 class="fw-bold">Rizal Central School</h4>
        <p class="text-light" style="font-size: 14px;">Document Archiving System</p>
      </div>

      <form @submit.prevent="submit">
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-0">
              <i class="bi bi-person-fill"></i>
            </span>
            <input
              type="email"
              v-model="form.email"
              @input="clearErrors"
              class="form-control bg-dark text-white border-0 placeholder-light"
              placeholder="Email"
              required
            />
          </div>
        </div>

        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-0">
              <i class="bi bi-lock-fill"></i>
            </span>
            <input
              type="password"
              v-model="form.password"
              @input="clearErrors"
              class="form-control bg-dark text-white border-0 placeholder-light"
              placeholder="Password"
              required
            />
          </div>
        </div>

        <!-- Error message fades out after 3 seconds -->
        <div v-if="showError && (form.errors.email || form.errors.password)" class="text-center">
          <small class="text-danger">
            {{ form.errors.email || form.errors.password }}
          </small>
        </div>

        <button type="submit" class="btn w-100 fw-bold mt-2"
                style="background: #c19f4e; color: #fff; border-radius: 8px;">
          Login
        </button>

        <div class="text-center mt-2">
          <Link :href="route('password.request')" class="text-light" style="font-size: 14px;">
            Forgot password?
          </Link>
        </div>
      </form>
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

input:-webkit-autofill,
input:-webkit-autofill:focus,
input:-webkit-autofill:hover,
input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 1000px #212529 inset !important;
  -webkit-text-fill-color: white !important;
  caret-color: white !important;
  transition: background-color 5000s ease-in-out 0s;
}

.card {
  backdrop-filter: blur(10px);
}
</style>
