<template>
  <div class="flex bg-gray-100 min-h-screen">
    <Sidebar />
    <div class="flex-1 bg-gray-100 overflow-y-auto ml-[210px] p-6">
      <h1 class="page-title">Daily Time Records (DTR)</h1>

      <!-- Filters -->
      <div class="filters mb-6">
        <div>
          <label class="filter-label">Teacher</label>
          <select v-model="filters.teacher" @change="applyFilters" class="filter-input">
            <option value="">All</option>
            <option v-for="t in teachers" :key="t.id" :value="t.full_name">{{ t.full_name }}</option>
          </select>
        </div>

        <div>
          <label class="filter-label">Search</label>
          <input
            type="text"
            v-model="filters.search"
            @keyup.enter="applyFilters"
            placeholder="Search document name..."
            class="filter-input"
          />
        </div>
      </div>

      <!-- Table -->
      <div class="table-container bg-white rounded shadow">
        <table class="property-docs-table">
          <thead>
            <tr>
              <th>File Name</th>
              <th>Teacher</th>
              <th>Category</th>
              <th>Uploaded</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="documents?.data?.length === 0">
              <td colspan="5" class="text-center py-4 text-gray-500">No DTR documents found.</td>
            </tr>
            <tr v-for="doc in documents?.data" :key="doc.id">
              <td class="truncate-cell">{{ doc.name }}</td>
              <td>{{ doc.teacher?.full_name || 'N/A' }}</td>
              <td>{{ doc.category?.name || 'N/A' }}</td>
              <td>{{ formatDate(doc.created_at) }}</td>
              <td>
                <button class="btn-action view" @click="previewFile(doc)">
                  <i class="bi bi-eye"></i>
                </button>
                <a :href="`/documents/${doc.id}/download`" class="btn-action download" target="_blank">
                  <i class="bi bi-download"></i>
                </a>
                <button class="btn-action" @click="openEditModal(doc)">
                  <i class="bi bi-pencil"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination mt-6" v-if="documents?.links && documents.links.length > 3">
        <template v-for="link in documents.links" :key="link.url || link.label">
          <button
            v-if="link.url"
            @click="goToPage(link.url)"
            v-html="link.label"
            class="page-link"
            :class="{ active: link.active }"
          ></button>
          <span v-else class="page-link disabled"> ... </span>
        </template>
      </div>

      <!-- Preview Modal -->
      <div v-if="previewUrl" class="preview-modal">
        <div class="preview-content">
          <button class="close-preview" @click="closePreview">&times;</button>
          <iframe v-if="previewType === 'pdf'" :src="previewUrl" frameborder="0" class="preview-frame"></iframe>
          <img v-else :src="previewUrl" alt="Preview" />
        </div>
      </div>

      <!-- Edit Metadata Modal -->
      <div v-if="editingDoc" class="preview-modal">
        <div class="preview-content p-6">
          <h2 class="font-bold text-lg mb-4">Edit Document Metadata</h2>

          <label class="block font-semibold">Document Name</label>
          <input v-model="editForm.name" type="text" class="border rounded p-2 w-full mb-4" />

          <label class="block font-semibold">Teacher</label>
          <select v-model="editForm.teacher_id" class="border rounded p-2 w-full mb-4">
            <option value="">-- Select Teacher --</option>
            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.full_name }}</option>
          </select>

          <label class="block font-semibold">Category</label>
          <select v-model="editForm.category_id" class="border rounded p-2 w-full mb-4">
            <option value="">-- Select Category --</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>

          <div class="flex justify-end gap-2 mt-4">
            <button @click="submitEdit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
            <button @click="editingDoc = null" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { usePage, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  documents: Object,
  teachers: Array,
  filters: Object,
  categories: Array,
});

const filters = ref({ ...props.filters });
const previewUrl = ref(null);
const previewType = ref("pdf");

const editingDoc = ref(null);
const editForm = ref({ name: "", teacher_id: "", category_id: "" });

const applyFilters = () => {
  router.get("/documents/dtr", filters.value, {
    preserveState: true,
    replace: true,
  });
};

const goToPage = (url) => {
  if (url) router.visit(url);
};

function previewFile(doc) {
  const ext = doc.name.split(".").pop().toLowerCase();
  if (ext === "pdf") {
    previewUrl.value = `/documents/${doc.id}/preview`;
    previewType.value = "pdf";
  } else if (["jpg", "jpeg", "png"].includes(ext)) {
    previewUrl.value = `/documents/${doc.id}/preview`;
    previewType.value = "image";
  } else if (doc.pdf_preview_path) {
    previewUrl.value = `/storage/${doc.pdf_preview_path}`;
    previewType.value = "pdf";
  } else {
    alert("No preview available.");
  }
  document.body.style.overflow = "hidden";
}

function closePreview() {
  previewUrl.value = null;
  document.body.style.overflow = "";
}

function formatDate(date) {
  return new Date(date).toLocaleDateString();
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
  router.patch(`/documents/${editingDoc.value.id}/update-metadata`, {
    teacher_id: editForm.value.teacher_id,
    category_id: editForm.value.category_id,
    name: editForm.value.name,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      editingDoc.value = null;
    },
  });
}
</script>

<style scoped>
.page-title {
  font-size: 26px;
  font-weight: bold;
  margin-bottom: 20px;
}

.filters {
  display: flex;
  gap: 25px;
  margin-bottom: 25px;
}

.filter-label {
  display: block;
  font-weight: bold;
  margin-bottom: 6px;
}

.filter-input {
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.table-container {
  overflow-x: auto;
  padding: 15px;
}

.property-docs-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.property-docs-table th,
.property-docs-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #eee;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.truncate-cell {
  max-width: 180px;
}

.property-docs-table thead th {
  background: #19184f;
  color: white;
}

.btn-action {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
  margin-right: 6px;
}

.btn-action.view i {
  color: #0d6efd;
}
.btn-action.download i {
  color: #198754;
}

.btn-action:hover i {
  opacity: 0.8;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 5px;
}

.page-link {
  padding: 6px 12px;
  border: 1px solid #ccc;
  border-radius: 6px;
  background: white;
  cursor: pointer;
}
.page-link.active {
  background: #19184f;
  color: white;
}
.page-link.disabled {
  background: #eee;
  color: #aaa;
  cursor: default;
}

.preview-modal {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 999;
  display: flex;
  justify-content: center;
  align-items: center;
}
.preview-content {
  background: white;
  width: 90vw;
  height: 85vh;
  max-width: 900px;
  border-radius: 10px;
  position: relative;
  display: flex;
  flex-direction: column;
}
.preview-frame {
  flex: 1;
  width: 100%;
  border-radius: 0 0 10px 10px;
}
.close-preview {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #dc3545;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 18px;
  cursor: pointer;
}
</style>
