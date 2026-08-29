# ENG-085 — Despliegue y ambientes: diseño

**Fase:** 17 — Plataforma y operación avanzada
**Alcance acordado:** reducido (recomendado, elegido por el usuario).

## Contexto y hallazgos de la investigación

A diferencia de todas las historias anteriores de esta fase, ENG-085 no
trae ninguna viñeta "Incluye:" — solo lista cinco ambientes previstos:
Local, Desarrollo, QA, Staging, Producción. La siguiente historia
(ENG-086 — CI/CD) sí trae viñetas explícitas (Pint, Larastan, Pest,
Construcción de imágenes, Migraciones controladas, Despliegue, Rollback),
lo que sitúa el pipeline de despliegue en sí fuera de ENG-085. Se
interpreta ENG-085 como configuración y documentación real que varía —o
debería variar deliberadamente— entre ambientes, no la mecánica de
construir/desplegar imágenes.

La investigación (un agente en background) encontró:

- **Solo un condicional real por ambiente en todo el código**:
  `FoundationServiceProvider::boot()` (ENG-069) exige secretos completos
  únicamente cuando `app()->environment('production')`.
- **`config/cors.php` no existe publicado** — el comportamiento CORS
  depende enteramente de los defaults internos del middleware
  `HandleCors` de Laravel, sin ninguna decisión explícita de orígenes
  permitidos por ambiente.
- **`database/seeders/DatabaseSeeder.php` crea una cuenta de prueba
  (`test@example.com`) incondicionalmente** — se ejecutaría igual en
  cualquier ambiente donde se invoque `db:seed`, sin ninguna guarda.
- **`.env.example` es el stock genérico de Laravel** (`DB_CONNECTION=sqlite`)
  y no refleja el setup real de Docker usado por el proyecto
  (`DB_CONNECTION=pgsql`, Redis, MinIO, Mailpit) — no sirve como plantilla
  fiel para ningún ambiente real.
- **No existe ningún documento** en `docs/**` que describa las diferencias
  reales entre los 5 ambientes previstos, ni siquiera atando lo que ya
  varía por ambiente en historias previas (secretos de ENG-069, canal
  Slack condicional de ENG-083).

## Decisiones de diseño

### A. `config/cors.php`

Se publica el archivo estándar de Laravel con `allowed_origins` leído de
una variable de entorno nueva `CORS_ALLOWED_ORIGINS` (lista separada por
comas), default `*` — preserva el comportamiento actual permisivo en Local/
Desarrollo sin romper nada, pero da un punto de configuración explícito
para restringir a dominios reales en QA/Staging/Producción vía `.env` de
cada ambiente (no versionado).

### B. Guard de ambiente en `DatabaseSeeder`

`DatabaseSeeder::run()` gana `if (! app()->environment(['local', 'testing'])) { return; }`
antes de crear la cuenta de prueba — evita que `test@example.com` con una
contraseña de factory conocida pueda crearse accidentalmente en QA/
Staging/Producción si alguien ejecuta `db:seed` ahí.

### C. `.env.example` actualizado

Se actualiza para reflejar fielmente el setup real de Docker de este
proyecto (Postgres/Redis/MinIO/Mailpit, mismo patrón que el `.env` real
no versionado), y gana `CORS_ALLOWED_ORIGINS` con un comentario explicando
que debe restringirse en QA/Staging/Producción.

### D. `docs/operaciones/ambientes.md`

Nuevo documento con la matriz real de diferencias entre los 5 ambientes,
atando explícitamente lo que ya existe disperso en historias anteriores:

| Aspecto | Local/Desarrollo | QA/Staging | Producción |
|---|---|---|---|
| `APP_DEBUG` | `true` | `false` | `false` |
| Validación de secretos (ENG-069) | no aplica | no aplica | `FoundationServiceProvider` exige `APP_KEY`/DB/S3 completos |
| Canal Slack de alertas (ENG-083) | inactivo (sin webhook) | según se configure | activo (`LOG_SLACK_WEBHOOK_URL` configurado) |
| Respaldo de base de datos (ENG-084) | opcional/manual | recomendado | `backup:database` diario vía scheduler |
| `CORS_ALLOWED_ORIGINS` | `*` (todo permitido) | dominios de QA/Staging | dominios de producción únicamente |
| Seeder de cuenta de prueba | se ejecuta | no se ejecuta (guarda de ambiente) | no se ejecuta (guarda de ambiente) |

## Fuera de alcance (documentado explícitamente)

- Archivos `compose.*.yaml` nuevos por ambiente (overlays de Docker Compose
  para QA/Staging/Producción).
- Plantillas `.env.*.example` separadas por ambiente.
- Cualquier mecánica de construcción de imágenes, pipeline de despliegue o
  rollback — eso es ENG-086, la siguiente historia, no se toca aquí.

## Plan de verificación

Pint, PHPStan (`--memory-limit=512M`) sobre los archivos tocados y luego
sobre el repo completo. Se confirma manualmente que el middleware CORS
sigue respondiendo correctamente con la nueva configuración publicada
(antes dependía de defaults internos sin archivo propio).
