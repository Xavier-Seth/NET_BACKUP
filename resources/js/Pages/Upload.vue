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
            <p class="text-gray-500 text-sm mt-2">Supports: PDF, DOCX, XLSX, XLS, PNG, JPG, JPEG, DOC</p>
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

            <!-- Detected / Override Info -->
            <div
              v-if="selectedFiles.length && !isScanning"
              class="mt-4 w-full max-w-xs mx-auto text-left text-sm text-gray-800"
            >
              <div
                v-if="!detectedCategory && !detectedTeacher && showCategorySelection && !overrideMode"
                class="mb-2 text-red-600 font-semibold flex items-center gap-1"
              >
                <i class="bi bi-x-circle-fill"></i>
                No category or teacher detected. Please select the category manually:
              </div>

              <div class="mb-2 flex items-center gap-2" v-if="detectedCategory || overrideMode">
                <span
                  class="inline-flex items-center gap-2 text-xs font-semibold px-2 py-1 rounded"
                  :class="overrideMode ? 'bg-yellow-100 text-yellow-800 border border-yellow-300' : 'bg-green-100 text-green-800 border border-green-300'"
                >
                  <i :class="overrideMode ? 'bi bi-pencil-square' : 'bi bi-check2-circle'"></i>
                  {{ overrideMode ? 'Overridden classification' : 'Detected classification' }}
                </span>

                <button
                  v-if="!overrideMode && detectedCategory"
                  @click="startReclassify"
                  class="text-indigo-700 hover:text-indigo-900 text-xs font-semibold underline"
                >
                  Reclassify
                </button>
                <button
                  v-if="overrideMode"
                  @click="resetToDetected"
                  class="text-gray-600 hover:text-gray-900 text-xs font-semibold underline"
                >
                  Reset to detected
                </button>
              </div>

              <div v-if="canonicalCategory" class="text-green-700 font-semibold mb-2">
                Category: {{ canonicalCategory }}
              </div>

              <div v-if="showCategorySelection || overrideMode" class="mb-4">
                <select v-model.number="category_id" class="border rounded p-2 w-full">
                  <option disabled :value="null">Select Category</option>

                  <optgroup label="📚 Teacher-Related Documents">
                    <option
                      v-for="c in categories.filter(c => isTeacherDocByName(c.name))"
                      :key="`teacher-${c.id}`"
                      :value="c.id"
                    >
                      {{ c.name }}
                    </option>
                  </optgroup>

                  <optgroup label="🏫 School Property Documents">
                    <option
                      v-for="c in categories.filter(c => !isTeacherDocByName(c.name))"
                      :key="`school-${c.id}`"
                      :value="c.id"
                    >
                      {{ c.name }}
                    </option>
                  </optgroup>
                </select>
              </div>

              <div v-if="requiresTeacher">
                <p v-if="!overrideMode && detectedCategory && !detectedTeacher" class="text-red-600">
                  This document “{{ detectedCategory }}” belongs to a teacher. Please select the teacher:
                </p>

                <div v-if="overrideMode || !detectedTeacher" class="mt-2">
                  <select v-model.number="teacher_id" class="border rounded p-2 w-full">
                    <option disabled :value="null">Select Teacher</option>
                    <option v-for="t in teachers" :key="t.id" :value="t.id">
                      {{ t.full_name }}
                    </option>
                  </select>
                </div>

                <p v-if="!overrideMode && detectedTeacher" class="mt-2 text-green-700">
                  <strong>Detected Teacher:</strong> {{ detectedTeacher }}
                </p>
              </div>
            </div>

            <div class="flex gap-2 justify-center mt-4">
              <input
                id="fileInput"
                ref="fileInput"
                type="file"
                :key="fileInputKey"
                class="hidden"
                multiple
                @change="handleFileUpload"
                accept=".pdf,.docx,.xlsx,.xls,.png,.jpg,.jpeg,.doc"
              />
              <button
                @click="browseFile"
                class="px-4 py-2 border-2 border-indigo-900 text-indigo-900 font-bold rounded-lg hover:bg-indigo-900 hover:text-white"
              >
                Browse Files
              </button>
            </div>

            <div v-if="isScanning || isUploading" class="spinner mt-6"></div>

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
                  <button @click="removeFile(i)" class="text-red-500 hover:text-red-700" title="Remove">
                    <i class="bi bi-trash"></i>
                  </button>
                </li>
              </ul>
            </div>

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

    <!-- Duplicate Modal: Continue / Cancel only -->
    <DuplicatePromptModal
      v-if="showDuplicateModal"
      :existing-name="duplicateDocName"
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
      teacher_id: null,
      category_id: null,
      fileInputKey: 0,
      detectedTeacher: null,
      detectedCategory: null,
      showCategorySelection: false,
      flaskOffline: false,
      successMessage: "",

      showDuplicateModal: false,
      duplicateDocName: "",
      pendingFormData: null,

      lastOcrText: "",
      overrideMode: false,

      teacherDocumentTypes: [
        "Work Experience Sheet",
        "Personal Data Sheet",
        "Oath of Office",
        "Certification of Assumption to Duty",
        "Transcript of Records",
        "TOR",
        "Appointment Form",
        "Daily Time Record"
      ]
    };
  },
  computed: {
    canonicalCategory() {
      if (this.overrideMode) {
        return this.canonicalCategoryNameById(this.category_id) || "";
      }
      return (this.detectedCategory && String(this.detectedCategory).trim())
        || this.canonicalCategoryNameById(this.category_id)
        || "";
    },
    requiresTeacher() {
      return this.isTeacherDocByName(this.canonicalCategory);
    }
  },
  watch: {
    category_id(newVal) {
      this.category_id = newVal === null ? null : Number(newVal);
      if (this.overrideMode && !this.isTeacherDocByName(this.canonicalCategoryNameById(this.category_id))) {
        this.teacher_id = null;
      }
    }
  },
  methods: {
    // ── Helpers ─────────────────────────────────────────────
    normalizeCategoryName(name) {
      if (!name) return null;
      const map = {
        TOR: "Transcript of Records",
        PDS: "Personal Data Sheet",
        COAD: "Certification of Assumption to Duty",
        WES: "Work Experience Sheet",
        DTR: "Daily Time Record",
        APPOINTMENT: "Appointment Form",
      };
      const raw = String(name).trim();
      return map[raw] || map[raw.toUpperCase()] || raw;
    },
    isTeacherDocByName(categoryName) {
      if (!categoryName) return false;
      const set = new Set(this.teacherDocumentTypes.map(n => n.trim().toLowerCase()));
      return set.has(String(categoryName).trim().toLowerCase());
    },
    canonicalCategoryNameById(id) {
      const c = this.categories.find(x => x.id === Number(id));
      return c ? String(c.name).trim() : "";
    },

    // ── UI actions ─────────────────────────────────────────
    browseFile() {
      const el = this.$refs.fileInput;
      if (el) {
        el.value = null;
        el.click();
      }
    },
    handleFileUpload(e) {
      const files = Array.from(e.target.files || []);
      this.selectedFiles = files;
      this.successMessage = "";
      this.overrideMode = false;
      if (this.selectedFiles.length) this.scanFile(this.selectedFiles[0]);
      e.target.value = null;
    },
    async scanFile(file) {
      this.isScanning = true;
      const formData = new FormData();
      formData.append("file", file);
      try {
        const res = await axios.post("/upload/scan", formData);

        this.detectedTeacher = res.data.teacher || null;
        const serverCat = res.data.category || null;
        this.detectedCategory = this.normalizeCategoryName(serverCat);
        this.showCategorySelection = !this.detectedCategory;

        if (res.data.category_id) {
          this.category_id = Number(res.data.category_id);
        } else if (this.detectedCategory) {
          const c = this.categories.find(
            x => x.name.trim().toLowerCase() === this.detectedCategory.trim().toLowerCase()
          );
          if (c) this.category_id = c.id;
        }

        if (this.detectedTeacher) {
          const t = this.teachers.find(x => x.full_name === this.detectedTeacher);
          if (t) this.teacher_id = t.id;
        }

        this.lastOcrText = res.data.text || "";
        this.flaskOffline = !!res.data.fallback_used;
      } catch (err) {
        console.error(err);
      } finally {
        this.isScanning = false;
      }
    },
    removeFile(i) {
      this.selectedFiles.splice(i, 1);
      if (this.selectedFiles.length === 0) {
        this.fileInputKey++;
        this.teacher_id = null;
        this.category_id = null;
        this.lastOcrText = "";
        this.detectedTeacher = null;
        this.detectedCategory = null;
        this.overrideMode = false;
        this.showCategorySelection = false;
      }
    },
    handleDragOver(e) { e.preventDefault(); this.isDragging = true; },
    handleDragLeave() { this.isDragging = false; },
    handleDrop(e) {
      e.preventDefault();
      this.isDragging = false;
      this.selectedFiles = Array.from(e.dataTransfer.files || []);
      this.overrideMode = false;
      if (this.selectedFiles.length) this.scanFile(this.selectedFiles[0]);
    },
    formatFileSize(size) {
      return `${(size / 1024).toFixed(2)} KB`;
    },

    // ── Reclassify controls ────────────────────────────────
    startReclassify() {
      this.overrideMode = true;
      if (this.detectedCategory && !this.category_id) {
        const c = this.categories.find(
          x => x.name.trim().toLowerCase() === this.detectedCategory.trim().toLowerCase()
        );
        if (c) this.category_id = c.id;
      }
    },
    resetToDetected() {
      this.overrideMode = false;
      if (this.detectedCategory) {
        const c = this.categories.find(
          x => x.name.trim().toLowerCase() === this.detectedCategory.trim().toLowerCase()
        );
        this.category_id = c ? c.id : null;
      } else {
        this.category_id = null;
      }
      if (this.detectedTeacher) {
        const t = this.teachers.find(x => x.full_name === this.detectedTeacher);
        this.teacher_id = t ? t.id : null;
      } else {
        this.teacher_id = null;
      }
    },

    // ── Upload ─────────────────────────────────────────────
    async uploadFiles() {
      if (this.requiresTeacher && !this.teacher_id) {
        return alert("❌ Please select a teacher.");
      }
      if ((this.showCategorySelection || this.overrideMode) && !this.category_id) {
        return alert("❌ Please select a category.");
      }

      this.isUploading = true;
      const formData = new FormData();
      this.selectedFiles.forEach(f => formData.append("files[]", f));
      if (this.teacher_id != null) formData.append("teacher_id", this.teacher_id);
      if (this.category_id != null) formData.append("category_id", this.category_id);
      formData.append("skip_ocr", "1");
      formData.append("scanned_text", this.lastOcrText || "");
      if (this.overrideMode) formData.append("override", "1");

      try {
        const resp = await axios.post("/upload", formData);

        if (resp?.data?.duplicate) {
          // Show prompt with Continue (allow duplicate) or Cancel
          this.duplicateDocName = resp.data.existing_document_name || "";
          this.pendingFormData = formData;
          this.showDuplicateModal = true;
          return;
        }

        if (resp?.data?.success) {
          this.resetAfterUpload();
        } else {
          alert("❌ Upload failed.");
        }
      } catch (err) {
        console.error(err);
        alert("❌ Upload failed.");
      } finally {
        this.isUploading = false;
      }
    },

    async handleContinue() {
      // Repost with allow_duplicate=1
      if (!this.pendingFormData) return;
      this.pendingFormData.set("allow_duplicate", "1");
      try {
        const resp = await axios.post("/upload", this.pendingFormData);

        if (resp?.data?.duplicate) {
          // Very rare: backend still flags duplicate → let user know
          alert("Server still detected a duplicate. Please try again or contact admin.");
          return;
        }

        if (resp?.data?.success) {
          this.resetAfterUpload();
        } else {
          alert("❌ Upload failed.");
        }
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
      this.teacher_id = null;
      this.category_id = null;
      this.lastOcrText = "";
      this.detectedTeacher = null;
      this.detectedCategory = null;
      this.overrideMode = false;
      this.showCategorySelection = false;
      this.successMessage = "✅ Document uploaded successfully!";
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
.upload-container.dragging { border-color: #2563eb; }
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
.upload-btn:hover { background: #1e40af; }
.upload-btn:disabled { background: #a0aec0; cursor: not-allowed; }
.spinner {
  margin-top: 1rem; width: 2.5rem; height: 2.5rem;
  border: 4px solid #ccc; border-top-color: #2563eb;
  border-radius: 50%; animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
