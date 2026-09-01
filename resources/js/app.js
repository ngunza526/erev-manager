import '../css/app.css';
import 'bootstrap';
import './offline-sync';
import './offline-media';
import { createApp, h } from 'vue';
import { createInertiaApp, Link, router } from '@inertiajs/vue3';

const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

createInertiaApp({
  resolve: (name) => pages[`./Pages/${name}.vue`],
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .component('Link', Link)
      .mount(el);
  },
});

window.ereve = { router };

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
}
