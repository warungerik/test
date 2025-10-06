import "./bootstrap.js";

import "../css/admin/bootstrap.min.css";
import "../css/admin/icons.min.css";
import "../css/admin/app.min.css";
import "../css/toast.css";

import "./plugins/admin/bootstrap.bundle.min.js";
import "./plugins/admin/simplebar/simplebar.min.js";
// import "../js/plugins/admin/apexcharts/apexcharts.min.js";
// import "../js/plugins/admin/index.init.js";
import "./plugins/admin/DynamicSelect.js";
import "./plugins/admin/app.js";

import "./plugins/toast.js";
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
        page.default.layout = page.default.layout || Layout;

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
