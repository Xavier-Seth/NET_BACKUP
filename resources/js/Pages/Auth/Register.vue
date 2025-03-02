<script setup>
import { Head, useForm, Link, usePage } from "@inertiajs/vue3";

const form = useForm({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  role: "LIS", // Default role
});

const page = usePage(); // Get Inertia page instance

const submit = () => {
  console.log("Form Data Before Submission:", form.data()); // Debugging log

  form.post(route("register"), {
    preserveScroll: true,
    onStart: () => console.log("Submitting form:", form.data()),
    onSuccess: () => {
      console.log("User registered successfully", form.data());
      window.location.href = route("dashboard"); // Redirect after success
    },
    onError: (errors) => {
      console.error("Registration failed:", errors);
    },
    onFinish: () => form.reset("password", "password_confirmation"),
  });
};

// Clear errors when input is focused
const clearErrors = () => {
  form.clearErrors();
};
</script>

<template>
  <Head title="Register Page" />

  <div
    class="d-flex justify-content-center align-items-center vh-100"
    style="background: linear-gradient(135deg, #0f0c29, #302b63, #24243e)"
  >
    <div
      class="card p-4 text-white shadow-lg border-0"
      style="width: 400px; background: #191645; border-radius: 20px"
    >
      <div class="text-center">
        <img
          src="/images/school_logo.png"
          alt="School Logo"
          width="90"
          class="mb-3 school-logo"
        />
        <h4 class="fw-bold">Rizal Central School</h4>
        <p class="text-light" style="font-size: 14px">
          Document Archiving System
        </p>
      </div>

      <form @submit.prevent="submit">
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-0">
              <i class="bi bi-person-fill"></i>
            </span>
            <input
              type="text"
              v-model="form.name"
              @focus="clearErrors"
              class="form-control bg-dark text-white border-0"
              placeholder="Name"
              required
            />
          </div>
        </div>

        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-0">
              <i class="bi bi-envelope-fill"></i>
            </span>
            <input
              type="email"
              v-model="form.email"
              @focus="clearErrors"
              class="form-control bg-dark text-white border-0"
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
              @focus="clearErrors"
              class="form-control bg-dark text-white border-0"
              placeholder="Password"
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
              v-model="form.password_confirmation"
              @focus="clearErrors"
              class="form-control bg-dark text-white border-0"
              placeholder="Confirm Password"
              required
            />
          </div>
        </div>

        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-0">
              <i class="bi bi-person-badge"></i>
            </span>
            <select
              v-model="form.role"
              class="form-control bg-dark text-white border-0"
              required
            >
              <option value="LIS">LIS</option>
              <option value="Admin">Admin</option>
            </select>
          </div>
        </div>

        <div v-if="form.errors.length" class="text-center">
          <small class="text-danger">Please check your inputs and try again.</small>
        </div>

        <button
          type="submit"
          class="btn w-100 fw-bold mt-2"
          style="background: #c19f4e; color: #fff; border-radius: 8px"
        >
          Register
        </button>

        <div class="text-center mt-2">
          <Link :href="route('dashboard')" class="text-light" style="font-size: 14px">
            Already registered? Go back.
          </Link>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.school-logo{
    margin-left: 120px;
}
.input-group-text {
  border-radius: 50px 0 0 50px;
  padding: 10px 15px;
}
.form-control {
  border-radius: 0 50px 50px 0;
  padding: 10px;
}
.form-control::placeholder {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}
.card {
  backdrop-filter: blur(10px);
}
</style>
