<template>
  <div class="flex bg-gray-100 min-h-screen">
    <Sidebar />

    <!-- MAIN CONTENT AREA -->
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

          <!-- Document Type -->
          <div class="mb-4 mt-4 w-full max-w-xs mx-auto">
            <label class="block mb-1 font-semibold text-gray-700">Document Type</label>
            <select v-model="documentType" class="border rounded p-2 w-full">
              <option disabled value="">Select Type</option>
              <option value="student">Student Document</option>
              <option value="school">School Document</option>
            </select>
          </div>

          <!-- File Input & Browse -->
          <div class="flex gap-2 justify-center mt-2">
            <input
              type="file"
              :key="fileInputKey"
              id="fileInput"
              class="hidden"
              multiple
              @change="handleFileUpload"
              accept=".pdf,.docx,.xlsx,.xls,.png,.jpg"
            >
            <button
              @click="browseFile"
              class="px-4 py-2 border-2 border-indigo-900 text-indigo-900 font-bold rounded-lg hover:bg-indigo-900 hover:text-white"
            >
              Browse Files
            </button>
          </div>

          <!-- Selected Files -->
          <div v-if="selectedFiles.length" class="selected-files mt-6 w-full text-left">
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

          <!-- Upload Button -->
          <button
            v-if="selectedFiles.length && documentType"
            @click="handleUpload"
            class="upload-btn"
            :disabled="isUploading"
          >
            {{ isUploading ? 'Uploading...' : 'Upload Document' }}
          </button>

          <!-- Upload Message -->
          <p
            v-if="uploadMessage"
            class="message mt-4 p-2 text-green-700 bg-green-100 border border-green-400 rounded-lg shadow-md text-center"
          >
            {{ uploadMessage }}
          </p>
        </div>
      </div>
    </div>

    <!-- Student Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
      <div class="bg-white rounded-lg shadow-lg p-6 w-80">
        <h3 class="text-lg font-semibold mb-4">Categorize Document</h3>
        <select v-model="category" class="border rounded p-2 w-full mb-4">
          <option disabled value="">Select Document Type</option>
          <option>Form 137</option>
          <option>PSA</option>
          <option>ECCRD</option> <!-- ✅ corrected here -->
        </select>
        <input type="text" v-model="lrn" class="border rounded p-2 w-full mb-4" placeholder="Enter LRN">

        <div class="flex justify-end gap-2">
          <button @click="showModal = false" class="px-4 py-2 bg-gray-400 rounded text-white hover:bg-gray-500">
            Cancel
          </button>
          <button @click="uploadFiles" class="px-4 py-2 bg-indigo-600 rounded text-white hover:bg-indigo-700">
            Upload
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Sidebar from "@/Components/Sidebar.vue";
import { router } from '@inertiajs/vue3';

export default {
  components: { Sidebar },
  data() {
    return {
      selectedFiles: [],
      isDragging: false,
      isUploading: false,
      uploadMessage: "",
      uploadSuccess: false,
      showModal: false,
      category: "",
      lrn: "",
      documentType: "",
      fileInputKey: 0,
    };
  },
  methods: {
    browseFile() {
      document.getElementById("fileInput").click();
    },
    handleFileUpload(event) {
      this.selectedFiles = Array.from(event.target.files);
      this.clearMessages();
    },
    removeFile(index) {
      this.selectedFiles.splice(index, 1);
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
    },
    formatFileSize(size) {
      return `${(size / 1024).toFixed(2)} KB`;
    },
    handleUpload() {
      if (this.documentType === 'student') {
        this.showModal = true;
      } else {
        this.uploadFiles();
      }
    },
    uploadFiles() {
      this.isUploading = true;
      const formData = new FormData();

      this.selectedFiles.forEach(file => {
        formData.append('files[]', file);
      });

      formData.append('type', this.documentType);

      if (this.documentType === 'student') {
        formData.append('category', this.category);
        formData.append('lrn', this.lrn);
      }

      router.post('/upload', formData, {
        forceFormData: true,
        onSuccess: () => {
          this.uploadMessage = `${this.selectedFiles.length} file(s) uploaded successfully!`;
          this.resetUploadState();
        },
        onError: () => {
          this.uploadMessage = "Upload failed.";
          this.isUploading = false;
        }
      });
    },
    resetUploadState() {
      this.selectedFiles = [];
      this.isUploading = false;
      this.uploadSuccess = true;
      this.showModal = false;
      this.category = "";
      this.lrn = "";
      this.documentType = "";
      this.fileInputKey++; // resets input file
      document.getElementById("fileInput").value = null;

      setTimeout(() => {
        this.uploadMessage = "";
      }, 3000);
    },
    clearMessages() {
      this.uploadSuccess = false;
      this.uploadMessage = "";
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

.upload-container.dragging {
  border-color: #2563eb;
}

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

.upload-btn:hover {
  background-color: #1e40af;
}

.upload-btn:disabled {
  background-color: #a0aec0;
  cursor: not-allowed;
}

.selected-files {
  width: 100%;
  margin-top: 1.5rem;
}

.file-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.message {
  text-align: center;
}
</style>
