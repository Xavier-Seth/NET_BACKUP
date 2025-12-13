<template>
  <div class="settings-layout">
    <Sidebar />

    <main class="content">
      <header class="page-header">
        <div class="title-wrap">
          <h1 class="page-title"><i class="bi bi-gear-wide-connected me-2"></i>Settings</h1>
          <p class="page-sub">Manage general preferences, create backups, and update security.</p>
        </div>
      </header>

      <nav class="tabs">
        <button v-if="isAdmin" class="tab" :class="{ active: activeTab === 'general' }" @click="activeTab = 'general'">
          <i class="bi bi-sliders"></i><span>General</span>
        </button>
        <button v-if="isAdmin" class="tab" :class="{ active: activeTab === 'backup' }" @click="activeTab = 'backup'">
          <i class="bi bi-hdd-stack"></i><span>Backup</span>
        </button>
        
        <button class="tab" :class="{ active: activeTab === 'security' }" @click="activeTab = 'security'">
          <i class="bi bi-shield-lock"></i><span>Security</span>
        </button>
      </nav>

      <section v-if="isAdmin" v-show="activeTab === 'general'" class="card">
        <div class="card-h">
          <div>
            <h5 class="card-title"><i class="bi bi-gear me-2"></i>General</h5>
            <p class="card-sub">Branding and basic system information.</p>
          </div>
        </div>

        <div class="card-b">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">School Name</label>
              <input type="text" v-model="form.school_name" class="form-control" placeholder="Enter school name" />
              <small v-if="errors.school_name" class="text-danger">{{ errors.school_name }}</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">School Logo</label>
              <input type="file" ref="logoInput" class="form-control" accept="image/png,image/jpeg,image/webp" @change="onLogoChange" />
              <small v-if="errors.logo" class="text-danger">{{ errors.logo }}</small>

              <div v-if="logoPreviewUrl" class="mt-2 d-flex align-items-center gap-2">
                <img :src="logoPreviewUrl" alt="Logo preview" style="height:56px;width:auto;border:1px solid #ddd;border-radius:8px;" />
                <button class="btn btn-sm btn-light" @click="clearLogo">Remove</button>
              </div>
              <div v-else-if="initialLogoUrl" class="mt-2">
                <img :src="initialLogoUrl" alt="Current logo" style="height:56px;width:auto;border:1px solid #ddd;border-radius:8px;" />
              </div>
            </div>
          </div>
        </div>

        <div class="card-f">
          <button class="btn btn-primary" @click="save('general')" :disabled="loading || restoring || backingUp">
            <i class="bi bi-check2-circle me-1"></i>
            <span v-if="!loading">Save Changes</span>
            <span v-else>Saving…</span>
          </button>
        </div>
      </section>

      <section v-if="isAdmin" v-show="activeTab === 'backup'" class="card">
        <div class="card-h">
          <div>
            <h5 class="card-title"><i class="bi bi-hdd-network me-2"></i>Backup</h5>
            <p class="card-sub">Manual backups only. Create a full archive (database + uploaded files) on demand.</p>
          </div>
          <div class="inline-help">
            <i class="bi bi-info-circle"></i>
            <span>Backups are stored in a secure internal folder.</span>
          </div>
        </div>

        <div class="card-b">
          <div class="backup-cta">
            <div class="cta-copy">
              <h6 class="mb-1">Create a new backup</h6>
              <small class="text-muted">Recommended before big changes or updates.</small>
            </div>
            <button class="btn btn-outline-primary" @click="confirmAndRun" :disabled="loading || restoring || backingUp">
              <i class="bi bi-hdd-stack me-2"></i>
              <span v-if="!loading">Run Backup Now</span>
              <span v-else>Working…</span>
            </button>
          </div>

          <hr class="my-4" />

          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">Backup Archives</h6>
            <div class="d-flex align-items-center gap-2">
              <span class="text-muted small">Total: {{ pagination.total }}</span>
              <button class="btn btn-sm btn-light" @click="refreshArchives()" :disabled="restoring || backingUp">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
              </button>
            </div>
          </div>

          <ul v-if="archives.length" class="archive-list">
            <li v-for="b in archives" :key="b.name" class="archive-item">
              <div class="meta">
                <div class="ic"><i class="bi bi-file-zip"></i></div>
                <div class="txt">
                  <div class="name" :title="b.name">{{ b.name }}</div>
                  <div class="sub">
                    <span class="badge date"><i class="bi bi-calendar2-week me-1"></i>{{ b.date }}</span>
                    <span class="dot">•</span>
                    <span class="badge size"><i class="bi bi-hdd me-1"></i>{{ mb(b.size) }} MB</span>
                  </div>
                </div>
              </div>

              <div class="actions d-flex gap-2">
                <a :href="downloadUrl(b.name)" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" :class="{ disabled: restoring || backingUp }" @click.prevent="onDownloadClick($event, b.name)" role="button" title="Download Archive">
                  <i class="bi bi-download"></i> Download
                </a>

                <button class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" @click="initRestoreExisting(b.name)" :disabled="restoring || backingUp" title="Restore from this archive">
                  <i v-if="restoring && restoringName === b.name" class="bi bi-arrow-repeat spin"></i>
                  <i v-else class="bi bi-arrow-counterclockwise"></i> Restore
                </button>

                <button class="btn btn-sm btn-outline-danger" @click="promptDelete(b.name)" :disabled="restoring || loading || backingUp" title="Delete Archive">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </li>
          </ul>

          <div v-else class="empty">
            <div class="empty-ic"><i class="bi bi-archive"></i></div>
            <div class="empty-txt">
              <strong>No backups yet</strong>
              <span>Click “Run Backup Now” to create your first archive.</span>
            </div>
          </div>

          <div v-if="pagination.last_page > 1" class="pager">
            <button class="btn btn-sm btn-light" :disabled="pagination.current_page <= 1 || restoring || backingUp" @click="goToPage(pagination.current_page - 1)">
              <i class="bi bi-chevron-left"></i> Prev
            </button>
            <span class="pg-info">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
            <button class="btn btn-sm btn-light" :disabled="pagination.current_page >= pagination.last_page || restoring || backingUp" @click="goToPage(pagination.current_page + 1)">
              Next <i class="bi bi-chevron-right"></i>
            </button>
          </div>

          <hr class="my-4" />
          <div class="backup-restore">
            <h6 class="mb-2">Restore from archive (upload)</h6>
            <div class="row g-2 align-items-center">
              <div class="col-md-6">
                <input type="file" ref="restoreInput" class="form-control" accept=".zip" @change="onRestoreFile" :disabled="restoring || backingUp" />
                <small class="text-muted d-block mt-1">Upload a ZIP created by this system.</small>
              </div>
              <div class="col-md-3">
                <select v-model="restoreOpts.is_encrypted" class="form-select" :disabled="restoring || backingUp">
                  <option :value="false">Decrypted (.zip)</option>
                  <option :value="true">Encrypted (-enc.zip)</option>
                </select>
              </div>
              <div class="col-md-3 d-flex gap-2">
                <select v-model="restoreOpts.mode" class="form-select" :disabled="restoring || backingUp">
                  <option value="replace">Replace (drop tables)</option>
                  <option value="merge">Merge (no drop)</option>
                </select>
                <button class="btn btn-danger" :disabled="!restoreFile || loading || restoring || backingUp" @click="initRestoreUpload">
                  <i v-if="restoring && restoringSource === 'upload'" class="bi bi-arrow-repeat me-1 spin"></i>
                  <i v-else class="bi bi-arrow-counterclockwise me-1"></i>
                  {{ restoring && restoringSource === 'upload' ? 'Restoring…' : 'Restore' }}
                </button>
              </div>
            </div>
            <div v-if="restoreMsg" class="alert mt-3" :class="restoreOk ? 'alert-success' : 'alert-warning'">
              {{ restoreMsg }}
            </div>
          </div>
        </div>
      </section>

      <section v-show="activeTab === 'security'" class="card">
        <div class="card-h">
          <div>
            <h5 class="card-title"><i class="bi bi-shield-check me-2"></i>Security</h5>
            <p class="card-sub">Update your password to keep your account secure.</p>
          </div>
        </div>
        <div class="card-b">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Current Password</label>
              <input type="password" v-model="form.current_password" class="form-control" placeholder="Enter current password" autocomplete="current-password" />
            </div>
            <div class="col-md-6">
              <label class="form-label">New Password</label>
              <input type="password" v-model="form.new_password" class="form-control" placeholder="Enter new password" autocomplete="new-password" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm New Password</label>
              <input type="password" v-model="form.confirm_password" class="form-control" placeholder="Confirm new password" autocomplete="new-password" />
            </div>
          </div>
          <div v-if="Object.keys(errors).length" class="alert alert-warning mt-3">
            <ul class="mb-0 ps-3">
              <li v-for="(msg, key) in errors" :key="key">{{ msg }}</li>
            </ul>
          </div>
          <div v-if="successMsg" class="alert alert-success mt-3">{{ successMsg }}</div>
        </div>
        <div class="card-f">
          <button class="btn btn-danger" @click="save('security')" :disabled="loading || restoring || backingUp">
            <i class="bi bi-key me-1"></i>
            <span v-if="!loading">Update Password</span>
            <span v-else>Working…</span>
          </button>
        </div>
      </section>
    </main>
  </div>

  <div v-if="showSuccess" class="modal-backdrop" @click.self="closeSuccess">
    <div class="modal-card" role="dialog">
      <div class="modal-h">
        <h5 class="m-0"><i class="bi bi-check-circle-fill me-2 text-success"></i>Backup Successful</h5>
        <button class="btn-close" @click="closeSuccess" aria-label="Close"></button>
      </div>
      <div class="modal-b">
        <p class="mb-2">Successfully backed up. Download files below.</p>
        <ul class="created-list" v-if="createdFiles.length">
          <li v-for="(fname, idx) in createdFiles" :key="fname" class="created-item">
            <div class="left">
              <i class="bi bi-file-zip me-2"></i>
              <span class="fname" :title="fname">{{ fname }}</span>
            </div>
            <div class="right">
              <a :href="downloadUrl(fname)" class="btn btn-sm btn-outline-primary" @click.prevent="onDownloadClick($event, fname)">
                <i class="bi bi-download me-1"></i> Download
              </a>
            </div>
          </li>
        </ul>
      </div>
      <div class="modal-f">
        <button class="btn btn-primary" @click="viewInList"><i class="bi bi-collection me-1"></i> View in Archives</button>
        <button class="btn btn-light" @click="closeSuccess">Close</button>
      </div>
    </div>
  </div>

  <div v-if="restoring || backingUp" class="restore-overlay" @click.stop>
    <div class="restore-box">
      <div class="spinner"></div>
      <div class="txts">
        <h6 class="m-0">{{ backingUp ? 'Creating Backup…' : 'Restoring…' }}</h6>
        <small class="text-muted">
          {{ backingUp ? 'Please wait while we archive your system.' : (restoreStep || 'Unpacking data...') }}
        </small>
        <div v-if="restoring && restoringName" class="mt-1 small">
          <i class="bi bi-file-zip me-1"></i><strong>{{ restoringName }}</strong>
        </div>
      </div>
    </div>
  </div>

  <div v-if="showDeleteModal" class="modal-backdrop">
    <div class="modal-box">
      <h5>Confirm Deletion</h5>
      <p>
        Are you sure you want to delete
        <strong>{{ targetDeleteName }}</strong>?
        <br>
        <small class="text-danger">This action cannot be undone.</small>
      </p>
      <div class="modal-actions">
        <button class="btn btn-secondary" @click="showDeleteModal = false">Cancel</button>
        <button class="btn btn-danger" @click="proceedDelete">Yes, Delete</button>
      </div>
    </div>
  </div>

  <div v-if="showRestoreModal" class="modal-backdrop">
    <div class="modal-box">
      <h5 class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Restore</h5>
      <p class="mb-2">
        You are about to restore:
        <br><strong>{{ pendingRestoreName }}</strong>
      </p>
      <p class="text-muted small mb-3">
        This will <strong>overwrite current data</strong>. This action is irreversible.
        Please type <strong class="text-dark">RESTORE</strong> below to confirm.
      </p>

      <input 
        type="text" 
        v-model="restoreConfirmationInput" 
        class="form-control text-center mb-3" 
        placeholder="Type RESTORE"
      >

      <div class="modal-actions">
        <button class="btn btn-secondary" @click="showRestoreModal = false">
          Cancel
        </button>
        <button 
          class="btn btn-danger" 
          :disabled="restoreConfirmationInput !== 'RESTORE'" 
          @click="executeRestore"
        >
          Confirm Restore
        </button>
      </div>
    </div>
  </div>

  <div v-if="showRestoreSuccess" class="modal-backdrop" @click.self="showRestoreSuccess = false">
    <div class="modal-card" role="dialog">
      <div class="modal-h">
        <h5 class="m-0"><i class="bi bi-check-circle-fill me-2 text-success"></i>Restore Complete</h5>
        <button class="btn-close" @click="showRestoreSuccess = false" aria-label="Close"></button>
      </div>
      <div class="modal-b text-center py-4">
        <p class="mb-0 fs-5">{{ restoreSuccessMessage }}</p>
      </div>
      <div class="modal-f justify-content-center">
        <button class="btn btn-primary" @click="showRestoreSuccess = false">Close</button>
      </div>
    </div>
  </div>

</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import Sidebar from '@/Components/Sidebar.vue'

const page = usePage()

// --- USER ROLE CHECK ---
const user = computed(() => page.props.auth.user)
const isAdmin = computed(() => user.value && user.value.role === 'Admin')

// --- ACTIVE TAB LOGIC ---
// If Admin, start at 'general'. If Teacher, start at 'security'.
const activeTab = ref(isAdmin.value ? 'general' : 'security')

const loading = ref(false)

const form = ref({
  school_name: page.props.settings?.school_name ?? '',
  current_password: '',
  new_password: '',
  confirm_password: '',
})

const logoFile = ref(null)
const logoPreviewUrl = ref('')
const logoInput = ref(null)
const initialLogoUrl = computed(() => page.props.settings?.logo_path_url || null)
const errors = ref({})
const successMsg = ref('')

/* -------- Blocking UI States -------- */
const restoring = ref(false)
const backingUp = ref(false) 

const restoringName = ref('')
const restoringSource = ref('')
const restoreStep = ref('')

/* -------- Modal States -------- */
const showDeleteModal = ref(false)
const targetDeleteName = ref('')

const showRestoreModal = ref(false)
const pendingRestoreType = ref('')
const pendingRestoreName = ref('')
const restoreConfirmationInput = ref('')

const showRestoreSuccess = ref(false)
const restoreSuccessMessage = ref('')

/* -------- Logo handling -------- */
const onLogoChange = (e) => {
  const f = e.target.files?.[0]
  logoFile.value = f || null
  logoPreviewUrl.value = f ? URL.createObjectURL(f) : ''
}
const clearLogo = () => {
  logoFile.value = null
  logoPreviewUrl.value = ''
  if (logoInput.value) logoInput.value.value = ''
}

/* -------- URL Helpers -------- */
const downloadUrl = (name) => {
  if (!name) return '#'
  try {
    if (typeof window !== 'undefined' && window.location && window.location.origin) {
      return `${window.location.origin}/settings/backup/download/${encodeURIComponent(name)}`
    }
  } catch (e) {}
  return `/settings/backup/download/${encodeURIComponent(name)}`
}

const archivesUrl = (page = 1, perPage = 10) => {
  return `/settings/backup/archives?page=${encodeURIComponent(page)}&perPage=${encodeURIComponent(perPage)}`
}

/* -------- CSRF Helpers -------- */
const getCsrf = async () => {
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta && meta.getAttribute('content')) return meta.getAttribute('content');
  try {
    const resp = await fetch('/csrf-token', {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (resp.ok) {
      const j = await resp.json();
      if (j && j.token) {
        let m = document.querySelector('meta[name="csrf-token"]');
        if (!m) {
          m = document.createElement('meta');
          m.setAttribute('name', 'csrf-token');
          document.head.appendChild(m);
        }
        m.setAttribute('content', j.token);
        return j.token;
      }
    }
  } catch (e) {}
  return null;
};

const securedFetch = async (input, init = {}, { retryOn419 = true } = {}) => {
  const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const token = metaToken || (await getCsrf());
  if (!token) throw new Error('CSRF token unavailable.');

  const headers = new Headers(init.headers || {});
  headers.set('X-CSRF-TOKEN', token);
  headers.set('X-Requested-With', 'XMLHttpRequest');

  let res = await fetch(input, { ...init, headers, credentials: 'same-origin' });
  if (res.status === 419 && retryOn419) {
    const fresh = await getCsrf();
    if (fresh) {
      headers.set('X-CSRF-TOKEN', fresh);
      res = await fetch(input, { ...init, headers, credentials: 'same-origin' });
    }
  }
  return res;
};

/* -------- Branding Refresh -------- */
const refreshBranding = () => {
  router.reload({ only: ['branding'] })
}

/* -------- Save Handlers -------- */
const save = async (section) => {
  if (section === 'general') {
    errors.value = {}
    successMsg.value = ''
    try {
      loading.value = true
      const fd = new FormData()
      fd.append('school_name', (form.value.school_name || '').trim())
      if (logoFile.value) fd.append('logo', logoFile.value)
      const res = await securedFetch(route('settings.general.update'), { method: 'POST', body: fd })
      if (res.status === 422) {
        const data = await res.json()
        errors.value = Object.fromEntries(Object.entries(data.errors || {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]))
        return
      }
      const data = await res.json()
      successMsg.value = data.message || 'Saved.'
      if (data.logo_url) logoPreviewUrl.value = `${data.logo_url}?t=${Date.now()}`
      logoFile.value = null
      if (logoInput.value) logoInput.value.value = ''
      refreshBranding()
    } finally {
      loading.value = false
    }
  }
  if (section === 'security') {
    errors.value = {}
    successMsg.value = ''
    try {
      loading.value = true
      const res = await securedFetch(route('settings.security.update'), {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          current_password: form.value.current_password,
          new_password: form.value.new_password,
          confirm_password: form.value.confirm_password,
        }),
      })
      if (res.status === 422) {
        const data = await res.json()
        errors.value = Object.fromEntries(Object.entries(data.errors || {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]))
        return
      }
      const data = await res.json()
      successMsg.value = data.message || 'Password updated.'
      form.value.current_password = ''
      form.value.new_password = ''
      form.value.confirm_password = ''
    } finally {
      loading.value = false
    }
  }
}

/* -------- Backup Logic (UPDATED) -------- */
const archives = ref([])
const pagination = ref({ current_page: 1, per_page: 10, total: 0, last_page: 1 })
const showSuccess = ref(false)
const createdFiles = ref([])
const mb = (bytes) => (bytes / 1024 / 1024).toFixed(2)

const confirmAndRun = async () => {
  if (!confirm('Run a new backup now?')) return
  try {
    loading.value = true
    backingUp.value = true 
    
    const res = await securedFetch(route('settings.backup.run'), { method: 'POST' })
    if (!res.ok) {
      alert(`Backup failed (HTTP ${res.status})`)
      return
    }
    const data = await res.json()
    createdFiles.value = Array.isArray(data.created) ? data.created.filter(Boolean) : []
    showSuccess.value = true
    setTimeout(() => refreshArchives(pagination.value.current_page), 1500)
  } finally {
    loading.value = false
    backingUp.value = false 
  }
}

const refreshArchives = async (page = 1) => {
  page = Math.max(1, Number(page) || 1)
  const per = pagination.value.per_page || 10
  const url = archivesUrl(page, per)
  const res = await fetch(url, {
    method: 'GET',
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  })
  if (!res.ok) return
  const data = await res.json()
  archives.value = Array.isArray(data.data) ? data.data : []
  pagination.value = {
    current_page: data.current_page || 1,
    per_page: data.per_page || 10,
    total: data.total || 0,
    last_page: data.last_page || 1,
  }
}
const goToPage = (p) => { if (p >= 1 && p <= pagination.value.last_page) refreshArchives(p) }
const closeSuccess = () => { showSuccess.value = false }
const viewInList = () => { activeTab.value = 'backup'; refreshArchives(1); closeSuccess() }

/* -------- DELETE LOGIC (Modal) -------- */
const promptDelete = (name) => {
  targetDeleteName.value = name
  showDeleteModal.value = true
}

const proceedDelete = async () => {
  if (!targetDeleteName.value) return
  
  try {
    loading.value = true
    const res = await securedFetch(route('settings.backup.delete', targetDeleteName.value), {
      method: 'DELETE',
    })
    const data = await res.json()
    if (!res.ok) {
      alert(data.message || 'Failed to delete backup.')
    } else {
      showDeleteModal.value = false
      targetDeleteName.value = ''
      await refreshArchives(pagination.value.current_page)
    }
  } catch (e) {
    console.error(e)
    alert('An error occurred while deleting the backup.')
  } finally {
    loading.value = false
  }
}

/* -------- Restore Logic -------- */
const restoreInput = ref(null)
const restoreFile = ref(null)
const restoreOpts = ref({ is_encrypted: false, mode: 'replace' })
const restoreMsg = ref('')
const restoreOk = ref(false)

const onRestoreFile = (e) => { restoreFile.value = e.target.files?.[0] || null }

const initRestoreUpload = () => {
  if (!restoreFile.value) return
  pendingRestoreType.value = 'upload'
  pendingRestoreName.value = restoreFile.value.name
  restoreConfirmationInput.value = ''
  showRestoreModal.value = true
}

const initRestoreExisting = (name) => {
  if (!name) return
  pendingRestoreType.value = 'existing'
  pendingRestoreName.value = name
  restoreConfirmationInput.value = ''
  showRestoreModal.value = true
}

const executeRestore = async () => {
  if (restoreConfirmationInput.value !== 'RESTORE') return
  showRestoreModal.value = false
  
  loading.value = true
  restoring.value = true 
  restoreStep.value = 'Initializing restore...'

  try {
    if (pendingRestoreType.value === 'upload') {
      restoringSource.value = 'upload'
      restoringName.value = pendingRestoreName.value
      restoreStep.value = 'Uploading archive…'
      
      await new Promise(r => setTimeout(r, 50))
      restoreStep.value = 'Processing restore on server…'
      
      const fd = new FormData()
      fd.append('archive', restoreFile.value)
      fd.append('is_encrypted', restoreOpts.value.is_encrypted ? '1' : '0')
      fd.append('mode', restoreOpts.value.mode)
      fd.append('confirm', 'RESTORE')
      
      const res = await securedFetch(route('settings.backup.restore.upload'), { method: 'POST', body: fd })
      const data = await res.json()
      
      restoreSuccessMessage.value = data.message || (res.ok ? 'Restore completed.' : 'Restore failed.')
      showRestoreSuccess.value = true

    } else if (pendingRestoreType.value === 'existing') {
      const name = pendingRestoreName.value
      restoringSource.value = 'existing'
      restoringName.value = name
      restoreStep.value = 'Preparing restore…'
      
      await new Promise(r => setTimeout(r, 50))
      restoreStep.value = 'Processing restore on server…'
      
      const res = await securedFetch(route('settings.backup.restore.existing', name), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          is_encrypted: name.toLowerCase().endsWith('-enc.zip'),
          mode: 'replace',
          confirm: 'RESTORE',
        }),
      })
      const data = await res.json()
      
      restoreSuccessMessage.value = data.message || (res.ok ? 'Restore completed.' : 'Restore failed.')
      showRestoreSuccess.value = true
    }
  } catch (e) {
    console.error(e)
    alert('An unexpected error occurred during restore.')
  } finally {
    restoreStep.value = ''
    restoring.value = false
    restoringSource.value = ''
    restoringName.value = ''
    pendingRestoreType.value = ''
    loading.value = false
  }
}

/* -------- Download Click Handler -------- */
const onDownloadClick = (e, name) => {
  if (restoring.value || backingUp.value) { e.preventDefault(); return }
  const url = downloadUrl(name)
  if (!url || url === '#') { e.preventDefault(); return }
  if (typeof window !== 'undefined' && window.location && typeof window.location.assign === 'function') {
    window.location.assign(url)
  } else {
    window.location.href = url
  }
}

watch(activeTab, (tab) => { 
  // Only refresh backup archives if user is Admin and backup tab selected
  if (tab === 'backup' && isAdmin.value) refreshArchives(pagination.value.current_page) 
})

onMounted(() => { 
  // Only refresh backup archives if user is Admin and backup tab selected
  if (activeTab.value === 'backup' && isAdmin.value) refreshArchives(1) 
})
</script>

<style scoped>
/* Layout */
.settings-layout {
  --bg: #f5f6fb; --card-bg: #fff; --text: #1f2430; --muted: #6b7280; --border: #e9ecf3; --brand: #12172b; --brand-50: rgba(18,23,43,.08);
  min-height: 100vh; display: flex; background: var(--bg); color: var(--text);
}
.content { flex: 1; padding: 24px; margin-left: 210px; }
.page-header { margin-bottom: 14px; }
.title-wrap { display: grid; gap: 6px; }
.page-title { margin: 0; font-size: 1.45rem; font-weight: 700; letter-spacing: .2px; }
.page-sub { margin: 0; color: var(--muted); font-size: .95rem; }

/* Tabs */
.tabs { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 20px; }
.tab {
  display: inline-flex; align-items: center; gap: 8px; background: transparent; border: 1px solid var(--border);
  color: var(--text); padding: 10px 14px; border-radius: 10px; font-weight: 600; transition: .2s ease;
}
.tab.active { background: var(--brand); color: #fff; border-color: var(--brand); }
.tab:hover { box-shadow: 0 2px 10px rgba(0,0,0,.04); }

/* Card */
.card { border: 1px solid var(--border); background: var(--card-bg); border-radius: 14px; overflow: hidden; margin-bottom: 16px; box-shadow: 0 2px 24px rgba(0,0,0,.03); }
.card-h, .card-f { display: flex; align-items: flex-start; justify-content: space-between; padding: 16px 18px; gap: 16px; background: #fff; }
.card-h { border-bottom: 1px solid var(--border); }
.card-f { border-top: 1px solid var(--border); }
.card-title { margin: 0; font-size: 1.05rem; font-weight: 700; }
.card-sub { margin: 4px 0 0; color: var(--muted); font-size: .92rem; }
.inline-help { display: flex; align-items: center; gap: 8px; color: var(--muted); background: var(--brand-50); border-radius: 10px; padding: 8px 12px; }
.card-b { padding: 18px; }

/* Backup CTA */
.backup-cta { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; border: 1px dashed var(--border); border-radius: 12px; background: #fafbff; }
.cta-copy h6 { margin: 0; font-weight: 700; }

/* Archives */
.archive-list { display: grid; gap: 10px; padding: 0; margin: 0; list-style: none; }
.archive-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 12px; background: #fff; }
.archive-item .meta { display: flex; align-items: center; gap: 12px; }
.archive-item .ic { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 10px; background: #f1f3fa; color: var(--brand); font-size: 1.05rem; }
.archive-item .txt .name { font-weight: 700; max-width: 52vw; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.archive-item .txt .sub { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: .9rem; }
.archive-item .txt .sub .dot { opacity: .6; }
.archive-item .actions { display: flex; gap: 8px; }

/* Empty */
.empty { display: grid; place-items: center; gap: 8px; padding: 40px 0; text-align: center; color: var(--muted); }
.empty-ic { width: 64px; height: 64px; display: grid; place-items: center; border-radius: 14px; background: #f1f3fa; color: var(--brand); font-size: 1.4rem; }
.empty-txt strong { color: var(--text); }

/* Pagination */
.pager { margin-top: 14px; display: flex; align-items: center; gap: 10px; }
.pg-info { font-size: .9rem; color: var(--muted); }

/* Modals */
.modal-backdrop { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 999; }
.modal-card, .modal-box { background: white; border-radius: 14px; box-shadow: 0 12px 40px rgba(0,0,0,.2); overflow: hidden; animation: pop .15s ease-out; }
.modal-card { width: 100%; max-width: 680px; }
.modal-box { padding: 20px 30px; width: 90%; max-width: 400px; text-align: center; }
.modal-h, .modal-f { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--border); }
.modal-f { border-top: 1px solid var(--border); border-bottom: 0; }
.modal-b { padding: 16px; }
.modal-actions { display: flex; justify-content: center; gap: 20px; margin-top: 20px; }
.modal-actions .btn { min-width: 110px; }
@keyframes pop { from { transform: translateY(8px); opacity: .6; } to { transform: translateY(0); opacity: 1; } }
.btn-close { border: 0; background: transparent; width: 32px; height: 32px; padding: 0; line-height: 1; display: grid; place-items: center; font-size: 1.2rem; }

/* Created list */
.created-list { list-style: none; padding: 0; margin: 10px 0 0; display: grid; gap: 10px; }
.created-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; }
.created-item .left { display: flex; align-items: center; gap: 8px; min-width: 0; }
.created-item .fname { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 48vw; }
.created-item .right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

/* Restoring overlay */
.restore-overlay { position: fixed; inset: 0; background: rgba(13, 18, 33, 0.45); display: grid; place-items: center; z-index: 10000; padding: 16px; backdrop-filter: blur(1px); }
.restore-box { display: flex; align-items: center; gap: 12px; background: #fff; border-radius: 12px; padding: 14px 16px; min-width: 280px; box-shadow: 0 10px 38px rgba(0,0,0,.18); border: 1px solid var(--border); }
.restore-box .spinner { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #e3e7f0; border-top-color: var(--brand); animation: spin 0.8s linear infinite; }
.spin { animation: spin 0.9s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 992px) { .archive-item .txt .name { max-width: 46vw; } }
@media (max-width: 768px) { .content { margin-left: 0; padding: 16px; } .archive-item { align-items: flex-start; } .archive-item .actions { margin-left: 52px; align-items: center; } }
</style>