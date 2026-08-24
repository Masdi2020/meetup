import type { UserRole } from '@/types/auth';
export type { UserRole } from '@/types/auth';

export type NavigationItem = {
    name: string;
    icon: string;
    to: string;
};

export type Navigation = {
    brand: string;
    items: NavigationItem[];
};

export const navigationByRole: Record<UserRole, Navigation> = {
    admin: {
        brand: 'Admin Menu',
        items: [
            { name: 'Dashboard', icon: '📊', to: '/admin' },
            { name: 'Peminjaman', icon: '📝', to: '/admin/bookings' },
            { name: 'Ruangan', icon: '🏢', to: '/admin/rooms' },
            { name: 'Fasilitas', icon: '🪑', to: '/admin/facilities' },
            { name: 'Pengguna', icon: '👤', to: '/admin/users' },
            { name: 'Audit', icon: '📜', to: '/admin/audits' },
            { name: 'Pengaturan', icon: '⚙️', to: '/admin/settings' },
            { name: 'Profil', icon: '👤', to: '/profile' },
        ],
    },
    display: {
        brand: 'Display',
        items: [
            { name: 'Ruangan', icon: 'R', to: '/display' },
            { name: 'Profil', icon: 'P', to: '/profile' },
        ],
    },
    user: {
        brand: 'User Menu',
        items: [
            { name: 'Ruangan', icon: '🗓️', to: '/availability' },
            { name: 'Peminjaman', icon: '📝', to: '/booking' },
            { name: 'Riwayat', icon: '📋', to: '/riwayat' },
            { name: 'Profil', icon: '👤', to: '/profile' },
        ],
    },
};
