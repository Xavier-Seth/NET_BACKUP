<template>
  <div class="layout">
    <Sidebar v-if="isSidebarVisible" />
    <div class="content">
      <div class="documents-container">
        <div class="controls d-flex align-items-center">
          <!-- Category filter -->
          <div class="category-filter">
            <label for="category" class="me-2">Category</label>
            <select id="category" v-model="categoryFilter" class="form-select">
              <option value="">All</option>
              <option v-for="c in categoriesFiltered" :key="c.id" :value="String(c.id)">
                {{ c.name }}
              </option>
            </select>
          </div>

          <!-- Teacher filter -->
          <div class="teacher-filter ms-3">
            <label for="teacher" class="me-2">Teacher</label>
            <select id="teacher" v-model="teacherFilter" class="form-select">
              <option value="">All</option>
              <option v-for="t in teachers" :key="t.id" :value="String(t.id)">
                {{ t.full_name }}
              </option>
            </select>
          </div>

          <!-- Search -->
          <input
            v-model="searchQuery"
            type="text"
            class="search-bar ms-auto"
            placeholder="Search by document name..."
          />
        </div>

        <!-- Table Section -->
        <div v-if="filteredDocuments.length === 0" class="text-center text-muted py-5 fs-6">
          <i class="bi bi-info-circle"></i> No documents found.
        </div>

        <div v-else class="table-wrapper">
          <table class="documents-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Teacher</th>
                <th>Type</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>

          <div class="scrollable-body">
            <table class="documents-table">
              <tbody>
                <tr v-for="document in paginatedDocuments" :key="document.id">
                  <td class="truncate-cell" data-label="Name">{{ document.name }}</td>
                  <td data-label="Teacher">{{ document.teacher?.full_name ?? "N/A" }}</td>
                  <td class="truncate-cell" data-label="Type">{{ document.category?.name ?? "N/A" }}</td>
                  <td data-label="Date">{{ formatDate(document.created_at) }}</td>
                  <td class="action-buttons" data-label="Actions">
                    <button @click="previewDocument(document)" class="icon-btn view" title="Preview">
                      <i class="bi bi-eye"></i>
                    </button>

                    <a
                      :href="`/documents/${document.id}/download`"
                      class="icon-btn download"
                      target="_blank"
                      title="Download"
                    >
                      <i class="bi bi-download"></i>
                    </a>

                    <button @click="openEditModal(document)" class="icon-btn edit" title="Edit Metadata">
                      <i class="bi bi-pencil"></i>
                    </button>

                    <button @click="openDeleteModal(document)" class="icon-btn delete" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="pagination-info">
            Showing {{ paginationRange.start }} to {{ paginationRange.end }} of
            {{ paginationRange.total }} entries
          </div>
          <div class="pagination-container">
            <button @click="currentPage--" :disabled="currentPage === 1" class="pagination-btn">
              Previous
            </button>
            <span class="pagination-text">Page {{ currentPage }} of {{ totalPages }}</span>
            <button
              @click="currentPage++"
              :disabled="currentPage === totalPages"
              class="pagination-btn"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="previewUrl" class="preview-modal" @click.self="closePreview">
      <div class="preview-content">
        <button class="close-preview" @click="closePreview">&times;</button>

        <template v-if="previewType === 'image'">
          <img :src="previewUrl" class="preview-img" alt="Preview image" />
        </template>
        <template v-else>
          <iframe :src="previewUrl" class="preview-frame" frameborder="0"></iframe>
        </template>
      </div>
    </div>

    <!-- Edit Metadata Modal -->
    <div v-if="editingDoc" class="preview-modal" @click.self="editingDoc = null">
      <div class="preview-content p-6 edit-modal">
        <h2 class="font-bold text-lg mb-4">Edit Document Metadata</h2>

        <label class="block font-semibold mb-1">Document Name</label>
        <input v-model="editForm.name" type="text" class="border rounded p-2 w-full mb-4" />

        <label class="block font-semibold mb-1">Teacher</label>
        <select v-model="editForm.teacher_id" class="border rounded p-2 w-full mb-4">
          <option value="">-- Select Teacher --</option>
          <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.full_name }}</option>
        </select>

        <label class="block font-semibold mb-1">Category</label>
        <select v-model="editForm.category_id" class="border rounded p-2 w-full mb-4">
          <option value="">-- Select Category --</option>
          <option v-for="c in categoriesFiltered" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>

        <div class="flex justify-end w-full mt-4">
          <button
            @click="submitEdit"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mr-2"
          >
            Save
          </button>
          <button @click="editingDoc = null" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deleteTarget" class="preview-modal" @click.self="cancelDelete">
      <div class="confirm-modal">
        <h3 class="text-lg font-bold mb-2">Delete Document</h3>
        <p class="text-sm text-gray-700">
          Are you sure you want to delete
          <span class="font-semibold">"{{ deleteTarget.name }}"</span>? This action cannot be undone.
        </p>

        <div class="flex justify-end gap-2 mt-5">
          <button
            @click="confirmDelete"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded"
          >
            Delete
          </button>
          <button
            @click="cancelDelete"
            class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const documents = computed(() => usePage().props.documents);
const teachers = computed(() => usePage().props.teachers);
const categories = computed(() => usePage().props.categories);

const currentPage = ref(1);
const searchQuery = ref("");
const teacherFilter = ref("");
const categoryFilter = ref("");
const previewUrl = ref(null);
const previewType = ref("pdf");
const ENTRIES_PER_PAGE = 20;
const isSidebarVisible = ref(true);

const editingDoc = ref(null);
const editForm = ref({ name: "", teacher_id: "", category_id: "" });
const deleteTarget = ref(null);

// ===== Excluded category NAMES (case-insensitive) =====
// combine your DTR/ICS/RIS exclusions with the SAL-N / Service credit / IPCRF / NOSI / NOSA / Travel order set
const EXCLUDED_CATEGORY_NAMES = [
  "Daily Time Record",
  "DTR",
  "DTR Reports",
  "ICS",
  "RIS",

  // additional ones to hide
  "SAL-N",
  "Service credit ledgers",
  "IPCRF",
  "IPCRF (Individual chuchu)",
  "NOSI",
  "NOSA",
  "Travel order"
];

// normalize and make a Set for fast lookups (case-insensitive)
const EXCLUDED_SET = new Set(EXCLUDED_CATEGORY_NAMES.map(n => String(n || "").trim().toLowerCase()));

// Filter categories to remove excluded ones (for dropdown + modal)
const categoriesFiltered = computed(() =>
  (categories.value || []).filter((c) => {
    const name = String(c.name || "").trim().toLowerCase();
    return !EXCLUDED_SET.has(name);
  })
);

function previewDocument(doc) {
  const ext = (doc.name || "").split(".").pop()?.toLowerCase();
  if (ext === "pdf") {
    previewType.value = "pdf";
    previewUrl.value = `/documents/${doc.id}/preview`;
  } else if (["jpg", "jpeg", "png", "gif", "webp", "bmp"].includes(ext)) {
    previewType.value = "image";
    previewUrl.value = doc.pdf_preview_path
      ? `/storage/${doc.pdf_preview_path}`
      : `/documents/${doc.id}/preview`;
  } else if (["doc", "docx", "xls", "xlsx"].includes(ext)) {
    if (doc.pdf_preview_path) {
      previewType.value = "pdf";
      previewUrl.value = `/storage/${doc.pdf_preview_path}`;
    } else {
      return;
    }
  }
}

function closePreview() {
  previewUrl.value = null;
  previewType.value = null;
}

function openDeleteModal(doc) {
  deleteTarget.value = doc;
  document.body.style.overflow = "hidden";
}

function cancelDelete() {
  deleteTarget.value = null;
  document.body.style.overflow = "";
}

function confirmDelete() {
  if (!deleteTarget.value) return;
  router.delete(`/documents/${deleteTarget.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      deleteTarget.value = null;
      document.body.style.overflow = "";
      router.reload({ only: ["documents"], preserveScroll: true, preserveState: true });
    },
    onError: () => {
      deleteTarget.value = null;
      document.body.style.overflow = "";
    },
    onFinish: () => {
      document.body.style.overflow = "";
    },
  });
}

function openEditModal(doc) {
  editingDoc.value = doc;
  editForm.value = {
    name: doc.name || "",
    teacher_id: doc.teacher_id || "",
    category_id: doc.category_id || "",
  };
}

function submitEdit() {
  if (!editingDoc.value) return;
  router.patch(
    `/documents/${editingDoc.value.id}/update-metadata`,
    {
      name: editForm.value.name,
      teacher_id: editForm.value.teacher_id,
      category_id: editForm.value.category_id,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        editingDoc.value = null;
        router.reload({ only: ["documents"], preserveScroll: true, preserveState: true });
      },
    }
  );
}

watch(previewUrl, (val) => {
  document.body.style.overflow = val ? "hidden" : "";
});
watch([searchQuery, teacherFilter, categoryFilter], () => (currentPage.value = 1));

const filteredDocuments = computed(() => {
  const q = searchQuery.value.toLowerCase().trim();
  const teacherId = teacherFilter.value;
  const categoryId = categoryFilter.value;

  return (documents.value || []).filter((doc) => {
    const name = (doc.name || "").toLowerCase();
    const docTeacherId = String(doc.teacher_id ?? "");
    const docCategoryId = String(doc.category_id ?? "");
    const docCategoryName = String(doc.category?.name ?? "").trim().toLowerCase();

    // Hide excluded categories (case-insensitive)
    if (EXCLUDED_SET.has(docCategoryName)) return false;

    const matchesSearch = !q || name.includes(q);
    const matchesTeacher = !teacherId || docTeacherId === teacherId;
    const matchesCategory = !categoryId || docCategoryId === categoryId;

    return matchesSearch && matchesTeacher && matchesCategory;
  });
});

const paginatedDocuments = computed(() => {
  const start = (currentPage.value - 1) * ENTRIES_PER_PAGE;
  return filteredDocuments.value.slice(start, start + ENTRIES_PER_PAGE);
});

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredDocuments.value.length / ENTRIES_PER_PAGE))
);

const paginationRange = computed(() => {
  const total = filteredDocuments.value.length;
  if (!total) return { start: 0, end: 0, total };
  const start = (currentPage.value - 1) * ENTRIES_PER_PAGE + 1;
  const end = Math.min(start + ENTRIES_PER_PAGE - 1, total);
  return { start, end, total };
});

function formatDate(d) {
  const dt = new Date(d);
  return isNaN(dt) ? "-" : dt.toLocaleDateString();
}
</script>


<style scoped>
/* ===== Layout ===== */
html,
body {
  background: white;
  height: 100%;
  margin: 0;
  overflow: hidden;
  font-family: system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans",
    "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
}

.layout {
  display: flex;
  flex-direction: row;
  height: 100vh;
  overflow: hidden;
}

.content {
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  flex: 1;
  margin-left: 200px;
  max-width: calc(100% - 200px);
  overflow: hidden;
  padding: 20px;
}

/* ===== Container ===== */
.documents-container {
  background: white;
  display: flex;
  flex: 1;
  flex-direction: column;
  overflow: hidden;
}

/* ===== Controls (filters + search) ===== */
.controls {
  align-items: center;
  background: white;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 15px;
  position: sticky;
  top: 0;
  z-index: 2;
}

/* ===== Inputs ===== */
.form-select {
  appearance: none;
  background-color: white;
  background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg width='14' height='8' viewBox='0 0 14 8' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l6 6 6-6' stroke='%23666' stroke-width='2' fill='none'/%3E%3C/svg%3E");
  background-position: right 10px center;
  background-repeat: no-repeat;
  border: 1px solid #707070;
  border-radius: 5px;
  padding: 6px 30px 6px 10px;
  width: 200px;
}

.search-bar {
  border: 2px solid #707070;
  border-radius: 8px;
  padding: 8px;
  width: 250px;
}

.search-bar:focus {
  border-color: #007bff;
  box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
  outline: none;
}

/* ===== Table ===== */
.table-wrapper {
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  flex: 1;
  flex-direction: column;
  overflow: hidden;
  padding: 0;
  width: 100%;
}

.documents-table {
  border-collapse: separate;
  border-spacing: 0;
  table-layout: fixed;
  width: 100%;
}

.documents-table thead {
  background: #0d0c37;
  color: white;
}

.documents-table tbody tr:nth-child(even) {
  background-color: #f9f9f9;
}

.documents-table th,
.documents-table td {
  border-bottom: 1px solid #ddd;
  font-size: 13px;
  line-height: 1.4;
  padding: 6px 8px;
  text-align: left;
  word-wrap: break-word;
}

.truncate-cell {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.scrollable-body {
  max-height: 600px;
  overflow-x: auto;
  overflow-y: auto;
}

/* ===== Pagination ===== */
.pagination-info {
  color: #555;
  font-size: 14px;
  padding: 10px 0;
  text-align: center;
}

.pagination-container {
  align-items: center;
  background-color: white;
  border-radius: 0 0 10px 10px;
  border-top: 1px solid #eee;
  display: flex;
  gap: 10px;
  justify-content: center;
  padding: 10px 0;
}

.pagination-btn {
  background: #007bff;
  border: none;
  border-radius: 5px;
  color: white;
  cursor: pointer;
  padding: 8px 12px;
}

.pagination-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.pagination-text {
  font-size: 14px;
  font-weight: bold;
}

/* ===== Row Actions (consistent colors) ===== */
.action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
  margin-right: 6px;
  padding: 2px;
}

/* View = Blue */
.icon-btn.view i {
  color: #0d6efd;
}
.icon-btn.view:hover i {
  color: #0a58ca;
}

/* Edit = Blue (same as view) */
.icon-btn.edit i {
  color: #0d6efd;
}
.icon-btn.edit:hover i {
  color: #0a58ca;
}

/* Download = Green */
.icon-btn.download i {
  color: #198754;
}
.icon-btn.download:hover i {
  color: #146c43;
}

/* Delete = Red */
.icon-btn.delete i {
  color: #dc3545;
}
.icon-btn.delete:hover i {
  color: #bb2d3b;
}

/* ===== Modals ===== */
.preview-modal {
  align-items: center;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  inset: 0;
  justify-content: center;
  position: fixed;
  z-index: 9999;
}

.preview-content {
  background: white;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  height: 85vh;
  max-width: 900px;
  overflow: hidden;
  position: relative;
  width: 90vw;
}

.preview-frame {
  border: none;
  height: 100%;
  width: 100%;
}

.close-preview {
  background: #dc3545;
  border: none;
  border-radius: 6px;
  color: white;
  cursor: pointer;
  font-size: 18px;
  padding: 6px 12px;
  position: absolute;
  right: 10px;
  top: 10px;
}

/* ===== Edit Modal ===== */
.edit-modal {
  align-items: flex-start;
  display: flex;
  flex-direction: column;
  height: auto;
  justify-content: flex-start;
  max-width: 900px;
  width: 90vw;
}

/* ===== Delete Confirmation Card ===== */
.confirm-modal {
  background: white;
  width: 92vw;
  max-width: 520px;
  border-radius: 10px;
  padding: 22px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .layout {
    flex-direction: column;
    height: auto;
  }

  .content {
    margin-left: 0;
    max-width: 100%;
    padding: 10px;
  }

  .form-select,
  .search-bar {
    margin-top: 8px;
    width: 100% !important;
  }

  .documents-table,
  .documents-table thead,
  .documents-table tbody,
  .documents-table th,
  .documents-table td,
  .documents-table tr {
    display: block;
  }

  .documents-table thead {
    display: none;
  }

  .documents-table td {
    border: none;
    border-bottom: 1px solid #eee;
    padding-left: 50%;
    position: relative;
    white-space: normal;
  }

  .documents-table td::before {
    color: #333;
    content: attr(data-label);
    font-weight: bold;
    left: 8px;
    padding-right: 10px;
    position: absolute;
    top: 6px;
    width: 45%;
    white-space: nowrap;
  }

  .pagination-container {
    flex-direction: column;
    gap: 5px;
  }

  .pagination-btn {
    width: 100%;
  }

  .preview-content {
    height: 80%;
    padding: 10px;
  }
}
</style>
