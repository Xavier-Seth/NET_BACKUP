<template>
  <div class="layout">
    <Sidebar />
    <div class="content">
      <div class="documents-container">
        <!-- Filters -->
        <div class="controls d-flex align-items-center">
          <!-- School Year Filter -->
          <div class="schoolyear-filter">
            <label for="schoolYear">School Year</label>
            <select id="schoolYear" v-model="schoolYearFilter" class="form-select">
              <option value="">All</option>
              <option v-for="year in uniqueSchoolYears" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>

          <!-- LRN Search with Suggestions -->
          <div class="ms-auto" style="position: relative;">
            <input
              type="text"
              v-model="searchQuery"
              class="search-bar"
              placeholder="Search by LRN..."
              @focus="showSuggestions = true"
              @blur="() => setTimeout(() => (showSuggestions = false), 150)"
            />
            <ul v-if="showSuggestions" class="suggestions-dropdown">
              <li
                v-for="suggestion in lrnSuggestions"
                :key="suggestion"
                @click="selectSuggestion(suggestion)"
              >
                {{ suggestion }}
              </li>
            </ul>
          </div>
        </div>

        <!-- Table -->
        <div class="table-wrapper scrollable-body">
          <table class="documents-table">
            <thead>
              <tr>
                <th>LRN</th>
                <th>Full Name</th>
                <th>School Year</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in paginatedStudents" :key="student.id">
                <td data-label="LRN">{{ student.lrn }}</td>
                <td data-label="Full Name">{{ student.full_name }}</td>
                <td data-label="School Year">{{ student.school_year }}</td>
                <td data-label="Actions" class="action-buttons">
                  <button
                    @click="goToRecord(student.id)"
                    class="btn btn-xs btn-warning"
                    title="Student Record"
                  >
                    <span class="icon-btn"><FileSearch /></span>
                  </button>
                  <button
                    @click="goToDocuments(student.lrn)"
                    class="btn btn-xs btn-success"
                    title="Documents"
                  >
                    <span class="icon-btn"><FilePlus /></span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
          <button @click="currentPage--" :disabled="currentPage === 1" class="pagination-btn">Previous</button>
          <span class="pagination-text">Page {{ currentPage }} of {{ totalPages }}</span>
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="pagination-btn">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { ref, computed, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { FileSearch, FilePlus } from "lucide-vue-next";

const { props } = usePage();
const students = ref(props.students);

const currentPage = ref(1);
const searchQuery = ref("");
const schoolYearFilter = ref("");
const showSuggestions = ref(false);

// Go to student record
const goToRecord = (id) => {
  router.get(`/students/${id}/record`);
};

// Go to documents
const goToDocuments = (lrn) => {
  router.visit('/documents', {
    method: 'get',
    data: {
      search: lrn,
      type: 'student',
    },
    preserveState: true,
    preserveScroll: true,
  });
};

// Unique School Years for filter dropdown
const uniqueSchoolYears = computed(() => {
  const years = students.value.map(s => s.school_year).filter(Boolean);
  return [...new Set(years)].sort().reverse();
});

// LRN Autocomplete Suggestions
const lrnSuggestions = computed(() => {
  if (!searchQuery.value) return [];
  return students.value
    .map((s) => String(s.lrn))
    .filter((lrn) => lrn.includes(searchQuery.value))
    .slice(0, 5);
});

const selectSuggestion = (lrn) => {
  searchQuery.value = lrn;
  showSuggestions.value = false;
};

// Filter students by LRN and school year
const filteredStudents = computed(() => {
  return students.value
    .filter((student) => {
      const matchesLRN = String(student.lrn).toLowerCase().includes(searchQuery.value.toLowerCase());
      const matchesYear = schoolYearFilter.value === "" || student.school_year === schoolYearFilter.value;
      return matchesLRN && matchesYear;
    })
    .sort((a, b) => a.full_name.localeCompare(b.full_name));
});

watch([searchQuery, schoolYearFilter], () => {
  currentPage.value = 1;
});

const entriesPerPage = 20;
const paginatedStudents = computed(() => {
  const start = (currentPage.value - 1) * entriesPerPage;
  const end = start + entriesPerPage;
  return filteredStudents.value.slice(start, end);
});

const totalPages = computed(() =>
  Math.ceil(filteredStudents.value.length / entriesPerPage)
);
</script>

<style scoped>
/* Same styles from your original code */
.documents-table thead th {
  position: sticky;
  top: 0;
  background-color: #0d0c37;
  z-index: 2;
}

.suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  width: 100%;
  margin-top: 4px;
  background: white;
  border: 1px solid #ccc;
  border-radius: 4px;
  z-index: 10;
  list-style: none;
  padding: 0;
  max-height: 180px;
  overflow-y: auto;
}
.suggestions-dropdown li {
  padding: 8px 12px;
  cursor: pointer;
}
.suggestions-dropdown li:hover {
  background-color: #f1f1f1;
}

html, body {
  height: 100%;
  margin: 0;
  background: white;
  overflow: hidden;
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

.schoolyear-filter {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
}

.form-select {
  padding: 5px;
  border-radius: 5px;
  border: 1px solid #707070;
  min-width: 140px;
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
  overflow-y: auto;
  overflow-x: hidden;
  flex: 1;
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
  flex-wrap: wrap;
  margin-top: 30px;
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
  padding: 5px 6px;
  font-size: 11px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 4px;
}

.btn-warning {
  background-color: #ffc107;
  color: white;
}
.btn-success {
  background-color: #28a745;
  color: white;
}

.icon-btn svg {
  width: 20px;
  height: 20px;
}

/* Responsive Layout */
@media (max-width: 768px) {
  .content {
    margin-left: 0;
    padding: 10px;
    max-width: 100%;
  }

  .search-bar,
  .form-select {
    width: 100%;
  }

  .controls {
    flex-direction: column;
    align-items: stretch;
  }

  .documents-table thead {
    display: none;
  }
  .documents-table,
  .documents-table tbody,
  .documents-table tr,
  .documents-table td {
    display: block;
    width: 100%;
  }
  .documents-table tr {
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    background-color: #f8f9fa;
  }
  .documents-table td {
    text-align: left;
    padding: 6px 10px;
    position: relative;
  }
  .documents-table td::before {
    content: attr(data-label);
    font-weight: bold;
    display: block;
    margin-bottom: 4px;
    color: #333;
  }
}
</style>
