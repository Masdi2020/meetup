import type { FlashMessages } from './shared';

export type UserRole = 'super_admin' | 'admin' | 'display' | 'user';

export interface RoleOption {
    id: number;
    code: UserRole;
    name: string;
}

export type User = {
    id: number;
    name: string;
    username: string;
    created_at: string;
    updated_at: string;
    force_change_password: boolean;
    role_id: number;
    role: UserRole;
};

export type Auth = {
    user: User;
};

export interface ProfilePageProps {
    user: Pick<User, 'name' | 'username'>;
    flash: FlashMessages;
}
