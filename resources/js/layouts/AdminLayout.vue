<script setup lang="ts">
import { ref } from 'vue';
import LogoutButton from '@/components/LogoutButton.vue';
import Sidebar from '@/components/Sidebar.vue';

const isOpen = ref(true);

const adminMenus = [
    { name: 'Dashboard', icon: '📊', to: '/admin' },
    { name: 'Peminjaman', icon: '📝', to: '/admin/bookings' },
    { name: 'Ruangan', icon: '🏢', to: '/admin/rooms' },
    { name: 'Fasilitas', icon: '🪑', to: '/admin/facilities' },
    { name: 'Pengguna', icon: '👤', to: '/admin/users' },
    { name: 'Audit', icon: '📜', to: '/admin/audits' },
    { name: 'Pengaturan', icon: '⚙️', to: '/admin/settings' },
];
</script>

<template>
    <div class="admin-layout">
        <Sidebar
            :is-open="isOpen"
            :menus="adminMenus"
            brand="Admin Menu"
            @toggle="isOpen = !isOpen"
        >
            <template #sidebar-footer>
                <LogoutButton />
            </template>
        </Sidebar>

        <main class="admin-content" :class="{ collapsed: !isOpen }">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.admin-layout {
    min-height: 100vh;
}

.admin-content {
    margin-left: 230px;
    min-height: 100vh;
    background: #f5f5f5;
    padding: 30px;
    transition: margin-left 0.3s ease;
}

.admin-content.collapsed {
    margin-left: 72px;
}
</style>
