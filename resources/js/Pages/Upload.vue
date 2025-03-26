<template>
  <div class="flex h-screen bg-gray-100">
    <Sidebar />

    <div class="flex-1 flex flex-col items-center justify-center relative">
      <div class="brand">
        <img src="/images/school_logo.png" alt="School Logo" class="school-logo" />
        <h1>Rizal Central School</h1>
      </div>

      <div 
        class="upload-container" 
        :class="{ dragging: isDragging }"
        @dragover.prevent="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
      >
        <div class="upload-box bg-white p-10 rounded-lg shadow-lg text-center">
          <i class="bi bi-cloud-arrow-up-fill upload-icon"></i>
          <h2 class="text-xl font-bold mt-4">Drag and drop your files</h2>
          <p class="text-gray-500 text-sm mt-2">Supports: PDF, DOCX, XLSX, XLS, PNG, JPG</p>
          <p class="text-gray-500 text-sm mt-2">Or</p>

          <div class="flex gap-2 justify-center mt-4">
            <input 
              type="file" 
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
        </div>

        <div v-if="selectedFiles.length" class="selected-files bg-white p-4 rounded-lg shadow-lg">
          <p class="text-gray-700 font-bold">Selected Files:</p>
          <ul class="file-list">
            <li v-for="(file, index) in selectedFiles" :key="index" class="text-gray-700 flex items-center justify-between border-b py-2">
              <span>{{ file.name }} ({{ formatFileSize(file.size) }})</span>
              <button @click="removeFile(index)" class="text-red-500 hover:text-red-700">
                <i class="bi bi-trash"></i>
              </button>
            </li>
          </ul>
        </div>

        <button 
          v-if="selectedFiles.length && !uploadSuccess" 
          @click="showModal=true" 
          class="upload-btn"
          :disabled="isUploading"
        >
          {{ isUploading ? 'Uploading...' : 'Upload Document' }}
        </button>

        <p v-if="uploadMessage" class="message mt-4 p-2 text-green-700 bg-green-100 border border-green-400 rounded-lg shadow-md">
          {{ uploadMessage }}
        </p>
      </div>

      <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
        <div class="bg-white rounded-lg shadow-lg p-6 w-80">
          <h3 class="text-lg font-semibold mb-4">Categorize Document</h3>
          <select v-model="category" class="border rounded p-2 w-full mb-4">
            <option disabled value="">Select Document Type</option>
            <option>Form 137</option>
            <option>PSA</option>
            <option>ECCRD</option>
          </select>

          <input type="text" v-model="lrn" class="border rounded p-2 w-full mb-4" placeholder="Enter LRN">

          <div class="flex justify-end gap-2">
            <button @click="showModal=false" class="px-4 py-2 bg-gray-400 rounded text-white hover:bg-gray-500">
              Cancel
            </button>
            <button @click="uploadFiles" class="px-4 py-2 bg-indigo-600 rounded text-white hover:bg-indigo-700">
              Upload
            </button>
          </div>
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
    };
  },
  methods: {
    browseFile() {
      document.getElementById("fileInput").click();
    },
    handleFileUpload(event) {
      this.selectedFiles = Array.from(event.target.files);
      this.uploadSuccess = false;
      this.isUploading = false;
      this.uploadMessage = "";
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
      this.uploadSuccess = false;
      this.isUploading = false;
      this.uploadMessage = "";
    },
    formatFileSize(size) {
      return `${(size / 1024).toFixed(2)} KB`;
    },
    uploadFiles() {
      this.isUploading = true;
      const formData = new FormData();
      this.selectedFiles.forEach(file => formData.append('files[]', file));
      formData.append('category', this.category);
      formData.append('lrn', this.lrn);

      router.post('/upload', formData, {
        forceFormData: true,
        onSuccess: () => {
          this.selectedFiles = [];
          this.uploadMessage = "Uploaded successfully!";
          this.uploadSuccess = false;
          this.showModal = false;
          this.category = "";
          this.lrn = "";
          this.isUploading = false;
        },
        onError: () => {
          this.uploadMessage = "Upload error.";
          this.isUploading = false;
          this.uploadSuccess = false;
        }
      });
    }
  }
};
</script>

<style scoped>
.message {
  margin-left: 340px;
}
.upload-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.upload-box {
  width: 700px;
  margin-left: 300px;
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 3px dashed #ccc;
  transition: border-color 0.3s ease;
}

.upload-box:hover,
.upload-container.dragging .upload-box {
  border-color: #2563eb;
}

.selected-files {
  width: 700px;
  margin-left: 300px;
  max-height: 120px;
  overflow-y: auto;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  position: absolute;
  top: 20px;
  left: 250px;
}

.school-logo {
  width: 50px;
  height: auto;
}

.upload-btn {
  display: block;
  margin: 20px auto;
  margin-left: 550px;
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
</style>
