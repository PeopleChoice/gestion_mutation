<#
    download-php.ps1
    --------------------------------------------------------------------------
    Télécharge un runtime PHP portable pour Windows (build NTS x64) et le place
    dans src-tauri/php, à côté du php.ini fourni.

    Ce runtime est embarqué dans l'application Tauri (resources) et sert à
    exécuter le serveur Laravel intégré sur le poste de l'utilisateur final.

    Usage :
        powershell -ExecutionPolicy Bypass -File scripts\download-php.ps1
        powershell -ExecutionPolicy Bypass -File scripts\download-php.ps1 -PhpVersion 8.3.14
#>

param(
    [string]$PhpVersion = "8.3.14",
    [string]$VsVersion  = "vs16"
)

$ErrorActionPreference = "Stop"

$root    = Split-Path -Parent $PSScriptRoot
$phpDir  = Join-Path $root "src-tauri\php"
$iniSrc  = Join-Path $phpDir "php.ini"
$tmpZip  = Join-Path $env:TEMP "php-portable.zip"

$fileName = "php-$PhpVersion-nts-Win32-$VsVersion-x64.zip"
$urls = @(
    "https://windows.php.net/downloads/releases/$fileName",
    "https://windows.php.net/downloads/releases/archives/$fileName"
)

Write-Host "==> Runtime PHP portable : $fileName" -ForegroundColor Cyan

# Sauvegarder le php.ini fourni (il sera réinjecté après extraction).
$iniBackup = $null
if (Test-Path $iniSrc) {
    $iniBackup = Get-Content -Raw -Path $iniSrc
}

$downloaded = $false
foreach ($url in $urls) {
    try {
        Write-Host "    Téléchargement depuis $url" -ForegroundColor DarkGray
        Invoke-WebRequest -Uri $url -OutFile $tmpZip -UseBasicParsing
        $downloaded = $true
        break
    } catch {
        Write-Host "    Indisponible ici, essai suivant..." -ForegroundColor Yellow
    }
}

if (-not $downloaded) {
    throw "Impossible de télécharger PHP $PhpVersion. Vérifiez la version sur https://windows.php.net/download/ (utilisez le build 'Non Thread Safe' x64) et relancez avec -PhpVersion."
}

# Nettoyer le dossier (sauf php.ini) puis extraire.
if (Test-Path $phpDir) {
    Get-ChildItem $phpDir -Exclude "php.ini" | Remove-Item -Recurse -Force
} else {
    New-Item -ItemType Directory -Path $phpDir | Out-Null
}

Write-Host "==> Extraction dans $phpDir" -ForegroundColor Cyan
Expand-Archive -Path $tmpZip -DestinationPath $phpDir -Force
Remove-Item $tmpZip -Force

# Réinjecter notre php.ini.
if ($iniBackup) {
    Set-Content -Path $iniSrc -Value $iniBackup -NoNewline
} elseif (Test-Path (Join-Path $phpDir "php.ini-production")) {
    Copy-Item (Join-Path $phpDir "php.ini-production") $iniSrc
}

# Vérification rapide.
$phpExe = Join-Path $phpDir "php.exe"
if (Test-Path $phpExe) {
    Write-Host "==> PHP installé :" -ForegroundColor Green
    & $phpExe -c $iniSrc -v
    Write-Host "==> Extensions chargées :" -ForegroundColor Green
    & $phpExe -c $iniSrc -m | Select-String -Pattern "pdo_mysql|pdo_sqlite|mbstring|openssl|gd|zip|curl|fileinfo"
} else {
    throw "php.exe introuvable après extraction."
}
