# ENG-069 — Gestión de secretos: alcance acordado

Tercera historia de la Fase 14 — Seguridad y cumplimiento. El roadmap pide cinco puntos: Variables de entorno, Rotación, Llaves de integraciones, Prohibición de secretos en Git, Gestión por ambiente.

## Estado previo encontrado (investigación, no una decisión del usuario)

- **Variables de entorno**: todos los secretos se leen vía `env()` en archivos de `config/*.php` (nunca hardcodeados en el código; se buscó explícitamente y no se encontró ningún caso). No existe ninguna validación de que las variables requeridas estén presentes al arrancar — nada falla rápido si falta `APP_KEY` o las credenciales de AWS/S3 en producción.
- **Rotación** y **Llaves de integraciones**: las llaves de integración de simuladores (`Modules\Simulation`) ya tienen un mecanismo completo construido junto con ENG-067 — `RotateSimulatorIntegrationKeyHandler`, hash SHA-256 (nunca se persiste la llave en texto plano, solo se revela una vez), y ciclo de vida completo (rotar/suspender/reactivar/retirar). No hay ninguna otra integración externa activa en el sistema (Postmark/Resend/Slack en `config/services.php` son *stubs* sin ningún módulo que los consuma) — no hay nada más que rotar.
- El hueco real de "Rotación" está en **Sanctum**: `config/sanctum.php` tiene `'expiration' => null` — los tokens de acceso nunca expiran, y no existe ninguna política de expiración ni rotación para la autenticación principal del sistema.
- **Prohibición de secretos en Git**: `.gitignore` ya excluye correctamente `.env`/`.env.*` manteniendo `.env.example`. Pero no existe absolutamente ningún mecanismo de escaneo de secretos: no hay `.github/workflows/`, ni pre-commit hooks, ni gitleaks/git-secrets/trufflehog configurado en ningún lugar del repositorio.
- **Gestión por ambiente**: solo existe un `.env.example` en la raíz; no hay separación formal de secretos por ambiente, gestor externo (Vault/AWS Secrets Manager/Docker secrets), ni documentación de estrategia por ambiente.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance reducido**: expiración de tokens Sanctum (cierra el hueco real de "Rotación"), validación de variables de entorno requeridas al arrancar (falla rápido en producción si falta un secreto), y un resguardo ligero de escaneo de secretos en Git (script + hook local, documentado) — **sin** construir un pipeline de CI completo ni integrar un gestor de secretos externo. "Llaves de integraciones" no requiere trabajo nuevo (ya resuelto por ENG-067). "Gestión por ambiente" se cubre únicamente en la medida de la validación de variables por ambiente (punto 2 abajo), no con infraestructura de gestor de secretos.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Expiración de tokens Sanctum**: se cambia `'expiration' => null` a `env('SANCTUM_EXPIRATION_MINUTES', 43200)` (30 días por defecto) — mismo patrón ya usado en ese archivo para `SANCTUM_STATEFUL_DOMAINS`/`SANCTUM_TOKEN_PREFIX`. No requiere cambios en `SanctumAccessTokenIssuer` ni en ningún caso de uso: Sanctum calcula la expiración a partir de `created_at + config('sanctum.expiration')` en su propio guard, de forma transparente. Se eligió un valor generoso (no unos minutos) porque no existe ningún mecanismo de *refresh token* en el sistema — un valor corto forzaría reautenticación frecuente sin ninguna forma de renovar la sesión silenciosamente.
- **Validación de variables de entorno requeridas**: se separa en (a) `Modules\Foundation\Infrastructure\Environment\RequiredSecretsValidator`, una clase pura (`ensureAllPresent(array $values): void`) que no sabe nada de Laravel ni de `env()` — recibe un array asociativo ya resuelto y lanza `MissingRequiredSecrets` si algún valor es `null` o cadena vacía; y (b) el cableado en `FoundationServiceProvider::boot()`, que solo se ejecuta cuando `app()->environment('production')` y arma el array leyendo `config()` (nunca `env()` directamente, porque `env()` deja de funcionar fuera de los archivos de configuración una vez que `config:cache` está activo en producción). Variables requeridas en producción: `APP_KEY`, la contraseña de la conexión de base de datos activa, y las credenciales S3 (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`) — `Modules\FileStorage` usa el disco `s3` de forma incondicional (`S3FileStorage::DISK`), así que esas credenciales son requeridas en producción sin importar `FILESYSTEM_DISK`.
- **Por qué no se prueba el cableado del `ServiceProvider` con una prueba de Feature**: forzar `APP_ENV=production` a media suite de pruebas es impráctico y arriesgado (el resto de la suite corre en `testing`). La lógica de validación (`RequiredSecretsValidator`) sí se prueba exhaustivamente de forma unitaria; el `if ($this->app->environment('production'))` que la invoca es una única línea trivial, consistente con cómo el resto del código ya tiene comportamiento condicionado por ambiente sin pruebas de integración dedicadas a ese `if`.
- **Escaneo de secretos en Git**: se separa en (a) `Modules\Foundation\Infrastructure\Security\SecretPatternScanner`, una clase pura (`scan(string $line): list<string>`) que evalúa una línea de texto contra un conjunto acotado de patrones de alta confianza (AWS Access Key ID, AWS Secret Access Key, bloques de llave privada PEM, *webhooks* de Slack) — deliberadamente no se intenta detectar "cualquier contraseña" de forma genérica (alta tasa de falsos positivos, fuera del alcance "ligero" acordado); (b) un comando Artisan `secrets:scan` que lee líneas por STDIN y reporta coincidencias con su número de línea, saliendo con código 1 si hay alguna; (c) un hook de Git en `.githooks/pre-commit` (shell, sin dependencias de PHP/Docker en su lógica de detección) que hace `git diff --cached` y lo canaliza al comando vía `docker compose exec -T app php artisan secrets:scan`, bloqueando el commit si se detecta algo. El hook no se activa automáticamente (Git no ejecuta hooks de un directorio versionado por defecto) — se documenta cómo activarlo con `git config core.hooksPath .githooks`.

## Incluye (del roadmap)

- Variables de entorno (validación de presencia al arrancar en producción).
- Rotación (expiración de tokens Sanctum; las llaves de simuladores ya estaban resueltas).
- Prohibición de secretos en Git (escáner + hook local).

## Diferido explícitamente

- Llaves de integraciones (sin trabajo nuevo — ya resuelto por ENG-067).
- Pipeline de CI completo (GitHub Actions) con gitleaks u otra herramienta de escaneo integrada.
- Gestor de secretos externo (Vault, AWS Secrets Manager, Docker secrets) y cualquier estrategia formal de gestión de secretos por ambiente más allá de la validación de variables requeridas.
- Rotación forzada o programada de tokens Sanctum (solo se agrega expiración; la rotación sigue siendo "cerrar sesión y volver a iniciar sesión", como ya ocurre hoy).
