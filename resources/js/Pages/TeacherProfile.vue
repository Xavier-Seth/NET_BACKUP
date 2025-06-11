<template>
  <div class="layout">
    <Sidebar v-if="isSidebarVisible" />
    <div class="content">
      <button class="toggle-sidebar-btn d-md-none" @click="toggleSidebar">Toggle Sidebar</button>

      <div class="documents-container">
        <!-- Controls -->
        <div class="controls d-flex align-items-center">
          <button class="btn upload-btn" @click="goToUpload">Upload</button>

          <div class="teacher-filter ms-3">
            <label for="teacher">Teacher</label>
            <select id="teacher" v-model="teacherFilter" class="form-select">
              <option value="">All</option>
              <option v-for="t in teachers" :key="t.id" :value="t.full_name">
                {{ t.full_name }}
              </option>
            </select>
          </div>

          <input
            type="text"
            v-model="searchQuery"
            class="search-bar ms-auto"
            placeholder="Search by document name..."
          />
        </div>

        <!-- No Results -->
        <div v-if="filteredDocuments.length === 0" class="text-center text-muted py-5 fs-6">
          <i class="bi bi-info-circle"></i> No documents found.
        </div>

        <!-- Table Results -->
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
                  <td class="truncate-cell" :title="document.name">
                    {{ document.name }}
                  </td>
                  <td>{{ document.teacher?.full_name ?? 'N/A' }}</td>
                  <td class="truncate-cell" :title="document.category?.name ?? 'N/A'">
                    {{ document.category?.name ?? 'N/A' }}
                  </td>
                  <td>{{ formatDate(document.created_at) }}</td>
                  <td class="action-buttons">
                    <button @click="previewDocument(document)" class="icon-btn" title="Preview">
                      <i class="bi bi-eye"></i>
                    </button>
                    <a
                      :href="`/documents/${document.id}/download`"
                      class="icon-btn"
                      target="_blank"
                      title="Download"
                    >
                      <i class="bi bi-download"></i>
                    </a>
                    <button @click="confirmDelete(document)" class="icon-btn text-danger" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="pagination-info">
            Showing {{ paginationRange.start }} to {{ paginationRange.end }} of {{ paginationRange.total }} entries
          </div>
          <div class="pagination-container">
            <button @click="currentPage--" :disabled="currentPage === 1" class="pagination-btn">Previous</button>
            <span class="pagination-text">Page {{ currentPage }} of {{ totalPages }}</span>
            <button @click="currentPage++" :disabled="currentPage === totalPages" class="pagination-btn">Next</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="previewUrl" class="preview-modal">
      <div class="preview-content">
        <button class="close-preview" @click="closePreview">&times;</button>
        <iframe v-if="previewType === 'pdf'" :src="previewUrl" frameborder="0"></iframe>
        <img v-else :src="previewUrl" alt="Document Preview" />
      </div>
    </div>
  </div>
</template>


<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const { props } = usePage();
const documents = ref(props.documents);
const teachers = ref(props.teachers);

const currentPage = ref(1);
const searchQuery = ref("");
const teacherFilter = ref("");
const previewUrl = ref(null);
const previewType = ref("pdf");
const ENTRIES_PER_PAGE = 20;
const isSidebarVisible = ref(true);

const teacherCategories = [
  "Work Experience Sheet",
  "Personal Data Sheet",
  "Oath of Office",
  "Certification of Assumption to Duty",
  "Transcript of Records",
  "Appointment Form"
];

function toggleSidebar() {
  isSidebarVisible.value = !isSidebarVisible.value;
}

function previewDocument(document) {
  const extension = document.name.split(".").pop().toLowerCase();

  if (extension === "pdf" || ["jpg", "jpeg", "png"].includes(extension)) {
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

function confirmDelete(document) {
  if (confirm(`Are you sure you want to delete "${document.name}"?`)) {
    router.delete(`/documents/${document.id}`, {
      onSuccess: () => {
        documents.value = documents.value.filter(d => d.id !== document.id);
      },
      onError: () => {
        alert("❗ Failed to delete the document.");
      }
    });
  }
}

watch(previewUrl, (val) => {
  document.body.style.overflow = val ? "hidden" : "";
});

watch([searchQuery, teacherFilter], () => {
  currentPage.value = 1;
});

const filteredDocuments = computed(() => {
  return documents.value.filter((doc) => {
    const name = doc.name ?? "";
    const teacher = doc.teacher?.full_name ?? "";
    const category = doc.category?.name ?? "";

    const matchesCategory = teacherCategories.includes(category);
    const matchesSearch = name.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesTeacher = teacherFilter.value === "" || teacher === teacherFilter.value;

    return matchesCategory && matchesSearch && matchesTeacher;
  });
});

const paginatedDocuments = computed(() => {
  const start = (currentPage.value - 1) * ENTRIES_PER_PAGE;
  const end = start + ENTRIES_PER_PAGE;
  return filteredDocuments.value.slice(start, end);
});

const totalPages = computed(() => Math.ceil(filteredDocuments.value.length / ENTRIES_PER_PAGE));

const paginationRange = computed(() => {
  const total = filteredDocuments.value.length;
  if (total === 0) return { start: 0, end: 0, total };
  const start = (currentPage.value - 1) * ENTRIES_PER_PAGE + 1;
  const end = Math.min(start + ENTRIES_PER_PAGE - 1, total);
  return { start, end, total };
});

function formatDate(date) {
  return new Date(date).toLocaleDateString();
}

const goToUpload = () => router.get("/upload");
</script>
  
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
    flex-direction: row;
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
  
  .toggle-sidebar-btn {
    margin-bottom: 10px;
    padding: 6px 12px;
    border: none;
    background: #343a40;
    color: white;
    border-radius: 4px;
    cursor: pointer;
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
    position: sticky;
    top: 0;
    background: white;
    z-index: 2;
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
  
  .form-select {
    padding: 6px 30px 6px 10px; /* ⬅️ more right padding */
    border-radius: 5px;
    border: 1px solid #707070;
    width: 200px;
    appearance: none; /* Remove native styles */
    background-position: right 10px center;
    background-repeat: no-repeat;
    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg width='14' height='8' viewBox='0 0 14 8' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l6 6 6-6' stroke='%23666' stroke-width='2' fill='none' fill-rule='evenodd'/%3E%3C/svg%3E");
    background-color: white;
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
    padding: 6px 8px;
    font-size: 13px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    word-wrap: break-word;
    line-height: 1.4;
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
    max-height: 600px;
    overflow-y: auto;
    overflow-x: auto;
  }
  
  .pagination-info {
    text-align: center;
    font-size: 14px;
    color: #555;
    padding: 10px 0;
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
    gap: 6px;
    flex-wrap: wrap;
  }
  
  .action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 5px 10px;
    font-size: 13px;
    height: 28px;
    line-height: 1;
    font-weight: 500;
    border-radius: 4px;
    border: none;
    text-decoration: none;
    white-space: nowrap;
    min-width: 90px;
  }
  .icon-btn {
  background: none;
  border: none;
  color: #0d6efd;
  font-size: 18px;
  cursor: pointer;
  margin-right: 6px;
}

.icon-btn i {
  color: #0d6efd;
}

.icon-btn:hover i {
  color: #0a58ca;
}

.icon-btn:last-child i {
  color: #198754;
}

.icon-btn:last-child:hover i {
  color: #146c43;
}
  
  .preview-btn {
    background-color: #0d6efd;
    color: #fff;
  }
  .preview-btn:hover {
    background-color: #0b5ed7;
  }
  
  .download-btn {
    background-color: #198754;
    color: #fff;
  }
  .download-btn:hover {
    background-color: #157347;
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
    width: 90vw;
    max-width: 700px;
    height: 500px;
    border-radius: 10px;
    overflow-y: auto;
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
      width: 100% !important;
      margin-top: 8px;
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
      position: relative;
      padding-left: 50%;
      border: none;
      border-bottom: 1px solid #eee;
      white-space: normal;
    }
  
    .documents-table td::before {
      position: absolute;
      top: 6px;
      left: 8px;
      width: 45%;
      padding-right: 10px;
      white-space: nowrap;
      font-weight: bold;
      color: #333;
    }
  
    .documents-table td:nth-of-type(1)::before { content: "ID"; }
    .documents-table td:nth-of-type(2)::before { content: "Name"; }
    .documents-table td:nth-of-type(3)::before { content: "Type"; }
    .documents-table td:nth-of-type(4)::before { content: "Category"; }
    .documents-table td:nth-of-type(5)::before { content: "LRN"; }
    .documents-table td:nth-of-type(6)::before { content: "Date Created"; }
    .documents-table td:nth-of-type(7)::before { content: "Size"; }
    .documents-table td:nth-of-type(8)::before { content: "Actions"; }
  
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
  