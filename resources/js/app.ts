import { createInertiaApp, router } from '@inertiajs/vue3';
import { configureEcho } from '@laravel/echo-vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import 'v-calendar/style.css';

import Toast from '@/components/Toast.vue';
import { useToast } from '@/composables/useToast';
import Layout from '@/layouts/Layout.vue';
import type { FlashMessages } from '@/types/shared';

configureEcho({
    broadcaster: 'reverb',
});

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
                            flash?: FlashMessages;
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
