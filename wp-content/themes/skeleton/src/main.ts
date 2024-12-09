import "@/css/index.css";
import { createApp, h, type Plugin } from "vue";
import { version } from "../package.json";
import UApp from "~/@nuxt/ui/dist/runtime/components/App.vue";

console.log(`Skeleton v${version} (${import.meta.env.MODE})`);

// Create Vue.js application from #app root element
const appElement = document.getElementById("app");
const innerHTML = appElement?.innerHTML || "";
export const app = createApp({
  render() {
    return h(UApp, null, {
      default: () => h({ template: innerHTML }),
    });
  },
});

// Vue.js plugins
const plugins: Record<string, { default: Plugin }> = import.meta.glob("./plugins/*.ts", { eager: true });
Object.keys(plugins).forEach((key) => {
  app.use(plugins[key].default);
});

app.mount("#app", true);
