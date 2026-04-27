import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const POLL_INTERVAL_MS = 60_000

// Shared state across components (module-level singleton)
const unreadCount = ref(0)
const notifications = ref([])
const loading = ref(false)

let pollTimer = null

function startPolling() {
    if (pollTimer) return
    pollTimer = setInterval(fetchUnreadCount, POLL_INTERVAL_MS)
}

function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null }
}

async function fetchUnreadCount() {
    try {
        const { data } = await axios.get('/api/notifications/unread-count')
        unreadCount.value = data.count
    } catch {
        // silently ignore — network errors shouldn't break the UI
    }
}

async function fetchAll() {
    loading.value = true
    try {
        const { data } = await axios.get('/api/notifications')
        notifications.value = data.data
        unreadCount.value = notifications.value.filter(n => !n.read).length
    } finally {
        loading.value = false
    }
}

async function markRead(id) {
    try {
        const { data } = await axios.post(`/api/notifications/${id}/read`)
        unreadCount.value = data.count
        const n = notifications.value.find(n => n.id === id)
        if (n) n.read = true
    } catch { /* ignore */ }
}

async function markAllRead() {
    try {
        await axios.post('/api/notifications/read-all')
        unreadCount.value = 0
        notifications.value.forEach(n => { n.read = true })
    } catch { /* ignore */ }
}

async function remove(id) {
    try {
        const { data } = await axios.delete(`/api/notifications/${id}`)
        unreadCount.value = data.count
        notifications.value = notifications.value.filter(n => n.id !== id)
    } catch { /* ignore */ }
}

export function useNotifications() {
    onMounted(() => {
        fetchUnreadCount()
        startPolling()
    })

    onUnmounted(() => {
        // Only stop if no other component is using it (handled by module singleton)
        // In practice the TopBar never unmounts, so this is fine
    })

    return {
        unreadCount,
        notifications,
        loading,
        fetchAll,
        fetchUnreadCount,
        markRead,
        markAllRead,
        remove,
    }
}
