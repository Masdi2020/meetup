<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ForceChangePasswordModal from '@/components/ForceChangePasswordModal.vue';
import LogoutButton from '@/components/LogoutButton.vue';
import Sidebar from '@/components/Sidebar.vue';
import { navigationByRole } from '@/config/navigation';
import type { UserRole } from '@/config/navigation';
import type { Auth } from '@/types/auth';

const isOpen = ref(true);
const isMobile = ref(false);
let removeNavigateListener: (() => void) | undefined;

const syncViewport = () => {
    const mobile = window.matchMedia('(max-width: 768px)').matches;
    if (mobile !== isMobile.value) isOpen.value = !mobile;
    isMobile.value = mobile;
};

onMounted(() => {
    syncViewport();
    window.addEventListener('resize', syncViewport);
    removeNavigateListener = router.on('navigate', () => {
        if (isMobile.value) isOpen.value = false;
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', syncViewport);
    removeNavigateListener?.();
});

const page = usePage<{ auth: Auth }>();

const navigation = computed(() => {
    const role = page.props.auth.user?.role as UserRole | undefined;

    return navigationByRole[role ?? 'user'];
});
</script>

<template>
    <div class="layout">
        <button
            v-if="isMobile && !isOpen"
            class="mobile-menu-button"
            type="button"
            aria-label="Buka menu navigasi"
            @click="isOpen = true"
        >
            <span aria-hidden="true">&#9776;</span>
        </button>
        <button
            v-if="isMobile && isOpen"
            class="sidebar-backdrop"
            type="button"
            aria-label="Tutup menu navigasi"
            @click="isOpen = false"
        />
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
    padding: clamp(16px, 2.5vw, 30px);
    transition: margin-left 0.3s ease;
}

.content.collapsed {
    margin-left: 72px;
}

.mobile-menu-button {
    display: none;
}

@media (max-width: 768px) {
    .content,
    .content.collapsed {
        margin-left: 0;
        padding-top: 72px;
    }
    .mobile-menu-button {
        position: fixed;
        top: 14px;
        left: 16px;
        z-index: 900;
        display: grid;
        width: 44px;
        height: 44px;
        place-items: center;
        border: 0;
        border-radius: 10px;
        background: #18326d;
        color: white;
        font-size: 22px;
        box-shadow: 0 4px 14px rgb(15 23 42 / 20%);
    }
    .sidebar-backdrop {
        position: fixed;
        inset: 0;
        z-index: 999;
        border: 0;
        background: rgb(15 23 42 / 48%);
    }
}
</style>
