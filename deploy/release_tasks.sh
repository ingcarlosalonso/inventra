  #!/usr/bin/env bash
# release_tasks.sh — Tareas puntuales de cada release, además de las migraciones estándar.
#
# Las migraciones (central + tenants) ya corren solas en cada deploy y son idempotentes
# por naturaleza (Laravel las trackea por DB). Este archivo es para lo que una versión
# necesita ADEMÁS de eso: seeders puntuales, comandos artisan ad-hoc, limpieza de caché
# específica, backfills de datos, etc.
#
# Cómo agregar una tarea para el próximo release:
#   1. Sumá un bloque al final de este archivo, bajo un heading "## vX.Y".
#   2. Envolvé el/los comandos con run_once "id-unico-y-descriptivo" -- comando arg1 arg2...
#   3. El id debe ser único para siempre (incluí la versión en el nombre).
#
# run_once registra el id en storage/app/release_tasks_done (en el servidor) la primera vez
# que corre con éxito. En deploys futuros, si el id ya está marcado, lo saltea — así este
# archivo se puede dejar creciendo release tras release sin re-ejecutar nada viejo.
#
# Este script es invocado por deploy_project.sh; no se ejecuta manualmente salvo debugging.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY_PATH="${DEPLOY_PATH:-$(cd "$SCRIPT_DIR/.." && pwd)}"
DONE_LOG="$DEPLOY_PATH/storage/app/release_tasks_done"

mkdir -p "$(dirname "$DONE_LOG")"
touch "$DONE_LOG"

run_once() {
    local task_id="$1"
    shift
    [[ "$1" == "--" ]] && shift

    if grep -qxF "$task_id" "$DONE_LOG"; then
        echo "[release_tasks] '$task_id' ya ejecutada, salteando."
        return 0
    fi

    echo "[release_tasks] Ejecutando '$task_id'..."
    (cd "$DEPLOY_PATH" && "$@")
    echo "$task_id" >> "$DONE_LOG"
    echo "[release_tasks] '$task_id' completada."
}

# ─── v1.1 ──────────────────────────────────────────────────────────────────
# (Sin tareas puntuales por ahora: v1.1 solo requiere las migraciones estándar
# de central y tenants, que ya corren automáticamente en deploy_project.sh)

# ─── Próximo release ───────────────────────────────────────────────────────
# run_once "v1.2-seed-default-something" -- sudo -u www-data php artisan db:seed --class=DefaultSomethingSeeder --force
# run_once "v1.2-backfill-x" -- sudo -u www-data php artisan app:backfill-x
