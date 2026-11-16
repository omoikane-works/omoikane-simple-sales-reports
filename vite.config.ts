// vite.config.ts

import { fileURLToPath } from "node:url";
import path from "node:path";
import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";

const rootDir = fileURLToPath(new URL(".", import.meta.url));

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            "@": path.resolve(rootDir, "assets/admin/src"),
        },
    },
    base: "",
    build: {
        manifest: true,
        outDir: path.resolve(rootDir, "assets/admin/dist"),
        emptyOutDir: true,
        rollupOptions: {
            input: path.resolve(rootDir, "assets/admin/src/main.ts"),
            output: {
                entryFileNames: "assets/[name]-[hash].js",
                chunkFileNames: "assets/[name]-[hash].js",
                assetFileNames: "assets/[name]-[hash][extname]",
            },
        },
    },
});
