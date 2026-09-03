import type { PageProps as InertiaPageProps } from '@inertiajs/core';

export interface FlashMessages {
    success?: string;
    error?: string;
    generated_password?: string;
}

export interface FlashPageProps extends InertiaPageProps {
    flash: FlashMessages;
}
