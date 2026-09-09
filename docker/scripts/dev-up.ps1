# Поднимает стек ЭТП (электронной торговой площадки) для локальной разработки.
# Запуск из любого каталога: powershell -File d:\OSPanel\domains\etp\backend\docker\scripts\dev-up.ps1

$ErrorActionPreference = "Stop"
$backendRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Set-Location $backendRoot

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "Создан backend/.env из .env.example"
}

if (-not (Test-Path "src\.env")) {
    Copy-Item "src\.env.example" "src\.env"
    Write-Host "Создан src/.env из .env.example"
}

Write-Host "Docker Compose up (без pgAdmin)..."
docker compose up -d --no-build
if ($LASTEXITCODE -ne 0) {
    Write-Host "Сборка образов могла понадобиться, повтор с build..."
    docker compose up -d
}

Write-Host "Ожидание PostgreSQL..."
$ready = $false
for ($i = 0; $i -lt 30; $i++) {
    docker exec postgres-etp pg_isready -U etp -d etp | Out-Null
    if ($LASTEXITCODE -eq 0) {
        $ready = $true
        break
    }
    Start-Sleep -Seconds 2
}
if (-not $ready) {
    throw "postgres-etp не готов"
}

Write-Host "Composer / миграции / сиды..."
docker exec -e XDEBUG_MODE=off backend-etp composer install --no-interaction --ignore-platform-reqs --no-progress
docker exec -e XDEBUG_MODE=off backend-etp php artisan migrate --force
docker exec -e XDEBUG_MODE=off backend-etp php artisan db:seed --force
docker exec -e XDEBUG_MODE=off backend-etp php artisan storage:link 2>$null
docker exec -e XDEBUG_MODE=off backend-etp php artisan config:clear
docker exec postgres-etp psql -U etp -d postgres -c "SELECT 1 FROM pg_database WHERE datname='etp_test'" | Out-Null
docker exec postgres-etp psql -U etp -d postgres -c "CREATE DATABASE etp_test;" 2>$null | Out-Null

$base = "http://localhost:8200"
if (Test-Path ".env") {
    $m = Select-String -Path ".env" -Pattern "^NGINX_PORT=(.+)$"
    if ($m) {
        $port = $m.Matches[0].Groups[1].Value.Trim()
        $base = "http://localhost:$port"
    }
}

Write-Host "Проверка $base/api/health ..."
try {
    $health = Invoke-RestMethod -Uri "$base/api/health" -TimeoutSec 15
    Write-Host ("health success={0} db={1} redis={2}" -f $health.success, $health.data.database, $health.data.redis)
} catch {
    Write-Host "Nginx ещё не ответил. Смотри: docker compose ps; docker logs nginx-etp"
    throw
}

Write-Host "Готово. API: $base/api/test  MailHog: http://localhost:8025  Reverb: ws://localhost:6001"
Write-Host "Демо-вход (пароль password, если не переопределён в .env):"
Write-Host "  super_admin  ИНН 770000000000"
Write-Host "  trade_admin  ИНН 770000000001"
Write-Host "  participant  ИНН 770000000002"
Write-Host "  auditor      ИНН 770000000003"
Write-Host "Демо-данные: компании/категории, ТЗП DEMO-*, CMS about/rules/contacts"
