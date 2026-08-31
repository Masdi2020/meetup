<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { computed } from 'vue';
import type { NavigationItem } from '@/config/navigation';

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        menus: NavigationItem[];
        brand?: string;
        subtitle?: string;
        activeTo?: string;
        userName?: string;
        userRole?: string;
    }>(),
    {
        brand: 'MEETUP',
        subtitle: 'Meeting Room',
        userName: 'Pengguna',
        userRole: 'user',
    },
);

const emit = defineEmits<{
    (e: 'toggle'): void;
}>();

const userInitials = computed(() =>
    props.userName
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase(),
);

const roleLabel = computed(
    () =>
        ({
            admin: 'Admin',
            display: 'Display',
            user: 'Pengguna',
        })[props.userRole] ?? props.userRole,
);
</script>

<template>
    <aside
        class="sidebar"
        :class="{ 'sidebar--collapsed': !isOpen }"
        aria-label="Navigasi utama"
    >
        <div class="sidebar__glow" aria-hidden="true" />

        <header class="sidebar__header">
            <Transition name="brand-fade">
                <div v-if="isOpen" class="brand-lockup">
                    <span class="brand-lockup__mark">
                        <img src="/favicon.svg" alt="" aria-hidden="true" />
                    </span>
                    <span class="brand-lockup__copy">
                        <strong>{{ brand }}</strong>
                        <small>{{ subtitle }}</small>
                    </span>
                </div>
            </Transition>

            <button
                type="button"
                class="sidebar__toggle"
                :aria-label="isOpen ? 'Ciutkan sidebar' : 'Buka sidebar'"
                :title="isOpen ? 'Ciutkan sidebar' : 'Buka sidebar'"
                @click="emit('toggle')"
            >
                <Menu
                    class="sidebar__hamburger"
                    :class="{ 'sidebar__hamburger--collapsed': !isOpen }"
                    :size="21"
                    :stroke-width="2.1"
                    aria-hidden="true"
                />
            </button>
        </header>

        <div v-if="isOpen" class="sidebar__section-label">Navigasi</div>

        <nav class="sidebar__nav">
            <Link
                v-for="menu in menus"
                :key="menu.name"
                :href="menu.to"
                class="menu-item"
                :class="{
                    'menu-item--collapsed': !isOpen,
                    'menu-item--active': menu.to === activeTo,
                }"
                :title="!isOpen ? menu.name : undefined"
                :aria-label="!isOpen ? menu.name : undefined"
                :aria-current="menu.to === activeTo ? 'page' : undefined"
                :aria-disabled="menu.to === activeTo ? 'true' : undefined"
                :tabindex="menu.to === activeTo ? -1 : undefined"
                @click="menu.to === activeTo && $event.preventDefault()"
            >
                <span class="menu-item__icon">
                    <component :is="menu.icon" :size="20" :stroke-width="1.9" />
                </span>
                <span class="menu-item__text" :aria-hidden="!isOpen">
                    {{ menu.name }}
                </span>
            </Link>
        </nav>

        <footer class="sidebar__footer">
            <div v-if="isOpen" class="user-card">
                <span class="user-card__avatar">{{ userInitials }}</span>
                <span class="user-card__identity">
                    <strong>{{ userName }}</strong>
                    <small>{{ roleLabel }}</small>
                </span>
            </div>
            <slot name="sidebar-footer" />
        </footer>
    </aside>
</template>

<style scoped>
.sidebar {
    position: fixed;
    z-index: 1000;
    top: 0;
    left: 0;
    display: flex;
    width: 244px;
    height: 100vh;
    height: 100dvh;
    flex-direction: column;
    overflow: hidden;
    border-right: 1px solid rgb(255 255 255 / 9%);
    background: linear-gradient(180deg, #173b7a 0%, #102a5c 52%, #091a3d 100%);
    color: #fff;
    box-shadow: 12px 0 36px rgb(15 23 42 / 18%);
    transition:
        width 0.2s cubic-bezier(0.4, 0, 0.2, 1),
        transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar--collapsed {
    width: 76px;
}

.sidebar__glow {
    position: absolute;
    top: -100px;
    right: -100px;
    width: 260px;
    height: 260px;
    border-radius: 999px;
    background: radial-gradient(circle, rgb(96 165 250 / 22%), transparent 68%);
    pointer-events: none;
}

.sidebar__header {
    position: relative;
    z-index: 1;
    display: flex;
    min-height: 88px;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 18px 16px 14px 18px;
    border-bottom: 1px solid rgb(255 255 255 / 9%);
}

.sidebar--collapsed .sidebar__header {
    justify-content: center;
    padding: 18px 12px 14px;
}

.brand-lockup {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 11px;
}

.brand-lockup__mark {
    display: grid;
    width: 42px;
    height: 42px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid rgb(255 255 255 / 22%);
    border-radius: 13px;
    background: rgb(255 255 255 / 94%);
    box-shadow: 0 8px 20px rgb(3 10 30 / 28%);
}

.brand-lockup__mark img {
    width: 29px;
    height: 29px;
    object-fit: contain;
}

.brand-lockup__copy {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.brand-lockup__copy strong {
    overflow: hidden;
    font-size: 15px;
    font-weight: 750;
    letter-spacing: 0.08em;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.brand-lockup__copy small {
    margin-top: 2px;
    overflow: hidden;
    color: #a9bee8;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sidebar__toggle {
    display: grid;
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid rgb(255 255 255 / 12%);
    border-radius: 11px;
    background: rgb(255 255 255 / 7%);
    color: #dbeafe;
    cursor: pointer;
    transition:
        border-color 0.2s ease,
        background 0.2s ease,
        transform 0.2s ease;
}

.sidebar__toggle:hover {
    border-color: rgb(255 255 255 / 24%);
    background: rgb(255 255 255 / 14%);
    transform: translateY(-1px);
}

.sidebar--collapsed .sidebar__toggle {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.sidebar--collapsed .sidebar__toggle:hover {
    transform: translateX(-50%) translateY(-1px);
}

.sidebar__hamburger {
    transition: transform 0.2s cubic-bezier(0.22, 1, 0.36, 1);
}

.sidebar__hamburger--collapsed {
    transform: scale(0.9);
}

.sidebar__section-label {
    padding: 21px 24px 8px;
    color: #829bc9;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.sidebar__nav {
    position: relative;
    z-index: 1;
    display: flex;
    min-height: 0;
    flex: 1;
    flex-direction: column;
    gap: 6px;
    overflow-x: hidden;
    overflow-y: auto;
    padding: 6px 14px 18px;
    scrollbar-width: thin;
    scrollbar-color: rgb(255 255 255 / 18%) transparent;
    transition: padding 0.2s ease;
}

.sidebar--collapsed .sidebar__nav {
    align-items: center;
    padding: 18px 10px;
}

.menu-item {
    position: relative;
    display: flex;
    width: 100%;
    min-height: 48px;
    align-items: center;
    gap: 12px;
    padding: 7px 10px;
    border: 1px solid transparent;
    border-radius: 13px;
    color: #bfd0ef;
    text-decoration: none;
    transition:
        border-color 0.2s ease,
        background 0.2s ease,
        color 0.2s ease,
        transform 0.2s ease,
        width 0.2s ease,
        padding 0.18s ease,
        gap 0.2s ease;
}

.menu-item:hover {
    border-color: rgb(255 255 255 / 9%);
    background: rgb(255 255 255 / 8%);
    color: #fff;
    transform: translateX(2px);
}

.menu-item--active {
    border-color: rgb(147 197 253 / 28%);
    background: linear-gradient(
        90deg,
        rgb(59 130 246 / 28%),
        rgb(59 130 246 / 10%)
    );
    color: #fff;
    cursor: default;
    pointer-events: none;
    box-shadow: inset 0 1px 0 rgb(255 255 255 / 7%);
}

.menu-item--collapsed {
    width: 52px;
    justify-content: center;
    gap: 0;
    padding: 6px;
}

.menu-item--collapsed:hover {
    transform: translateY(-1px);
}

.menu-item__icon {
    display: grid;
    width: 34px;
    height: 34px;
    flex: 0 0 auto;
    place-items: center;
    border-radius: 10px;
    background: rgb(255 255 255 / 5%);
    transition: background 0.2s ease;
}

.menu-item--active .menu-item__icon {
    background: #fff;
    color: #2563eb;
    box-shadow: 0 5px 14px rgb(3 7 18 / 18%);
}

.menu-item__text {
    max-width: 150px;
    overflow: hidden;
    flex: 1;
    font-size: 14px;
    font-weight: 560;
    opacity: 1;
    text-overflow: ellipsis;
    white-space: nowrap;
    transform: translateX(0);
    transition:
        max-width 0.18s ease,
        opacity 0.13s ease,
        transform 0.18s ease;
}

.menu-item--collapsed .menu-item__text {
    max-width: 0;
    flex: 0 0 auto;
    opacity: 0;
    transform: translateX(-8px);
}

.menu-item--active .menu-item__text {
    font-weight: 700;
}

.sidebar__footer {
    position: relative;
    z-index: 1;
    display: grid;
    gap: 10px;
    padding: 14px;
    border-top: 1px solid rgb(255 255 255 / 9%);
    background: rgb(3 10 30 / 18%);
}

.sidebar--collapsed .sidebar__footer {
    padding: 14px 10px;
}

.user-card {
    display: flex;
    min-width: 0;
    align-items: flex-start;
    gap: 11px;
    padding: 5px 4px;
}

.user-card__avatar {
    display: grid;
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid rgb(255 255 255 / 15%);
    border-radius: 12px;
    background: rgb(96 165 250 / 18%);
    color: #dbeafe;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.04em;
}

.user-card__identity {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.user-card__identity strong {
    display: -webkit-box;
    overflow: hidden;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.3;
    overflow-wrap: anywhere;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.user-card__identity small {
    margin-top: 2px;
    color: #8fa7d3;
    font-size: 11px;
}

.brand-fade-enter-active,
.brand-fade-leave-active {
    transition:
        opacity 0.16s ease,
        transform 0.22s ease;
}

.brand-fade-enter-from,
.brand-fade-leave-to {
    opacity: 0;
    transform: translateX(-8px);
}

@media (max-width: 768px) {
    .sidebar,
    .sidebar--collapsed {
        width: min(88vw, 300px);
        transform: translateX(0);
    }

    .sidebar--collapsed {
        transform: translateX(-100%);
        pointer-events: none;
    }

    .sidebar__header {
        min-height: 82px;
    }

    .menu-item {
        min-height: 50px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .sidebar,
    .sidebar__toggle,
    .sidebar__nav,
    .menu-item,
    .menu-item__text,
    .sidebar__hamburger,
    .brand-fade-enter-active,
    .brand-fade-leave-active {
        transition: none;
    }
}
</style>
