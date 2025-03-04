<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col items-center justify-center p-6">
      <h1 class="header text-2xl font-bold text-gray-700 mb-6">Register New User</h1>

      <form @submit.prevent="registerUser" class="all-field bg-white p-6 rounded-lg shadow-md w-full max-w-5xl">
        <div class="grid grid-cols-3 gap-4">
          <!-- Name Fields -->
          <div>
            <label class="block text-gray-700">Last Name</label>
            <input type="text" v-model="form.last_name" class="input-field" required />
          </div>
          <div>
            <label class="block text-gray-700">First Name</label>
            <input type="text" v-model="form.first_name" class="input-field" required />
          </div>
          <div>
            <label class="block text-gray-700">Middle Name</label>
            <input type="text" v-model="form.middle_name" class="input-field" />
          </div>

          <!-- Personal Info -->
          <div>
            <label class="block text-gray-700">Sex</label>
            <select v-model="form.sex" class="input-field" required>
              <option value="">Select</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div>
            <label class="block text-gray-700">Civil Status</label>
            <select v-model="form.civil_status" class="input-field" required>
              <option value="">Select</option>
              <option value="Single">Single</option>
              <option value="Married">Married</option>
              <option value="Widowed">Widowed</option>
            </select>
          </div>
          <div>
            <label class="block text-gray-700">Date of Birth</label>
            <input type="date" v-model="form.date_of_birth" class="input-field" required />
          </div>

          <!-- Religion & Contact -->
          <div>
            <label class="block text-gray-700">Religion</label>
            <input type="text" v-model="form.religion" class="input-field" />
          </div>
          <div>
            <label class="block text-gray-700">Email Address</label>
            <input type="email" v-model="form.email" class="input-field" required />
          </div>
          <div>
            <label class="block text-gray-700">Phone Number</label>
            <input type="text" v-model="form.phone_number" class="input-field" required />
          </div>

          <!-- Password Fields -->
          <div>
            <label class="block text-gray-700">Password</label>
            <input type="password" v-model="form.password" class="input-field" required />
          </div>
          <div>
            <label class="block text-gray-700">Confirm Password</label>
            <input type="password" v-model="form.password_confirmation" class="input-field" required />
          </div>
          <div>
            <label class="block text-gray-700">Role</label>
            <select v-model="form.role" class="input-field" required>
              <option value="">Select</option>
              <option value="LIS">LIS</option>
              <option value="Admin">Admin</option>
            </select>
          </div>

          <!-- Status Field -->
          <div>
            <label class="block text-gray-700">Status</label>
            <select v-model="form.status" class="input-field" required>
              <option value="">Select</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="mt-4 w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">
          Register
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';

export default {
  name: 'RegisterUser',
  components: { Sidebar },
  setup() {
    const form = useForm({
      last_name: '',
      first_name: '',
      middle_name: '',
      sex: '',
      civil_status: '',
      date_of_birth: '',
      religion: '',
      email: '',
      phone_number: '',
      password: '',
      password_confirmation: '',
      role: '',
      status: '' // ✅ Added status field
    });

    const registerUser = () => {
      if (form.password !== form.password_confirmation) {
        alert('Passwords do not match!');
        return;
      }
      form.post(route('register'), {
        onSuccess: () => {
          alert('User registered successfully!');
        },
        onError: (errors) => {
          console.log('Validation errors:', errors);
        },
      });
    };

    return { form, registerUser };
  },
};
</script>

<style scoped>
.all-field {
  margin-left: 200px;
  width: 100%;
}
.header {
  margin-top: 10px;
  margin-left: 250px;
}
.input-field {
  width: 100%;
  padding: 8px;
  margin-top: 4px;
  border: 1px solid #ccc;
  border-radius: 4px;
}
</style>
