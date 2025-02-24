<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onSuccess: () => {
            window.location.href = route('dashboard'); // Redirect to dashboard
        },
        onError: () => {
            console.error("Login failed. Check your email and password.");
        },
        onFinish: () => form.reset('password'),
    });
};

// Clear errors when clicking the input field
const clearErrors = () => {
    form.clearErrors();
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
                        <input type="email" v-model="form.email" @focus="clearErrors" 
                               class="form-control bg-dark text-white border-0 placeholder-light" 
                               placeholder="Email" required />
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-white border-0">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" v-model="form.password" @focus="clearErrors"
                               class="form-control bg-dark text-white border-0 placeholder-light" 
                               placeholder="Password" required />
                    </div>
                </div>

                <!-- Error Message (Only in Middle) -->
                <div v-if="form.errors.email || form.errors.password" class="text-center">
                    <small class="text-danger">These credentials do not match our records.</small>
                </div>

                <button type="submit" class="btn w-100 fw-bold mt-2" 
                        style="background: #c19f4e; color: #fff; border-radius: 8px;">
                    Login
                </button>

                <div class="text-center mt-2">
                    <a href="#" class="text-light" style="font-size: 14px;">Forgot password?</a>
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
}

/* Ensure placeholder text is visible */
.form-control::placeholder {
    color: rgba(255, 255, 255, 0.6); /* Light gray for visibility */
    font-size: 14px;
}

/* Blur effect for card */
.card {
    backdrop-filter: blur(10px);
}
</style>
