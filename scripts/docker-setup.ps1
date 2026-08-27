$ErrorActionPreference = 'Stop'

$projectDir = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $projectDir '.env.docker'
$envExample = Join-Path $projectDir '.env.docker.example'
$composeFile = Join-Path $projectDir 'compose.yaml'

Set-Location $projectDir

docker compose version | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw 'Docker Compose no está disponible. Instala Docker Desktop y vuelve a ejecutar este archivo.'
}

if (-not (Test-Path $envFile)) {
    Copy-Item $envExample $envFile
    Write-Host 'Se creó .env.docker desde el ejemplo seguro.'
}

$content = Get-Content $envFile -Raw
if ($content -match '(?m)^APP_KEY=$') {
    $bytes = New-Object byte[] 32
    $generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try {
        $generator.GetBytes($bytes)
    }
    finally {
        $generator.Dispose()
    }

    $appKey = 'base64:' + [Convert]::ToBase64String($bytes)
    $content = $content -replace '(?m)^APP_KEY=$', "APP_KEY=$appKey"
    [System.IO.File]::WriteAllText($envFile, $content, (New-Object System.Text.UTF8Encoding($false)))
    Write-Host 'Se generó APP_KEY.'
}

docker compose --env-file $envFile -f $composeFile up -d --build
if ($LASTEXITCODE -ne 0) { throw 'No se pudo construir o iniciar los contenedores.' }

docker compose --env-file $envFile -f $composeFile exec -T app php artisan migrate --seed --force
if ($LASTEXITCODE -ne 0) { throw 'Falló la creación de tablas o datos iniciales.' }

docker compose --env-file $envFile -f $composeFile exec -T app php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw 'Falló la limpieza de cachés.' }

$portMatch = [regex]::Match((Get-Content $envFile -Raw), '(?m)^APP_PORT=(.+)$')
$port = if ($portMatch.Success) { $portMatch.Groups[1].Value.Trim('"', "`r") } else { '8080' }

Write-Host ''
Write-Host 'SDAYA Cartas quedó instalado.' -ForegroundColor Green
Write-Host "Dirección: http://localhost:$port"
Write-Host 'Usuario inicial: revisa ADMIN_EMAIL en .env.docker'
Write-Host 'Contraseña inicial: revisa ADMIN_PASSWORD en .env.docker y cámbiala después del primer ingreso.'
