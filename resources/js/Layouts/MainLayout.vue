<template>
    <div class="main-layout">
      <!-- Sidebar -->
      <Sidebar :activeMenu="activeMenu" @update-active="setActive" />
  
      <!-- Main Content -->
      <div class="main-content">
        <!-- Header -->
        <Header />
  
        <!-- Dynamic Content Slot -->
        <slot></slot>
      </div>
    </div>
  </template>
  
  <script>
  import Sidebar from "@/Components/Sidebar.vue";
  import Header from "@/Components/Header.vue";
  
  export default {
    components: {
      Sidebar,
      Header,
    },
    props: {
      activeMenu: String,
    },
    methods: {
      setActive(menuItem) {
        this.activeMenu = menuItem;
      },
    },
  };
  </script>
  
  <style scoped>
  .main-layout {
    display: flex;
    width: 100%;
    height: 100vh;
    overflow: hidden;
    background: #f5f5f5;
  }
  
  .main-content {
    flex: 1;
    margin-left: 220px;
    display: flex;
    flex-direction: column;
    padding: 20px;
  }
  </style>
  