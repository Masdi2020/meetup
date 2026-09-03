import type { FlashMessages } from './shared';

export type UserRole = 'admin' | 'display' | 'user';

export type User = {
    id: number;
    name: string;
    username: string;
    created_at: string;
    updated_at: string;
    force_change_password: boolean;
    role: UserRole;
};

export type Auth = {
    user: User;
};

export interface ProfilePageProps {
    user: Pick<User, 'name' | 'username'>;
    flash: FlashMessages;
}
