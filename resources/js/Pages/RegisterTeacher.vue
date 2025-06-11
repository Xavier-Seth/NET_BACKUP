<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar />

    <div class="flex-1">
      <div class="container mt-4 p-4 form-wrapper rounded shadow">
        <h4 class="fw-bold text-center mb-4">Register New Teacher</h4>

        <form @submit.prevent="submit">
          <!-- Name Fields -->
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label">First Name:</label>
              <input v-model="form.first_name" type="text" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Middle Name:</label>
              <input v-model="form.middle_name" type="text" class="form-control" placeholder="(optional)" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Last Name:</label>
              <input v-model="form.last_name" type="text" class="form-control" />
            </div>
          </div>

          <!-- Employment Details -->
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label">Name Extension:</label>
              <input v-model="form.name_extension" type="text" class="form-control" placeholder="Jr., Sr., III (optional)" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Employee ID:</label>
              <input v-model="form.employee_id" type="text" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Position:</label>
              <input v-model="form.position" type="text" class="form-control" />
            </div>
          </div>

          <!-- Dates & Department -->
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label">Birthdate:</label>
              <input v-model="form.birth_date" type="date" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Department / Unit:</label>
              <input v-model="form.department" type="text" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Date Hired:</label>
              <input v-model="form.date_hired" type="date" class="form-control" />
            </div>
          </div>

          <!-- Contact Info -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Contact Number:</label>
              <input v-model="form.contact" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Email Address:</label>
              <input v-model="form.email" type="email" class="form-control" />
            </div>
          </div>

          <!-- Address -->
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Permanent Address:</label>
              <input v-model="form.address" type="text" class="form-control" />
            </div>
          </div>

          <!-- Remarks -->
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Remarks:</label>
              <textarea v-model="form.remarks" class="form-control" rows="3" placeholder="Additional notes or info..."></textarea>
            </div>
          </div>

          <!-- ✅ PDS Upload -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Upload PDS File:</label>
              <input
                ref="pdsRef"
                type="file"
                accept=".pdf,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                class="form-control"
                @change="handleFileUpload($event)"
              />
              <p v-if="form.pds" class="text-success mt-1">Selected file: {{ form.pds.name }}</p>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="text-end">
            <button class="btn btn-primary">Register Teacher</button>
          </div>
        </form>

        <!-- ✅ Success Modal -->
        <Teleport to="body">
          <Transition name="fade">
            <div v-if="showSuccessModal" class="modal-backdrop">
              <div class="modal-content-box">
                <h5>Success</h5>
                <p>{{ successMessage }}</p>
                <button class="btn btn-success mt-2" @click="showSuccessModal = false">OK</button>
              </div>
            </div>
          </Transition>
        </Teleport>
      </div>
    </div>
  </div>
</template>

<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

const showSuccessModal = ref(false);
const successMessage = ref('');
const pdsRef = ref(null);

const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  full_name: '',
  name_extension: '',
  employee_id: '',
  position: '',
  birth_date: '',
  department: '',
  date_hired: '',
  contact: '',
  email: '',
  address: '',
  remarks: '',
  pds: null,
});

const handleFileUpload = (event) => {
  form.pds = event.target.files[0];
};

const submit = () => {
  const nameParts = [form.first_name, form.middle_name, form.last_name].filter(Boolean);
  form.full_name = nameParts.join(' ');

  form.post(route('teachers.store'), {
    forceFormData: true,
    onSuccess: () => {
      showSuccessModal.value = true;
      successMessage.value = '✅ Teacher registered successfully.';
      form.reset();
      pdsRef.value.value = '';
    },
    onError: () => {
      showSuccessModal.value = false;
    }
  });
};
</script>

<style scoped>
.flex-1 {
  margin-left: 200px;
  padding: 20px;
}

.container {
  max-width: 1000px;
  margin: 0 auto;
}

.form-wrapper {
  background-color: #ffffff;
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content-box {
  background: white;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
  width: 320px;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.4s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
