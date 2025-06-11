<template>
  <div class="flex bg-gray-100 min-h-screen">
    <Sidebar />

    <div class="flex-1 bg-gray-100 overflow-y-auto ml-[200px]">
      <div class="flex justify-center items-center min-h-screen px-4 py-10">
        <div
          class="upload-container w-full max-w-3xl mx-auto"
          :class="{ dragging: isDragging }"
          @dragover.prevent="handleDragOver"
          @dragleave="handleDragLeave"
          @drop="handleDrop"
        >
          <i class="bi bi-cloud-arrow-up-fill upload-icon text-3xl"></i>
          <h2 class="text-xl font-bold mt-4">Drag and drop your files</h2>
          <p class="text-gray-500 text-sm mt-2">Supports: PDF, DOCX, XLSX, XLS, PNG, JPG</p>
          <p class="text-gray-500 text-sm mt-2">Or</p>

          <p v-if="flaskOffline" class="text-red-600 font-bold mt-2">
            ⚠️ Flask app is offline. Using fallback mode (no OCR).
          </p>

          <!-- ✅ Success Confirmation -->
          <p
            v-if="successMessage"
            class="text-green-700 font-semibold bg-green-100 border border-green-300 rounded p-3 mt-4 w-full text-center transition-all duration-500"
          >
            {{ successMessage }}
          </p>

          <!-- 🧠 Detected Info Section -->
          <div
            v-if="selectedFiles.length && !isScanning"
            class="mt-4 w-full max-w-xs mx-auto text-left text-sm text-gray-800"
          >
            <div
              v-if="!detectedCategory && !detectedTeacher && showCategorySelection"
              class="mb-2 text-red-600 font-semibold flex items-center gap-1"
            >
              <i class="bi bi-x-circle-fill"></i>
              No category or teacher detected. Please select the category manually:
            </div>

            <div v-if="showCategorySelection" class="mb-4">
              <select v-model="category_id" class="border rounded p-2 w-full">
                <option disabled value="">Select Category</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>

            <div v-if="detectedCategory" class="text-green-700 font-semibold mb-2">
              Detected Category: {{ detectedCategory }}
            </div>

            <div v-if="showTeacherSelection">
              <p v-if="detectedCategory && !detectedTeacher" class="text-red-600">
                This document "{{ detectedCategory }}" belongs to a teacher. Please select the teacher:
              </p>
              <div v-if="!detectedTeacher" class="mt-2">
                <select v-model="teacher_id" class="border rounded p-2 w-full">
                  <option disabled value="">Select Teacher</option>
                  <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                    {{ teacher.full_name }}
                  </option>
                </select>
              </div>
              <p v-if="detectedTeacher" class="mt-2 text-green-700">
                <strong>Detected Teacher:</strong> {{ detectedTeacher }}
              </p>
            </div>
          </div>

          <!-- 📂 File Input + Browse -->
          <div class="flex gap-2 justify-center mt-4">
            <input
              type="file"
              :key="fileInputKey"
              id="fileInput"
              class="hidden"
              multiple
              @change="handleFileUpload"
              accept=".pdf,.docx,.xlsx,.xls,.png,.jpg,.doc"
            >
            <button
              @click="browseFile"
              class="px-4 py-2 border-2 border-indigo-900 text-indigo-900 font-bold rounded-lg hover:bg-indigo-900 hover:text-white"
            >
              Browse Files
            </button>
          </div>

          <div v-if="isScanning || isUploading" class="spinner mt-6"></div>

          <!-- 📑 Selected Files -->
          <div
            v-if="selectedFiles.length && !isScanning && !isUploading"
            class="selected-files mt-6 w-full text-left"
          >
            <p class="text-gray-700 font-bold mb-2">Selected Files:</p>
            <ul class="file-list">
              <li
                v-for="(file, index) in selectedFiles"
                :key="index"
                class="text-gray-700 flex items-center justify-between border-b py-2"
              >
                <span>{{ file.name }} ({{ formatFileSize(file.size) }})</span>
                <button @click="removeFile(index)" class="text-red-500 hover:text-red-700">
                  <i class="bi bi-trash"></i>
                </button>
              </li>
            </ul>
          </div>

          <!-- 📤 Upload Button -->
          <button
            v-if="selectedFiles.length && !isScanning"
            @click="uploadFiles"
            class="upload-btn"
            :disabled="isUploading"
          >
            {{ isUploading ? 'Uploading...' : 'Upload Document' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Sidebar from "@/Components/Sidebar.vue";
import { router } from '@inertiajs/vue3';
import axios from 'axios';

export default {
  props: {
    teachers: Array,
    categories: Array,
  },
  components: { Sidebar },
  data() {
    return {
      selectedFiles: [],
      isDragging: false,
      isUploading: false,
      isScanning: false,
      uploadMessage: "",
      uploadSuccess: false,
      teacher_id: "",
      category_id: "",
      fileInputKey: 0,
      detectedTeacher: null,
      detectedCategory: null,
      showTeacherSelection: false,
      showCategorySelection: false,
      flaskOffline: false,
      successMessage: "",

      teacherDocumentTypes: [
        'Work Experience Sheet',
        'Personal Data Sheet',
        'Oath of Office',
        'Certification of Assumption to Duty',
        'Transcript of Records',
        'Appointment Form'
      ],
    };
  },
  computed: {
    selectedCategoryName() {
      const selected = this.categories.find(c => c.id === this.category_id);
      return selected ? selected.name : null;
    }
  },
  watch: {
    category_id(newVal) {
      const selected = this.categories.find(c => c.id === newVal);
      if (selected && this.teacherDocumentTypes.includes(selected.name)) {
        this.showTeacherSelection = true;
      } else {
        this.showTeacherSelection = false;
        this.teacher_id = "";
      }
    }
  },
  methods: {
    browseFile() {
      document.getElementById("fileInput").click();
    },
    handleFileUpload(event) {
      this.selectedFiles = Array.from(event.target.files);
      this.clearMessages();
      if (this.selectedFiles.length > 0) {
        this.scanFile(this.selectedFiles[0]);
      }
    },
    async scanFile(file) {
      const formData = new FormData();
      formData.append('file', file);
      this.isScanning = true;

      try {
        const response = await axios.post('/upload/scan', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });

        this.detectedTeacher = response.data.teacher || null;
        this.detectedCategory = response.data.category || null;
        this.flaskOffline = !!response.data.fallback_used;
        this.showTeacherSelection = response.data.belongs_to_teacher;
        this.showCategorySelection = this.detectedCategory === null;

        if (this.detectedTeacher) {
          const matchedTeacher = this.teachers.find(t => t.full_name === this.detectedTeacher);
          if (matchedTeacher) {
            this.teacher_id = matchedTeacher.id;
          }
        }
      } catch (error) {
        console.error('Error scanning document', error);
      } finally {
        this.isScanning = false;
      }
    },
    removeFile(index) {
      this.selectedFiles.splice(index, 1);
      this.clearMessages();
    },
    handleDragOver(e) {
      e.preventDefault();
      this.isDragging = true;
    },
    handleDragLeave() {
      this.isDragging = false;
    },
    handleDrop(e) {
      e.preventDefault();
      this.selectedFiles = Array.from(e.dataTransfer.files);
      this.isDragging = false;
      this.clearMessages();
      if (this.selectedFiles.length > 0) {
        this.scanFile(this.selectedFiles[0]);
      }
    },
    formatFileSize(size) {
      return `${(size / 1024).toFixed(2)} KB`;
    },
    uploadFiles() {
      if (this.showTeacherSelection && !this.detectedTeacher && !this.teacher_id) {
        alert('❌ Please select a teacher before uploading.');
        return;
      }
      if (this.showCategorySelection && !this.detectedCategory && !this.category_id) {
        alert('❌ Please select a category before uploading.');
        return;
      }

      this.isUploading = true;
      const formData = new FormData();
      this.selectedFiles.forEach(file => {
        formData.append('files[]', file);
      });
      formData.append('teacher_id', this.teacher_id);
      formData.append('category_id', this.category_id);

      router.post('/upload', formData, {
        forceFormData: true,
        onSuccess: (page) => {
          this.resetUploadState();
          this.successMessage = "✅ Document has been successfully uploaded and categorized!";
          setTimeout(() => {
            this.successMessage = "";
          }, 4000);
        },
        onError: () => {
          alert("❌ Upload failed. Please try again.");
          this.isUploading = false;
        }
      });
    },
    resetUploadState() {
      this.selectedFiles = [];
      this.isUploading = false;
      this.isScanning = false;
      this.uploadSuccess = true;
      this.teacher_id = "";
      this.category_id = "";
      this.detectedTeacher = null;
      this.detectedCategory = null;
      this.showTeacherSelection = false;
      this.showCategorySelection = false;
      this.flaskOffline = false;
      this.fileInputKey++;
      document.getElementById("fileInput").value = null;
    },
    clearMessages() {
      this.uploadSuccess = false;
      this.uploadMessage = "";
      this.detectedTeacher = null;
      this.detectedCategory = null;
      this.showTeacherSelection = false;
      this.showCategorySelection = false;
      this.teacher_id = "";
      this.category_id = "";
      this.flaskOffline = false;
      this.successMessage = "";
    }
  }
};
</script>

<style scoped>
.upload-container {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  border: 3px dashed #ccc;
  transition: border-color 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}
.upload-container.dragging { border-color: #2563eb; }
.upload-btn {
  margin-top: 1.5rem;
  padding: 12px 24px;
  background-color: #2563eb;
  color: white;
  font-weight: bold;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: background-color 0.3s ease;
}
.upload-btn:hover { background-color: #1e40af; }
.upload-btn:disabled { background-color: #a0aec0; cursor: not-allowed; }
.selected-files { width: 100%; margin-top: 1.5rem; }
.file-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.spinner {
  margin-top: 1rem;
  width: 40px;
  height: 40px;
  border: 4px solid #ccc;
  border-top: 4px solid #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
