<script setup>
import MainLayout from "@/Layouts/MainLayout.vue";
import { ref, computed, watch } from "vue";
import { FilePlus } from "lucide-vue-next";

const documents = ref([
    { id: "doc_01", name: "Document 1", dateCreated: "2024-02-28", type: "Form 137" },
    { id: "doc_02", name: "Document 2", dateCreated: "2024-02-27", type: "PSA" },
    { id: "doc_03", name: "Document 3", dateCreated: "2024-02-26", type: "Kindergarten Record" },
    { id: "doc_04", name: "Document 4", dateCreated: "2024-02-25", type: "Form 137" },
    { id: "doc_05", name: "Document 5", dateCreated: "2024-02-24", type: "PSA" },
    { id: "doc_06", name: "Document 6", dateCreated: "2024-02-23", type: "PSA" },
    { id: "doc_07", name: "Document 7", dateCreated: "2024-02-22", type: "PSA" },
    { id: "doc_08", name: "Document 8", dateCreated: "2024-02-21", type: "Form 137" },
    { id: "doc_09", name: "Document 9", dateCreated: "2024-02-20", type: "PSA" },
]);

const entries = ref(5);
const currentPage = ref(1);
const tableHeight = ref("auto");

watch(entries, (newVal) => {
    if (newVal == 10 || newVal == 25) {
        tableHeight.value = "400px";
    } else {
        tableHeight.value = "auto";
    }
});

const paginatedDocuments = computed(() => {
    const start = (currentPage.value - 1) * entries.value;
    return documents.value.slice(start, start + entries.value);
});

const totalPages = computed(() => Math.ceil(documents.value.length / entries.value));
</script>

<template>
    <MainLayout >
        <div class="documents-container">
            <div class="controls d-flex align-items-center">
                <button class="btn upload-btn">
                    <FilePlus class="icon" /> Upload
                </button>
                <div class="entries-dropdown ms-3">
                    <label for="entries">Show</label>
                    <select id="entries" v-model="entries" class="form-select">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                    </select>
                    <span>Entries</span>
                </div>
                <input type="text" class="search-bar ms-auto" placeholder="Search" />
            </div>

            <div class="table-wrapper">
                <div class="table-container mt-3" :style="{ maxHeight: tableHeight, overflowY: 'auto' }">
                    <table class="table documents-table">
                        <thead>
                            <tr>
                                <th>File ID</th>
                                <th>Name</th>
                                <th>Date Created</th>
                                <th>Type of Document</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="document in paginatedDocuments" :key="document.id">
                                <td>{{ document.id }}</td>
                                <td>{{ document.name }}</td>
                                <td>{{ document.dateCreated }}</td>
                                <td>{{ document.type }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container fixed-pagination">
                <button @click="currentPage--" :disabled="currentPage === 1" class="pagination-btn">Previous</button>
                <span class="pagination-text">Page {{ currentPage }} of {{ totalPages }}</span>
                <button @click="currentPage++" :disabled="currentPage === totalPages" class="pagination-btn">Next</button>
            </div>
        </div>
    </MainLayout>
</template>

<style>
.documents-container {
    padding: 20px;
    background: #f4f4f4;
    min-height: 100vh;
    width: 100%;
    position: relative;
}

.controls {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
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
    position: relative;
    padding-bottom: 60px;
}

.table-container {
    margin-top: 20px;
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    width: 100%;
    overflow-x: auto;
}

.documents-table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 10px;
    overflow: hidden;
}

.documents-table thead {
    background: #0D0C37 !important; /* Updated header color */
    color: white;
}

.documents-table th, .documents-table td {
    padding: 12px;
    text-align: left;
    font-size: 15px;
    border-bottom: 1px solid #ddd;
}

.documents-table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

.pagination-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.fixed-pagination {
    position: fixed;
    bottom: 10px;
    left: 55%;
    transform: translateX(-50%);
    padding: 10px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
</style>
