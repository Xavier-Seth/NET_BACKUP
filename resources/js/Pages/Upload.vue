<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col items-center justify-center relative">
      <div class="brand">
        <img src="/images/school_logo.png" alt="School Logo" class="school-logo" />
        <h1>DocuNet</h1>
      </div>

      <div 
        class="upload-container"
        @dragover.prevent="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
      >
        <div class="upload-box bg-white p-10 rounded-lg shadow-lg text-center">
          <i class="bi bi-cloud-arrow-up-fill upload-icon"></i>
          <h2 class="text-xl font-bold mt-4">Drag and drop your files</h2>
          <p class="text-gray-500 text-sm mt-2">Files supported: JPG, PNG, GIF, PDF, DOCX</p>
          <p class="text-gray-500 text-sm mt-2">Or</p>
          <input type="file" id="fileInput" class="hidden" multiple @change="handleFileUpload">
          <button @click="browseFile" class="mt-4 px-6 py-2 border-2 border-indigo-900 text-indigo-900 font-bold rounded-lg hover:bg-indigo-900 hover:text-white">
            Browse files
          </button>
        </div>

        <div v-if="selectedFiles.length" class="selected-files bg-white p-4 rounded-lg shadow-lg">
          <p class="text-gray-700 font-bold">Selected Files:</p>
          <ul class="file-list">
            <li v-for="(file, index) in selectedFiles" :key="index" class="text-gray-700 flex items-center justify-between border-b py-2">
              <span>{{ file.name }}</span>
              <button @click="removeFile(index)" class="text-red-500 hover:text-red-700">
                <i class="bi bi-trash"></i>
              </button>
            </li>
          </ul>
        </div>

        <button v-if="selectedFiles.length" class="upload-btn">
          Upload Document
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import Sidebar from "@/Components/Sidebar.vue"; // Import Sidebar Component

export default {
  components: {
    Sidebar, // Register Sidebar Component
  },
  name: "Upload",
  data() {
    return {
      selectedFiles: [],
      isDragging: false
    };
  },
  methods: {
    browseFile() {
      document.getElementById("fileInput").click();
    },
    handleFileUpload(event) {
      this.selectedFiles = [...this.selectedFiles, ...Array.from(event.target.files)];
    },
    removeFile(index) {
      this.selectedFiles.splice(index, 1);
    },
    // Handle drag and drop
    handleDragOver(event) {
      event.preventDefault();
      this.isDragging = true;
    },
    handleDragLeave() {
      this.isDragging = false;
    },
    handleDrop(event) {
      event.preventDefault();
      this.isDragging = false;

      const droppedFiles = event.dataTransfer.files;
      if (droppedFiles.length) {
        this.selectedFiles = [...this.selectedFiles, ...Array.from(droppedFiles)];
      }
    }
  }
};
</script>

<style scoped>

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
  border: 2px dashed #ccc;
  transition: border-color 0.3s ease;
}

/* Change border color when dragging */
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

.file-list {
  max-height: none;
  overflow-y: visible;
  list-style: none;
  padding: 0;
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

.upload-icon {
  font-size: 90px;
  color: #333;
}

/* Upload button */
.upload-btn {
  margin-top: 30px;
  align-self: flex-start;
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
</style>
