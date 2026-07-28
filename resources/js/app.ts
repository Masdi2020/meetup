import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import type { DefineComponent } from "vue";

import Layout from "@/layouts/Layout.vue";

createInertiaApp({
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>("./pages/**/*.vue")
        );

        const withoutLayout = [
            "Login",
            "Register",
            "Banner"
        ];

        if (!withoutLayout.includes(name)) {
            page.default.layout ??= Layout;
        }

        return page;
    },

    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h(App, props),
        })
            .use(plugin)
            .mount(el);
    },
});
