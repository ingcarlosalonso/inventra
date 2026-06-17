<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresa no encontrada</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 48px 40px; max-width: 440px; width: 100%; text-align: center; }
        .icon { width: 64px; height: 64px; margin: 0 auto 24px; background: #1e3a5f; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .icon svg { width: 32px; height: 32px; color: #60a5fa; stroke: #60a5fa; }
        h1 { font-size: 22px; font-weight: 600; margin-bottom: 12px; color: #f8fafc; }
        p { font-size: 14px; color: #94a3b8; line-height: 1.6; }
        .subdomain { display: inline-block; margin-top: 16px; padding: 6px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; font-size: 13px; font-family: monospace; color: #64748b; }
        .contact { margin-top: 24px; font-size: 13px; color: #64748b; }
        .contact strong { color: #94a3b8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
            </svg>
        </div>
        <h1>Empresa no encontrada</h1>
        <p>No existe ninguna cuenta registrada para este dominio.</p>
        <div class="subdomain">{{ request()->getHost() }}</div>
        <p class="contact">Si sos cliente de In-ventra y creés que es un error,<br>escribinos a <strong>soporte@in-ventra.com</strong></p>
    </div>
</body>
</html>
