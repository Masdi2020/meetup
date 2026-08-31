import {
    Armchair,
    Building2,
    CalendarDays,
    CircleUserRound,
    ClipboardList,
    History as HistoryIcon,
    LayoutDashboard,
    MonitorPlay,
    ScrollText,
    Settings,
    Users,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import type { UserRole } from '@/types/auth';

export type { UserRole } from '@/types/auth';

export type NavigationItem = {
    name: string;
    icon: LucideIcon;
    to: string;
};

export type Navigation = {
    brand: string;
    items: NavigationItem[];
};

export const navigationByRole: Record<UserRole, Navigation> = {
    admin: {
        brand: 'Panel Admin',
        items: [
            { name: 'Dashboard', icon: LayoutDashboard, to: '/admin' },
            { name: 'Peminjaman', icon: ClipboardList, to: '/admin/bookings' },
            { name: 'Ruangan', icon: Building2, to: '/admin/rooms' },
            { name: 'Fasilitas', icon: Armchair, to: '/admin/facilities' },
            { name: 'Pengguna', icon: Users, to: '/admin/users' },
            { name: 'Audit', icon: ScrollText, to: '/admin/audits' },
            { name: 'Pengaturan', icon: Settings, to: '/admin/settings' },
            { name: 'Profil', icon: CircleUserRound, to: '/profile' },
        ],
    },
    display: {
        brand: 'Panel Display',
        items: [
            { name: 'Ruangan', icon: MonitorPlay, to: '/display' },
            { name: 'Profil', icon: CircleUserRound, to: '/profile' },
        ],
    },
    user: {
        brand: 'Panel Pengguna',
        items: [
            { name: 'Ruangan', icon: CalendarDays, to: '/availability' },
            { name: 'Peminjaman', icon: ClipboardList, to: '/booking' },
            { name: 'Riwayat', icon: HistoryIcon, to: '/riwayat' },
            { name: 'Profil', icon: CircleUserRound, to: '/profile' },
        ],
    },
};
