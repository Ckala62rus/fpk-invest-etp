# Бэкап PostgreSQL ЭТП с хоста Windows (фаза 12.4).
# Запуск из каталога backend/:  powershell -File docker/scripts/backup-postgres.ps1

$ErrorActionPreference = "Stop"
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$outDir = Join-Path $root "backups"
New-Item -ItemType Directory -Force -Path $outDir | Out-Null
$outFile = Join-Path $outDir "etp-$stamp.sql"

docker exec postgres-etp pg_dump -U etp -d etp | Set-Content -Encoding utf8 $outFile
Write-Host "SQL dump: $outFile"
