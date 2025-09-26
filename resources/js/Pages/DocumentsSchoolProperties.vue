<template>
  <div class="flex bg-gray-100 min-h-screen">
    <Sidebar />
    <div class="flex-1 bg-gray-100 overflow-y-auto ml-[210px] p-6">
      <h1 class="page-title">School Property Documents</h1>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <!-- LEFT: Preview -->
        <section class="xl:col-span-7">
          <div class="card group">
            <!-- Header -->
            <div class="card-h">
              <div class="truncate">
                <div class="flex items-center gap-3">
                  <strong class="truncate" :title="selectedDoc?.name || 'No document selected'">
                    {{ selectedDoc?.name || 'No document selected' }}
                  </strong>
                  <span v-if="selectedDoc?.category?.name" class="chip">
                    {{ selectedDoc.category.name }}
                  </span>
                </div>
                <div class="subline" v-if="selectedDoc">
                  <span>Uploaded {{ formatDate(selectedDoc.created_at) }}</span>
                  <span v-if="selectedDoc.user?.name">• by {{ selectedDoc.user.name }}</span>
                </div>
              </div>

              <!-- Floating actions -->
              <div class="action-dock">
                <button class="dock-btn" @click="previewFile(selectedDoc)" :disabled="!selectedDoc" title="Open in modal">
                  <i class="bi bi-arrows-fullscreen"></i>
                </button>
                <a v-if="selectedDoc" :href="`/documents/school-properties/${selectedDoc.id}/download`" class="dock-btn" title="Download">
                  <i class="bi bi-download"></i>
                </a>
                <button class="dock-btn danger" @click="selectedDoc && confirmDelete(selectedDoc)" :disabled="!selectedDoc" title="Delete">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>

            <!-- Preview body -->
            <div class="card-b">
              <template v-if="selectedDoc && previewSrc(selectedDoc)">
                <template v-if="isImage(previewSrc(selectedDoc))">
                  <img :src="previewSrc(selectedDoc)" class="preview-img" alt="Preview image" />
                </template>
                <template v-else>
                  <iframe :src="previewSrc(selectedDoc)" class="preview-iframe" frameborder="0"></iframe>
                </template>
              </template>

              <template v-else>
                <div class="empty">
                  <div class="empty-circle"><i class="bi bi-file-earmark-text"></i></div>
                  <p class="text-gray-600">
                    {{ selectedDoc ? 'No preview available for this file.' : 'Select a document from the list to preview.' }}
                  </p>
                </div>
              </template>
            </div>
          </div>
        </section>

        <!-- RIGHT: Search + Grid -->
        <aside class="xl:col-span-5">
          <div class="card">
            <!-- Sticky toolbar -->
            <div class="tools sticky top-0 bg-white/90 backdrop-blur z-10">
              <div class="tool-grid">
                <div class="tool">
                  <label>Search</label>
                  <input
                    type="text"
                    v-model="filtersLocal.search"
                    @keyup.enter="applyFilters"
                    placeholder="Search file name"
                  />
                </div>

                <div class="tool">
                  <label>Category</label>
                  <select v-model="filtersLocal.category" @change="applyFilters">
                    <option value="">All</option>
                    <option v-for="c in categories" :key="c.id" :value="c.name">{{ c.name }}</option>
                  </select>
                </div>

                <div class="tool">
                  <label class="invisible">Filter</label>
                  <button class="btn w-full" @click="applyFilters">
                    <i class="bi bi-search"></i>&nbsp;Filter
                  </button>
                </div>
              </div>
            </div>

            <!-- GRID (always compact) -->
            <div class="grid-wrap density-compact">
              <div
                v-for="doc in documents.data"
                :key="doc.id"
                class="grid-card"
                :class="{ active: selectedDoc?.id === doc.id }"
                @click="selectDoc(doc)"
              >
                <div class="g-thumb">
                  <img v-if="thumbSrc(doc)" :src="thumbSrc(doc)" />
                  <div v-else class="g-fallback"><i class="bi bi-file-earmark-text"></i></div>
                </div>
                <div class="g-name" :title="doc.name">{{ doc.name }}</div>
                <div class="g-sub">
                  <span class="chip chip-s">{{ doc.category?.name || 'Uncategorized' }}</span>
                  <span class="muted">{{ formatDate(doc.created_at) }}</span>
                </div>
                <div class="g-actions" @click.stop>
                  <button class="icon" title="Preview" @click="previewFile(doc)"><i class="bi bi-eye"></i></button>
                  <a :href="`/documents/school-properties/${doc.id}/download`" class="icon" title="Download">
                    <i class="bi bi-download"></i>
                  </a>
                  <button class="icon danger" title="Delete" @click="confirmDelete(doc)"><i class="bi bi-trash"></i></button>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div class="pagination" v-if="documents.links.length > 3">
              <template v-for="link in documents.links" :key="link.url || link.label">
                <button
                  v-if="link.url"
                  @click="goToPage(link.url)"
                  v-html="link.label"
                  class="page-link"
                  :class="{ active: link.active }"
                ></button>
                <span v-else class="page-link disabled"> ... </span>
              </template>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>

  <!-- Modal preview via Teleport -->
  <Teleport to="body">
    <div v-if="previewUrl" class="modal" @click.self="closePreview">
      <div class="modal-c">
        <button class="modal-x" @click="closePreview">&times;</button>

        <template v-if="isImage(previewUrl)">
          <img :src="previewUrl" class="modal-img" alt="Preview" />
        </template>
        <template v-else>
          <iframe :src="previewUrl" frameborder="0" class="modal-iframe"></iframe>
        </template>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import { router } from "@inertiajs/vue3";
import { ref, watch, onMounted } from "vue";

const props = defineProps({
  documents: Object,
  categories: Array,
  filters: Object,
});

const filtersLocal = ref({ ...props.filters });
const selectedDoc = ref(null);
const previewUrl = ref(null);

// Initialize selection
function initSelection() {
  const list = props.documents?.data || [];
  if (!list.length) { selectedDoc.value = null; return; }

  const qdoc = new URLSearchParams(window.location.search).get("doc");
  if (qdoc) {
    const found = list.find(d => String(d.id) === String(qdoc));
    if (found) { selectedDoc.value = found; return; }
  }
  selectedDoc.value = list[0];
}
onMounted(initSelection);

// Watch for data changes and preserve selection
watch(
  () => props.documents.data,
  (list) => {
    list = list || [];
    if (!list.length) { selectedDoc.value = null; return; }

    if (selectedDoc.value) {
      const still = list.find(d => d.id === selectedDoc.value.id);
      if (still) { selectedDoc.value = still; return; }
    }

    const qdoc = new URLSearchParams(window.location.search).get("doc");
    if (qdoc) {
      const found = list.find(d => String(d.id) === String(qdoc));
      if (found) { selectedDoc.value = found; return; }
    }

    selectedDoc.value = list[0];
  }
);

function previewSrc(doc) {
  if (!doc) return null;
  if (doc.pdf_preview_path) return `/storage/${doc.pdf_preview_path}`;
  if (doc.image_preview_path) return `/storage/${doc.image_preview_path}`;
  return null;
}
function thumbSrc(doc) {
  if (!doc) return null;
  if (doc.thumb_path) return `/storage/${doc.thumb_path}`;
  if (doc.image_preview_path) return `/storage/${doc.image_preview_path}`;
  return null;
}
function formatDate(iso) {
  try {
    return new Date(iso).toLocaleDateString();
  } catch {
    return "";
  }
}
function isImage(src) {
  return /\.(png|jpe?g|gif|bmp|webp)$/i.test(src || "");
}

// Fixed selectDoc
function selectDoc(doc) {
  selectedDoc.value = doc;
  const url = new URL(window.location.href);
  url.searchParams.set("doc", doc.id);
  window.history.replaceState({}, "", url.toString());
}

function applyFilters() {
  router.get("/documents/school-properties", { ...filtersLocal.value }, { preserveState: true, replace: true });
}
function goToPage(url) {
  if (url) router.visit(url, { preserveState: true, preserveScroll: true });
}

function previewFile(doc) {
  const src = previewSrc(doc);
  if (!src) {
    alert("No preview file available.");
    return;
  }
  previewUrl.value = src;
  document.body.style.overflow = "hidden";
}
function closePreview() {
  previewUrl.value = null;
  document.body.style.overflow = "";
}

function confirmDelete(doc) {
  if (confirm(`Delete "${doc.name}"?`)) {
    router.delete(`/documents/school-properties/${doc.id}`, {
      replace: true,
      preserveScroll: true,
      onSuccess: () => {
        if (selectedDoc.value?.id === doc.id) selectedDoc.value = null;
      },
      onError: () => alert("Failed to delete the document."),
    });
  }
}
</script>


<style scoped>
/* Modal (PC-only, above everything) */
.modal {
  position: fixed;
  inset: 0; /* full viewport */
  z-index: 2147483647; /* higher than any sidebar */
  display: grid; /* easy centering */
  place-items: center;
  background: rgba(0,0,0,.7);
}
.modal-c {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 92vw;
  height: 86vh;
  max-width: 1100px;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 20px 60px rgba(0,0,0,.35);
}
.modal-iframe { flex: 1; width: 100%; border: 0; }
.modal-x {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 6px 12px;
  border: 0;
  border-radius: 10px;
  background: #dc3545;
  color: #fff;
  font-size: 18px;
  cursor: pointer;
}

/* Headings */
.page-title {
  margin: 0 0 1.25rem;
  font-weight: 800;
  font-size: 1.625rem; /* 26px */
}

/* Card + Header + Body */
.card {
  overflow: hidden;
  border: 1px solid #eef0f5;
  border-radius: 0.875rem; /* 14px */
  background: linear-gradient(180deg, #fff 0%, #fbfbff 100%);
  box-shadow: 0 6px 24px rgba(16, 24, 40, .06);
}
.card-h {
  display: flex;
  gap: 0.75rem;
  justify-content: space-between;
  padding: 0.875rem 1rem; /* 14px 16px */
  border-bottom: 1px solid #edf0f6;
}
.card-b {
  height: 68vh;
  background: #f7f8fc;
}
.preview-iframe {
  width: 100%;
  height: 100%;
  border: 0;
}
.subline {
  margin-top: 0.125rem;
  color: #64748b;
  font-size: 0.75rem;
}
.preview-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: #fff;
}
.modal-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: #000;
}

/* Action Dock */
.action-dock {
  display: flex;
  gap: 0.5rem;
  opacity: 0;
  transform: translateY(2px);
  transition: opacity .15s ease, transform .15s ease;
}
.group:hover .action-dock,
.action-dock:focus-within {
  opacity: 1;
  transform: translateY(0);
}
.dock-btn {
  width: 2.25rem; /* 36px */
  height: 2.25rem;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 0.625rem; /* 10px */
  background: #f3f4f6;
  cursor: pointer;
}
.dock-btn:hover { background: #e7eaf0; }
.dock-btn.danger { color: #dc3545; }

/* Empty State */
.empty {
  height: 100%;
  display: grid;
  place-items: center;
  text-align: center;
}
.empty-circle {
  display: grid;
  place-items: center;
  width: 4rem; /* 64px */
  height: 4rem;
  margin: 0 0 0.5rem;
  color: #4f46e5;
  font-size: 1.625rem; /* 26px */
  background: #eef2ff;
  border-radius: 999px;
}

/* Tools / Filters (PC-only) */
.tools {
  padding: 0.75rem 0.875rem; /* 12px 14px */
  border-bottom: 1px solid #edf0f6;
  border-radius: 0.875rem 0.875rem 0 0;
  background: rgba(255,255,255,.9);
  backdrop-filter: blur(4px);
}
.tool-grid {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.625rem; /* 10px */
}
.tool {
  display: grid;
  gap: 0.375rem;
}
.tool input,
.tool select {
  padding: 0.5625rem 0.75rem; /* 9px 12px */
  border: 1px solid #d6dae3;
  border-radius: 0.625rem; /* 10px */
  background: #fff;
}
.btn {
  height: 2.375rem; /* 38px */
  padding: 0 0.875rem; /* 14px */
  border: 1px solid #19184f;
  border-radius: 0.625rem; /* 10px */
  background: #19184f;
  color: #fff;
}

/* Grid (compact, PC-only) */
.grid-wrap {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr)); /* fixed 3 cols on desktop */
  gap: 0.625rem; /* 10px */
  max-height: 58vh;
  padding: 0.375rem; /* 6px */
  overflow: auto;
}
.grid-card {
  display: grid;
  gap: 0.375rem; /* 6px */
  grid-template-rows: auto auto auto auto;
  padding: 0.5rem; /* 8px */
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem; /* 12px */
  background: #fff;
  cursor: pointer;
}
.grid-card.active { outline: 2px solid #c7d2fe; outline-offset: 0; }
.g-thumb {
  display: grid;
  place-items: center;
  height: 6rem; /* 96px */
  overflow: hidden;
  border: 1px solid #eef0f5;
  border-radius: 0.625rem; /* 10px */
}
.g-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.g-fallback { color: #667085; font-size: 1.625rem; }
.g-name {
  font-weight: 600;
  font-size: 0.8125rem; /* 13px */
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.g-sub {
  display: flex;
  gap: 0.375rem;
  align-items: center;
}
.g-actions {
  display: flex;
  gap: 0.375rem;
  margin-top: 0.125rem;
}
.icon {
  background: transparent;
  border: 0;
  font-size: 1.125rem; /* 18px */
  cursor: pointer;
  color: #374151;
}
.icon.danger { color: #dc3545; }

/* Chips */
.chip {
  display: inline-block;
  padding: 0.1875rem 0.625rem; /* 3px 10px */
  font-size: 0.75rem;
  color: #3730a3;
  background: #eef2ff;
  border-radius: 999px;
}
.chip-s {
  font-size: 0.6875rem;
  padding: 0.125rem 0.5rem;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  gap: 0.375rem; /* 6px */
  margin: 0.75rem 0 0.25rem; /* 12px 0 4px */
}
.page-link {
  padding: 0.375rem 0.625rem; /* 6px 10px */
  border: 1px solid #d1d5db;
  border-radius: 0.625rem; /* 10px */
  background: #fff;
  cursor: pointer;
}
.page-link.active { background: #19184f; color: #fff; }
.page-link.disabled { background: #eee; color: #aaa; }
</style>
