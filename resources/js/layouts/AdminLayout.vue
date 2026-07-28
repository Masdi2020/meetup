<script setup lang="ts">
import AdminSidebar from "@/components/admin/Sidebar.vue";
</script>

<template>
  <div class="admin-layout">
    <AdminSidebar />

    <main class="admin-content">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
}

.admin-content {
  flex: 1;
  padding: 32px;
  background: #f5f7fb;
}
</style>
