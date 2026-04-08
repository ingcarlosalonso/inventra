import axios from 'axios'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export function useApi() {
    const errors = ref({})
    const loading = ref(false)

    function clearErrors() {
        errors.value = {}
    }

    async function request(method, url, data = null) {
        loading.value = true
        clearErrors()

        try {
            const config = { method, url }
            if (data) config.data = data

            const response = await axios(config)
            return { data: response.data, error: null }
        } catch (err) {
            if (err.response?.status === 401) {
                localStorage.removeItem('token')
                delete axios.defaults.headers.common['Authorization']
                router.visit('/login')
                return { data: null, error: null }
            }

            if (err.response?.status === 422) {
                errors.value = err.response.data.errors ?? {}
            }

            const message = err.response?.data?.message ?? err.message
            return { data: null, error: message }
        } finally {
            loading.value = false
        }
    }

    const get = (url, params = {}) => {
        const query = new URLSearchParams(
            Object.fromEntries(
                Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''),
            ),
        ).toString()
        return request('get', query ? `${url}?${query}` : url)
    }

    const post = (url, data) => request('post', url, data)
    const put = (url, data) => request('put', url, data)
    const patch = (url, data) => request('patch', url, data)
    const del = (url) => request('delete', url)

    return { errors, loading, clearErrors, get, post, put, patch, del }
}
