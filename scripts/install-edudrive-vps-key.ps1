$ErrorActionPreference = 'Stop'

$taskKeyPath = Join-Path $env:USERPROFILE '.ssh\edudrive_contabo_user'
$taskPublicKeyPath = "$taskKeyPath.pub"
$taskHost = '207.244.241.15'

New-Item -ItemType Directory -Force -Path (Split-Path $taskKeyPath) | Out-Null

if (-not (Test-Path -LiteralPath $taskKeyPath)) {
    Write-Host 'Creando llave SSH dedicada para EduDrive...'
    & ssh-keygen -q -t ed25519 -f $taskKeyPath -N '""' -C 'edudrive-deploy@vr506'

    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo crear la llave SSH.'
    }
}

Write-Host ''
Write-Host 'Introduce ahora la contrasena nueva de root cuando SSH la solicite.' -ForegroundColor Cyan
Get-Content -LiteralPath $taskPublicKeyPath |
    & ssh "root@$taskHost" 'umask 077; mkdir -p ~/.ssh; cat >> ~/.ssh/authorized_keys; sed -i "s/\r$//" ~/.ssh/authorized_keys; chmod 700 ~/.ssh; chmod 600 ~/.ssh/authorized_keys; chown -R root:root ~/.ssh'

if ($LASTEXITCODE -ne 0) {
    throw 'No se pudo instalar la llave publica en el VPS.'
}

Write-Host ''
Write-Host 'Verificando acceso sin contrasena...'
& ssh -i $taskKeyPath -o BatchMode=yes "root@$taskHost" 'echo SSH_KEY_AUTH_OK'

if ($LASTEXITCODE -ne 0) {
    throw 'El VPS aun no acepta la llave SSH.'
}

Write-Host ''
Write-Host 'LLAVE INSTALADA Y VERIFICADA' -ForegroundColor Green
Read-Host 'Presiona Enter para cerrar esta ventana'
