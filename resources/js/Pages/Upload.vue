<template>
  <div>
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

            <p
              v-if="successMessage"
              class="text-green-700 font-semibold bg-green-100 border border-green-300 rounded p-3 mt-4 w-full text-center transition-all duration-500"
            >
              {{ successMessage }}
            </p>

            <!-- Detected Info -->
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
                  <option v-for="c in categories" :key="c.id" :value="c.id">
                    {{ c.name }}
                  </option>
                </select>
              </div>

              <div v-if="detectedCategory" class="text-green-700 font-semibold mb-2">
                Detected Category: {{ detectedCategory }}
              </div>

              <div v-if="showTeacherSelection">
                <p v-if="detectedCategory && !detectedTeacher" class="text-red-600">
                  This document “{{ detectedCategory }}” belongs to a teacher. Please select the teacher:
                </p>
                <div v-if="!detectedTeacher" class="mt-2">
                  <select v-model="teacher_id" class="border rounded p-2 w-full">
                    <option disabled value="">Select Teacher</option>
                    <option v-for="t in teachers" :key="t.id" :value="t.id">
                      {{ t.full_name }}
                    </option>
                  </select>
                </div>
                <p v-if="detectedTeacher" class="mt-2 text-green-700">
                  <strong>Detected Teacher:</strong> {{ detectedTeacher }}
                </p>
              </div>
            </div>

            <!-- File Input -->
            <div class="flex gap-2 justify-center mt-4">
              <input
                id="fileInput"
                type="file"
                :key="fileInputKey"
                class="hidden"
                multiple
                @change="handleFileUpload"
                accept=".pdf,.docx,.xlsx,.xls,.png,.jpg,.doc"
              />
              <button
                @click="browseFile"
                class="px-4 py-2 border-2 border-indigo-900 text-indigo-900 font-bold rounded-lg hover:bg-indigo-900 hover:text-white"
              >
                Browse Files
              </button>
            </div>

            <div v-if="isScanning || isUploading" class="spinner mt-6"></div>

            <!-- Selected Files -->
            <div
              v-if="selectedFiles.length && !isScanning && !isUploading"
              class="selected-files mt-6 w-full text-left"
            >
              <p class="text-gray-700 font-bold mb-2">Selected Files:</p>
              <ul class="file-list">
                <li
                  v-for="(file, i) in selectedFiles"
                  :key="i"
                  class="flex items-center justify-between border-b py-2 text-gray-700"
                >
                  <span>{{ file.name }} ({{ formatFileSize(file.size) }})</span>
                  <button @click="removeFile(i)" class="text-red-500 hover:text-red-700">
                    <i class="bi bi-trash"></i>
                  </button>
                </li>
              </ul>
            </div>

            <!-- Upload Button -->
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

    <!-- Duplicate Prompt Modal -->
    <DuplicatePromptModal
      v-if="showDuplicateModal"
      :existing-name="duplicateDocName"
      @replace="handleReplace"
      @continue="handleContinue"
      @cancel="showDuplicateModal = false"
    />
  </div>
</template>

<script>
import Sidebar from "@/Components/Sidebar.vue";
import DuplicatePromptModal from "@/Components/DuplicatePromptModal.vue";
import axios from "axios";

export default {
  props: {
    teachers: Array,
    categories: Array
  },
  components: { Sidebar, DuplicatePromptModal },
  data() {
    return {
      selectedFiles: [],
      isDragging: false,
      isUploading: false,
      isScanning: false,
      teacher_id: "",
      category_id: "",
      fileInputKey: 0,
      detectedTeacher: null,
      detectedCategory: null,
      showTeacherSelection: false,
      showCategorySelection: false,
      flaskOffline: false,
      successMessage: "",

      showDuplicateModal: false,
      duplicateDocName: "",
      pendingFormData: null,

      teacherDocumentTypes: [
        "Work Experience Sheet",
        "Personal Data Sheet",
        "Oath of Office",
        "Certification of Assumption to Duty",
        "Transcript of Records",
        "Appointment Form"
      ]
    };
  },
  methods: {
    browseFile() {
      document.getElementById("fileInput").click();
    },

    handleFileUpload(e) {
      this.selectedFiles = Array.from(e.target.files);
      this.successMessage = "";
      if (this.selectedFiles.length) {
        this.scanFile(this.selectedFiles[0]);
      }
    },

    async scanFile(file) {
      this.isScanning = true;
      const formData = new FormData();
      formData.append("file", file);

      try {
        const res = await axios.post("/upload/scan", formData, {
          headers: { "Content-Type": "multipart/form-data" }
        });

        this.detectedTeacher = res.data.teacher || null;
        this.detectedCategory = res.data.category || null;
        this.flaskOffline = !!res.data.fallback_used;
        // Always show category selection if not detected
        this.showCategorySelection = !this.detectedCategory;
        // Show teacher selection if the chosen or detected category is in teacherDocumentTypes
        const categoryForCheck = this.detectedCategory ||
          this.categories.find(c => c.id === this.category_id)?.name;
        this.showTeacherSelection =
          !!categoryForCheck &&
          this.teacherDocumentTypes.includes(categoryForCheck);

        if (this.detectedTeacher) {
          const t = this.teachers.find(x => x.full_name === this.detectedTeacher);
          if (t) this.teacher_id = t.id;
        }
        if (this.detectedCategory) {
          const c = this.categories.find(x => x.name === this.detectedCategory);
          if (c) this.category_id = c.id;
        }
      } catch (err) {
        console.error(err);
      } finally {
        this.isScanning = false;
      }
    },

    removeFile(i) {
      this.selectedFiles.splice(i, 1);
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
      if (this.selectedFiles.length) {
        this.scanFile(this.selectedFiles[0]);
      }
    },

    formatFileSize(size) {
      return `${(size / 1024).toFixed(2)} KB`;
    },

    async uploadFiles() {
      if (this.showTeacherSelection && !this.teacher_id) {
        return alert("❌ Please select a teacher.");
      }
      if (this.showCategorySelection && !this.category_id) {
        return alert("❌ Please select a category.");
      }

      this.isUploading = true;
      const formData = new FormData();
      this.selectedFiles.forEach(f => formData.append("files[]", f));
      formData.append("teacher_id", this.teacher_id);
      formData.append("category_id", this.category_id);

      try {
        const resp = await axios.post("/upload", formData, {
          headers: { "Content-Type": "multipart/form-data" }
        });

        // Debug response
        console.log('Upload response:', resp.data);

        if (resp.data.duplicate) {
          this.duplicateDocName = resp.data.existing_document_name;
          this.pendingFormData = formData;
          this.showDuplicateModal = true;
          return;
        }

        this.resetAfterUpload();
      } catch (err) {
        console.error(err);
        alert("❌ Upload failed.");
      } finally {
        this.isUploading = false;
      }
    },

    async handleReplace() {
      this.pendingFormData.append("action", "replace");
      await this.sendFinalUpload();
    },
    async handleContinue() {
      this.pendingFormData.append("action", "upload");
      await this.sendFinalUpload();
    },
    async sendFinalUpload() {
      try {
        const resp = await axios.post("/upload", this.pendingFormData, {
          headers: { "Content-Type": "multipart/form-data" }
        });
        console.log('Final upload response:', resp.data);
        this.resetAfterUpload();
      } catch (err) {
        console.error(err);
        alert("❌ Upload failed.");
      } finally {
        this.showDuplicateModal = false;
        this.pendingFormData = null;
      }
    },

    resetAfterUpload() {
      this.selectedFiles = [];
      this.teacher_id = "";
      this.category_id = "";
      this.successMessage = "✅ Document uploaded successfully!";
      // force file input reset
      this.fileInputKey++;
      setTimeout(() => (this.successMessage = ""), 4000);
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
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  transition: border-color 0.3s;
}
.upload-container.dragging {
  border-color: #2563eb;
}
.upload-btn {
  margin-top: 1.5rem;
  padding: 0.75rem 1.5rem;
  background: #2563eb;
  color: white;
  font-weight: bold;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: background 0.3s;
}
.upload-btn:hover {
  background: #1e40af;
}
.upload-btn:disabled {
  background: #a0aec0;
  cursor: not-allowed;
}
.spinner {
  margin-top: 1rem;
  width: 2.5rem;
  height: 2.5rem;
  border: 4px solid #ccc;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
