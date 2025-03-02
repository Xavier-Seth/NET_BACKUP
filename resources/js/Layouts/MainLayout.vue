<template>
  <div class="main-layout">
    <!-- Unauthorized Access Alert (Fixed Position) -->
    <div v-if="errorMessage" class="alert-box">
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ errorMessage }}
        <button type="button" class="btn-close" @click="errorMessage = ''"></button>
      </div>
    </div>

    <!-- Sidebar -->
    <Sidebar :activeMenu="activeMenu" @update-active="setActive" />

    <!-- Main Content -->
    <div class="main-content">
      <Header />
      <slot></slot>
    </div>
  </div>
</template>

<script>
import Sidebar from "@/Components/Sidebar.vue";
import Header from "@/Components/Header.vue";
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";

export default {
  components: {
    Sidebar,
    Header,
  },
  props: {
    activeMenu: String,
  },
  setup() {
    const errorMessage = ref("");

    // Listen for Unauthorized Errors (403)
    const handleUnauthorized = (errors) => {
      if (errors.status === 403) {
        errorMessage.value = "❌ Unauthorized Access: You do not have permission.";
        setTimeout(() => {
          errorMessage.value = "";
        }, 5000); // Auto-hide error after 5 seconds
      }
    };

    onMounted(() => {
      router.on("error", handleUnauthorized);
    });

    return { errorMessage };
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

/* Floating Alert Box */
.alert-box {
  position: fixed;
  top: 15px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1050;
  width: 350px;
  text-align: center;
}
</style>
