<template>
  <div>
    <div class="flex bg-gray-100 min-h-screen">
      <Sidebar />
      <div class="flex-1 bg-gray-100 overflow-y-auto ml-[200px]">
        <div class="flex justify-center items-center min-h-screen px-4 py-10">
          <div
            class="upload-container w-full max-w-4xl mx-auto"
            :class="{ dragging: isDragging }"
            @dragover.prevent="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
          >
            <i class="bi bi-cloud-arrow-up-fill upload-icon text-2xl md:text-3xl"></i>
            <h2 class="text-lg md:text-xl font-bold mt-3 md:mt-4">Drag and drop your files</h2>
            <p class="text-gray-500 text-xs md:text-sm mt-2">
              Supports: PDF, DOCX, XLSX, XLS, PNG, JPG, JPEG, DOC
            </p>
            <p class="text-gray-500 text-xs md:text-sm mt-2">Or</p>

            <p v-if="flaskOffline" class="text-red-600 font-bold mt-2 text-sm">
              ⚠️ Flask app is offline. Using fallback mode (no OCR).
            </p>

            <p
              v-if="successMessage"
              class="text-green-700 font-semibold bg-green-100 border border-green-300 rounded p-2 md:p-3 mt-4 w-full text-center transition-all duration-500 text-sm"
            >
              {{ successMessage }}
            </p>

            <!-- Browse -->
            <div class="flex gap-2 justify-center mt-4">
              <input
                id="fileInput"
                ref="fileInput"
                type="file"
                :key="fileInputKey"
                class="hidden"
                multiple
                @change="handleFileUpload"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
              />
              <button
                @click="!busy && browseFile()"
                class="px-4 py-2 border-2 border-indigo-900 text-indigo-900 font-bold rounded-lg hover:bg-indigo-900 hover:text-white text-sm"
                :disabled="busy"
                :class="busy ? 'opacity-50 cursor-not-allowed' : ''"
              >
                Browse Files
              </button>
            </div>

            <!-- Global spinner -->
            <div v-if="busy" class="spinner mt-5"></div>

            <!-- File list (paginated) -->
            <div v-if="files.length" class="selected-files mt-5 w-full text-left">
              <div class="flex items-center justify-between mb-2">
                <p class="text-gray-700 font-bold text-sm md:text-base">Selected Files:</p>
                <div class="text-xs text-gray-600">{{ showingRangeText }}</div>
              </div>

              <ul class="file-list">
                <li
                  v-for="(it, i) in pagedFiles"
                  :key="i"
                  class="file-card"
                  :class="busy ? 'opacity-70' : ''"
                >
                  <div class="flex items-center justify-between">
                    <div class="filename">
                      {{ it.file.name }} ({{ formatFileSize(it.file.size) }})
                      <span v-if="it.state==='scanning'" class="ml-2 text-[11px] text-indigo-700">scanning…</span>
                      <span v-if="it.state==='ready'" class="ml-2 text-[11px] text-green-700">ready</span>
                      <span v-if="it.state==='error'" class="ml-2 text-[11px] text-red-700">scan failed</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <button
                        v-if="it.state!=='scanning'"
                        @click="!busy && scanOneLocal(i)"
                        class="icon-btn text-indigo-700 hover:text-indigo-900"
                        title="Re-scan this file"
                        :disabled="busy"
                        :class="busy ? 'opacity-50 cursor-not-allowed' : ''"
                      >
                        <i class="bi bi-arrow-clockwise"></i>
                      </button>
                      <button
                        @click="removeLocal(i)"
                        class="icon-btn text-red-500 hover:text-red-700"
                        title="Remove"
                        :disabled="busy"
                        :class="busy ? 'opacity-50 cursor-not-allowed' : ''"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>

                  <!-- Status chip + override toggle -->
                  <div class="mt-1.5 flex flex-wrap items-center gap-2">
                    <span class="chip" :class="it.override ? 'chip-warn' : 'chip-ok'">
                      <i :class="it.override ? 'bi bi-pencil-square' : 'bi bi-check2-circle'"></i>
                      <span class="ml-1">{{ it.override ? 'Overridden classification' : 'Detected classification' }}</span>
                    </span>

                    <button
                      v-if="!it.override"
                      @click="!busy && (it.override = true)"
                      class="link-btn"
                      :disabled="busy"
                      :class="busy ? 'opacity-50 cursor-not-allowed' : ''"
                    >
                      Reclassify
                    </button>
                    <button
                      v-else
                      @click="!busy && resetItemToDetected(it)"
                      class="link-btn text-gray-600 hover:text-gray-900"
                      :disabled="busy"
                      :class="busy ? 'opacity-50 cursor-not-allowed' : ''"
                    >
                      Reset to detected
                    </button>
                  </div>

                  <!-- Detected view (with teacher select when required) -->
                  <div v-if="!it.override" class="mt-2 grid gap-1">
                    <div v-if="it.detectedCategory" class="text-green-700 font-semibold text-[13px]">
                      Category: {{ it.detectedCategory }}
                    </div>

                    <div v-if="it.requiresTeacher && !it.detectedTeacher" class="text-red-600 text-[12px]">
                      This document requires a teacher. Please select one below.
                    </div>

                    <div v-if="it.detectedTeacher" class="text-green-700 text-[12px]">
                      <strong>Detected Teacher:</strong> {{ it.detectedTeacher }}
                    </div>

                    <div v-if="it.requiresTeacher && !it.detectedTeacher" class="mt-1">
                      <label class="small-label">Teacher</label>
                      <select
                        v-model.number="it.teacher_id"
                        class="small-select"
                        :disabled="busy"
                      >
                        <option disabled :value="null">Select Teacher</option>
                        <option v-for="t in teachers" :key="t.id" :value="t.id">
                          {{ t.full_name }}
                        </option>
                      </select>
                    </div>
                  </div>

                  <!-- Override (Reclassify) controls -->
                  <div v-if="it.override" class="mt-2 grid gap-2 md:grid-cols-2">
                    <div>
                      <label class="small-label">Category</label>
                      <select
                        v-model.number="it.category_id"
                        class="small-select"
                        :disabled="busy"
                      >
                        <option disabled :value="null">Select Category</option>
                        <optgroup label="📚 Teacher-Related Documents">
                          <option
                            v-for="c in categories.filter(c => isTeacherDocByName(c.name))"
                            :key="'t-'+c.id"
                            :value="c.id"
                          >
                            {{ c.name }}
                          </option>
                        </optgroup>
                        <optgroup label="🏫 School Property Documents">
                          <option
                            v-for="c in categories.filter(c => !isTeacherDocByName(c.name))"
                            :key="'s-'+c.id"
                            :value="c.id"
                          >
                            {{ c.name }}
                          </option>
                        </optgroup>
                      </select>
                    </div>

                    <div v-if="isTeacherDocByName(canonicalCategoryNameById(it.category_id))">
                      <label class="small-label">Teacher</label>
                      <select
                        v-model.number="it.teacher_id"
                        class="small-select"
                        :disabled="busy"
                      >
                        <option disabled :value="null">Select Teacher</option>
                        <option v-for="t in teachers" :key="t.id" :value="t.id">
                          {{ t.full_name }}
                        </option>
                      </select>
                    </div>
                  </div>

                  <!-- Validation hint -->
                  <div class="mt-1 text-[12px] text-gray-500">
                    <span
                      v-if="it.requiresTeacher && (!it.teacher_id && (it.override || !it.detectedTeacher))"
                      class="text-red-600"
                    >
                      * Teacher is required for this category.
                    </span>
                  </div>
                </li>
              </ul>

              <!-- Pagination controls -->
              <div class="mt-2 flex items-center justify-between text-sm text-gray-600">
                <span>{{ showingRangeText }}</span>
                <div class="flex items-center gap-2">
                  <button
                    class="px-2 py-1 border rounded"
                    :disabled="currentPage===1"
                    @click="prevPage()"
                  >
                    Prev
                  </button>
                  <span>Page {{ currentPage }} / {{ totalPages }}</span>
                  <button
                    class="px-2 py-1 border rounded"
                    :disabled="currentPage===totalPages"
                    @click="nextPage()"
                  >
                    Next
                  </button>
                </div>
              </div>
            </div>

            <!-- Upload button -->
            <button
              v-if="files.length"
              @click="uploadFiles"
              class="upload-btn"
              :disabled="busy"
              :class="busy ? 'opacity-50 cursor-not-allowed' : ''"
            >
              {{ isUploading ? 'Uploading...' : (isScanning ? 'Please wait…' : 'Upload All') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Duplicate Modal -->
    <DuplicatePromptModal
      v-if="showDuplicateModal"
      :existing-name="duplicateDocName"
      :busy="duplicateBusy"
      @continue="handleContinueDuplicate"
      @cancel="handleCancelDuplicate"
    />

    <!-- Uncategorized Modal -->
    <div v-if="showUncatModal" class="modal-overlay" @click.self="closeUncatModal">
      <div class="modal-card">
        <div class="modal-head">
          <h3>Action needed: Uncategorized file(s)</h3>
        </div>
        <div class="modal-body">
          <p class="text-sm text-gray-700">
            The following file(s) are still <strong>Uncategorized</strong> or missing a category.
            Please click <em>Reclassify</em> and choose the correct <strong>Category</strong>.
            If the category is teacher-related, also choose a <strong>Teacher</strong>.
          </p>
          <ul class="mt-3 list-disc pl-5 text-sm text-gray-800">
            <li v-for="n in uncategorizedList" :key="n">{{ n }}</li>
          </ul>
        </div>
        <div class="modal-foot">
          <button class="btn-secondary" @click="reclassifyUncategorized">Reclassify Now</button>
          <button class="btn-primary" @click="closeUncatModal">OK</button>
        </div>
      </div>
    </div>

    <!-- Missing Teacher Modal -->
    <div v-if="showTeacherReqModal" class="modal-overlay" @click.self="closeTeacherReqModal">
      <div class="modal-card">
        <div class="modal-head">
          <h3>Action needed: Missing teacher</h3>
        </div>
        <div class="modal-body">
          <p class="text-sm text-gray-700">
            The following file(s) are in a <strong>teacher-related category</strong> but no
            <strong>Teacher</strong> is selected. Please choose a teacher for each.
          </p>
          <ul class="mt-3 list-disc pl-5 text-sm text-gray-800">
            <li v-for="n in teacherRequiredList" :key="n">{{ n }}</li>
          </ul>
        </div>
        <div class="modal-foot">
          <button class="btn-primary" @click="closeTeacherReqModal">OK</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Sidebar from "@/Components/Sidebar.vue";
import DuplicatePromptModal from "@/Components/DuplicatePromptModal.vue";
import axios from "axios";

export default {
  name: "Upload",
  props: {
    teachers: { type: Array, required: true },
    categories: { type: Array, required: true },
    // NEW: provided by backend (CategorizationService->getTeacherDocumentTypes)
    teacherDocumentTypes: { type: Array, required: true }
  },
  components: { Sidebar, DuplicatePromptModal },
  data() {
    return {
      files: [],
      isDragging: false,
      isScanning: false,
      isUploading: false,
      fileInputKey: 0,
      flaskOffline: false,
      successMessage: "",
      showDuplicateModal: false,
      duplicateDocName: "",
      duplicateBusy: false,
      duplicateIndex: null,
      // Uncategorized modal
      showUncatModal: false,
      uncategorizedList: [],
      // Missing-teacher modal
      showTeacherReqModal: false,
      teacherRequiredList: [],
      // ---------- Pagination ----------
      pageSize: 10,
      currentPage: 1,
    };
  },
  computed: {
    busy() {
      return this.isScanning || this.isUploading || this.showDuplicateModal || this.duplicateBusy;
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.files.length / this.pageSize));
    },
    pagedFiles() {
      const start = (this.currentPage - 1) * this.pageSize;
      return this.files.slice(start, start + this.pageSize);
    },
    showingRangeText() {
      if (!this.files.length) return "0–0 of 0";
      const start = (this.currentPage - 1) * this.pageSize + 1;
      const end = Math.min(this.currentPage * this.pageSize, this.files.length);
      return `${start}–${end} of ${this.files.length}`;
    },
  },
  watch: {
    files() {
      if (this.currentPage > this.totalPages) {
        this.currentPage = this.totalPages;
      }
    }
  },
  methods: {
    // ---------- pagination controls ----------
    setPage(n) {
      const p = Number(n);
      if (Number.isFinite(p)) {
        this.currentPage = Math.min(Math.max(1, p), this.totalPages);
      }
    },
    nextPage() { this.setPage(this.currentPage + 1); },
    prevPage() { this.setPage(this.currentPage - 1); },
    localToGlobalIndex(iLocal) {
      return (this.currentPage - 1) * this.pageSize + iLocal;
    },

    // ---------- new helpers to avoid replacing the files array ----------
    makeItem(f) {
      return {
        file: f,
        state: "queued",
        detectedCategory: null,
        detectedTeacher: null,
        category_id: null,
        teacher_id: null,
        scanned_text: "",
        override: false,
        requiresTeacher: false,
      };
    },
    addFiles(fileList) {
      const added = [];
      Array.from(fileList || []).forEach(f => {
        const exists = this.files.some(it => it.file.name === f.name && it.file.size === f.size);
        if (!exists) added.push(this.makeItem(f));
      });
      if (added.length) {
        this.files.push(...added);
        this.successMessage = "";
        this.$nextTick(() => {
          this.setPage(this.totalPages);
          this.scanQueued();
        });
      }
    },

    // ---------- UI ----------
    browseFile() {
      const el = this.$refs.fileInput;
      if (el) { el.value = null; el.click(); }
    },
    handleFileUpload(e) {
      this.addFiles(e.target.files);
      e.target.value = null;
    },
    handleDragOver(evt) { evt.preventDefault(); this.isDragging = true; },
    handleDragLeave() { this.isDragging = false; },
    handleDrop(evt) {
      evt.preventDefault();
      this.isDragging = false;
      this.addFiles(evt.dataTransfer.files);
    },

    removeLocal(iLocal) {
      if (this.busy) return;
      const index = this.localToGlobalIndex(iLocal);
      this.files.splice(index, 1);
      if (!this.files.length) this.resetAll();
    },
    scanOneLocal(iLocal) {
      const index = this.localToGlobalIndex(iLocal);
      return this.scanOne(index);
    },

    // ---------- Category helpers ----------
    normalizeCategoryName(name) {
      if (!name) return null;
      const map = {
        // existing
        TOR: "Transcript of Records",
        PDS: "Personal Data Sheet",
        COAD: "Certification of Assumption to Duty",
        WES: "Work Experience Sheet",
        DTR: "Daily Time Record",
        APPOINTMENT: "Appointment Form",
        // new categories / aliases
        SALN: "SAL-N",
        "SAL-N": "SAL-N",
        "STATEMENT OF ASSETS, LIABILITIES AND NET WORTH": "SAL-N",
        "SERVICE CREDIT": "Service credit ledgers",
        "SERVICE CREDITS": "Service credit ledgers",
        "CREDIT LEDGER": "Service credit ledgers",
        "LEDGER OF CREDITS": "Service credit ledgers",
        "LEAVE CREDITS": "Service credit ledgers",
        IPCRF: "IPCRF",
        NOSI: "NOSI",
        NOSA: "NOSA",
        "TRAVEL ORDER": "Travel order",
        "AUTHORITY TO TRAVEL": "Travel order",
      };
      const raw = String(name).trim();
      return map[raw] || map[raw.toUpperCase()] || raw;
    },
    isTeacherDocByName(categoryName) {
      if (!categoryName) return false;
      const set = new Set(this.teacherDocumentTypes.map(n => n.trim().toLowerCase()));
      return set.has(String(categoryName).trim().toLowerCase());
    },
    isUncategorized(catName) {
      return String(catName || "").trim().toLowerCase() === "uncategorized";
    },
    canonicalCategoryNameById(id) {
      const c = this.categories.find(x => x.id === Number(id));
      return c ? String(c.name).trim() : "";
    },
    requiresTeacherFor(catName) {
      if (!catName || this.isUncategorized(catName)) return false;
      return this.isTeacherDocByName(catName);
    },

    // ---------- Scanning ----------
    async scanQueued() {
      const toScan = this.files
        .map((it, i) => ({ it, i }))
        .filter(x => x.it.state === "queued");

      if (!toScan.length) return;

      this.isScanning = true;
      try {
        for (const { i } of toScan) {
          await this.scanOne(i);
        }
      } finally {
        this.isScanning = false;
      }
    },

    async scanOne(index) {
      const it = this.files[index];
      if (!it) return;
      it.state = "scanning";
      const fd = new FormData();
      fd.append("file", it.file);
      try {
        const res = await axios.post("/upload/scan", fd);
        const serverCat = this.normalizeCategoryName(res?.data?.category || null);
        it.detectedCategory = serverCat || null;
        it.detectedTeacher = res?.data?.teacher || null;
        it.scanned_text = res?.data?.text || "";
        this.flaskOffline = !!res?.data?.fallback_used;

        if (serverCat) {
          const c = this.categories.find(x => x.name.trim().toLowerCase() === serverCat.trim().toLowerCase());
          if (c) it.category_id = c.id;
        }
        if (it.detectedTeacher) {
          const t = this.teachers.find(x => x.full_name === it.detectedTeacher);
          if (t) it.teacher_id = t.id;
        }

        const catName = it.detectedCategory || this.canonicalCategoryNameById(it.category_id);
        it.requiresTeacher = this.requiresTeacherFor(catName);
        it.state = "ready";
      } catch (e) {
        console.error("scanOne failed", e);
        it.state = "error";
      }
    },
    resetItemToDetected(it) {
      it.override = false;
      if (it.detectedCategory) {
        const c = this.categories.find(
          x => x.name.trim().toLowerCase() === it.detectedCategory.trim().toLowerCase()
        );
        it.category_id = c ? c.id : null;
      } else {
        it.category_id = null;
      }
      if (it.detectedTeacher) {
        const t = this.teachers.find(x => x.full_name === it.detectedTeacher);
        it.teacher_id = t ? t.id : null;
      } else {
        it.teacher_id = null;
      }
      const catName = it.detectedCategory || this.canonicalCategoryNameById(it.category_id);
      it.requiresTeacher = this.requiresTeacherFor(catName);
    },

    // ---------- Upload + modals ----------
    async uploadFiles() {
      // (1) Block uncategorized/missing category
      const uncats = [];
      for (const it of this.files) {
        const catName = it.override
          ? this.canonicalCategoryNameById(it.category_id)
          : (it.detectedCategory || this.canonicalCategoryNameById(it.category_id));

        if (!catName || this.isUncategorized(catName)) {
          uncats.push(it.file.name);
        }
      }
      if (uncats.length) {
        this.uncategorizedList = uncats;
        this.showUncatModal = true;
        return;
      }

      // (2) Missing teacher
      const missingTeacher = [];
      for (const it of this.files) {
        if (it.state !== "ready" && it.state !== "error") {
          return alert(`⏳ Please wait for scanning to finish for ${it.file.name}.`);
        }
        const catName = it.override
          ? this.canonicalCategoryNameById(it.category_id)
          : (it.detectedCategory || this.canonicalCategoryNameById(it.category_id));

        const requiresTeacher = this.requiresTeacherFor(catName);
        if (!catName) {
          return alert(`❌ ${it.file.name}: Please select a category (Reclassify).`);
        }
        if (requiresTeacher && !it.teacher_id && !it.detectedTeacher) {
          missingTeacher.push(it.file.name);
        }
      }
      if (missingTeacher.length) {
        this.teacherRequiredList = missingTeacher;
        this.showTeacherReqModal = true;
        return;
      }

      // (3) Proceed with upload
      this.isUploading = true;
      try {
        const fd = new FormData();
        const meta = [];
        this.files.forEach(it => {
          fd.append("files[]", it.file);
          meta.push({
            name: it.file.name,
            category_id: it.override
              ? (it.category_id ?? null)
              : (this.categoryIdFromName(it.detectedCategory) ?? it.category_id ?? null),
            teacher_id: (it.override
              ? it.teacher_id
              : (this.teacherIdFromName(it.detectedTeacher) ?? it.teacher_id)) ?? null,
            override: !!it.override,
            scanned_text: it.scanned_text || "",
            size: it.file.size,
            type: it.file.type,
          });
        });
        fd.append("meta", JSON.stringify(meta));
        const resp = await axios.post("/upload", fd);
        const results = resp?.data?.results || [];
        const failed = results.filter(r => !r.success && !r.duplicate);
        const dups = results.filter(r => r.duplicate);
        const successNames = new Set(results.filter(r => r.success).map(r => r.name));
        this.files = this.files.filter(it => !successNames.has(it.file.name));

        if (failed.length) alert(`❌ Failed: ${failed.map(r => r.name).join(", ")}`);
        if (dups.length) {
          const first = dups[0];
          this.openDuplicateModalByName(first.name);
        } else if (!failed.length) {
          this.resetAll();
          this.successMessage = "✅ Document(s) uploaded successfully!";
          setTimeout(() => (this.successMessage = ""), 4000);
        }
      } catch (e) {
        console.error(e);
        alert("❌ Upload failed.");
      } finally {
        this.isUploading = false;
      }
    },

    // ---------- Duplicate modal ----------
    openDuplicateModalByName(name) {
      const index = this.files.findIndex(it => it.file.name === name);
      if (index === -1) return;
      this.duplicateIndex = index;
      this.duplicateDocName = name;
      this.duplicateBusy = false;
      this.showDuplicateModal = true;
    },
    handleCancelDuplicate() {
      this.showDuplicateModal = false;
      this.duplicateBusy = false;
      this.duplicateIndex = null;
    },
    async handleContinueDuplicate() {
      if (this.duplicateBusy) return;
      this.duplicateBusy = true;
      try {
        const i = this.duplicateIndex;
        if (i == null || i < 0 || i >= this.files.length) {
          this.showDuplicateModal = false;
          this.duplicateBusy = false;
          return;
        }
        const it = this.files[i];
        const fd = new FormData();
        fd.append("files[]", it.file);
        const meta = [{
          name: it.file.name,
          category_id: it.override
            ? (it.category_id ?? null)
            : (this.categoryIdFromName(it.detectedCategory) ?? it.category_id ?? null),
          teacher_id: (it.override
            ? it.teacher_id
            : (this.teacherIdFromName(it.detectedTeacher) ?? it.teacher_id)) ?? null,
          override: !!it.override,
          scanned_text: it.scanned_text || "",
          size: it.file.size,
          type: it.file.type,
        }];
        fd.append("meta", JSON.stringify(meta));
        fd.append("allow_duplicate", "1");
        const resp = await axios.post("/upload", fd);
        const r = resp?.data?.results?.[0] || {};
        if (r.duplicate) {
          alert("⚠️ Server still detected a duplicate.");
        } else if (r.success) {
          this.files.splice(i, 1);
          if (!this.files.length) {
            this.resetAll();
            this.successMessage = "✅ Document(s) uploaded successfully!";
            setTimeout(() => (this.successMessage = ""), 4000);
          }
        } else {
          alert(`❌ Upload failed for ${it.file.name}.`);
        }
        this.showDuplicateModal = false;
        this.duplicateBusy = false;
      } catch (e) {
        console.error(e);
        alert("❌ Upload failed.");
        this.duplicateBusy = false;
      }
    },

    // ---------- Misc helpers ----------
    categoryIdFromName(name) {
      if (!name) return null;
      const n = String(name).trim().toLowerCase();
      const c = this.categories.find(x => x.name.trim().toLowerCase() === n);
      return c ? c.id : null;
    },
    teacherIdFromName(fullName) {
      if (!fullName) return null;
      const t = this.teachers.find(x => x.full_name === fullName);
      return t ? t.id : null;
    },
    formatFileSize(size) { return `${(size / 1024).toFixed(2)} KB`; },
    resetAll() {
      this.files = [];
      this.fileInputKey++;
      this.flaskOffline = false;
      this.duplicateIndex = null;
      this.duplicateDocName = "";
      this.showDuplicateModal = false;
      this.duplicateBusy = false;
      this.showUncatModal = false;
      this.uncategorizedList = [];
      this.showTeacherReqModal = false;
      this.teacherRequiredList = [];
      this.currentPage = 1;
    },

    // Uncategorized modal actions
    closeUncatModal() { this.showUncatModal = false; },
    reclassifyUncategorized() {
      this.files.forEach(it => {
        const catName = it.override
          ? this.canonicalCategoryNameById(it.category_id)
          : (it.detectedCategory || this.canonicalCategoryNameById(it.category_id));
        if (!catName || this.isUncategorized(catName)) {
          it.override = true;
        }
      });
      this.showUncatModal = false;
    },

    // Missing-teacher modal action
    closeTeacherReqModal() { this.showTeacherReqModal = false; },
  }
};
</script>




<style scoped>
/* Container */
.upload-container {
  background: #fff;
  padding: 1.5rem;              /* smaller */
  border-radius: 12px;
  border: 3px dashed #d1d5db;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  transition: border-color 0.3s;
}
.upload-container.dragging { border-color: #2563eb; }

/* Button */
.upload-btn {
  margin-top: 0.75rem;          /* smaller */
  padding: 0.5rem 1rem;         /* smaller */
  background: #2563eb;
  color: #fff;
  font-weight: 600;
  border-radius: 0.4rem;
  cursor: pointer;
  transition: background 0.3s;
  font-size: 0.9rem;
}
.upload-btn:hover { background: #1e40af; }
.upload-btn:disabled { background: #a0aec0; cursor: not-allowed; }

/* Spinner */
.spinner {
  margin-top: 0.5rem;
  width: 1.4rem;
  height: 1.4rem;
  border: 3px solid #d1d5db;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* File list */
.file-list { width: 100%; }
.file-card {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 0.5rem 0.75rem;      /* tighter */
  margin-bottom: 0.5rem;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  transition: all 0.2s;
}
.file-card:hover { box-shadow: 0 2px 4px rgba(0,0,0,0.07); }

/* Top row */
.filename {
  font-size: 0.9rem;            /* smaller */
  font-weight: 600;
  color: #1f2937;
  display: flex; align-items: center; flex-wrap: wrap; gap: 4px;
}

/* Buttons */
.icon-btn { font-size: 0.9rem; line-height: 1; padding: 2px 4px; }

/* Chips */
.chip {
  display: inline-flex; align-items: center;
  font-size: 11px; font-weight: 600;
  padding: 2px 6px; border-radius: 6px; border: 1px solid;
}
.chip-ok { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
.chip-warn { background: #fffbeb; color: #92400e; border-color: #fde68a; }

/* Small form controls */
.small-label { font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 0.15rem; }
.small-select {
  padding: 0.35rem 0.5rem;
  font-size: 0.85rem;
  line-height: 1.35;
  height: auto; min-height: 36px;   /* avoids clipping on Windows */
  border: 1px solid #d1d5db; border-radius: 6px;
  width: 100%; box-sizing: border-box;
}

/* ========== Simple Modal ========== */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.35);
  display: flex; align-items: center; justify-content: center;
  z-index: 50;
}
.modal-card {
  width: 92%; max-width: 520px;
  background: #fff; border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,.2);
  overflow: hidden;
}
.modal-head { padding: 14px 16px; border-bottom: 1px solid #eee; }
.modal-head h3 { margin: 0; font-weight: 700; font-size: 1rem; }
.modal-body { padding: 14px 16px; }
.modal-foot {
  padding: 12px 16px; display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid #eee;
}
.btn-primary {
  background: #2563eb; color: #fff; font-weight: 600; border-radius: 8px; padding: 8px 12px;
}
.btn-primary:hover { background:#1e40af; }
.btn-secondary {
  background: #f3f4f6; color: #111827; font-weight: 600; border-radius: 8px; padding: 8px 12px;
}
.btn-secondary:hover { background:#e5e7eb; }

.link-btn {
  background: transparent;
  border: none;
  padding: 0.15rem 0.25rem;
  font-weight: 700;
  font-size: 0.85rem;
  color: #2563eb;                  /* default color */
  cursor: pointer;
  transition: color 150ms ease, background 150ms ease, transform 120ms ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Hover state: change color (and optionally subtle lift) */
.link-btn:hover {
  color: #1e40af;                  /* hover color */
  transform: translateY(-1px);
  text-decoration: underline;
}
</style>
