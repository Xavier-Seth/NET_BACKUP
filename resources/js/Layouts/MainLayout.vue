<template>
  <div class="main-layout">
    <!-- Floating Unauthorized Alert -->
    <div v-if="errorMessage" class="alert-box">
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ errorMessage }}
        <button type="button" class="btn-close" @click="errorMessage = ''"></button>
      </div>
    </div>

    <!-- Sidebar -->
    <Sidebar :activeMenu="activeMenu" @update-active="setActive" />

    <!-- Main Content Area -->
    <div class="main-content">
      <Header :user="user" />
      <slot />
    </div>
  </div>
</template>

<script>
import Sidebar from "@/Components/Sidebar.vue"
import Header from "@/Components/Header.vue"
import { ref, onMounted } from "vue"
import { usePage, router } from "@inertiajs/vue3"

export default {
  name: "MainLayout",
  components: { Sidebar, Header },
  props: {
    activeMenu: { type: String, default: "" },
  },
  setup() {
    const page = usePage()
    const user = page.props.auth.user
    const errorMessage = ref("")

    const handleUnauthorized = (errors) => {
      if (errors.status === 403) {
        errorMessage.value = "❌ Unauthorized Access: You do not have permission."
        setTimeout(() => (errorMessage.value = ""), 5000)
      }
    }

    onMounted(() => {
      router.on("error", handleUnauthorized)
    })

    return { user, errorMessage }
  },
  methods: {
    setActive(menuItem) {
      this.activeMenu = menuItem
    },
  },
}
</script>

<style scoped>
.main-layout {
  display: flex; flex-direction: row; min-height: 100vh;
  background: #f5f5f5; overflow: hidden;
}
.main-content { flex: 1; padding: 20px; margin-left: 200px; transition: margin-left .3s ease; }
.alert-box {
  position: fixed; top: 15px; left: 50%;
  transform: translateX(-50%); z-index: 1050;
  width: 90%; max-width: 350px; text-align: center;
}
@media (max-width: 768px) { .main-content { margin-left: 0; } }
</style>
