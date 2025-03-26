    <template>
      <div class="page-wrapper">
        <!-- ✅ Top Header -->
        <header class="top-bar">
          <div class="brand">
            <img src="/images/school_logo.png" alt="School Logo" class="school-logo" />
            <h1>Rizal Central School</h1>
          </div>

          <div class="profile dropdown">
            <div class="dropdown-toggle" data-bs-toggle="dropdown">
              <img src="/images/user-avatar.png" alt="User Avatar" class="avatar" />
              <div class="user-info">
                <span class="user-name">{{ userName }}</span>
                <small class="user-role">{{ userRole }}</small>
              </div>
            </div>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><Link class="dropdown-item" :href="route('profile.edit')">Profile Settings</Link></li>
              <li><button @click="checkRole" class="dropdown-item">Register New User</button></li>
              <li><button @click="checkRoleStudent" class="dropdown-item">Register Student</button></li>
              <li><Link class="dropdown-item text-danger" href="/logout" method="post" as="button">Logout</Link></li>
            </ul>
          </div>
        </header>

        <!-- ✅ Student Form -->
        <div class="container mt-4 p-4 form-wrapper rounded shadow">
          <div class="mb-3">
            <button class="btn btn-link text-decoration-none back-arrow" @click="goBack">
              ← Back to Dashboard
            </button>
          </div>

          <h4 class="fw-bold text-center mb-4">Register Student</h4>

          <form @submit.prevent="submit">
            <!-- ✅ Student Basic Info -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">LRN:</label>
                <input v-model="form.lrn" type="text" class="form-control" />
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4"><label class="form-label">First Name:</label><input v-model="form.first_name" type="text" class="form-control" /></div>
              <div class="col-md-4"><label class="form-label">Middle Name:</label><input v-model="form.middle_name" type="text" class="form-control" /></div>
              <div class="col-md-4"><label class="form-label">Last Name:</label><input v-model="form.last_name" type="text" class="form-control" /></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4"><label class="form-label">Birthdate:</label><input v-model="form.birthdate" type="date" class="form-control" /></div>
              <div class="col-md-2">
                <label class="form-label">Sex:</label>
                <select v-model="form.sex" class="form-select">
                  <option value="" disabled>Sex</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="col-md-4"><label class="form-label">Civil Status:</label><input v-model="form.civil_status" type="text" class="form-control" /></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4"><label class="form-label">Citizenship:</label><input v-model="form.citizenship" type="text" class="form-control" /></div>
              <div class="col-md-4"><label class="form-label">Place of Birth:</label><input v-model="form.place_of_birth" type="text" class="form-control" /></div>
              <div class="col-md-4"><label class="form-label">S/Y:</label><input v-model="form.school_year" type="text" class="form-control" /></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6"><label class="form-label">Guardian Phone No:</label><input v-model="form.guardian_phone" type="text" class="form-control" /></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-8"><label class="form-label">Permanent Address:</label><input v-model="form.address" type="text" class="form-control" /></div>
              <div class="col-md-4">
                <label class="form-label">Grade Level:</label>
                <select v-model="form.grade_level" class="form-select">
                  <option value="" disabled>Select Grade Level</option>
                  <option value="Grade 1">Grade 1</option>
                  <option value="Grade 2">Grade 2</option>
                  <option value="Grade 3">Grade 3</option>
                  <option value="Grade 4">Grade 4</option>
                  <option value="Grade 5">Grade 5</option>
                  <option value="Grade 6">Grade 6</option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4"><label class="form-label">Father's Name:</label><input v-model="form.father_name" type="text" class="form-control" /></div>
              <div class="col-md-4"><label class="form-label">Mother's Name:</label><input v-model="form.mother_name" type="text" class="form-control" /></div>
              <div class="col-md-4"><label class="form-label">Guardian's Name:</label><input v-model="form.guardian_name" type="text" class="form-control" /></div>
            </div>

            <!-- ✅ File Uploads -->
            <div class="row mb-3">
              <div class="col-md-4"><label class="form-label">Form 137:</label><input type="file" class="form-control" @change="handleFileUpload($event, 'form137')" /></div>
              <div class="col-md-4"><label class="form-label">PSA:</label><input type="file" class="form-control" @change="handleFileUpload($event, 'psa')" /></div>
              <div class="col-md-4"><label class="form-label">ECCRD:</label><input type="file" class="form-control" @change="handleFileUpload($event, 'eccrd')" /></div>
            </div>

            <div class="text-end">
              <button class="btn btn-primary">Submit</button>
            </div>

            <!-- ✅ Flash Success -->
            <div v-if="$page.props.flash?.success" class="alert alert-success mt-4">
              {{ $page.props.flash.success }}
            </div>
          </form>
        </div>
      </div>
    </template>

    <script setup>
    import { useForm, usePage, router, Link } from '@inertiajs/vue3'

    const user = usePage().props.auth.user
    const userName = user ? `${user.last_name}, ${user.first_name}` : 'User'
    const userRole = user?.role ? `(${user.role})` : ''

    const checkRole = () => {
      if (user.role !== 'Admin') {
        alert('❌ Access Denied: Only Admin users can register new users.')
      } else {
        router.visit(route('register'))
      }
    }

    const checkRoleStudent = () => {
      if (user.role !== 'Admin') {
        alert('❌ Access Denied: Only Admin users can register students.')
      } else {
        router.visit(route('students.register'))
      }
    }

    const goBack = () => {
      router.visit(route('dashboard'))
    }

    const form = useForm({
      lrn: '',
      first_name: '',
      middle_name: '',
      last_name: '',
      birthdate: '',
      sex: '',
      civil_status: '',
      citizenship: '',
      place_of_birth: '',
      school_year: '',
      guardian_phone: '',
      address: '',
      grade_level: '',
      father_name: '',
      mother_name: '',
      guardian_name: '',
      form137: null,
      psa: null,
      eccrd: null,
    })

    // ✅ Auto-generate school year
    const currentYear = new Date().getFullYear()
    const nextYear = currentYear + 1
    form.school_year = `${currentYear}-${nextYear}`

    const handleFileUpload = (event, field) => {
      form[field] = event.target.files[0]
    }

    const submit = () => {
      form.post(route('students.store'), {
        forceFormData: true,
        onSuccess: () => {
          form.reset()
        },
        onError: () => {
          alert('❌ Failed to submit. Please check for validation errors.')
        },
      })
    }
    </script>

    <style scoped>
    .container {
      max-width: 1000px;
      margin: 0 auto;
    }
    .top-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 20px;
      background: white;
      width: 100%;
      margin-left: 40px;
      margin-top: 10px;
      border-radius: 0 0 10px 10px;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .school-logo {
      width: 60px;
      height: auto;
    }
    .profile {
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      min-width: 180px;
      justify-content: flex-end;
      margin-right: 50px;
    }
    .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid #ccc;
      object-fit: cover;
      flex-shrink: 0;
      margin-right: 10px;
    }
    .user-info {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    .user-name {
      font-weight: bold;
    }
    .user-role {
      font-size: 12px;
      color: gray;
    }
    .dropdown-menu {
      background: white;
      border-radius: 8px;
      box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);
    }
    .dropdown-item {
      color: black;
      padding: 10px;
    }
    .dropdown-item:hover {
      background: #f1f1f1;
    }
    .dropdown-toggle {
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 200px;
    }
    .form-wrapper {
      background-color: #ffffff;
    }
    .page-wrapper {
      background-color: #ffffff;
    }
    .back-arrow {
      font-size: 16px;
      color: #0d6efd;
      padding-left: 0;
    }
    .back-arrow:hover {
      text-decoration: underline;
      color: #0a58ca;
    }
    </style>
