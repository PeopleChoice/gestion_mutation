<#
    build-windows.ps1
    --------------------------------------------------------------------------
    Construit l'exécutable / installeur Windows de Gestion Mutations avec Tauri.

    Arborescence attendue :
        <projet Laravel>\
        └── versionwind\           <- tout le packaging Tauri
            ├── server.php
            ├── .env.tauri
            ├── scripts\build-windows.ps1   (ce fichier)
            └── src-tauri\

    Étapes :
      1. Dépendances PHP de production (composer --no-dev)
      2. Compilation des assets front (Vite)
      3. Copie Laravel embarquée -> versionwind\src-tauri\laravel
         (+ injection de server.php et .env.tauri)
      4. Téléchargement du runtime PHP portable (si absent)
      5. Build Tauri (installeur NSIS)

    Usage :
      powershell -ExecutionPolicy Bypass -File versionwind\scripts\build-windows.ps1
#>

$ErrorActionPreference = "Stop"

# versionwind\scripts -> versionwind -> <projet Laravel>
$versionwind = Split-Path -Parent $PSScriptRoot
$projectRoot = Split-Path -Parent $versionwind
$srcTauri    = Join-Path $versionwind "src-tauri"

Write-Host "========================================================" -ForegroundColor Cyan
Write-Host " Build Windows — Gestion Mutations (Tauri)" -ForegroundColor Cyan
Write-Host " Projet : $projectRoot" -ForegroundColor DarkGray
Write-Host "========================================================" -ForegroundColor Cyan

Set-Location $projectRoot

# 1. Dépendances PHP de production
Write-Host "`n[1/5] composer install (production)..." -ForegroundColor Green
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Assets front
Write-Host "`n[2/5] Compilation des assets (Vite)..." -ForegroundColor Green
npm install
npm run build

# On ne met PAS la config en cache : elle dépend du .env choisi à l'exécution.
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Copie Laravel embarquée
Write-Host "`n[3/5] Préparation de src-tauri\laravel..." -ForegroundColor Green
$dest = Join-Path $srcTauri "laravel"
if (Test-Path $dest) { Remove-Item $dest -Recurse -Force }
New-Item -ItemType Directory -Path $dest | Out-Null

# Copie du projet en excluant ce qui ne doit pas être embarqué.
# (versionwind est exclu pour éviter la récursion.)
robocopy $projectRoot $dest /MIR `
    /XD ".git" "node_modules" "versionwind" "tests" "storage" ".github" `
    /XF ".env" "d.env copy" "*.sqlite" ".DS_Store" `
    /NFL /NDL /NJH /NJS /NP | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy a échoué (code $LASTEXITCODE)." }

# Injection du routeur et du modèle .env (rangés dans versionwind).
Copy-Item (Join-Path $versionwind "server.php")  (Join-Path $dest "server.php")  -Force
Copy-Item (Join-Path $versionwind ".env.tauri")  (Join-Path $dest ".env.tauri")  -Force

# Arborescence storage minimale (de toute façon relocalisée à l'exécution).
foreach ($d in @(
    "storage\app\public",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs"
)) {
    New-Item -ItemType Directory -Path (Join-Path $dest $d) -Force | Out-Null
}

# 4. Runtime PHP portable
Write-Host "`n[4/5] Runtime PHP portable..." -ForegroundColor Green
if (-not (Test-Path (Join-Path $srcTauri "php\php.exe"))) {
    & (Join-Path $PSScriptRoot "download-php.ps1")
} else {
    Write-Host "    Déjà présent (src-tauri\php\php.exe)." -ForegroundColor DarkGray
}

# 5. Build Tauri
Write-Host "`n[5/5] Build Tauri (installeur NSIS)..." -ForegroundColor Green
Set-Location $versionwind

if (-not (Test-Path (Join-Path $srcTauri "icons\icon.ico"))) {
    $logo = Join-Path $projectRoot "logo.png"
    if (Test-Path $logo) {
        Write-Host "    Génération des icônes depuis logo.png..." -ForegroundColor Yellow
        npx --yes @tauri-apps/cli icon $logo
    } else {
        Write-Host "    (Aucun logo.png — Tauri utilisera ses icônes par défaut.)" -ForegroundColor Yellow
    }
}

npx --yes @tauri-apps/cli build

Write-Host "`n========================================================" -ForegroundColor Cyan
Write-Host " Terminé. Installeur dans :" -ForegroundColor Green
Write-Host "   versionwind\src-tauri\target\release\bundle\nsis\" -ForegroundColor Green
Write-Host "========================================================" -ForegroundColor Cyan
