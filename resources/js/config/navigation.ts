import {
    Armchair,
    Building2,
    CalendarDays,
    ClipboardList,
    History as HistoryIcon,
    LayoutDashboard,
    MonitorPlay,
    ScrollText,
    Settings,
    Users,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { index as availabilityIndex } from '@/routes/availability';
import { index as displayIndex } from '@/routes/display';
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

const adminItems: NavigationItem[] = [
    { name: 'Dashboard', icon: LayoutDashboard, to: '/admin' },
    { name: 'Peminjaman', icon: ClipboardList, to: '/admin/bookings' },
    { name: 'Ruangan', icon: Building2, to: '/admin/rooms' },
    { name: 'Fasilitas', icon: Armchair, to: '/admin/facilities' },
    { name: 'Pengguna', icon: Users, to: '/admin/users' },
];

export const navigationByRole: Record<UserRole, Navigation> = {
    super_admin: {
        brand: 'Panel Super Admin',
        items: [
            ...adminItems,
            { name: 'Audit', icon: ScrollText, to: '/admin/audits' },
            { name: 'Pengaturan', icon: Settings, to: '/admin/settings' },

            {
                name: 'Kalender Ruangan',
                icon: CalendarDays,
                to: availabilityIndex.url(),
            },
            { name: 'Display', icon: MonitorPlay, to: displayIndex.url() },
        ],
    },
    admin: {
        brand: 'Panel Admin',
        items: adminItems,
    },
    display: {
        brand: 'Panel Display',
        items: [{ name: 'Ruangan', icon: MonitorPlay, to: '/display' }],
    },
    user: {
        brand: 'Panel Pengguna',
        items: [
            { name: 'Ruangan', icon: CalendarDays, to: '/availability' },
            { name: 'Peminjaman', icon: ClipboardList, to: '/booking' },
            { name: 'Riwayat', icon: HistoryIcon, to: '/riwayat' },
        ],
    },
};
