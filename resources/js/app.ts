import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';

import Toast from '@/components/Toast.vue';
import { useToast } from '@/composables/useToast';
import Layout from '@/layouts/Layout.vue';
import { configureEcho } from '@laravel/echo-vue';

configureEcho({
    broadcaster: 'reverb',
});

interface Flash {
    success?: string;
    error?: string;
}

createInertiaApp({
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        );

        const withoutLayout = ['Login', 'Register', 'Banner'];

        if (!withoutLayout.includes(name)) {
            page.default.layout ??= Layout;
        }

        return page;
    },

    setup({ el, App, props, plugin }) {
        const app = createApp({
            setup() {
                const { showToast } = useToast();

                router.on('success', (event) => {
                    const flash = (
                        event.detail.page.props as {
                            flash?: Flash;
                        }
                    ).flash;

                    if (flash?.success) {
                        showToast(flash.success, 'success');
                    }

                    if (flash?.error) {
                        showToast(flash.error, 'error');
                    }
                });

                return () => h('div', [h(Toast), h(App, props)]);
            },
        });

        app.use(plugin).mount(el);
    },
});
