<template>
  <MainLayout activeMenu="profile">
    
    <div class="container d-flex justify-content-center pt-4">
      <div class="position-relative w-100" style="max-width: 1000px;">
        <img
          src="/images/rizal.jpg"
          class="w-100 rounded-top"
          style="height: 200px; object-fit: cover;"
        />

        <div class="position-absolute top-100 start-50 translate-middle text-center">
          <img
            :src="teacher.photo_path ? `/storage/${teacher.photo_path}` : '/images/user-avatar.png'"
            class="rounded-circle border border-white shadow"
            style="width: 160px; height: 160px; object-fit: cover;"
            alt="Photo"
          />
          <h5 class="mt-2 fw-bold">{{ teacher.full_name }}</h5>
          <span class="badge bg-primary">{{ teacher.status }}</span>
        </div>
      </div>
    </div>

    <div class="container d-flex justify-content-center">
      <div
        class="form-wrapper mt-1 bg-white shadow p-4"
        style="max-width: 1000px; width: 100%; border-radius: 0 0 10px 10px;"
      >
        <form>
          <div class="row mb-3" style="margin-top: 5rem;">
            <div class="col-md-4">
              <label class="form-label text-muted">First Name</label>
              <input :value="teacher.first_name" class="form-control" disabled />
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted">Middle Name</label>
              <input :value="teacher.middle_name || '-'" class="form-control" disabled />
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted">Last Name</label>
              <input :value="teacher.last_name" class="form-control" disabled />
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label text-muted">Name Extension</label>
              <input :value="teacher.name_extension || '-'" class="form-control" disabled />
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted">Employee ID</label>
              <input :value="teacher.employee_id" class="form-control" disabled />
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted">Position</label>
              <input :value="teacher.position" class="form-control" disabled />
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label text-muted">Birthdate</label>
              <input :value="teacher.birth_date" type="date" class="form-control" disabled />
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted">Department</label>
              <input :value="teacher.department" class="form-control" disabled />
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted">Date Hired</label>
              <input :value="teacher.date_hired" type="date" class="form-control" disabled />
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label text-muted">Contact</label>
              <input :value="teacher.contact" class="form-control" disabled />
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted">Email</label>
              <input :value="teacher.email" class="form-control" disabled />
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label text-muted">Address</label>
              <input :value="teacher.address" class="form-control" disabled />
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label text-muted">Remarks</label>
              <textarea
                :value="teacher.remarks"
                class="form-control"
                rows="3"
                disabled
              ></textarea>
            </div>
          </div>
          
        </form>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayout.vue' // <--- Use MainLayout!

const props = defineProps({ teacher: Object })
</script>

<style scoped>
/* No need for margin-left logic here, MainLayout handles it */
.form-wrapper {
  background: #fff;
  padding: 25px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
}
.form-control:disabled {
  background-color: #f8f9fa; 
  border: 1px solid #dee2e6;
  color: #495057;
  font-weight: 500;
}
</style>