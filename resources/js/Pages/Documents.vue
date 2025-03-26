<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { ref, computed, watch } from "vue";
import { Eye } from "lucide-vue-next";
import { router, usePage } from "@inertiajs/vue3";

const { props } = usePage();
const documents = ref(props.documents);

const entries = ref(5);
const currentPage = ref(1);
const searchQuery = ref("");
const categoryFilter = ref("");

const previewUrl = ref(null);
const previewType = ref("pdf");

function previewDocument(document) {
  const extension = document.name.split(".").pop().toLowerCase();

  if (extension === "pdf") {
    previewType.value = "pdf";
    previewUrl.value = `/storage/${document.path}`;
  } else if (["docx", "xlsx", "xls"].includes(extension)) {
    previewType.value = "pdf";
    previewUrl.value = `/storage/${document.pdf_preview_path}`;
  } else {
    previewType.value = "image";
    previewUrl.value = `/storage/${document.path}`;
  }
}

function closePreview() {
  previewUrl.value = null;
  previewType.value = null;
}

watch([entries, searchQuery, categoryFilter], () => {
  currentPage.value = 1;
});

const filteredDocuments = computed(() => {
  return documents.value.filter((doc) => {
    const matchesLRN = doc.lrn.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesCategory = categoryFilter.value === '' || doc.category === categoryFilter.value;
    return matchesLRN && matchesCategory;
  });
});

const paginatedDocuments = computed(() => {
  const start = (currentPage.value - 1) * entries.value;
  const end = start + parseInt(entries.value);
  return filteredDocuments.value.slice(start, end);
});

const totalPages = computed(() =>
  Math.ceil(filteredDocuments.value.length / entries.value)
);

function formatDate(date) {
  return new Date(date).toLocaleDateString();
}

function formatSize(bytes) {
  if (bytes < 1024) return bytes + " B";
  else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + " KB";
  else return (bytes / 1048576).toFixed(2) + " MB";
}

const goToUpload = () => router.get("/upload");
</script>

<template>
  <div class="layout">
    <Sidebar />
    <div class="content">
      <div class="documents-container">
        <div class="controls d-flex align-items-center">
          <button class="btn upload-btn" @click="goToUpload">Upload</button>

          <div class="entries-dropdown ms-3">
            <label for="entries">Show</label>
            <select id="entries" v-model="entries" class="form-select">
              <option value="5">5</option>
              <option value="10">10</option>
              <option value="25">25</option>
            </select>
            <span>Entries</span>
          </div>

          <div class="category-filter ms-3">
            <label for="category">Category</label>
            <select id="category" v-model="categoryFilter" class="form-select">
              <option value="">All</option>
              <option value="Form 137">Form 137</option>
              <option value="PSA">PSA</option>
              <option value="ECCRD">ECCRD</option>
            </select>
          </div>

          <input type="text" v-model="searchQuery" class="search-bar ms-auto" placeholder="Search by LRN..." />
        </div>

        <div class="table-wrapper">
          <table class="documents-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>LRN</th>
                <th>Date Created</th>
                <th>Size</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>

          <div class="scrollable-body">
            <table class="documents-table">
              <tbody>
                <tr v-for="document in paginatedDocuments" :key="document.id">
                  <td>{{ document.id }}</td>
                  <td :title="document.name" class="truncate-cell">{{ document.name }}</td>
                  <td>{{ document.category }}</td>
                  <td>{{ document.lrn }}</td>
                  <td>{{ formatDate(document.created_at) }}</td>
                  <td>{{ formatSize(document.size) }}</td>
                  <td class="action-buttons">
                    <button @click="previewDocument(document)" class="btn btn-xs btn-primary">
                      <Eye size="12" /> Preview
                    </button>
                    <a :href="`/storage/${document.path}`" :download="document.name" target="_blank" class="btn btn-xs btn-success">
                      ⬇ Download
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="pagination-container">
          <button @click="currentPage--" :disabled="currentPage === 1" class="pagination-btn">Previous</button>
          <span class="pagination-text">Page {{ currentPage }} of {{ totalPages }}</span>
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="pagination-btn">Next</button>
        </div>
      </div>
    </div>

    <div v-if="previewUrl" class="preview-modal">
      <div class="preview-content">
        <button class="close-preview" @click="closePreview">&times;</button>
        <iframe v-if="previewType === 'pdf'" :src="previewUrl" frameborder="0"></iframe>
        <img v-else :src="previewUrl" alt="Document Preview" />
      </div>
    </div>
  </div>
</template>

<style scoped>
html, body {
  height: 100%;
  margin: 0;
  overflow: hidden;
  background: white;
}

.layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
}

.content {
  margin-left: 200px;
  padding: 20px;
  flex: 1;
  max-width: calc(100% - 200px);
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.documents-container {
  background: white;
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.controls {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;
}

.upload-btn {
  background: #28a745;
  color: white;
  padding: 10px 15px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  display: flex;
  align-items: center;
  gap: 5px;
}
.upload-btn:hover {
  background: #218838;
}

.entries-dropdown {
  display: flex;
  align-items: center;
  gap: 5px;
}
.entries-dropdown label,
.entries-dropdown span {
  font-size: 14px;
}
.form-select {
  padding: 5px;
  border-radius: 5px;
  border: 1px solid #707070;
  width: 70px;
}

.search-bar {
  padding: 8px;
  border-radius: 8px;
  border: 2px solid #707070;
  width: 250px;
}
.search-bar:focus {
  border-color: #007bff;
  box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

.table-wrapper {
  background: white;
  border-radius: 10px;
  padding: 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  width: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.documents-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  table-layout: fixed;
}
.documents-table th,
.documents-table td {
  padding: 10px;
  text-align: left;
  font-size: 14px;
  border-bottom: 1px solid #ddd;
  word-wrap: break-word;
}
.documents-table thead {
  background: #0d0c37;
  color: white;
}
.documents-table tbody tr:nth-child(even) {
  background-color: #f9f9f9;
}
.documents-table td:nth-child(2),
.truncate-cell {
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.scrollable-body {
  max-height: 400px;
  overflow-y: auto;
  overflow-x: hidden;
}

.pagination-container {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  padding: 10px 0;
  background-color: white;
  border-top: 1px solid #eee;
  border-radius: 0 0 10px 10px;
}

.pagination-btn {
  padding: 8px 12px;
  border: none;
  background: #007bff;
  color: white;
  cursor: pointer;
  border-radius: 5px;
}
.pagination-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}
.pagination-text {
  font-size: 14px;
  font-weight: bold;
}

.action-buttons {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.btn-xs {
  padding: 3px 6px;
  font-size: 11px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

.preview-modal {
  position: fixed;
  inset: 0;
  z-index: 999;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
}

.preview-content {
  position: relative;
  background: white;
  width: 700px;
  height: 500px;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 15px;
}
.preview-content iframe,
.preview-content img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 6px;
}
.close-preview {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #dc3545;
  color: white;
  border: none;
  padding: 4px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  z-index: 1;
}

.category-filter select {
  min-width: 140px;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 14px;
  appearance: none;
}

.category-filter {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}
</style>
