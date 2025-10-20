<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const localErrors = ref({ email: null, password: null });
const showError = ref(false);

const mergedErrors = computed(() => ({
  email: localErrors.value.email || form.errors.email || null,
  password: localErrors.value.password || form.errors.password || null,
}));

const triggerFadeOut = () => {
  showError.value = true;
  setTimeout(() => {
    showError.value = false;
    localErrors.value = { email: null, password: null };
  }, 3000);
};

const submit = () => {
  // clear previous client errors
  localErrors.value = { email: null, password: null };

  // client-side validation
  if (!form.email) localErrors.value.email = 'The email field is required.';
  if (!form.password) localErrors.value.password = 'The password field is required.';

  if (localErrors.value.email || localErrors.value.password) {
    triggerFadeOut();
    return;
  }

  // server request
  form.post(route('login'), {
    onSuccess: () => {
      router.visit(route('dashboard'));
    },
    onError: () => {
      triggerFadeOut();
    },
    onFinish: () => form.reset('password'),
  });
};

const clearErrors = (field) => {
  if (field in localErrors.value) localErrors.value[field] = null;
  if (field in form.errors) delete form.errors[field];

  if (!mergedErrors.value.email && !mergedErrors.value.password) {
    showError.value = false;
  }
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

      <!-- General backend login error -->
      <div v-if="showError && form.errors.email && !localErrors.email && !localErrors.password"
           class="alert alert-danger py-2 px-3 mb-3" role="alert" style="font-size: 13px;">
        {{ form.errors.email }}
      </div>

      <!-- Disable native HTML validation -->
      <form @submit.prevent="submit" novalidate>
        <!-- Email field -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-0">
              <i class="bi bi-person-fill"></i>
            </span>
            <input
              type="email"
              v-model="form.email"
              @input="clearErrors('email')"
              class="form-control bg-dark text-white border-0 placeholder-light"
              placeholder="Email"
            />
          </div>
          <small v-if="showError && mergedErrors.email" class="text-danger">
            {{ mergedErrors.email }}
          </small>
        </div>

        <!-- Password field -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-0">
              <i class="bi bi-lock-fill"></i>
            </span>
            <input
              type="password"
              v-model="form.password"
              @input="clearErrors('password')"
              class="form-control bg-dark text-white border-0 placeholder-light"
              placeholder="Password"
            />
          </div>
          <small v-if="showError && mergedErrors.password" class="text-danger">
            {{ mergedErrors.password }}
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

.alert {
  border-radius: 10px;
}
</style>
