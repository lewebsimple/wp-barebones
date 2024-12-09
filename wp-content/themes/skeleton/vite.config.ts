import { homedir } from "os";
import { resolve } from "path";
import { defineConfig } from "vite";
import basicSsl from "@vitejs/plugin-basic-ssl";
import restart from "vite-plugin-restart";
import vue from "@vitejs/plugin-vue";
import ui from "@nuxt/ui/vite";

// https://vite.dev/config/
export default defineConfig(({ mode }) => ({
  ...(mode === "production" && {}),
  build: {
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      external: ["jquery"],
      input: "src/main.ts",
      output: { entryFileNames: "assets/[name].js" },
    },
    sourcemap: true,
  },
  clearScreen: false,
  css: { devSourcemap: true },
  plugins: [
    basicSsl({ certDir: resolve(homedir(), ".local/share/certs") }),
    restart({ reload: ["/**/*.php", "!vendor/**/*"] }),
    vue(),
    ui({ colorMode: false }),
  ],
  resolve: {
    alias: {
      "~": resolve(__dirname, "node_modules"),
      "@": resolve(__dirname, "src"),
      "vue": "vue/dist/vue.esm-bundler.js",
    },
  },
  server: {
    host: "0.0.0.0",
    origin: `https://localhost:3001`,
    port: 3001,
    strictPort: true,
  },
}));
