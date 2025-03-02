<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100">
      <div class="max-w-md w-full bg-white shadow-md rounded-lg p-6 text-center">
        <h1 class="text-4xl font-bold text-red-600 mb-4">
          {{ statusCode }}
        </h1>
        <p class="text-gray-700 text-lg">
          {{ message }}
        </p>
        <button
          @click="goBack"
          class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          Go Back
        </button>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    props: {
      statusCode: Number,
      message: String,
    },
    methods: {
      goBack() {
        window.history.back();
      },
    },
  };
  </script>
  