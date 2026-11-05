<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, provide, ref, watch } from 'vue';
import { LogoutButton } from '@/components/molecules';
import { ForceChangePasswordModal, Sidebar } from '@/components/organisms';
import { navigationByRole } from '@/config/navigation';
import type { UserRole } from '@/config/navigation';
import type { Auth } from '@/types/auth';

const isOpen = ref(true);
const isMobile = ref(false);
let removeNavigateListener: (() => void) | undefined;

const sidebarOffset = computed(() => {
    if (isMobile.value) {
        return '0px';
    }

    return isOpen.value ? '244px' : '76px';
});

provide('sidebarOffset', sidebarOffset);

const syncViewport = () => {
    const mobile = window.matchMedia('(max-width: 768px)').matches;

    if (mobile !== isMobile.value) {
        isOpen.value = !mobile;
    }

    isMobile.value = mobile;
};

onMounted(() => {
    syncViewport();
    window.addEventListener('resize', syncViewport);
    removeNavigateListener = router.on('navigate', () => {
        if (isMobile.value) {
            isOpen.value = false;
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', syncViewport);
    removeNavigateListener?.();
});

const page = usePage<{ auth: Auth; name: string }>();

watch(
    () => page.props.name,
    (name) => {
        document.title = name;
    },
    { immediate: true },
);

const navigation = computed(() => {
    const role = page.props.auth.user?.role as UserRole | undefined;

    return navigationByRole[role ?? 'user'];
});

const currentPath = computed(() => {
    const url = page.url.split(/[?#]/)[0] || '/';

    return url.length > 1 ? url.replace(/\/$/, '') : url;
});

const activeMenuTo = computed(() => {
    return navigation.value.items
        .filter((item) => {
            const target =
                item.to.length > 1 ? item.to.replace(/\/$/, '') : item.to;

            return (
                currentPath.value === target ||
                (target !== '/' && currentPath.value.startsWith(`${target}/`))
            );
        })
        .sort((a, b) => b.to.length - a.to.length)[0]?.to;
});
</script>

<template>
    <div class="layout">
        <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
        <header class="mobile-header">
            <img src="/favicon.svg" alt="" width="28" height="28" />
            <span>{{ page.props.name }}</span>
        </header>
        <button
            v-if="isMobile && !isOpen"
            class="mobile-menu-button"
            type="button"
            aria-label="Buka menu navigasi"
            @click="isOpen = true"
        >
            <Menu :size="22" aria-hidden="true" />
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
            :brand="page.props.name"
            :subtitle="navigation.brand"
            :active-to="activeMenuTo"
            :user-name="page.props.auth.user?.name"
            :user-role="page.props.auth.user?.role"
            @toggle="isOpen = !isOpen"
        >
            <template #sidebar-footer>
                <LogoutButton :compact="!isOpen" />
            </template>
        </Sidebar>

        <ForceChangePasswordModal />
        <main
            id="main-content"
            class="content"
            :class="{ collapsed: !isOpen }"
            tabindex="-1"
        >
            <div class="content-inner"><slot /></div>
        </main>
    </div>
</template>

<style scoped>
.layout {
    min-height: 100vh;
}

.content {
    margin-left: 244px;
    min-height: 100vh;
    min-width: 0;
    background: var(--ui-background);
    padding: var(--ui-page-padding);
    transition: margin-left 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.content-inner {
    width: 100%;
    min-width: 0;
    max-width: var(--ui-content-width);
    margin-inline: auto;
}

.content.collapsed {
    margin-left: 76px;
}

.mobile-menu-button {
    display: none;
}
.mobile-header {
    display: none;
}
.skip-link {
    position: fixed;
    top: 8px;
    left: 8px;
    z-index: 1100;
    padding: 12px 16px;
    border-radius: var(--ui-radius);
    background: white;
    color: var(--ui-primary);
    transform: translateY(-150%);
}
.skip-link:focus {
    transform: translateY(0);
}

@media (max-width: 768px) {
    .content,
    .content.collapsed {
        margin-left: 0;
        padding-top: 16px;
    }
    .mobile-header {
        display: flex;
        min-height: 72px;
        align-items: center;
        gap: 10px;
        padding: 14px 16px 14px 76px;
        border-bottom: 1px solid var(--ui-border);
        background: var(--ui-surface);
        color: var(--ui-heading);
        font-weight: 700;
    }
    .mobile-menu-button {
        position: absolute;
        top: 14px;
        left: 16px;
        z-index: 900;
        display: grid;
        width: 44px;
        height: 44px;
        place-items: center;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(145deg, #2563eb, #173b7a);
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

@media (prefers-reduced-motion: reduce) {
    .content {
        transition: none;
    }
}
</style>
