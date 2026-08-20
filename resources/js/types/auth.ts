export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
    force_change_password: boolean;
    role: 'admin' | 'display' | 'user';
};

export type Auth = {
    user: User;
};
