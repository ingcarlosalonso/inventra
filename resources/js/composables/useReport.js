import { ref, reactive } from 'vue'
import { useApi } from '@/composables/useApi'
import axios from 'axios'

export function useReport(endpoint, extraFilters = {}) {
    const { get } = useApi()
    const data = ref(null)
    const loading = ref(true)
    const exporting = ref(false)
    const error = ref(null)

    const today = new Date().toISOString().split('T')[0]
    const thirtyDaysAgo = new Date(Date.now() - 29 * 86400000).toISOString().split('T')[0]

    const filters = reactive({
        date_from: thirtyDaysAgo,
        date_to: today,
        ...extraFilters,
    })

    async function fetchData() {
        loading.value = true
        error.value = null
        const { data: res, error: err } = await get(`/api/reports/${endpoint}`, { ...filters })
        if (res) {
            data.value = res
        } else {
            error.value = err ?? 'Error al cargar el reporte'
        }
        loading.value = false
    }

    async function exportXlsx() {
        exporting.value = true
        const params = new URLSearchParams(
            Object.fromEntries(
                Object.entries(filters).filter(([, v]) => v !== null && v !== undefined && v !== ''),
            ),
        ).toString()

        try {
            const response = await axios.get(
                `/api/reports/${endpoint}/export${params ? '?' + params : ''}`,
                { responseType: 'blob' },
            )
            const url = window.URL.createObjectURL(new Blob([response.data]))
            const link = document.createElement('a')
            link.href = url
            link.setAttribute('download', `reporte-${endpoint}-${today}.xlsx`)
            document.body.appendChild(link)
            link.click()
            link.remove()
            window.URL.revokeObjectURL(url)
        } catch (e) {
            console.error('Export error', e)
        } finally {
            exporting.value = false
        }
    }

    function fmtMoney(value) {
        const n = parseFloat(value) || 0
        return '$' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    }

    function fmtNum(value, decimals = 2) {
        const n = parseFloat(value) || 0
        return n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: decimals })
    }

    function fmtDate(iso) {
        if (!iso) return '—'
        return new Date(iso).toLocaleDateString(undefined, { day: '2-digit', month: '2-digit', year: '2-digit' })
    }

    function fmtDateTime(iso) {
        if (!iso) return '—'
        return new Date(iso).toLocaleDateString(undefined, {
            day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit',
        })
    }

    function stateBadgeStyle(color) {
        if (!color) return {}
        return { backgroundColor: color + '20', color, border: `1px solid ${color}40` }
    }

    return {
        data, loading, exporting, error, filters,
        fetchData, exportXlsx,
        fmtMoney, fmtNum, fmtDate, fmtDateTime, stateBadgeStyle,
    }
}
