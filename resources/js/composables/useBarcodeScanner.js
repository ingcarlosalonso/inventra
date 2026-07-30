import { onMounted, onBeforeUnmount } from 'vue'

// Characters arriving faster than this threshold (ms) are treated as scanner input.
// Human typing is typically 100ms+ between keystrokes; scanners are < 30ms.
const SCAN_MAX_INTERVAL_MS = 50
const SCAN_MIN_LENGTH = 3

/**
 * Detects barcode scanner input globally (without requiring focus on a specific input).
 *
 * When a scanner fires: characters arrive very fast and end with Enter.
 * When the user is typing normally in any text field, this composable does nothing.
 *
 * @param {import('vue').Ref} barcodeInputRef - ref to the barcode <input> element
 * @param {function(string): void} onScan - callback invoked with the scanned barcode string
 */
export function useBarcodeScanner(barcodeInputRef, onScan) {
  let buffer = ''
  let lastCharTime = 0
  let clearTimer = null

  function handleKeydown(e) {
    const inputEl = barcodeInputRef.value

    // Already in the barcode input — the existing @keydown.enter handler takes over
    if (inputEl && e.target === inputEl) return

    // User is typing in any other text field — don't intercept
    const tag = e.target.tagName
    if (tag === 'INPUT' || tag === 'TEXTAREA' || e.target.isContentEditable) return

    if (e.key === 'Enter') {
      if (buffer.length >= SCAN_MIN_LENGTH) {
        onScan(buffer)
      }
      buffer = ''
      clearTimeout(clearTimer)
      return
    }

    // Ignore non-printable keys (Shift, Ctrl, arrows, etc.)
    if (e.key.length !== 1) return

    const now = Date.now()

    // If too much time passed since the last character, it's probably human typing — reset
    if (now - lastCharTime > SCAN_MAX_INTERVAL_MS && buffer.length > 0) {
      buffer = ''
    }

    lastCharTime = now
    buffer += e.key

    // Safety net: clear buffer if no Enter arrives within 300ms
    clearTimeout(clearTimer)
    clearTimer = setTimeout(() => { buffer = '' }, 300)
  }

  onMounted(() => document.addEventListener('keydown', handleKeydown))
  onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown)
    clearTimeout(clearTimer)
  })
}
