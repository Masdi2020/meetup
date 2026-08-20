<script setup lang="ts">
import { ref } from 'vue';
import ForceChangePasswordModal from '@/components/ForceChangePasswordModal.vue';
import LogoutButton from '@/components/LogoutButton.vue';
import Sidebar from '@/components/Sidebar.vue';

const isOpen = ref(true);

const displayMenus = [
    { name: 'Ruangan', icon: 'R', to: '/display' },
    { name: 'Profil', icon: 'P', to: '/profile' },
];
</script>

<template>
    <div class="display-layout">
        <Sidebar
            :is-open="isOpen"
            :menus="displayMenus"
            brand="Display"
            @toggle="isOpen = !isOpen"
        >
            <template #sidebar-footer>
                <LogoutButton />
            </template>
        </Sidebar>

        <ForceChangePasswordModal />

        <main class="display-content" :class="{ collapsed: !isOpen }">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.display-layout {
    min-height: 100vh;
}

.display-content {
    min-height: 100vh;
    margin-left: 230px;
    padding: 30px;
    background: #f5f5f5;
    transition: margin-left 0.3s ease;
}

.display-content.collapsed {
    margin-left: 72px;
}
</style>