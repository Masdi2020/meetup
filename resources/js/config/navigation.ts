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
import { dashboard, settings } from '@/routes/admin';
import { index as auditsIndex } from '@/routes/admin/audits';
import { index as bookingsIndex } from '@/routes/admin/bookings';
import { index as facilitiesIndex } from '@/routes/admin/facilities';
import { index as roomsIndex } from '@/routes/admin/rooms';
import { index as usersIndex } from '@/routes/admin/users';
import { index as availabilityIndex } from '@/routes/availability';
import { index as bookingIndex } from '@/routes/booking';
import { index as displayIndex } from '@/routes/display';
import { index as historyIndex } from '@/routes/history';
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
    { name: 'Dashboard', icon: LayoutDashboard, to: dashboard.url() },
    { name: 'Peminjaman', icon: ClipboardList, to: bookingsIndex.url() },
    { name: 'Ruangan', icon: Building2, to: roomsIndex.url() },
    { name: 'Fasilitas', icon: Armchair, to: facilitiesIndex.url() },
    { name: 'Pengguna', icon: Users, to: usersIndex.url() },
];

export const navigationByRole: Record<UserRole, Navigation> = {
    super_admin: {
        brand: 'Panel Super Admin',
        items: [
            ...adminItems,
            { name: 'Audit', icon: ScrollText, to: auditsIndex.url() },
            { name: 'Pengaturan', icon: Settings, to: settings.url() },

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
        items: [{ name: 'Ruangan', icon: MonitorPlay, to: displayIndex.url() }],
    },
    user: {
        brand: 'Panel Pengguna',
        items: [
            {
                name: 'Ruangan',
                icon: CalendarDays,
                to: availabilityIndex.url(),
            },
            { name: 'Peminjaman', icon: ClipboardList, to: bookingIndex.url() },
            { name: 'Riwayat', icon: HistoryIcon, to: historyIndex.url() },
        ],
    },
};
