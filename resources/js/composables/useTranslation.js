import { usePage } from '@inertiajs/vue3'

export function useTranslation() {
    const page = usePage()

    const t = (key, replacements = {}) => {
        const translations = page.props.translations ?? {}
        const keys = key.split('.')
        let value = translations

        for (const k of keys) {
            value = value?.[k]
        }

        if (typeof value !== 'string') return key

        return Object.entries(replacements).reduce(
            (str, [k, v]) => str.replaceAll(`:${k}`, v),
            value,
        )
    }

    return { t }
}
