<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ForceChangePasswordModal from '@/components/ForceChangePasswordModal.vue';
import LogoutButton from '@/components/LogoutButton.vue';
import Sidebar from '@/components/Sidebar.vue';
import { navigationByRole, type UserRole } from '@/config/navigation';
import type { Auth } from '@/types/auth';

const isOpen = ref(true);

const page = usePage<{ auth: Auth }>();

const navigation = computed(() => {
    const role = page.props.auth.user?.role as UserRole | undefined;

    return navigationByRole[role ?? 'user'];
});
</script>

<template>
    <div class="layout">
        <Sidebar
            :is-open="isOpen"
            :menus="navigation.items"
            :brand="navigation.brand"
            @toggle="isOpen = !isOpen"
        >
            <template #sidebar-footer>
                <LogoutButton />
            </template>
        </Sidebar>

        <ForceChangePasswordModal />
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
