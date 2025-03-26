<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { FileText, FileSearch, FilePlus } from "lucide-vue-next";

const { props } = usePage();
const students = ref(props.students);

const currentPage = ref(1);
const searchQuery = ref("");
const gradeFilter = ref("");

watch([searchQuery, gradeFilter], () => {
  currentPage.value = 1;
});

const filteredStudents = computed(() => {
  return students.value.filter((student) => {
    const matchesLRN = student.lrn.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesGrade = gradeFilter.value === "" || student.grade_level === gradeFilter.value;
    return matchesLRN && matchesGrade;
  });
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

function goTo(page, id) {
  router.get(`/students/${id}/${page}`);
}
</script>

<template>
  <div class="layout">
    <Sidebar />
    <div class="content">
      <div class="documents-container">
        <div class="controls d-flex align-items-center">
          <!-- Grade Level Filter -->
          <div class="grade-filter">
            <label for="grade">Grade Level</label>
            <select id="grade" v-model="gradeFilter" class="form-select">
              <option value="">All</option>
              <option value="Grade 1">Grade 1</option>
              <option value="Grade 2">Grade 2</option>
              <option value="Grade 3">Grade 3</option>
              <option value="Grade 4">Grade 4</option>
              <option value="Grade 5">Grade 5</option>
              <option value="Grade 6">Grade 6</option>
              <option value="Graduated">Graduated</option>
            </select>
          </div>

          <!-- Search by LRN -->
          <input
            type="text"
            v-model="searchQuery"
            class="search-bar ms-auto"
            placeholder="Search by LRN..."
          />
        </div>

        <div class="table-wrapper">
          <table class="documents-table">
            <thead>
              <tr>
                <th>LRN</th>
                <th>Full Name</th>
                <th>Grade Level</th>
                <th>School Year</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>

          <div class="scrollable-body">
            <table class="documents-table">
              <tbody>
                <tr v-for="student in paginatedStudents" :key="student.id">
                  <td>{{ student.lrn }}</td>
                  <td>{{ student.full_name }}</td>
                  <td>{{ student.grade_level }}</td>
                  <td>{{ student.school_year }}</td>
                  <td class="action-buttons">
                    <button
                      @click="goTo('grades', student.id)"
                      class="btn btn-xs btn-primary"
                      title="View Grade"
                    >
                      <span class="icon-btn"><FileText /></span>
                    </button>
                    <button
                      @click="goTo('record', student.id)"
                      class="btn btn-xs btn-warning"
                      title="Student Record"
                    >
                      <span class="icon-btn"><FileSearch /></span>
                    </button>
                    <button
                      @click="goTo('documents', student.id)"
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
        </div>

        <div class="pagination-container">
          <button @click="currentPage--" :disabled="currentPage === 1" class="pagination-btn">Previous</button>
          <span class="pagination-text">Page {{ currentPage }} of {{ totalPages }}</span>
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="pagination-btn">Next</button>
        </div>
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
  padding: 4px 6px;
  font-size: 11px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 4px;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}
.btn-warning {
  background-color: #ffc107;
  color: white;
}
.btn-success {
  background-color: #28a745;
  color: white;
}

.grade-filter {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
}

/* 🔍 Icon Size Control */
.icon-btn svg {
  width: 24px;
  height: 24px;
}
</style>
