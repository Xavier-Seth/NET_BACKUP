<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const { props } = usePage();
const documents = ref(props.documents || []);
const categories = ref(props.categories || []);
const teachers = ref(props.teachers || []);

const isSidebarVisible = ref(true);
const toggleSidebar = () => (isSidebarVisible.value = !isSidebarVisible.value);

/* ---------------- Filters & Search (debounced) ---------------- */
const searchInput = ref("");
const searchQuery = ref("");
let debounceId = null;
watch(searchInput, (v) => {
  clearTimeout(debounceId);
  debounceId = setTimeout(() => (searchQuery.value = v.trim()), 250);
});
onBeforeUnmount(() => clearTimeout(debounceId));

const categoryFilter = ref("");
const teacherFilter = ref("");
const showTeacherFilter = computed(() => {
  // Show the teacher filter when a category is chosen OR always show (your call)
  return true;
});
const clearFilters = () => {
  searchInput.value = "";
  searchQuery.value = "";
  categoryFilter.value = "";
  teacherFilter.value = "";
  currentPage.value = 1;
};

/* ---------------- Sorting ---------------- */
const sortBy = ref("created_at");
const sortDir = ref("desc");
const setSort = (key) => {
  if (sortBy.value === key) {
    sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
  } else {
    sortBy.value = key;
    sortDir.value = "asc";
  }
};

/* ---------------- Pagination ---------------- */
const perPageOptions = [10, 20, 50, 100];
const perPage = ref(20);
const currentPage = ref(1);

const filteredDocuments = computed(() => {
  const q = (searchQuery.value || "").toLowerCase();
  return documents.value.filter((doc) => {
    const name = (doc.name || "").toLowerCase();
    const category = doc.category?.name || "";
    const teacher = doc.teacher?.full_name || "";
    const matchesSearch = !q || name.includes(q);
    const matchesCategory = !categoryFilter.value || category === categoryFilter.value;
    const matchesTeacher = !teacherFilter.value || teacher === teacherFilter.value;
    return matchesSearch && matchesCategory && matchesTeacher;
  });
});

const sortedDocuments = computed(() => {
  const key = sortBy.value;
  const dir = sortDir.value === "asc" ? 1 : -1;
  return [...filteredDocuments.value].sort((a, b) => {
    const av =
      key === "category" ? (a.category?.name || "") :
      key === "teacher" ? (a.teacher?.full_name || "") :
      a[key];
    const bv =
      key === "category" ? (b.category?.name || "") :
      key === "teacher" ? (b.teacher?.full_name || "") :
      b[key];
    if (av == null && bv == null) return 0;
    if (av == null) return -1 * dir;
    if (bv == null) return  1 * dir;
    if (typeof av === "string" && typeof bv === "string") return av.localeCompare(bv) * dir;
    return (av > bv ? 1 : av < bv ? -1 : 0) * dir;
  });
});

const totalItems = computed(() => sortedDocuments.value.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / perPage.value)));
watch([perPage, filteredDocuments], () => { currentPage.value = 1; });

const paginatedDocuments = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return sortedDocuments.value.slice(start, start + perPage.value);
});

const rangeText = computed(() => {
  if (!totalItems.value) return "0 of 0";
  const start = (currentPage.value - 1) * perPage.value + 1;
  const end = Math.min(currentPage.value * perPage.value, totalItems.value);
  return `${start}–${end} of ${totalItems.value}`;
});

/* ---------------- Preview Modal ---------------- */
const previewUrl = ref(null);
const previewType = ref("pdf");

const previewDocument = (document) => {
  const extension = (document.name || "").split(".").pop()?.toLowerCase();
  if (["pdf", "jpg", "jpeg", "png"].includes(extension)) {
    previewType.value = extension === "pdf" ? "pdf" : "image";
    previewUrl.value = `/documents/${document.id}/preview`;
  } else if (["doc", "docx", "xls", "xlsx"].includes(extension)) {
    if (document.pdf_preview_path) {
      previewType.value = "pdf";
      previewUrl.value = `/storage/${document.pdf_preview_path}`;
    } else {
      alert("❗ No PDF preview available for this document.");
    }
  } else {
    alert("❗ Unsupported file format for preview.");
  }
};

const closePreview = () => {
  previewUrl.value = null;
  previewType.value = null;
};

watch(previewUrl, (val) => {
  document.body.style.overflow = val ? "hidden" : "";
});
const onBackdropClick = (e) => {
  if (e.target?.classList?.contains("preview-modal")) closePreview();
};
const onKey = (e) => { if (e.key === "Escape") closePreview(); };
onMounted(() => window.addEventListener("keydown", onKey));
onBeforeUnmount(() => window.removeEventListener("keydown", onKey));

/* ---------------- Formatters ---------------- */
const formatDate = (date) => new Date(date).toLocaleDateString();
const formatSize = (bytes) => {
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1048576) return (bytes / 1024).toFixed(2) + " KB";
  return (bytes / 1048576).toFixed(2) + " MB";
};

const goToUpload = () => router.get("/upload", {}, { preserveScroll: true });
</script>

<template>
  <div class="layout">
    <Sidebar v-if="isSidebarVisible" />
    <div class="content">
      <div class="documents-container">
        <!-- Top bar -->
        <div class="topbar">
          <div class="left">
            <h2 class="title">Documents</h2>
            <p class="subtext">
              <strong>{{ totalItems }}</strong> result<span v-if="totalItems !== 1">s</span>
              <span v-if="searchQuery"> for “{{ searchQuery }}”</span>
            </p>
          </div>
          <div class="right">
            <button class="btn subtle" @click="toggleSidebar">
              <i class="bi bi-layout-sidebar-inset"></i>
              <span class="hide-sm">Toggle Sidebar</span>
            </button>
            <button class="btn primary" @click="goToUpload">
              <i class="bi bi-upload me-1"></i> Upload
            </button>
          </div>
        </div>

        <!-- Controls -->
        <div class="controls">
          <div class="search">
            <i class="bi bi-search"></i>
            <input
              type="text"
              v-model="searchInput"
              class="search-input"
              placeholder="Search by document name…"
              aria-label="Search documents"
            />
            <button v-if="searchQuery" class="clear" @click="searchInput = ''" aria-label="Clear search">
              <i class="bi bi-x-circle"></i>
            </button>
          </div>

          <div class="filters">
            <div class="filter">
              <label>Category</label>
              <select v-model="categoryFilter" class="form-select">
                <option value="">All</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
              </select>
            </div>

            <div v-if="showTeacherFilter" class="filter">
              <label>Teacher</label>
              <select v-model="teacherFilter" class="form-select">
                <option value="">All</option>
                <option v-for="t in teachers" :key="t.id" :value="t.full_name">{{ t.full_name }}</option>
              </select>
            </div>

            <div class="filter compact">
              <label>Sort</label>
              <div class="sort-wrap">
                <select v-model="sortBy" class="form-select">
                  <option value="created_at">Uploaded</option>
                  <option value="name">Name</option>
                  <option value="teacher">Teacher</option>
                  <option value="category">Category</option>
                  <option value="size">Size</option>
                </select>
                <button class="btn sort-dir" @click="setSort(sortBy)">
                  <i :class="sortDir === 'asc' ? 'bi bi-sort-up' : 'bi bi-sort-down'"></i>
                </button>
              </div>
            </div>

            <div class="filter compact">
              <label>Rows</label>
              <select v-model.number="perPage" class="form-select">
                <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
              </select>
            </div>

            <button class="btn ghost" @click="clearFilters">
              <i class="bi bi-eraser me-1"></i> Reset
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="table-wrapper">
          <table class="documents-table">
            <thead>
              <tr>
                <th @click="setSort('id')"       :class="{ sortable: true, active: sortBy==='id' }">ID</th>
                <th @click="setSort('name')"     :class="{ sortable: true, active: sortBy==='name' }">Name</th>
                <th @click="setSort('teacher')"  :class="{ sortable: true, active: sortBy==='teacher' }">Teacher</th>
                <th @click="setSort('category')" :class="{ sortable: true, active: sortBy==='category' }">Category</th>
                <th @click="setSort('created_at')" :class="{ sortable: true, active: sortBy==='created_at' }">Uploaded</th>
                <th @click="setSort('size')"     :class="{ sortable: true, active: sortBy==='size' }">Size</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody v-if="paginatedDocuments.length">
              <tr v-for="document in paginatedDocuments" :key="document.id">
                <td>{{ document.id }}</td>
                <td class="truncate" :title="document.name">{{ document.name }}</td>
                <td>{{ document.teacher?.full_name ?? 'N/A' }}</td>
                <td>{{ document.category?.name ?? 'N/A' }}</td>
                <td>{{ formatDate(document.created_at) }}</td>
                <td>{{ formatSize(document.size) }}</td>
                <td class="action-buttons">
                  <button class="icon-btn view" @click="previewDocument(document)" title="Preview" aria-label="Preview">
                    <i class="bi bi-eye"></i>
                  </button>
                  <a
                    :href="`/documents/${document.id}/download`"
                    class="icon-btn download"
                    target="_blank"
                    title="Download"
                    aria-label="Download"
                  >
                    <i class="bi bi-download"></i>
                  </a>
                </td>
              </tr>
            </tbody>

            <tbody v-else>
              <tr>
                <td colspan="7" class="empty">
                  <i class="bi bi-folder-x fs-4 d-block mb-2"></i>
                  No documents found. Try changing filters or search terms.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-controls">
          <button class="pagination-btn" :disabled="currentPage === 1" @click="currentPage = 1" aria-label="First">
            «
          </button>
          <button class="pagination-btn" :disabled="currentPage === 1" @click="currentPage--" aria-label="Previous">
            ‹
          </button>

          <span class="range">{{ rangeText }}</span>

          <button class="pagination-btn" :disabled="currentPage === totalPages" @click="currentPage++" aria-label="Next">
            ›
          </button>
          <button class="pagination-btn" :disabled="currentPage === totalPages" @click="currentPage = totalPages" aria-label="Last">
            »
          </button>
        </div>
      </div>

      <!-- Preview Modal -->
      <div v-if="previewUrl" class="preview-modal" @click="onBackdropClick">
        <div class="preview-content" role="dialog" aria-modal="true" aria-label="Document Preview">
          <button class="close-preview" @click="closePreview" aria-label="Close preview">&times;</button>
          <iframe v-if="previewType === 'pdf'" :src="previewUrl" frameborder="0"></iframe>
          <img v-else :src="previewUrl" alt="Preview" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Layout */
.layout { display: flex; }
.content { flex: 1; margin-left: 200px; padding: 20px; }
@media (max-width: 992px) { .content { margin-left: 0; } }

/* Container */
.documents-container { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,.04); }

/* Top bar */
.topbar { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom: 10px; }
.title { margin:0; font-size:22px; font-weight:700; }
.subtext { margin:0; color:#6b7280; font-size:13px; }
.right { display:flex; gap:8px; }
.btn { display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; background:#fff; padding:8px 12px; border-radius:8px; cursor:pointer; }
.btn.primary { background:#0d6efd; color:#fff; border-color:#0d6efd; }
.btn.subtle { background:#f9fafb; }
.btn.ghost { background:#fff; }
.btn:disabled { opacity:.6; cursor:not-allowed; }
.hide-sm { display:inline; }
@media (max-width:600px){ .hide-sm { display:none; } }

/* Controls */
.controls { display:flex; flex-direction:column; gap:10px; margin-bottom: 12px; }
.search { display:flex; align-items:center; gap:8px; border:1px solid #e5e7eb; background:#fff; border-radius:10px; padding:6px 10px; }
.search i { opacity:.7; }
.search-input { border:none; outline:none; flex:1; padding:0; }
.search .clear { border:none; background:transparent; cursor:pointer; opacity:.7; }
.filters { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
.filter { display:flex; flex-direction:column; gap:6px; min-width:180px; }
.filter.compact { min-width:auto; }
.form-select { border:1px solid #e5e7eb; border-radius:8px; padding:8px; background:#fff; }

/* Table */
.table-wrapper { overflow:auto; max-height: calc(80vh - 160px); border:1px solid #eef0f3; border-radius:10px; }
.documents-table { width:100%; border-collapse:separate; border-spacing:0; }
.documents-table thead th {
  position: sticky; top: 0; z-index: 1;
  background:#0d0c37; color:#fff; padding:10px; text-align:left;
  border-bottom:1px solid #0b0a30; cursor: default;
}
.documents-table th.sortable { cursor:pointer; }
.documents-table th.active::after { content:" ↕"; font-weight:400; opacity:.8; }
.documents-table td { padding:10px; border-bottom:1px solid #f1f2f4; }
.truncate { max-width: 260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.empty { text-align:center; padding:24px; color:#6b7280; }

/* Actions */
.action-buttons { display:flex; gap:8px; }
.icon-btn { display:inline-flex; align-items:center; justify-content:center; background:transparent; border:1px solid #e5e7eb; border-radius:8px; padding:6px 8px; font-size:16px; cursor:pointer; transition:background .12s ease; }
.icon-btn:hover { background:#f9fafb; }
.icon-btn.view { color:#0d6efd; border-color:#d6e4ff; }
.icon-btn.download { color:#198754; border-color:#c9efdb; }

/* Pagination */
.pagination-controls { margin-top: 12px; display:flex; justify-content:center; align-items:center; gap:8px; }
.pagination-btn { padding:6px 10px; background:#fff; color:#0d6efd; border:1px solid #d6e4ff; border-radius:6px; cursor:pointer; }
.pagination-btn:disabled { opacity:.5; cursor:not-allowed; }
.range { font-size:13px; color:#6b7280; }

/* Preview Modal */
.preview-modal { position:fixed; inset:0; background:rgba(0,0,0,.7); display:flex; justify-content:center; align-items:center; padding:18px; }
.preview-content { background:#fff; width: min(1200px, 96vw); height: min(86vh, 900px); position:relative; border-radius:10px; overflow:hidden; }
.close-preview { position:absolute; top:10px; right:10px; background:#dc3545; border:none; color:#fff; padding:8px 12px; cursor:pointer; font-size:18px; border-radius:8px; z-index:2; }
iframe, img { width:100%; height:100%; border:none; object-fit:contain; }
</style>
