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

            <!-- Direct GET to stream the ZIP -->
            <a
              :href="route('settings.backup.run_download')"
              class="btn btn-outline-primary"
              @click.prevent="confirmAndRun"
            >
              <i class="bi bi-cloud-arrow-down me-2"></i>
              Run Backup Now
            </a>
          </div>

          <hr class="my-4" />

          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">Backup Archives</h6>
            <button class="btn btn-sm btn-light" @click="refreshArchives">
              <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
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
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from '@/Components/Sidebar.vue'

const props = defineProps({
  archives: { type: Array, default: () => [] },
})

const activeTab = ref('general')

const form = ref({
  school_name: 'Rizal Central School',
  language: 'en',
  new_password: '',
  confirm_password: '',
})

const archives = ref(props.archives || [])

// helpers
const mb = (bytes) => (bytes / 1024 / 1024).toFixed(2)

// actions
const save = (section) => {
  alert(`Saving ${section} (wire this to your controller when ready)`)
}

const confirmAndRun = () => {
  if (confirm('Run a new backup now?')) {
    // normal navigation so the browser can accept the file stream
    window.location.href = route('settings.backup.run_download')
  }
}

const refreshArchives = async () => {
  try {
    const res = await fetch(route('settings.backup.archives'), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    const data = await res.json()
    archives.value = Array.isArray(data) ? data : []
  } catch (e) {
    alert('Unable to refresh archives.')
  }
}
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

.content { flex: 1; padding: 24px; margin-left: 210px; }

/* Header */
.page-header { margin-bottom: 14px; }
.title-wrap { display: grid; gap: 6px; }
.page-title { margin: 0; font-size: 1.45rem; font-weight: 700; letter-spacing: .2px; }
.page-sub { margin: 0; color: var(--muted); font-size: .95rem; }

/* Tabs */
.tabs { display: flex; gap: 8px; margin: 10px 0 20px; flex-wrap: wrap; }
.tab {
  display: inline-flex; align-items: center; gap: 8px;
  background: transparent; border: 1px solid var(--border);
  color: var(--text); padding: 10px 14px; border-radius: 10px;
  font-weight: 600; transition: .2s ease;
}
.tab.active { background: var(--brand); color: #fff; border-color: var(--brand); }
tab:hover { box-shadow: 0 2px 10px rgba(0,0,0,.04); }

/* Card */
.card { border: 1px solid var(--border); background: var(--card-bg); border-radius: 14px; overflow: hidden; margin-bottom: 16px; box-shadow: 0 2px 24px rgba(0,0,0,.03); }
.card-h, .card-f {
  display: flex; align-items: start; justify-content: space-between;
  padding: 16px 18px; gap: 16px; background: #fff;
}
.card-h { border-bottom: 1px solid var(--border); }
.card-f { border-top: 1px solid var(--border); }
.card-title { margin: 0; font-size: 1.05rem; font-weight: 700; }
.card-sub { margin: 4px 0 0; color: var(--muted); font-size: .92rem; }
.inline-help { display: flex; align-items: center; gap: 8px; color: var(--muted); background: var(--brand-50); border-radius: 10px; padding: 8px 12px; }
.card-b { padding: 18px; }

/* Backup CTA */
.backup-cta {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; padding: 14px 16px; border: 1px dashed var(--border);
  border-radius: 12px; background: #fafbff;
}
.cta-copy h6 { margin: 0; font-weight: 700; }

/* Archives */
.archive-list { display: grid; gap: 10px; padding: 0; margin: 0; list-style: none; }
.archive-item {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; padding: 12px 14px; border: 1px solid var(--border);
  border-radius: 12px; background: #fff;
}
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

/* Responsive */
@media (max-width: 992px) { .archive-item .txt .name { max-width: 46vw; } }
@media (max-width: 768px) {
  .content { margin-left: 0; padding: 16px; }
  .archive-item { align-items: start; }
  .archive-item .actions { margin-left: 52px; }
}
</style>
