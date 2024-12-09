import "@/css/index.css";
import { createApp, h } from "vue";
import { version } from "../package.json";
import App from "@/app.vue";

console.log(`Skeleton v${version} (${import.meta.env.MODE})`);

// Create Vue.js application from #app root element
const appElement = document.getElementById("app");
const innerHTML = appElement?.innerHTML || "";
export const app = createApp({
  render() {
    return h(App, null, {
      default: () => h("div", { innerHTML }),
    });
  },
});

app.mount("#app", true);
