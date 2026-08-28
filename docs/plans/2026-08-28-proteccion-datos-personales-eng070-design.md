# ENG-070 — Protección de datos personales: alcance acordado

Cuarta historia de la Fase 14 — Seguridad y cumplimiento. El roadmap pide seis puntos: Minimización, Retención, Eliminación, Anonimización, Exportación de datos personales, Consentimiento.

## Estado previo encontrado (investigación, no una decisión del usuario)

- **Minimización**: ya satisfecha. `Modules\Identity`'s `User` solo almacena `name`/`email`/`password`/`status`/`last_login_at` — ningún módulo recolecta más de lo necesario (sin fecha de nacimiento, teléfono, dirección, cédula, en ningún lugar del sistema).
- **Retención**: no existe ningún mecanismo — `routes/console.php` solo tiene el comando `inspire` de scaffold, cero tareas programadas, cero soft-deletes.
- **Eliminación**: no existe ningún flujo de "eliminar mi cuenta" ni de borrado administrativo. Sin embargo, la mayoría de las tablas que referencian `user_id` YA tienen `cascadeOnDelete()` configurado (certificados, notificaciones, sesiones de simulación, tablas de gamificación, pasaporte vial, intentos de examen, inscripciones, eventos de aprendizaje). Dos excepciones sin FK en absoluto: `authorization_role_assignments` y `audit_logs` (columna `user_id` nullable pero sin `foreign()`).
- **Anonimización**: no existe ningún mecanismo. Confirmado greenfield también por el propio roadmap: ENG-066 (Analítica nacional anonimizada) está diferido explícitamente bloqueado en "Anonimización... Consentimientos...".
- **Exportación de datos personales**: no existe ningún endpoint de autoservicio ("descarga todo lo que el sistema tiene sobre mí"). Las exportaciones de ENG-062 son todas administrativas (cursos, inscripciones, auditoría), no de autoservicio para el propio usuario.
- **Consentimiento**: existe un precedente angosto — `Modules\Notification`'s `NotificationPreference` con `consentGiven`/`consentUpdatedAt`, un booleano simple sin versión de política legal (ya señalado como diferido en la nota de ENG-057). No existe ningún módulo ni tabla de consentimiento general versionado por política legal.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance completo**: se implementan los seis puntos, incluyendo piezas que el alcance reducido habría diferido: borrado físico real (no anonimización en el lugar) con limpieza explícita de las tablas sin FK, un sistema de consentimiento versionado por política legal construido ahora (no diferido a ENG-071), retención con un período de inactividad real, y exportación granular a través de todos los módulos que almacenan datos de un usuario.
2. **Período de retención**: 3 años de inactividad (medida desde `last_login_at`, o `created_at` si el usuario nunca inició sesión) antes de que una cuenta sea candidata a eliminación automática. Configurable vía `IDENTITY_RETENTION_INACTIVITY_YEARS`, no hardcodeado.
3. **Certificados ante una cuenta eliminada**: se conservan indefinidamente, desvinculados del usuario — el certificado sigue siendo verificable por su código de validación (`Modules\Certification`'s verificación pública ya existente, ENG-04x) aunque la cuenta se elimine. Esto requiere que `certificates.user_id` pase de `cascadeOnDelete()` a `nullOnDelete()` (columna nullable).

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

### Eliminación (borrado físico)

- **Un único `DeleteAccountHandler` en `Modules\Identity`** reutilizado tanto por el flujo de autoservicio (`DELETE /api/v1/me`, autenticado) como por el job de retención (ver abajo) — ambos terminan en el mismo borrado físico de la fila `users`, la única diferencia es quién lo dispara.
- **Cascadas ya existentes se mantienen sin cambio** para todo lo demás con `cascadeOnDelete()` (notificaciones, sesiones de simulación, gamificación, pasaporte vial, intentos de examen, inscripciones, eventos de aprendizaje) — consecuencia aceptada y documentada: los indicadores institucionales agregados de ENG-065 dejarán de contar la contribución histórica de una cuenta eliminada. Es el comportamiento esperado de un borrado físico real (a diferencia de la alternativa de anonimización en el lugar que el usuario descartó explícitamente).
- **Dos correcciones de esquema nuevas**:
  - `authorization_role_assignments.user_id` gana FK con `cascadeOnDelete()` (una asignación de rol de un usuario eliminado no tiene ningún valor independiente).
  - `audit_logs.user_id` gana FK con `nullOnDelete()` (la fila de auditoría sobrevive — es un registro de seguridad/cumplimiento, no un dato personal que deba desaparecer — pero pierde su vínculo con el usuario eliminado). Reutiliza el mismo patrón de "actor nulo" ya establecido en ENG-068 para acciones sin actor HTTP.
  - `certificates.user_id` cambia de `cascadeOnDelete()` a `nullOnDelete()` con la columna nullable (decisión confirmada arriba). Esto ondula por todo `Modules\Certification`: `Certificate::userId` pasa a `?string`, `restore()` acepta `?string $userId`, `CertificateResponse`/`CertificateVerificationResponse` exponen el campo como nullable, `EloquentCertificateRepository::toDomain()` deja de forzar `(string)` sobre un valor potencialmente nulo (bug latente detectado y corregido en el mismo cambio: `(string) null` produce `''`, no `null`), y `VerifyCertificateHandler` deja de asumir que el titular siempre existe (`holderName` se vuelve `?string`, `null` cuando el usuario fue eliminado).
- **Auditoría de la eliminación**: se registra `identity.account_deleted` antes de borrar la fila (para que `entityId` sea útil), con `outcome: 'success'` y `userId` igual al actor (el propio usuario en autoservicio, `null` cuando lo dispara el job de retención — mismo patrón de actor nulo de ENG-068).

### Retención

- Comando Artisan programado (`identity:purge-inactive-accounts`, diario vía `routes/console.php`) que busca usuarios con `last_login_at` (o `created_at` si nunca inició sesión) anterior a `now() - IDENTITY_RETENTION_INACTIVITY_YEARS` años, y llama al mismo `DeleteAccountHandler` por cada uno. No es un reporte pasivo: el alcance completo confirmado pide una política de retención real, no solo visibilidad.

### Anonimización

- Queda satisfecha por las dos correcciones de `nullOnDelete()` (certificados y auditoría): son los dos lugares donde un dato vinculado a un usuario eliminado debe sobrevivir sin poder re-identificar a la persona. No se anonimiza el resto de los datos (que se borran físicamente por la decisión de alcance).

### Consentimiento (nuevo módulo `Modules\Legal`)

- Nuevo módulo siguiendo la estructura estándar (`Domain/Application/Infrastructure/Presentation/Tests`), con dos agregados: `ConsentPolicy` (clave, versión, fecha de vigencia — p. ej. `terms_of_service`, `privacy_policy`) y `UserConsent` (usuario, política, versión aceptada, fecha). Se registra la versión exacta aceptada (no solo un booleano) para poder responder "¿qué versión de la política aceptó este usuario y cuándo?".
- Rutas: `GET /api/v1/legal/policies` (política vigente de cada clave, pública), `POST /api/v1/legal/consents` (el usuario autenticado acepta una política+versión), `GET /api/v1/legal/me/consents` (historial propio).
- Este módulo es deliberadamente genérico (no específico de menores de edad) para que ENG-071 (Seguridad para menores de edad, consentimiento parental) lo extienda en vez de construir un segundo mecanismo de consentimiento desde cero.

### Exportación de datos personales

- Cada módulo que posee datos de un usuario expone una query de solo lectura vía `QueryBus` (`Get{Modulo}DataExportQuery(userId)`), autorizada implícitamente por ser siempre los datos del propio usuario autenticado (sin permiso especial, igual que `GET /me`). Un controlador central en `Modules\Identity` (`GET /api/v1/me/data-export`) despacha una query por módulo y agrega los resultados en un único JSON, agrupado por módulo — formato estructurado y de uso común, alineado con el espíritu de portabilidad de datos.
- Módulos cubiertos: Identity (perfil), Academic (inscripciones, intentos de examen), Certification (certificados), Simulation (sesiones y telemetría), Learning (eventos de aprendizaje), Gamification (insignias, retos, experiencia), Notification (preferencias y consentimiento de notificaciones), Authorization (asignaciones de rol), RoadPassport (pasaporte vial), Legal (historial de consentimientos).

## Incluye (del roadmap)

- Minimización (confirmación, sin cambios de código).
- Retención (job programado con período de inactividad configurable).
- Eliminación (autoservicio + reutilizado por retención).
- Anonimización (certificados y auditoría desvinculados, no borrados).
- Exportación de datos personales (autoservicio, granular, todos los módulos).
- Consentimiento (nuevo módulo `Modules\Legal`, versionado por política).

## Diferido explícitamente

- Consentimiento parental específico (ENG-071 construirá sobre `Modules\Legal`).
- Eliminación disparada por un administrador en nombre de otro usuario (solo autoservicio y el job de retención en esta historia).
- Cualquier interfaz de administración para gestionar el contenido de las políticas legales más allá de crear una nueva versión (sin editor de texto enriquecido, sin flujo de aprobación).
