import "./bootstrap";

import "../css/populer.css";
import "../css/swiper.css";
import "../css/style.css";
import "../css/toast.css";
import "../css/custom.css";

import "../js/plugins/toast.js";

import "@fortawesome/fontawesome-free/css/all.min.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import Layout from "./template/layout.vue";

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob("./pages/**/*.vue", { eager: true });
        let page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        if (
            !name.startsWith("auth/") ||
            !name.startsWith("admin/") ||
            !name.startsWith("user/")
        ) {
            page.default.layout = page.default.layout || Layout;
        }
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
