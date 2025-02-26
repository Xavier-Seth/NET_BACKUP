<template>
  <div class="container">
    <Sidebar />

    <div class="main-content">
      <!-- Top Bar with Logo & Profile -->
      <header class="top-bar">
        <div class="left-top">
          <img src="/images/school_logo.png" alt="Logo" class="logo" />
          <h1>DocuNet</h1>
        </div>
        <div class="right-top">
          <div class="profile">
            <img src="/images/user-avatar.png" alt="User Avatar" class="profile-img" />
            <span class="profile-name">Xavier Noynay</span>
          </div>
        </div>
      </header>

      <!-- Documents Container -->
      <div class="documents-container">
        <div class="header-section">
          <h2>Documents</h2>
          <div class="actions">
            <button class="upload-btn">
              <i class="bi bi-upload"></i> Upload
            </button>
            <button class="settings-btn">
              <i class="bi bi-gear"></i>
            </button>
          </div>
        </div>

        <!-- Table Controls -->
        <div class="table-controls">
          <label>
            Show
            <select v-model="entriesToShow">
              <option>10</option>
              <option>25</option>
              <option>50</option>
            </select>
            Entries
          </label>
          <div class="search-bar">
            <label>Search: <input v-model="searchQuery" type="text" /></label>
          </div>
        </div>

        <!-- Documents Table -->
        <div class="table-wrapper">
          <table class="documents-table">
            <thead>
              <tr>
                <th><input type="checkbox" /></th>
                <th>File ID</th>
                <th>Name</th>
                <th>Date Created</th>
                <th>Type of Document</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(document, index) in paginatedDocuments" :key="index">
                <td><input type="checkbox" /></td>
                <td>{{ document.fileId }}</td>
                <td>{{ document.name }}</td>
                <td>{{ document.dateCreated }}</td>
                <td>{{ document.type }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Controls -->
        <div class="pagination">
          <button @click="prevPage" :disabled="currentPage === 1">&lt;</button>
          <span>Page {{ currentPage }}</span>
          <button @click="nextPage" :disabled="currentPage * entriesToShow >= filteredDocuments.length">&gt;</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Sidebar from "@/Components/Sidebar.vue";

export default {
  components: {
    Sidebar,
  },
  data() {
    return {
      entriesToShow: 10,
      searchQuery: "",
      currentPage: 1,
      documents: [
        { fileId: "doc_01", name: "Document 1", dateCreated: "2024-02-24", type: "Report" },
        { fileId: "doc_02", name: "Document 2", dateCreated: "2024-02-23", type: "Invoice" },
        // ... add more sample documents as needed
      ],
    };
  },
  computed: {
    filteredDocuments() {
      return this.documents.filter(doc =>
        doc.name.toLowerCase().includes(this.searchQuery.toLowerCase())
      );
    },
    paginatedDocuments() {
      const start = (this.currentPage - 1) * this.entriesToShow;
      return this.filteredDocuments.slice(start, start + this.entriesToShow);
    },
  },
  methods: {
    nextPage() {
      if (this.currentPage * this.entriesToShow < this.filteredDocuments.length) {
        this.currentPage++;
      }
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
      }
    },
  },
};
</script>

<style scoped>
/* Overall Layout */
.container {
  display: flex;
}

.main-content {
  flex-grow: 1;
  margin-left: 220px; /* Space for the sidebar */
  min-height: 100vh;
  background: #f4f4f4;
  display: flex;
  flex-direction: column;
  /* Remove extra margin on top/right */
  margin-top: 0;
  margin-right: 0;
}

/* Top Bar */
.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #12172B;
  color: white;
  padding: 10px 20px;
  border-radius: 10px;
}
.left-top {
  display: flex;
  align-items: center;
  gap: 10px;
}
.logo {
  height: 40px;
}
.right-top .profile {
  display: flex;
  align-items: center;
  gap: 10px;
}
.profile-img {
  width: 30px;
  height: 30px;
  border-radius: 50%;
}
.profile-name {
  font-size: 14px;
}

/* Documents Container */
.documents-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  margin-top: 15px;
  /* Minimizing side margins so table can fill space */
  padding: 0 10px 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Header Section (Documents Title & Actions) */
.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 15px 0;
}
.header-section h2 {
  margin: 0;
  font-size: 24px;
  font-weight: bold;
}
.actions button {
  background: #28a745;
  color: white;
  border: none;
  padding: 8px 12px;
  margin-right: 5px;
  cursor: pointer;
  border-radius: 5px;
  transition: background 0.3s ease;
}
.actions button:hover {
  background: #1e7e34;
}
.settings-btn {
  background: #6c757d;
}

/* Table Controls */
.table-controls {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
  padding: 0 10px;
}
.search-bar input {
  padding: 5px;
  border-radius: 4px;
  border: 1px solid #ccc;
}

/* Table Wrapper */
.table-wrapper {
  flex: 1;
  overflow-x: auto;
  border-radius: 10px;
  padding: 0 10px;
}

/* Documents Table */
.documents-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  margin-top: 15px;
}
.documents-table th,
.documents-table td {
  padding: 12px;
  border: 1px solid #ddd;
  text-align: left;
}
.documents-table thead th {
  background: #12172B;
  color: white;
}

/* Pagination */
.pagination {
  margin-top: 15px;
  display: flex;
  justify-content: center;
  align-items: center;
}
.pagination button {
  background: #1a1f3a;
  color: white;
  border: none;
  padding: 5px 10px;
  cursor: pointer;
  margin: 0 5px;
}
</style>
