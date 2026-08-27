param(
    [string]$EnvironmentFile = '.env.docker',
    [string]$ComposeFile = 'compose.yaml'
)

$ErrorActionPreference = 'Stop'
$projectDir = Split-Path -Parent $PSScriptRoot
Set-Location $projectDir

$envPath = if ([System.IO.Path]::IsPathRooted($EnvironmentFile)) { $EnvironmentFile } else { Join-Path $projectDir $EnvironmentFile }
$composePath = if ([System.IO.Path]::IsPathRooted($ComposeFile)) { $ComposeFile } else { Join-Path $projectDir $ComposeFile }
$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$backupDir = Join-Path $projectDir "backups/sdaya_$stamp"
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null

$dbContainer = (docker compose --env-file $envPath -f $composePath ps -q db).Trim()
$appContainer = (docker compose --env-file $envPath -f $composePath ps -q app).Trim()

if (-not $dbContainer -or -not $appContainer) {
    throw 'Los servicios app y db deben estar en ejecución.'
}

docker exec $dbContainer sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc -f /tmp/sdaya_database.dump'
if ($LASTEXITCODE -ne 0) { throw 'No se pudo respaldar PostgreSQL.' }
docker cp "${dbContainer}:/tmp/sdaya_database.dump" (Join-Path $backupDir 'database.dump')
docker exec $dbContainer rm -f /tmp/sdaya_database.dump

docker exec $appContainer tar -czf /tmp/sdaya_documents.tar.gz -C /var/www/html/storage/app/private .
if ($LASTEXITCODE -ne 0) { throw 'No se pudieron respaldar los documentos.' }
docker cp "${appContainer}:/tmp/sdaya_documents.tar.gz" (Join-Path $backupDir 'documents.tar.gz')
docker exec $appContainer rm -f /tmp/sdaya_documents.tar.gz

@(
    "SDAYA Cartas - respaldo $stamp"
    'Base de datos: database.dump (formato personalizado de PostgreSQL)'
    'Documentos: documents.tar.gz'
    "Entorno: $envPath"
    "Compose: $composePath"
) | Set-Content (Join-Path $backupDir 'LEEME.txt') -Encoding UTF8

Write-Host "Respaldo creado en: $backupDir" -ForegroundColor Green
