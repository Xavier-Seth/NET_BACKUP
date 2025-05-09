<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const { props } = usePage();
const documents = ref(props.documents);
const categories = ref(props.categories);
const teachers = ref(props.teachers);

const currentPage = ref(1);
const searchQuery = ref("");
const categoryFilter = ref("");
const teacherFilter = ref("");

const previewUrl = ref(null);
const previewType = ref("pdf");
const ENTRIES_PER_PAGE = 20;
const isSidebarVisible = ref(true);

function toggleSidebar() {
  isSidebarVisible.value = !isSidebarVisible.value;
}

function previewDocument(document) {
  const extension = document.name.split(".").pop().toLowerCase();
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
}

function closePreview() {
  previewUrl.value = null;
  previewType.value = null;
}

watch(previewUrl, (val) => {
  document.body.style.overflow = val ? "hidden" : "";
});

const filteredDocuments = computed(() => {
  return documents.value.filter((doc) => {
    const name = doc.name ?? "";
    const category = doc.category?.name ?? "";
    const teacher = doc.teacher?.full_name ?? "";

    const matchesSearch = name.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesCategory = categoryFilter.value === "" || category === categoryFilter.value;
    const matchesTeacher = teacherFilter.value === "" || teacher === teacherFilter.value;

    return matchesSearch && matchesCategory && matchesTeacher;
  });
});

const paginatedDocuments = computed(() => {
  const start = (currentPage.value - 1) * ENTRIES_PER_PAGE;
  const end = start + ENTRIES_PER_PAGE;
  return filteredDocuments.value.slice(start, end);
});

const totalPages = computed(() =>
  Math.ceil(filteredDocuments.value.length / ENTRIES_PER_PAGE)
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
    <Sidebar v-if="isSidebarVisible" />
    <div class="content">
      <div class="documents-container">
        <!-- Filters -->
        <div class="controls">
          <button class="btn upload-btn" @click="goToUpload">Upload</button>

          <div class="filter">
            <label>Category</label>
            <select v-model="categoryFilter" class="form-select">
              <option value="">All</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
            </select>
          </div>

          <input
            type="text"
            v-model="searchQuery"
            class="search-bar"
            placeholder="Search by document name..."
          />
        </div>

        <!-- Table -->
        <div class="table-wrapper">
          <table class="documents-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Teacher</th>
                <th>Category</th>
                <th>Uploaded</th>
                <th>Size</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="document in paginatedDocuments" :key="document.id">
                <td>{{ document.id }}</td>
                <td class="truncate">{{ document.name }}</td>
                <td>{{ document.teacher?.full_name ?? 'N/A' }}</td>
                <td>{{ document.category?.name ?? 'N/A' }}</td>
                <td>{{ formatDate(document.created_at) }}</td>
                <td>{{ formatSize(document.size) }}</td>
                <td class="action-buttons">
                  <button class="icon-btn view" @click="previewDocument(document)">
                    <i class="bi bi-eye"></i>
                  </button>
                  <a
                    :href="`/documents/${document.id}/download`"
                    class="icon-btn download"
                    target="_blank"
                  >
                    <i class="bi bi-download"></i>
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-controls">
          <button @click="currentPage--" :disabled="currentPage === 1" class="pagination-btn">Previous</button>
          <span>Page {{ currentPage }} of {{ totalPages }}</span>
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="pagination-btn">Next</button>
        </div>
      </div>

      <!-- Preview Modal -->
      <div v-if="previewUrl" class="preview-modal">
        <div class="preview-content">
          <button class="close-preview" @click="closePreview">&times;</button>
          <iframe v-if="previewType === 'pdf'" :src="previewUrl" frameborder="0"></iframe>
          <img v-else :src="previewUrl" alt="Preview" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.layout {
  display: flex;
}
.content {
  flex: 1;
  margin-left: 200px;
  padding: 20px;
}
.documents-container {
  background: white;
  border-radius: 8px;
  padding: 20px;
}
.controls {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}
.upload-btn {
  background: #198754;
  color: white;
  padding: 8px 16px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}
.filter select,
.search-bar {
  border: 1px solid #ccc;
  padding: 8px;
  border-radius: 6px;
}
.table-wrapper {
  overflow-x: auto;
}
.documents-table {
  width: 100%;
  border-collapse: collapse;
}
.documents-table th {
  background: #0d0c37;
  color: white;
  padding: 10px;
}
.documents-table td {
  padding: 10px;
  border-bottom: 1px solid #eee;
}
.truncate {
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.action-buttons {
  display: flex;
  gap: 8px;
}
.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 18px;
}
.icon-btn.view {
  color: #0d6efd;
}
.icon-btn.download {
  color: #198754;
}
.pagination-controls {
  margin-top: 15px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
}
.pagination-btn {
  padding: 5px 10px;
  background: #0d6efd;
  color: white;
  border: none;
  border-radius: 5px;
}
.preview-modal {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
}
.preview-content {
  background: white;
  width: 90vw;
  height: 85vh;
  position: relative;
}
.close-preview {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #dc3545;
  border: none;
  color: white;
  padding: 8px;
  cursor: pointer;
  font-size: 18px;
}
iframe, img {
  width: 100%;
  height: 100%;
  border: none;
}
</style>
