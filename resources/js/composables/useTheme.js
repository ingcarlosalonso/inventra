function hexToHsl(hex) {
    const r = parseInt(hex.slice(1, 3), 16) / 255
    const g = parseInt(hex.slice(3, 5), 16) / 255
    const b = parseInt(hex.slice(5, 7), 16) / 255

    const max = Math.max(r, g, b)
    const min = Math.min(r, g, b)
    let h, s
    const l = (max + min) / 2

    if (max === min) {
        h = 0
        s = 0
    } else {
        const d = max - min
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min)
        switch (max) {
            case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break
            case g: h = ((b - r) / d + 2) / 6; break
            case b: h = ((r - g) / d + 4) / 6; break
        }
    }

    return [Math.round(h * 360), Math.round(s * 100), Math.round(l * 100)]
}

function buildPalette(hex) {
    const [h, s] = hexToHsl(hex)
    // Fixed lightness values matching the Tailwind indigo scale shape
    const shades = { 50: 97, 100: 93, 200: 86, 300: 77, 400: 64, 500: 52, 600: 42, 700: 35, 800: 27, 900: 21 }
    return Object.fromEntries(
        Object.entries(shades).map(([shade, l]) => [shade, `hsl(${h}, ${s}%, ${l}%)`])
    )
}

export function applyTheme(primaryHex) {
    if (!primaryHex || !/^#[0-9A-Fa-f]{6}$/.test(primaryHex)) { return }

    let style = document.getElementById('app-theme')
    if (!style) {
        style = document.createElement('style')
        style.id = 'app-theme'
        document.head.appendChild(style)
    }

    const p = buildPalette(primaryHex)

    style.textContent = `
        :root {
            --color-indigo-50:  ${p[50]};
            --color-indigo-100: ${p[100]};
            --color-indigo-200: ${p[200]};
            --color-indigo-300: ${p[300]};
            --color-indigo-400: ${p[400]};
            --color-indigo-500: ${p[500]};
            --color-indigo-600: ${p[600]};
            --color-indigo-700: ${p[700]};
            --color-indigo-800: ${p[800]};
            --color-indigo-900: ${p[900]};
        }
    `
}
