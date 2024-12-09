import "@/css/index.css";
import { createApp, h } from "vue";
import ui from "@nuxt/ui/vue-plugin";
import { version } from "../package.json";
import UApp from "~/@nuxt/ui/dist/runtime/components/App.vue";

console.log(`Skeleton v${version} (${import.meta.env.MODE})`);

// Create Vue.js application from #app root element
const appElement = document.getElementById("app");
const innerHTML = appElement?.innerHTML || "";
export const app = createApp({
  render() {
    return h(UApp, null, {
      default: () => h("div", { innerHTML }),
    });
  },
});

app.use(ui);

app.mount("#app", true);
