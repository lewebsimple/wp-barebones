import type { Plugin } from "vue";
import ui from "@nuxt/ui/vue-plugin";

export default {
  install(app) {
    app.use(ui);
  },
} as Plugin;
