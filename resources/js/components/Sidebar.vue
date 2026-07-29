<script setup lang="ts">
import { Link } from "@inertiajs/vue3";

defineProps<{
  isOpen: boolean;
}>();

const emit = defineEmits<{
  (e: "toggle"): void;
}>();

const menus = [
  { name: "Halaman Utama", icon: "🏠", to: "/dashboard" },
  { name: "Ruangan", icon: "🗓️", to: "/kalender" },
  { name: "Peminjaman", icon: "📝", to: "/pinjam" },
  { name: "Riwayat", icon: "📋", to: "/riwayat" },
];
</script>

<template>
  <aside :class="['sidebar', { collapsed: !isOpen }]">
    <div class="menu-header" @click="emit('toggle')">
      <span class="hamburger">☰</span>
      <span v-if="isOpen" class="brand">Menu</span>
    </div>

    <nav>
      <Link
        v-for="menu in menus"
        :key="menu.name"
        :href="menu.to"
        class="menu-item"
        :class="{ collapsed: !isOpen }"
      >
        <span class="icon">{{ menu.icon }}</span>
        <span v-show="isOpen" class="menu-text">
          {{ menu.name }}
        </span>
      </Link>
    </nav>
  </aside>
</template>

<style scoped>
.sidebar {
  position: fixed;
  top: 0;
  left: 0;

  width: 230px;
  height: 100vh;

  display: flex;
  flex-direction: column;

  background: linear-gradient(180deg, #18326d 0%, #12244f 100%);
  color: white;

  overflow-y: auto;
  overflow-x: hidden;

  transition: width 0.3s ease;
  z-index: 1000;
}

.sidebar.collapsed {
  width: 72px;
}

.menu-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  cursor: pointer;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 12px;
}

.sidebar.collapsed .menu-header {
  justify-content: center;
  padding: 20px 0;
}

.hamburger {
  font-size: 20px;
  line-height: 1;
}

.brand {
  font-weight: 600;
  font-size: 16px;
  letter-spacing: 0.3px;
}

nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 0 12px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 10px;
  color: rgba(255, 255, 255, 0.85);
  text-decoration: none;
  transition: background 0.2s ease, color 0.2s ease;
}

.menu-item:hover {
  background: rgba(255, 255, 255, 0.12);
  color: white;
}

.menu-item.collapsed {
  justify-content: center;
  padding: 12px 0;
}

.icon {
  width: 24px;
  min-width: 24px;
  font-size: 20px;
  text-align: center;
}

.menu-text {
  white-space: nowrap;
  font-size: 14px;
  font-weight: 500;
}
</style>
