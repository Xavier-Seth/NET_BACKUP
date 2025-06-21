<template>
  <div class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
      <h2 class="text-lg font-bold text-red-600 mb-2">Duplicate Document Detected</h2>
      <p class="text-gray-700 mb-4">
        A document of this type already exists for the selected teacher:
        <strong>"{{ existingName }}"</strong>
      </p>
      <p class="text-sm text-gray-600 mb-4">
        What would you like to do?
      </p>
      <div class="flex justify-end gap-2">
        <button
          class="px-4 py-2 text-sm rounded bg-gray-300 hover:bg-gray-400 text-gray-800"
          @click="$emit('cancel')"
        >
          Cancel
        </button>
        <button
          class="px-4 py-2 text-sm rounded bg-blue-600 hover:bg-blue-700 text-white"
          @click="$emit('continue')"
        >
          Continue (Add Another)
        </button>
        <button
          class="px-4 py-2 text-sm rounded bg-green-600 hover:bg-green-700 text-white"
          @click="$emit('replace')"
        >
          Replace Existing
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    existingName: {
      type: String,
      required: true,
    },
  },
};
</script>
