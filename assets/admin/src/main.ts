import { createApp } from "vue";
import App from "./App.vue";
import SettingsApp from "./SettingsApp.vue";

const templateTarget = document.getElementById("ossr-admin");
const settingsTarget = document.getElementById("ossr-settings");

if (templateTarget) {
    createApp(App).mount(templateTarget);
} else if (settingsTarget) {
    createApp(SettingsApp).mount(settingsTarget);
}
