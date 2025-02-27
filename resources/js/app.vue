<template>
  <div class="container mt-4">
    <h1>Welcome to DocuNet</h1>
    <p>A Document Archiving System for Schools</p>

    <!-- Router View for dynamic components -->
    <router-view />
  </div>
</template>

<script>
export default {
  name: "App",
};
</script>

<style>
body {
  font-family: Arial, sans-serif;
}
</style>
