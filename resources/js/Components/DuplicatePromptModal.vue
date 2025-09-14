<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
      <h3 class="text-lg font-semibold mb-2">Possible Duplicate</h3>
      <p class="text-sm text-gray-700">
        A document with a similar name already exists
        <span v-if="existingName"><strong> ({{ existingName }})</strong></span>.
      </p>
      <p class="text-sm text-gray-700 mt-2">
        Do you want to <strong>add this as another file</strong>?
      </p>

      <div class="mt-6 flex items-center justify-end gap-2">
        <button
          class="px-4 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-100"
          @click="$emit('cancel')"
        >
          Cancel
        </button>
        <button
          class="px-4 py-2 rounded bg-indigo-600 text-white font-semibold hover:bg-indigo-700"
          @click="$emit('continue')"
        >
          Continue
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "DuplicatePromptModal",
  props: {
    existingName: {
      type: String,
      default: ""
    }
  }
};
</script>
