<script setup lang="ts">
import { ref } from 'vue';
import Sidebar from '@/components/Sidebar.vue';
import LogoutButton from '@/components/LogoutButton.vue';

const isOpen = ref(true);

const userMenus = [
    { name: 'Halaman Utama', icon: '🏠', to: '/dashboard' },
    { name: 'Ruangan', icon: '🗓️', to: '/availability' },
    { name: 'Peminjaman', icon: '📝', to: '/pinjam' },
    { name: 'Riwayat', icon: '📋', to: '/riwayat' },
    { name: 'Profil', icon: '👤', to: '/profile' },
];
</script>

<template>
    <div class="layout">
        <Sidebar :is-open="isOpen" :menus="userMenus" brand="User Menu" @toggle="isOpen = !isOpen">
            <template #sidebar-footer>
                <LogoutButton />
            </template>
        </Sidebar>

        <main class="content" :class="{ collapsed: !isOpen }">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.layout {
    min-height: 100vh;
}

.content {
    margin-left: 230px;
    min-height: 100vh;
    background: #f5f5f5;
    padding: 30px;
    transition: margin-left 0.3s ease;
}

.content.collapsed {
    margin-left: 72px;
}
</style>
