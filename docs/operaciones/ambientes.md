# Ambientes

Documento operativo derivado de ENG-085 (Despliegue y ambientes). Ver
`docs/plans/2026-08-29-despliegue-ambientes-eng085-design.md` para el
diseño técnico completo. Cubre la **configuración** que varía —o debería
variar deliberadamente— entre ambientes. El pipeline de construcción de
imágenes, despliegue y rollback es responsabilidad de ENG-086 (CI/CD), no
de este documento.

## Ambientes previstos

Local, Desarrollo, QA, Staging, Producción.

## Matriz de diferencias reales

| Aspecto | Local / Desarrollo | QA / Staging | Producción |
|---|---|---|---|
| `APP_ENV` | `local` | `qa` / `staging` | `production` |
| `APP_DEBUG` | `true` | `false` | `false` |
| Validación de secretos requeridos (ENG-069, `FoundationServiceProvider::boot()`) | no aplica | no aplica | exige `APP_KEY`, credenciales de base de datos y de S3 completos — la aplicación falla al arrancar si falta alguno |
| Alertas por Slack (ENG-083, canal `slack` en `config/logging.php`) | inactivo (`LOG_SLACK_WEBHOOK_URL` vacío) | activo si se configura | activo (`LOG_SLACK_WEBHOOK_URL` configurado, nivel `LOG_SLACK_LEVEL=critical`) |
| Formato de logs (ENG-083) | JSON en disco (`single`) | JSON en disco | JSON en disco — mismo formato en todos los ambientes, para que las herramientas de análisis de logs no necesiten distinguir |
| Respaldo de base de datos (ENG-084, `backup:database`) | manual/opcional | recomendado antes de cada release | diario vía el scheduler (`Schedule::command('backup:database')->daily()`) |
| `CORS_ALLOWED_ORIGINS` (ENG-085, `config/cors.php`) | `*` (cualquier origen) | dominios reales de QA/Staging | dominios reales de producción únicamente — nunca `*` |
| Seeder de cuenta de prueba (ENG-085, `DatabaseSeeder`) | se ejecuta (`test@example.com`) | no se ejecuta (guarda de ambiente `local`/`testing`) | no se ejecuta (misma guarda) |
| Versionado de objetos en MinIO/S3 (ENG-084) | habilitado (`files:ensure-bucket`) | habilitado | habilitado — mismo comportamiento en todos los ambientes |

## Notas

- **QA y Staging comparten el mismo perfil de configuración** en esta
  matriz — ambos son ambientes previos a producción con datos no reales,
  se diferencian entre sí por su propósito (QA para validación funcional,
  Staging como réplica más fiel de producción) más que por configuración
  de la aplicación.
- Ningún archivo de configuración de Laravel (`config/*.php`) tiene lógica
  condicional por ambiente más allá de lo ya listado arriba — el resto de
  la configuración es idéntica en todos los ambientes y solo cambia por
  variables de entorno (`.env` de cada ambiente, no versionado).
- No existen archivos `compose.*.yaml` ni `.env.*.example` separados por
  ambiente — `compose.yaml` es exclusivamente para Local/Desarrollo con
  Docker; la topología real de QA/Staging/Producción es responsabilidad
  de ENG-086.
