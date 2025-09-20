<template>
  <div class="settings-layout">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Page -->
    <main class="content">
      <!-- Header -->
      <header class="page-header">
        <div class="title-wrap">
          <h1 class="page-title"><i class="bi bi-gear-wide-connected me-2"></i>Settings</h1>
          <p class="page-sub">Manage general preferences, create backups, and update security.</p>
        </div>
      </header>

      <!-- Tabs -->
      <nav class="tabs">
        <button class="tab" :class="{ active: activeTab === 'general' }" @click="activeTab = 'general'">
          <i class="bi bi-sliders"></i><span>General</span>
        </button>
        <button class="tab" :class="{ active: activeTab === 'backup' }" @click="activeTab = 'backup'">
          <i class="bi bi-hdd-stack"></i><span>Backup</span>
        </button>
        <button class="tab" :class="{ active: activeTab === 'security' }" @click="activeTab = 'security'">
          <i class="bi bi-shield-lock"></i><span>Security</span>
        </button>
      </nav>

      <!-- General -->
      <section v-show="activeTab === 'general'" class="card">
        <div class="card-h">
          <div>
            <h5 class="card-title"><i class="bi bi-gear me-2"></i>General</h5>
            <p class="card-sub">Basic system information and preferences.</p>
          </div>
        </div>

        <div class="card-b">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">School Name</label>
              <input type="text" v-model="form.school_name" class="form-control" placeholder="Enter school name" />
            </div>
            <div class="col-md-6">
              <label class="form-label">System Language</label>
              <select v-model="form.language" class="form-select">
                <option value="en">English</option>
                <option value="fil">Filipino</option>
              </select>
            </div>
          </div>
        </div>

        <div class="card-f">
          <button class="btn btn-primary" @click="save('general')">
            <i class="bi bi-check2-circle me-1"></i> Save Changes
          </button>
        </div>
      </section>

      <!-- Backup -->
      <section v-show="activeTab === 'backup'" class="card">
        <div class="card-h">
          <div>
            <h5 class="card-title"><i class="bi bi-hdd-network me-2"></i>Backup</h5>
            <p class="card-sub">
              Manual backups only. Create a full archive (database + uploaded files) on demand.
            </p>
          </div>
          <div class="inline-help">
            <i class="bi bi-info-circle"></i>
            <span>Backups are stored in a secure internal folder. You can download archives below.</span>
          </div>
        </div>

        <div class="card-b">
          <div class="backup-cta">
            <div class="cta-copy">
              <h6 class="mb-1">Create a new backup</h6>
              <small class="text-muted">Recommended before big changes or updates.</small>
            </div>

            <!-- POST to runBackup and refresh list -->
            <button class="btn btn-outline-primary" @click="confirmAndRun" :disabled="loading">
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
              <button class="btn btn-sm btn-light" @click="refreshArchives()">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
              </button>
            </div>
          </div>

          <!-- List -->
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
              <div class="actions">
                <a :href="route('settings.backup.download', b.name)" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-download"></i>
                  <span class="d-none d-sm-inline ms-1">Download</span>
                </a>
              </div>
            </li>
          </ul>

          <!-- Empty -->
          <div v-else class="empty">
            <div class="empty-ic"><i class="bi bi-archive"></i></div>
            <div class="empty-txt">
              <strong>No backups yet</strong>
              <span>Click “Run Backup Now” to create your first archive.</span>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="pager">
            <button class="btn btn-sm btn-light" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">
              <i class="bi bi-chevron-left"></i> Prev
            </button>

            <span class="pg-info">
              Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>

            <button class="btn btn-sm btn-light" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">
              Next <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </section>

      <!-- Security -->
      <section v-show="activeTab === 'security'" class="card">
        <div class="card-h">
          <div>
            <h5 class="card-title"><i class="bi bi-shield-check me-2"></i>Security</h5>
            <p class="card-sub">Update your password to keep your account secure.</p>
          </div>
        </div>

        <div class="card-b">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">New Password</label>
              <input type="password" v-model="form.new_password" class="form-control" placeholder="Enter new password" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm Password</label>
              <input
                type="password"
                v-model="form.confirm_password"
                class="form-control"
                placeholder="Confirm new password"
              />
            </div>
          </div>
        </div>

        <div class="card-f">
          <button class="btn btn-danger" @click="save('security')">
            <i class="bi bi-key me-1"></i> Update Password
          </button>
        </div>
      </section>
    </main>
  </div>

  <!-- Success Modal -->
  <div v-if="showSuccess" class="modal-backdrop" @click.self="closeSuccess">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="backupSuccessTitle">
      <div class="modal-h">
        <h5 id="backupSuccessTitle" class="m-0">
          <i class="bi bi-check-circle-fill me-2 text-success"></i>
          Backup Successful
        </h5>
        <button class="btn-close" @click="closeSuccess" aria-label="Close"></button>
      </div>

      <div class="modal-b">
        <p class="mb-2">Successfully backed up. You can download the <strong>encrypted</strong> and <strong>decrypted</strong> files below.</p>

        <ul class="created-list" v-if="createdFiles.length">
          <li v-for="(fname, idx) in createdFiles" :key="fname" class="created-item">
            <div class="left">
              <i class="bi bi-file-zip me-2"></i>
              <span class="fname" :title="fname">{{ fname }}</span>
            </div>
            <div class="right">
              <span class="badge type" :class="idx === 0 ? 'enc' : 'dec'">{{ idx === 0 ? 'Encrypted' : 'Decrypted' }}</span>
              <a :href="route('settings.backup.download', fname)" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-download me-1"></i> Download
              </a>
            </div>
          </li>
        </ul>

        <div v-else class="text-muted small">No file names returned by the server.</div>
      </div>

      <div class="modal-f">
        <button class="btn btn-primary" @click="viewInList">
          <i class="bi bi-collection me-1"></i> View in Archives
        </button>
        <button class="btn btn-light" @click="closeSuccess">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import Sidebar from '@/Components/Sidebar.vue'

defineProps({
  // we ignore server-provided archives on first paint and fetch paginated data instead
  archives: { type: Array, default: () => [] },
})

const activeTab = ref('backup') // open Backup by default since you're testing
const loading = ref(false)

const form = ref({
  school_name: 'Rizal Central School',
  language: 'en',
  new_password: '',
  confirm_password: '',
})

const archives = ref([])

// pagination state (10 per page as requested)
const pagination = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})

// success modal state
const showSuccess = ref(false)
const createdFiles = ref([]) // array of filenames from server: [encrypted, decrypted]

// helpers
const mb = (bytes) => (bytes / 1024 / 1024).toFixed(2)

// actions
const save = (section) => {
  alert(`Saving ${section} (wire this to your controller when ready)`)
}

const confirmAndRun = async () => {
  if (!confirm('Run a new backup now?')) return
  try {
    loading.value = true
    const res = await fetch(route('settings.backup.run'), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      credentials: 'same-origin',
    })
    if (!res.ok) {
      const t = await res.text()
      console.error('runBackup failed', res.status, t)
      alert(`Backup failed (HTTP ${res.status}).`)
      return
    }

    const data = await res.json()
    // Expecting: { ok: true, created: [encName, decName], message: '...' }
    createdFiles.value = Array.isArray(data.created) ? data.created.filter(Boolean) : []
    showSuccess.value = true

    // give the server a brief moment to finish writing the zips, then refresh the list
    setTimeout(() => refreshArchives(pagination.value.current_page), 1500)
  } catch (e) {
    console.error(e)
    alert('Backup failed.')
  } finally {
    loading.value = false
  }
}

const refreshArchives = async (page = 1) => {
  try {
    const url = new URL(route('settings.backup.archives'))
    url.searchParams.set('page', page)
    url.searchParams.set('perPage', pagination.value.per_page)

    const res = await fetch(url, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    })
    if (!res.ok) {
      const text = await res.text()
      console.error('Archives fetch failed', res.status, text)
      alert(`Unable to refresh archives (HTTP ${res.status}).`)
      return
    }
    const data = await res.json()
    archives.value = Array.isArray(data.data) ? data.data : []
    pagination.value.current_page = data.current_page || 1
    pagination.value.per_page = data.per_page || 10
    pagination.value.total = data.total || 0
    pagination.value.last_page = data.last_page || 1
  } catch (e) {
    console.error(e)
    alert('Unable to refresh archives.')
  }
}

const goToPage = (p) => {
  if (p < 1 || p > pagination.value.last_page) return
  refreshArchives(p)
}

const closeSuccess = () => {
  showSuccess.value = false
}

const viewInList = () => {
  // Ensure we're on the Backup tab and refresh the first page so users can see the new files
  activeTab.value = 'backup'
  refreshArchives(1)
  closeSuccess()
}

// keep list in sync when opening the Backup tab
watch(activeTab, (tab) => {
  if (tab === 'backup') refreshArchives(pagination.value.current_page)
})

onMounted(() => {
  if (activeTab.value === 'backup') refreshArchives(1)
})
</script>

<style scoped>
/* Layout aligned to fixed Sidebar (210px) */
.settings-layout {
  --bg: #f5f6fb;
  --card-bg: #fff;
  --text: #1f2430;
  --muted: #6b7280;
  --border: #e9ecf3;
  --brand: #12172b;
  --brand-50: rgba(18,23,43,.08);

  min-height: 100vh;
  display: flex;
  background: var(--bg);
  color: var(--text);
}

.content {
  flex: 1;
  padding: 24px;
  margin-left: 210px;
}

/* Header */
.page-header { margin-bottom: 14px; }
.title-wrap { display: grid; gap: 6px; }
.page-title {
  margin: 0;
  font-size: 1.45rem;
  font-weight: 700;
  letter-spacing: .2px;
}
.page-sub {
  margin: 0;
  color: var(--muted);
  font-size: .95rem;
}

/* Tabs */
.tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 10px 0 20px;
}
.tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text);
  padding: 10px 14px;
  border-radius: 10px;
  font-weight: 600;
  transition: .2s ease;
}
.tab.active {
  background: var(--brand);
  color: #fff;
  border-color: var(--brand);
}
.tab:hover { box-shadow: 0 2px 10px rgba(0,0,0,.04); }

/* Card */
.card {
  border: 1px solid var(--border);
  background: var(--card-bg);
  border-radius: 14px;
  overflow: hidden;
  margin-bottom: 16px;
  box-shadow: 0 2px 24px rgba(0,0,0,.03);
}
.card-h,
.card-f {
  display: flex;
  align-items: start;
  justify-content: space-between;
  padding: 16px 18px;
  gap: 16px;
  background: #fff;
}
.card-h { border-bottom: 1px solid var(--border); }
.card-f { border-top: 1px solid var(--border); }
.card-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
}
.card-sub {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: .92rem;
}
.inline-help {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  background: var(--brand-50);
  border-radius: 10px;
  padding: 8px 12px;
}
.card-b { padding: 18px; }

/* Backup CTA */
.backup-cta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  border: 1px dashed var(--border);
  border-radius: 12px;
  background: #fafbff;
}
.cta-copy h6 {
  margin: 0;
  font-weight: 700;
}

/* Archives */
.archive-list {
  display: grid;
  gap: 10px;
  padding: 0;
  margin: 0;
  list-style: none;
}
.archive-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: #fff;
}
.archive-item .meta {
  display: flex;
  align-items: center;
  gap: 12px;
}
.archive-item .ic {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: #f1f3fa;
  color: var(--brand);
  font-size: 1.05rem;
}
.archive-item .txt .name {
  font-weight: 700;
  max-width: 52vw;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.archive-item .txt .sub {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: .9rem;
}
.archive-item .txt .sub .dot { opacity: .6; }
.archive-item .actions { display: flex; gap: 8px; }

/* Empty */
.empty {
  display: grid;
  place-items: center;
  gap: 8px;
  padding: 40px 0;
  text-align: center;
  color: var(--muted);
}
.empty-ic {
  width: 64px;
  height: 64px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  background: #f1f3fa;
  color: var(--brand);
  font-size: 1.4rem;
}
.empty-txt strong { color: var(--text); }

/* Pagination */
.pager {
  margin-top: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.pg-info {
  font-size: .9rem;
  color: var(--muted);
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(13, 18, 33, 0.45);
  display: grid;
  place-items: center;
  z-index: 9999;
  padding: 16px;
}
.modal-card {
  width: 100%;
  max-width: 680px;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 12px 40px rgba(0,0,0,.2);
  overflow: hidden;
  animation: pop .15s ease-out;
}
@keyframes pop {
  from { transform: translateY(8px); opacity: .6; }
  to { transform: translateY(0); opacity: 1; }
}
.modal-h, .modal-f {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border);
}
.modal-f { border-top: 1px solid var(--border); border-bottom: 0; }
.modal-b {
  padding: 16px;
}
.btn-close {
  border: 0;
  background: transparent;
  font-size: 1.2rem;
  line-height: 1;
}
.created-list {
  list-style: none;
  padding: 0;
  margin: 10px 0 0;
  display: grid;
  gap: 10px;
}
.created-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: 10px;
}
.created-item .left {
  display: flex;
  align-items: center;
  min-width: 0;
}
.created-item .fname {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 48vw;
}
.created-item .right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.badge.type {
  padding: 4px 8px;
  border-radius: 999px;
  font-size: .75rem;
  border: 1px solid var(--border);
  background: #f7f7fb;
}
.badge.type.enc { background: #eaf3ff; border-color: #cfe3ff; }
.badge.type.dec { background: #eaffea; border-color: #cfeccf; }

/* Responsive */
@media (max-width: 992px) {
  .archive-item .txt .name { max-width: 46vw; }
}
@media (max-width: 768px) {
  .content { margin-left: 0; padding: 16px; }
  .archive-item { align-items: start; }
  .archive-item .actions { margin-left: 52px; }
}
</style>
