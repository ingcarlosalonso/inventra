import './bootstrap'
import '../css/app.css'
import { createApp, h } from 'vue'
import { createInertiaApp, usePage } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'

const i18nPlugin = {
    install(app) {
        app.config.globalProperties.$t = (key, replacements = {}) => {
            const page = usePage()
            const translations = page.props.translations ?? {}
            const keys = key.split('.')
            let value = translations

            for (const k of keys) {
                value = value?.[k]
            }

            if (typeof value !== 'string') return key

            return Object.entries(replacements).reduce(
                (str, [k, v]) => str.replace(`:${k}`, v),
                value,
            )
        }
    },
}

createInertiaApp({
    title: (title) => (title ? `${title} — Inventra` : 'Inventra'),

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18nPlugin)
            .use(ZiggyVue)
            .mount(el)
    },

    progress: {
        color: '#6366f1',
    },
})
