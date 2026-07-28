<script setup lang="ts">
import { Link } from "@inertiajs/vue3";

const menus = [
    {
        title: "Dashboard",
        icon: "📊",
        route: "/admin"
    },
    {
        title: "Peminjaman",
        icon: "📝",
        route: "/admin/bookings"
    },
    {
        title: "Ruangan",
        icon: "🏢",
        route: "/admin/rooms"
    },
    {
        title: "Gedung",
        icon: "🏬",
        route: "/admin/buildings"
    },
    {
        title: "Fasilitas",
        icon: "🪑",
        route: "/admin/facilities"
    },
    {
        title: "Pengguna",
        icon: "👤",
        route: "/admin/users"
    },
    {
        title: "Audit",
        icon: "📜",
        route: "/admin/audits"
    },
    {
        title: "Pengaturan",
        icon: "⚙️",
        route: "/admin/settings"
    }
];
</script>

<template>
    <aside class="sidebar">
        <h2>ADMIN</h2>

        <Link
            v-for="menu in menus"
            :key="menu.route"
            :href="menu.route"
            class="menu"
        >
            {{ menu.icon }}
            <span>{{ menu.title }}</span>
        </Link>
    </aside>
</template>
