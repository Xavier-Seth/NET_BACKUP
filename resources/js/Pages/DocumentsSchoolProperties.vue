<template>
  <div class="flex bg-gray-100 min-h-screen">
    <Sidebar />
    <div class="flex-1 bg-gray-100 overflow-y-auto ml-[210px] p-6">
      <h1 class="page-title">School Property Documents</h1>

      <!-- Filters -->
      <div class="filters mb-6">
        <div>
          <label class="filter-label">Category</label>
          <select v-model="filters.category" @change="applyFilters" class="filter-input">
            <option value="">All</option>
            <option v-for="category in categories" :key="category.id" :value="category.name">
              {{ category.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="filter-label">Search</label>
          <input
            type="text"
            v-model="filters.search"
            @keyup.enter="applyFilters"
            placeholder="Document No or File Name"
            class="filter-input"
          />
        </div>
      </div>

      <!-- Table -->
      <div class="table-container bg-white rounded shadow">
        <table class="property-docs-table">
          <thead>
            <tr>
              <th>Document No</th>
              <th class="truncate-header">Category</th>
              <th>Issued Date</th>
              <th>Uploaded By</th>
              <th class="truncate-header">File Name</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in documents.data" :key="doc.id">
              <td>{{ doc.document_no || 'N/A' }}</td>
              <td class="truncate-cell" :title="doc.category?.name">{{ doc.category?.name || 'N/A' }}</td>
              <td>{{ doc.issued_date || 'N/A' }}</td>
              <td>{{ doc.user?.name || 'N/A' }}</td>
              <td class="truncate-cell" :title="doc.name">{{ doc.name }}</td>
              <td>
                <button class="btn-action view" @click="previewFile(doc)">
                  <i class="bi bi-eye"></i>
                </button>
                <a :href="`/documents/school-properties/${doc.id}/download`" class="btn-action download">
                  <i class="bi bi-download"></i>
                </a>
                <button class="btn-action delete" @click="confirmDelete(doc)">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination mt-6" v-if="documents.links.length > 3">
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
          <iframe :src="previewUrl" frameborder="0" class="preview-frame"></iframe>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  documents: Object,
  categories: Array,
  filters: Object,
});

const filters = ref({ ...props.filters });
const previewUrl = ref(null);

const applyFilters = () => {
  router.get("/documents/school-properties", filters.value, {
    preserveState: true,
    replace: true,
  });
};

const goToPage = (url) => {
  if (url) router.visit(url);
};

function previewFile(doc) {
  if (!doc.pdf_preview_path) {
    alert("No preview file available.");
    return;
  }
  previewUrl.value = `/storage/${doc.pdf_preview_path}`;
  document.body.style.overflow = "hidden";
}

function closePreview() {
  previewUrl.value = null;
  document.body.style.overflow = "";
}

function confirmDelete(doc) {
  if (confirm(`Are you sure you want to delete "${doc.name}"?`)) {
    router.delete(`/documents/school-properties/${doc.id}`, {
      replace: true, // ✅ Refresh data after delete
      preserveScroll: true,
      onSuccess: () => {
        previewUrl.value = null;
        document.body.style.overflow = "";
      },
      onError: () => {
        alert("Failed to delete the document.");
      },
    });
  }
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
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.truncate-header {
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

.btn-action.delete i {
  color: #dc3545;
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
