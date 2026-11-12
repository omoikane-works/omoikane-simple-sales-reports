// main.ts

import { createApp } from "vue";
import App from "./App.vue";

const mountTarget = document.getElementById("ossr-admin");

if (mountTarget === null) {
    console.warn("OSSR mount target was not found.");
} else {
    const app = createApp(App);
    app.mount(mountTarget);
}
