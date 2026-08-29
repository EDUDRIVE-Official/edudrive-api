# ENG-000 — Roadmap Técnico del Backend EDUDRIVE

## 1. Información del documento

| Campo | Valor |
|---|---|
| Código | ENG-000 |
| Nombre | Roadmap Técnico del Backend EDUDRIVE |
| Proyecto | EDUDRIVE |
| Componente | edudrive-api |
| Estado | Activo |
| Versión | 1.8.0 |
| Fecha | 2026-08-08 |
| Responsable | Equipo de Ingeniería EDUDRIVE |

---

## 2. Propósito

Este documento define el orden oficial de construcción del backend de EDUDRIVE.

Su objetivo es asegurar que el desarrollo avance de forma:

- Modular.
- Trazable.
- Segura.
- Probada.
- Documentada.
- Alineada con la arquitectura del ecosistema EDUDRIVE y SIMUDRIVE.

Este roadmap es la referencia principal para determinar:

- Qué se está desarrollando.
- Qué ya se completó.
- Qué depende de otros componentes.
- Qué debe validarse antes de avanzar.
- Qué commit corresponde a cada bloque funcional.

No deben agregarse nuevas historias técnicas sin registrarlas primero en este documento.

---

## 3. Stack técnico oficial

| Componente | Tecnología |
|---|---|
| Backend | Laravel 12 |
| Lenguaje | PHP 8.4 |
| Base de datos | PostgreSQL |
| Contenedores | Docker Compose |
| Caché y colas | Redis |
| Archivos | MinIO |
| Correo local | Mailpit |
| Autenticación API | Laravel Sanctum |
| Pruebas | Pest |
| Análisis estático | Larastan / PHPStan |
| Formato | Laravel Pint |
| Arquitectura | Modular, Clean Architecture y DDD pragmático |

---

## 4. Convenciones de desarrollo

Cada historia técnica deberá seguir este flujo:

Una historia no se considerará completada hasta que:

El código esté implementado.
Las pruebas correspondientes pasen.
composer quality finalice correctamente.
Se haya realizado el commit.
El estado se actualice en este documento.
5. Estados permitidos
Estado	Significado
Pendiente	Todavía no iniciado
En progreso	Actualmente en desarrollo
Bloqueado	No puede continuar por una dependencia
En validación	Código terminado, pendiente de pruebas
Completado	Implementado, probado y versionado
Diferido	Se realizará en una fase posterior
6. Fases del backend
Fase 0 — Fundamentos técnicos
ENG-001 — Inicialización del proyecto

Estado: Completado

Incluye:

Laravel 12.
PHP 8.4.
Docker Compose.
PostgreSQL.
Redis.
MinIO.
Mailpit.
Configuración de desarrollo local.
ENG-002 — Arquitectura modular

Estado: Completado

Incluye:

Directorio src/modules.
Namespace Modules\.
Separación por módulos.
Capas Domain, Application, Infrastructure y Presentation.
Service Providers por módulo.
ENG-003 — Estándares de calidad

Estado: Completado

Incluye:

Pest.
Laravel Pint.
Larastan.
PHPStan.
Comando composer quality.
Comando composer format.
ENG-004 — Foundation Module

Estado: Completado

Incluye:

Respuestas estándar de API.
ApiResponse.
Manejo de errores.
Correlation ID.
Health Check.
Base de excepciones HTTP.
ENG-005 — Persistencia base

Estado: Completado

Incluye:

PostgreSQL.
Migraciones.
Configuración Eloquent.
Pruebas de integración.
Convenciones para repositorios y mappers.
7. Fase 1 — Identidad y autenticación
ENG-006 — Dominio de usuarios

Estado: Completado

Incluye:

Entidad User.
Value Object Email.
Enum UserStatus.
Interfaz UserRepository.
UserModel.
UserMapper.
EloquentUserRepository.
Pruebas unitarias.
Pruebas de persistencia.
ENG-007 — Registro de usuarios

Estado: Completado

Incluye:

RegisterUserCommand.
RegisterUserResponse.
RegisterUserUseCase.
PasswordHasher.
UuidGenerator.
LaravelPasswordHasher.
LaravelUuidGenerator.
EmailAlreadyExists.
RegisterUserRequest.
AuthController.
Endpoint:
POST /api/v1/auth/register
ENG-008 — Autenticación con Sanctum

Estado: En progreso (solo falta ENG-008.8)

ENG-008.1 — Instalación de Laravel Sanctum

Estado: Completado

Incluye:

Paquete laravel/sanctum.
Configuración config/sanctum.php.
Migración personal_access_tokens.
Ejecución de migraciones.
Validación con composer quality.
ENG-008.2 — Servicio de verificación de contraseña

Estado: Completado

Incluye:

Extensión de PasswordHasher.
Verificación de contraseña.
Implementación mediante Laravel Hash.
ENG-008.3 — Servicio de emisión de tokens

Estado: Completado

Incluye:

Interfaz AccessTokenIssuer.
Implementación con Sanctum.
Encapsulación de createToken.
DTO de token.
ENG-008.4 — Login de usuario

Estado: Completado

Incluye:

LoginUserCommand.
LoginUserResponse.
LoginUserUseCase.
Excepción InvalidCredentials.
Verificación del estado del usuario.
LoginUserRequest.
Endpoint:
POST /api/v1/auth/login
ENG-008.5 — Perfil autenticado

Estado: Completado

Incluye:

Endpoint protegido:
GET /api/v1/auth/me
Identificación del usuario mediante Sanctum.
Respuesta de perfil sin información sensible.
ENG-008.6 — Cierre de sesión

Estado: Completado

Incluye:

Revocación del token actual.
Endpoint:
POST /api/v1/auth/logout
ENG-008.7 — Cierre de todas las sesiones

Estado: Completado

Incluye:

Revocación de todos los tokens del usuario.
Endpoint:
POST /api/v1/auth/logout-all
ENG-008.8 — Pruebas de autenticación

Estado: Completado

Nota (2026-07-29): los endpoints de login, /me, logout y logout-all funcionan y fueron validados manualmente, pero no existen todavía pruebas Feature automatizadas para ellos (solo hay un test de integración del repositorio de usuarios). Queda pendiente.

Nota (2026-08-29): la investigación confirmó que la nota anterior estaba desactualizada — sí existían tests que tocaban estos endpoints, pero incidentalmente, con otro propósito (auditoría, throttling, expiración de token), ninguno cubría explícitamente los 8 casos pedidos. Se creó `modules/Identity/Tests/Feature/LoginTest.php` con los 8 casos. Se encontró y corrigió un bug real preexistente: `InvalidCredentials` (lanzada tanto para contraseña incorrecta como para email inexistente, práctica de seguridad correcta) extendía `RuntimeException` plano en vez del `DomainException` base que usan sus hermanas `UserCannotAuthenticate`/`UserNotFound`, así que Laravel no la mapeaba a un error propio y producía HTTP 500 en vez de un 401 semántico — corregido, y actualizado `AuthAuditLogTest` que dependía del 500 incorrecto. También se encontró y corrigió un artefacto conocido de las pruebas de Laravel: el guard de Sanctum cachea el usuario resuelto dentro del mismo test, por lo que verificar la revocación de un token requirió `Auth::forgetGuards()` explícito entre la revocación y la petición que confirma el rechazo. "Usuario inactivo" y "Revocación de tokens" ya tenían la lógica de dominio construida (`UserStatus::canAuthenticate()`, `AccessTokenRevoker`); solo faltaban las pruebas. Detalle completo en `docs/plans/2026-08-29-pruebas-autenticacion-eng0088-design.md`.

Incluye pruebas para:

Login correcto.
Credenciales inválidas.
Usuario inexistente.
Usuario inactivo.
Acceso sin token.
Acceso con token.
Logout.
Revocación de tokens.
ENG-009 — Recuperación de contraseña

Estado: Pendiente

Incluye:

Solicitud de recuperación.
Token temporal.
Correo de recuperación.
Restablecimiento de contraseña.
Invalidación de sesiones anteriores.
Auditoría de la operación.

Endpoints previstos:

POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password
ENG-010 — Verificación de correo electrónico

Estado: Pendiente

Incluye:

Envío de enlace.
Reenvío de enlace.
Verificación de correo.
Registro de fecha de verificación.
Restricción de acciones para correos no verificados.
ENG-011 — Gestión de sesiones y dispositivos

Estado: Pendiente

Incluye:

Nombre del dispositivo.
Fecha de creación del token.
Último uso.
Revocación individual.
Expiración de tokens.
Políticas de seguridad por aplicación.
8. Fase 2 — Autorización y gobierno de acceso
ENG-012 — Roles del sistema

Estado: Parcial — ver sección 25

Nota (2026-07-31): se construyeron 4 de los 11 roles listados abajo (Superadministrador, Administrador institucional, Docente/Instructor y Estudiante), como parte de la historia de alcance reducido de Autorización y Organizaciones (ver sección 25 y ENG-LOG.md, IMP-022). Administrador EDUDRIVE, Coordinador, Tutor o encargado, Evaluador, Soporte e Integración SIMUDRIVE quedan pendientes.

Roles iniciales previstos:

Superadministrador.
Administrador EDUDRIVE.
Administrador institucional.
Coordinador.
Docente.
Instructor.
Estudiante.
Tutor o encargado.
Evaluador.
Soporte.
Integración SIMUDRIVE.
ENG-013 — Permisos

Estado: Parcial — ver sección 25

Nota (2026-07-31): se implementó un catálogo simple de 3 permisos (`organizations.manage`, `organizations.view`, `roles.manage`), su asignación a roles y verificación mediante el middleware `permission`, como parte de la historia de alcance reducido (ver sección 25 y ENG-LOG.md, IMP-022). Las políticas de acceso más allá de la verificación booleana por permiso quedan pendientes.

Nota (2026-08-01): se corrigió un defecto del middleware `permission` (`EnsurePermission`) — antes devolvía siempre una respuesta JSON aunque la petición no fuera a la API, lo que no se había notado porque solo se usaba en rutas `api/*`. Ahora negocia contenido igual que el resto de la aplicación (JSON solo para `api/*`/`expectsJson()`, página HTML de error en cualquier otro caso), lo que permitió reusarlo sin cambios de fondo en las primeras rutas web del panel de Organizaciones (ver ENG-LOG.md, IMP-023). Es una corrección, no una ampliación de alcance de ENG-013.

Nota (2026-08-02): se agregaron dos permisos nuevos al catálogo — `courses.manage` y `courses.view` — como parte del cierre de ENG-026 (Cursos). El catálogo pasa de 3 a 5 permisos. Detalle en ENG-LOG.md (IMP-024).

Incluye:

Catálogo de permisos.
Asignación de permisos a roles.
Verificación mediante middleware.
Políticas de acceso.
Pruebas de autorización.
ENG-014 — Contexto organizacional

Estado: Pendiente

Nota (2026-07-31): `RoleAssignment` incluye un campo `organizationId` opcional desde la historia de alcance reducido (ver sección 25), pero `PermissionChecker` lo ignora por completo: no hay cambio de contexto, ni restricción de datos por institución, ni ninguna otra lógica de autorización que lo utilice todavía. El campo existe en el modelo, pero no está funcionalmente conectado, por lo que esta historia se mantiene en Pendiente.

Incluye:

Usuario global.
Usuario institucional.
Roles por organización.
Cambio de contexto.
Restricción de datos por institución.
ENG-015 — Auditoría de accesos

Estado: Pendiente

Incluye:

Inicio de sesión.
Cierre de sesión.
Intentos fallidos.
Cambio de contraseña.
Cambio de correo.
Revocación de tokens.
Cambios de roles y permisos.
9. Fase 3 — Organizaciones e instituciones
ENG-016 — Organizaciones

Estado: Parcial — ver sección 25

Nota (2026-07-31): se implementó el aggregate `Organization` con endpoints de creación y listado, como parte de la historia de alcance reducido (ver sección 25 y ENG-LOG.md, IMP-022). El tipo institucional usa un enum simplificado de 5 valores (incluyendo un catch-all `Other`) que no distingue individualmente Universidades, Asociaciones ni Operadores EDUDRIVE, y no se implementó ningún campo adicional (información de contacto, ubicación, estado operativo, configuración regional).

Tipos previstos:

Centros educativos.
Escuelas de manejo.
Empresas.
Instituciones públicas.
Universidades.
Asociaciones.
Operadores EDUDRIVE.
ENG-017 — Sedes

Estado: Parcial — ver sección 25

Nota (2026-07-31): se implementó la entidad `Campus` y el endpoint para agregar una sede a una organización existente, como parte de la historia de alcance reducido (ver sección 25 y ENG-LOG.md, IMP-022). Solo se modeló el nombre de la sede; información de contacto, ubicación, estado operativo y configuración regional quedan pendientes.

Incluye:

Sedes por organización.
Información de contacto.
Ubicación.
Estado operativo.
Configuración regional.
ENG-018 — Membresías organizacionales

Estado: Parcial — ver sección 25

Nota (2026-07-31): `RoleAssignment(usuario, rol, organización)` sirve como un registro simple de membresía (vinculación de usuario, rol y fecha de asignación), construido como parte de la historia de alcance reducido (ver sección 25 y ENG-LOG.md, IMP-022). No existe estado de membresía, ni historial de cambios, ni revocación (el modelo es de solo inserción, sin endpoint `DELETE`).

Incluye:

Vinculación de usuarios.
Rol dentro de la organización.
Estado de la membresía.
Fecha de ingreso.
Historial de cambios.
ENG-019 — Grupos y cohortes

Estado: Pendiente

Incluye:

Secciones.
Grupos de estudiantes.
Cohortes.
Generaciones.
Asignación de docentes.
Periodos lectivos.
10. Fase 4 — Perfiles educativos
ENG-020 — Perfil del estudiante

Estado: Pendiente

Incluye:

Información académica.
Edad o rango etario.
Nivel educativo.
Necesidades de accesibilidad.
Preferencias de aprendizaje.
Estado del Pasaporte Vial.
ENG-021 — Perfil del docente o instructor

Estado: Pendiente

Incluye:

Especialidades.
Certificaciones.
Organizaciones relacionadas.
Grupos asignados.
Permisos de evaluación.
ENG-022 — Tutores y encargados

Estado: Pendiente

Incluye:

Relación con estudiantes menores.
Consentimientos.
Visualización de progreso.
Notificaciones.
Restricciones de privacidad.
ENG-023 — Consentimientos y privacidad

Estado: Pendiente

Incluye:

Consentimiento informado.
Tratamiento de datos.
Consentimiento parental.
Historial de aceptación.
Versionado de términos.
Revocación de consentimiento.
11. Fase 5 — Catálogo educativo

Nota (2026-07-29): esta fase se adelantó parcialmente fuera de orden, bajo los bloques de implementación IMP-020 e IMP-021 (ver ENG-LOG.md), antes de completar las Fases 2 y 3. Se implementó el agregado `Course` (dominio, persistencia, creación y listado de cursos vía `CommandBus`/`QueryBus`), lo que cubre parte del alcance de ENG-026 (Cursos). El resto de ENG-026 y las historias ENG-024, ENG-025, ENG-027 a ENG-029 permanecen pendientes. La historia técnica activa vuelve a las Fases 2 y 3 (Autorización y Organizaciones) antes de continuar aquí — ver sección 25.

ENG-024 — Catálogo de competencias

Estado: Completado

Nota (2026-08-03): se completó el catálogo regional latinoamericano de competencias viales con jerarquía de subcompetencias e indicadores observables, categorías y niveles de dominio controlados, persistencia PostgreSQL, casos de uso mediante `CommandBus`/`QueryBus` y API protegida por `competencies.manage`/`competencies.view`. Ver sección 25 y `docs/engineering/ENG-LOG.md` (IMP-025). Los perfiles normativos por país, asociaciones con cursos/evaluaciones/SIMUDRIVE y el versionado curricular quedan explícitamente diferidos a historias posteriores; no forman parte de este incremento.

Incluye:

Competencias viales.
Subcompetencias.
Indicadores.
Niveles de dominio.
Relación con teoría y práctica.
ENG-025 — Programas educativos

Estado: Completado

Nota (2026-08-03): se completaron las plantillas regionales reutilizables mediante el agregado `EducationalProgram`, con segmentación opcional por edad, etapa neutral de licencia, contexto y tipo de vehículo; secuencia ordenada de cursos existentes; ciclo de vida `draft`/`published`/`archived`; persistencia normalizada; casos de uso mediante `CommandBus`/`QueryBus`; y API protegida por `programs.manage`/`programs.view`. Ver sección 25 y `docs/engineering/ENG-LOG.md` (IMP-026). Quedan explícitamente diferidos la propiedad por organización, los perfiles y categorías legales por país, módulos/lecciones, asociaciones adicionales entre cursos, competencias, evaluaciones o SIMUDRIVE más allá de la secuencia propia del programa, inscripción/progreso y versionado.

Incluye:

Programas por edad.
Programas por licencia.
Programas institucionales.
Programas corporativos.
Programas para motocicleta y automóvil.
ENG-026 — Cursos

Estado: Parcial — ver sección 25

Nota (2026-08-02): se completaron datos generales, objetivos, requisitos, duración, modalidad y estado de publicación (endpoints `publish`/`archive` reales, protegidos por los permisos `courses.manage`/`courses.view`), como parte del cierre de esta historia (ver sección 25 y ENG-LOG.md, IMP-024). El versionado real (borradores, revisión, aprobación, historial de versiones) se difiere explícitamente a ENG-029, su propia historia futura — no se construyó aquí para evitar duplicar/adelantar esa historia.

Incluye:

Datos generales.
Objetivos.
Requisitos.
Duración.
Modalidad.
Estado de publicación.
Versionado.
ENG-027 — Módulos y unidades

Estado: Completado

Nota (2026-08-04): se completó el currículo regional latinoamericano de cursos mediante el agregado `Course`, con la jerarquía fija curso → módulos ordenados → unidades ordenadas, metadatos curriculares y prerrequisitos dirigidos exclusivamente a elementos anteriores. El reemplazo de la estructura completa es atómico y solo está permitido en estado `draft`; los cursos `published` y `archived` conservan una estructura inmutable. La persistencia normalizada preserva UUID estables, y la API de consulta/reemplazo reutiliza `courses.view`/`courses.manage`. Ver sección 25 y `docs/engineering/ENG-LOG.md` (IMP-027).

Quedan diferidos explícitamente: lecciones, multimedia y accesibilidad del contenido (ENG-028); revisión, publicación y versionado curricular (ENG-029); progreso y reglas de avance (ENG-035–037); reutilización de módulos o unidades entre cursos; interfaz web; y perfiles normativos por país.

Incluye:

Organización jerárquica.
Orden.
Dependencias.
Requisitos de avance.
Contenido asociado.
ENG-028 — Lecciones

Estado: Completado

Nota (2026-08-08): se completó la incorporación de lecciones y bloques de contenido accesible ordenados y tipados (texto, imagen, video, audio, interactivos y descargas) asociados a las unidades de curso. El backend expone la consulta y el reemplazo atómico del contenido de una unidad protegidos por los permisos `courses.view`/`courses.manage`. El contenido se valida atómicamente contra las invariantes de accesibilidad, URLs HTTPS y posiciones secuenciales consecutivas. La publicación del curso ahora exige contenido completo en todas sus unidades. Detalle completo en `docs/plans/2026-08-05-lecciones-contenido.md` y `docs/engineering/ENG-LOG.md`.

Incluye:

Texto.
Imágenes.
Videos.
Audio.
Contenido interactivo.
Recursos descargables.
Accesibilidad.
ENG-029 — Publicación y versionado curricular

Estado: Completado

Nota (2026-08-10): se completó el ciclo de vida de publicación del agregado `Course` con los estados intermedios `under_review` y `approved` entre el borrador y la publicación, y un historial de versiones inmutables en la tabla `academic_course_versions`. `publish()` del dominio exige `approved`; cada publicación congela un snapshot completo (datos generales + currículo + contenido por unidad) dentro del mismo lock de fila. Reabrir (`reopen`) devuelve el curso a `draft` para construir la siguiente versión sin borrar las publicadas. La API expone 4 mutaciones nuevas y 2 consultas de historial protegidas por `courses.manage`/`courses.view`, con errores públicos `COURSE_REVIEW_STATE_INVALID`, `COURSE_CANNOT_BE_REOPENED` y `COURSE_VERSION_NOT_FOUND`. Detalle completo en `docs/plans/2026-08-10-publicacion-versionado-curricular-design.md` y `docs/engineering/ENG-LOG.md`.

Incluye:

Borradores.
Revisión.
Aprobación.
Publicación.
Versiones.
Historial curricular.
12. Fase 6 — Evaluaciones
ENG-030 — Banco de preguntas

Estado: Completado

Nota (2026-08-10): se completó el agregado `Question` con respuesta tipada por tipo de pregunta, persistencia normalizada (tabla única + opciones con `response` JSONB) y CQRS completo (create/update/delete/get/list). La API expone 5 endpoints bajo `auth:sanctum`: listado/detalle protegidos por `questions.view` y creación/actualización/eliminación por `questions.manage`. Los tipos cubiertos son selección única, selección múltiple, verdadero/falso, asociación, ordenamiento y situacional (que además exige media). Las URLs de media son estrictamente `https`, y los errores públicos son `INVALID_QUESTION` (422) y `QUESTION_NOT_FOUND` (404). Detalle completo en `docs/plans/2026-08-10-banco-preguntas-eng030-implementation.md` y `docs/engineering/ENG-LOG.md`.

Incluye:

Preguntas de selección única.
Selección múltiple.
Verdadero o falso.
Asociación.
Ordenamiento.
Preguntas situacionales.
Recursos multimedia.
ENG-031 — Exámenes y cuestionarios

Estado: Completado

Nota (2026-08-11): se completó el agregado `Exam` anclado a un curso, con plantilla reutilizable de examen (sin estados de ciclo de vida), lista ordenada de preguntas del banco (posición + puntos), configuración de duración, intentos máximos, regla de aprobación, barajado y modo de retroalimentación, y CQRS completo (create/update/delete/get/list). La API expone 5 endpoints bajo `auth:sanctum`: listado/detalle protegidos por `exams.view` y creación/actualización/eliminación por `exams.manage`. La respuesta de detalle enriquece cada pregunta con `ref_id`/`type` desde el banco. Los errores públicos son `INVALID_EXAM` (422) y `EXAM_NOT_FOUND` (404), reutilizando `COURSE_NOT_FOUND`/`QUESTION_NOT_FOUND` para referencias inválidas. Solo la definición del examen; los intentos (ENG-032), el motor de calificación (ENG-033) y el examen teórico (ENG-034) quedan diferidos. Detalle completo en `docs/plans/2026-08-11-examenes-cuestionarios-eng031-implementation.md` y `docs/engineering/ENG-LOG.md`.

Incluye:

Plantillas de examen por curso.
Lista explícita de preguntas con puntaje.
Aleatorización configurable.
Tiempo límite configurable.
Intentos máximos.
Reglas de aprobación.
Retroalimentación configurable.
ENG-032 — Intentos de evaluación

Estado: Completado

Nota (2026-08-12): se completó el agregado `ExamAttempt` como snapshot inmutable del examen al iniciar cada intento, con estados `in_progress`/`submitted`/`canceled`, respuestas por pregunta, guardado progresivo, resultado básico (`score`, `total_points`, `percentage`, `passed`) y prevención de duplicados por intento activo y `max_attempts`. La persistencia quedó normalizada en `academic_exam_attempts` y `academic_exam_attempt_questions`, con CQRS completo (start/answer/submit/cancel/get/list), API HTTP bajo `auth:sanctum` y permiso nuevo `exam_attempts.view` para listar o revisar intentos de terceros. Los errores públicos incluyen `EXAM_ATTEMPT_NOT_FOUND` (404), `EXAM_ATTEMPT_LIMIT_REACHED` (409) y `EXAM_ATTEMPT_ALREADY_SUBMITTED` (409). El motor de calificación fina (ENG-033) y el examen teórico (ENG-034) quedan diferidos. Detalle completo en `docs/plans/2026-08-12-intentos-evaluacion-eng032-implementation.md` y `docs/engineering/ENG-LOG.md`.

Nota (2026-08-26): el código validado técnicamente desde el cierre original quedó consolidado en git en `1d6d90b` junto con ENG-033/034 (ver nota de ENG-034 más abajo sobre por qué se consolidaron en un solo commit).

Incluye:

Inicio.
Respuestas.
Guardado progresivo.
Finalización.
Resultado.
Estado del intento.
Prevención de duplicados.
ENG-033 — Motor de calificación

Estado: Completado

Nota (2026-08-12): se completó el motor de calificación sobre `ExamAttempt`, reemplazando el scoring básico inline por un servicio de dominio `ExamAttemptGrader` con `GradingPolicy`, `GradingResult`, breakdown por pregunta y por competencia, y persistencia JSON en `academic_exam_attempts` (`grading_breakdown`, `competency_results`). El snapshot del intento se enriqueció con `competency_id`, se habilitó partial credit para `multi_select`, `matching` y `ordering`, y se mantuvo `single_choice` / `true_false` como todo-o-nada. La API existente de intentos ahora devuelve grading detallado en `submit` y lo expone en `show` solo cuando el intento quedó `submitted` y además pasa las reglas de visibilidad (`feedback_mode` / permisos). El examen teórico de conducción (ENG-034) queda diferido y ya puede reutilizar este motor. Detalle completo en `docs/plans/2026-08-12-motor-calificacion-eng033-implementation.md` y `docs/engineering/ENG-LOG.md`.

Nota (2026-08-26): consolidado en git junto con ENG-032/034 en `1d6d90b` (ver nota de ENG-034 más abajo).

Incluye:

Puntaje.
Porcentajes.
Penalizaciones.
Competencias evaluadas.
Resultados parciales.
Reglas configurables.
ENG-034 — Examen teórico de conducción

Estado: Completado

Nota (2026-08-13): la primera versión backend de examen teórico de conducción ya quedó implementada sobre `Modules\Academic`, reutilizando `Question`, `Exam`, `ExamAttempt` y `ExamAttemptGrader`. El incremento cubre metadata teórica en preguntas y exámenes, validación de banco oficial y categoría, reglas configurables de grading por examen, recomendaciones básicas de estudio, endpoints especializados `theory-exams`, e historial teórico filtrable por categoría. La validación técnica focalizada ya quedó en verde (`129 passed / 533 assertions`, `phpstan` sin errores, `pint` aplicado), pero el estado se mantuvo en **En validación** porque el trabajo aún no estaba consolidado en commits locales. Ver `docs/plans/2026-08-12-examen-teorico-conduccion-eng034-implementation.md` y `docs/engineering/ENG-LOG.md` (IMP-034).

Nota (2026-08-26): consolidado en git. ENG-032, ENG-033 y ENG-034 se comitearon juntos en `1d6d90b` (`feat(academic): consolidate exam attempts, grading engine and theory exam`) porque las tres historias comparten los mismos archivos (`Exam.php`, `ExamAttempt.php`, `Question.php`, `AttemptQuestion.php` y sus capas de persistencia/HTTP) al haberse construido como extensiones sucesivas del mismo código en una sesión previa continua — sus diffs no se pueden separar limpiamente en tres commits históricos distintos después del hecho. Estado actualizado de "En validación" a **Completado**.

Incluye:

Simulación de examen.
Categorías de licencia.
Reglas configurables.
Banco oficial autorizado.
Historial de intentos.
Recomendaciones de estudio.
13. Fase 7 — Progreso y Learning OS
ENG-035 — Inscripciones

Estado: Completado

Nota (2026-08-26): implementado sobre `Modules\Academic` el agregado `Enrollment` (identidad propia `EnrollmentId`, estados `pending`/`active`/`completed`/`canceled`, origen `individual`/`bulk`/`institutional`), con CQRS completo (creación individual/masiva/institucional, activar/completar/cancelar, consulta y listado filtrado) y persistencia Eloquent en `academic_enrollments`. La API HTTP (`EnrollmentController`, rutas y permisos `enrollments.view`/`enrollments.manage`) ya se había comiteado en su momento; el dominio, la aplicación y la persistencia quedaron consolidados ahora en `e3e2186` (`feat(academic): consolidate enrollment domain, application and persistence`), tras haber quedado implementados y validados sin commitear en una sesión previa. Detalle completo en `docs/plans/2026-08-13-inscripciones-eng035-implementation.md`, `docs/plans/2026-08-14-eng035-enrollment-api.md` y `docs/engineering/ENG-LOG.md`.

Incluye:

Inscripción individual.
Inscripción masiva.
Asignación institucional.
Fechas de inicio y cierre.
Estados de matrícula.
ENG-036 — Seguimiento de progreso

Estado: Completado

Nota (2026-08-16): se completó el seguimiento de progreso del estudiante sobre `Enrollment`, con el nuevo agregado `EnrollmentProgress` (1:1 con la inscripción) respaldado por la tabla `academic_enrollment_lesson_completions` (una fila por lección completada, única por `enrollment_id`+`lesson_id`, con FK en cascada a `academic_enrollments` y a `academic_lessons`). El porcentaje de avance, el tiempo invertido y la última actividad se calculan en `EnrollmentProgressCalculator`, combinando las lecciones completadas con el catálogo de lecciones del curso (`CourseLessonCatalog`) y los intentos de examen enviados para ese curso (cruce con `Exam`/`ExamAttempt` vía `courseId`). CQRS completo (`CompleteLessonCommand`/`GetEnrollmentProgressQuery`), expuesto en `POST /enrollments/{enrollmentId}/lessons/{lessonId}/complete` y `GET /enrollments/{enrollmentId}/progress` bajo `auth:sanctum`, con autorización por pertenencia (dueño del enrollment) o por el permiso ya existente `enrollments.view` para terceros. Errores públicos: `ENROLLMENT_NOT_FOUND` (404, reutilizado), `INVALID_ENROLLMENT` (422, reutilizado) y `LESSON_NOT_FOUND` (404, nuevo). Detalle completo en `docs/plans/2026-08-15-seguimiento-progreso-eng036-design.md`, `docs/plans/2026-08-15-seguimiento-progreso-eng036-implementation.md` y `docs/engineering/ENG-LOG.md` (IMP-036).

Incluye:

Lecciones completadas.
Tiempo invertido.
Evaluaciones realizadas.
Porcentaje de avance.
Última actividad.
ENG-037 — Reglas de avance

Estado: Completado

Nota (2026-08-16): se implementó el bloqueo/desbloqueo de módulos y unidades de un curso para un estudiante inscrito, según los prerrequisitos ya modelados en `Course` (`prerequisiteModuleIds`/`prerequisiteUnitIds`). Nuevo servicio de dominio `CourseCurriculumUnlockCalculator` que deriva (sin persistir) el estado completo/desbloqueado de cada módulo y unidad, combinando `Course` con `EnrollmentProgress` (ENG-036). Un módulo se desbloquea cuando todos sus módulos prerrequisito están completos; una unidad se desbloquea cuando su módulo padre está desbloqueado y todas sus unidades prerrequisito están completas (una unidad sin lecciones publicadas cuenta como completada). Se expone en `GET /enrollments/{enrollmentId}/curriculum` (misma autorización que el resto de ENG-036: dueño del enrollment o permiso `enrollments.view`) y se aplica como gate en `CompleteLessonHandler`, que ahora rechaza completar una lección de una unidad todavía bloqueada (`422 UNIT_LOCKED`, nuevo). Fuera de alcance de este incremento: puntaje mínimo de examen (requeriría anclar `Exam` a una unidad/módulo), distinción de actividad obligatoria/opcional, y rutas adaptativas (probablemente solapa con ENG-039). Detalle completo en `docs/plans/2026-08-16-reglas-avance-eng037-design.md`, `docs/plans/2026-08-16-reglas-avance-eng037-implementation.md` y `docs/engineering/ENG-LOG.md` (IMP-037).

Incluye:

Prerrequisitos.
Bloqueo y desbloqueo.

Diferido:

Puntaje mínimo.
Actividades obligatorias.
Rutas adaptativas.
ENG-038 — Learning Record Store interno

Estado: Completado

Nota (2026-08-26): se registran como hechos inmutables (append-only) los eventos de aprendizaje que ya ocurrían en `Academic` — lección completada y examen enviado — en un nuevo módulo `Modules\Learning` con DDD completo (`LearningEvent` como entidad inmutable, `LearningEventRepository`, tabla `learning_events`). `Academic` depende de la abstracción `LearningEventRecorder` (mismo patrón que `Identity` → `Audit` con `AuditLogger`) desde `CompleteLessonHandler` y `SubmitExamAttemptHandler`; `Learning` depende a su vez de `EnrollmentRepository`/`EnrollmentNotFound` de `Academic` para autorizar la consulta por pertenencia (dueño del enrollment o `enrollments.view`) — acoplamiento bidireccional real e intencional, documentado en el plan. Se expone `GET /enrollments/{enrollmentId}/learning-events` bajo `auth:sanctum`. `SubmitExamAttemptHandler` resuelve el enrollment del alumno para el curso del examen vía `EnrollmentRepository::findActiveOrPendingFor()` (ENG-035) y omite el registro sin fallar si no hay enrollment resoluble. Detalle completo en `docs/plans/2026-08-16-learning-record-store-eng038-design.md`, `docs/plans/2026-08-16-learning-record-store-eng038-implementation.md` y `docs/engineering/ENG-LOG.md` (IMP-038).

Incluye:

Eventos de aprendizaje.
Acciones del estudiante.
Evidencias.
Trazabilidad histórica.

Diferido:

Origen web, móvil o simulador (no se agregó ningún campo de origen/canal al evento; puede incorporarse en una historia futura si se necesita).
ENG-039 — Recomendaciones de aprendizaje

Estado: Completado

Nota (2026-08-26): implementado sobre `Modules\Academic`, sin módulo nuevo, siguiendo el mismo patrón que `EnrollmentProgressCalculator`/`CourseCurriculumUnlockCalculator`/`TheoryStudyRecommendationService`. Nuevo servicio `EnrollmentLearningRecommendationService::build()` combina tres piezas por inscripción: (1) próxima lección — primera lección del curso, en orden curricular, que no esté completada y cuya unidad esté desbloqueada (`CourseLessonCatalog` + `CourseCurriculumUnlockCalculator`); (2) refuerzo de competencias — generaliza `TheoryStudyRecommendationService` a través de todos los exámenes del curso, usando solo el intento enviado más reciente por examen (para que una mejora posterior no quede ensombrecida por un intento antiguo reprobado), ordenado de peor a mejor desempeño y acotado a un máximo de 5; (3) exámenes para reintentar — exámenes reprobados con intentos disponibles y sin un intento activo en curso. Expuesto en `GET /enrollments/{enrollmentId}/recommendations` (misma autorización que progreso/currículo: dueño del enrollment o permiso ya existente `enrollments.view`, sin permiso nuevo). Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-recomendaciones-aprendizaje-eng039-design.md` y `docs/engineering/ENG-LOG.md`.

Incluye:

Recomendación de lecciones.
Refuerzo de competencias.
Repetición de evaluaciones.

Diferido:

Recomendaciones según errores a nivel de pregunta individual, más allá de la evidencia (`question_ids`) que ya aporta el refuerzo de competencias.
Preparación para SIMUDRIVE (sistema externo, fuera de este repositorio).
14. Fase 8 — Pasaporte Vial
ENG-040 — Núcleo del Pasaporte Vial

Estado: Completado

Nota (2026-08-26): nuevo módulo `Modules\RoadPassport` (siguiendo ENG-003 al pie de la letra, mismo patrón de bootstrap que `Modules\Learning` en ENG-038), con el agregado `RoadPassport`: identificador propio, `userId` (uno por persona, único), estado (`active`/`suspended`/`revoked`, con `revoked` terminal) y nivel entero (solo sube mientras está `active`). El historial formativo de este alcance reducido es el propio historial de cambios de estado y nivel del pasaporte (`PassportHistoryEntry`) — no la agregación de cursos/evaluaciones/prácticas, que es ENG-041. CQRS completo (emitir/suspender/reactivar/revocar/cambiar nivel/consultar) y API HTTP bajo `auth:sanctum` en `/api/v1/road-passport`, con permisos nuevos `road_passports.manage`/`road_passports.view` (mismo patrón de concesión que `enrollments.manage`/`enrollments.view`: SuperAdmin e InstitutionalAdmin ambos, Teacher solo view, Student ninguno — accede al propio por pertenencia vía `GET /road-passport/me`). Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-nucleo-pasaporte-vial-eng040-design.md` y `docs/engineering/ENG-LOG.md`.

Incluye:

Identificador del Pasaporte Vial.
Propietario.
Estado.
Nivel.
Historial de cambios de estado y nivel.

Diferido:

Vigencia (fecha de expiración o renovación del pasaporte — no se modeló ningún campo de vigencia en este alcance reducido).
Agregación de evidencias (cursos, evaluaciones, prácticas, simulaciones, certificaciones — ENG-041).
Cálculo automático de confianza/nivel a partir de evidencia (ENG-042).
Reemisión de un pasaporte revocado.
ENG-041 — Evidencias del Pasaporte Vial

Estado: Completado

Nota (2026-08-26): agregado al núcleo (`RoadPassport::evidence(): list<Evidence>`) dos tipos de evidencia registrados reactivamente, mismo patrón que `LearningEventRecorder` de ENG-038: `course_completed` (al completar un `Enrollment`, desde `CompleteEnrollmentHandler`) y `exam_passed` (al aprobar un `ExamAttempt`, desde `SubmitExamAttemptHandler`, solo cuando `passed() === true`). `RoadPassport::recordEvidence()` es idempotente por `type`+`subjectId` (una entrada por matrícula completada o intento aprobado). `Academic` depende de la abstracción `RoadPassportEvidenceRecorder` (escritura); `RoadPassport` no depende de `Academic` en absoluto — solo recibe datos ya resueltos vía `EvidenceEntry`. Si el usuario no tiene pasaporte emitido, el registro simplemente no ocurre (no falla). Expuesta como parte de `RoadPassportResponse` (`GET /road-passport/me` y `/{id}`), sin endpoint nuevo. Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-evidencias-pasaporte-vial-eng041-design.md`.

Incluye:

Cursos.
Evaluaciones.

Diferido:

Prácticas y simulaciones (dependen de SIMUDRIVE, sistema externo).
Certificaciones (sin concepto de dominio modelado todavía).
Evidencias externas autorizadas (no existe ningún mecanismo de ingesta externa; toda la evidencia se origina internamente en `Academic`).
Cálculo automático de confianza/nivel a partir de la evidencia acumulada (ENG-042).
ENG-042 — Competency Trust Model

Estado: Completado

Nota (2026-08-26): servicio de dominio puro `RoadPassportTrustCalculator::calculate(RoadPassport, DateTimeImmutable): int` (sin dependencias de infraestructura, mismo espíritu que `CourseCurriculumUnlockCalculator` en Academic), que calcula un `trust_score` (0-100) global para todo el pasaporte a partir de su evidencia acumulada (ENG-041) — no se persiste, se recalcula al vuelo en cada consulta. Combina: peso fijo por fuente (`exam_passed` = 15, `course_completed` = 10 — un examen aprobado pesa más que un curso completado); decaimiento lineal por recencia (factor 1.0 hasta 90 días, decae a un piso de 0.2 a partir de 365 días — reglas de degradación temporal, la evidencia vieja nunca llega a pesar cero); y un multiplicador de consistencia creciente con la cantidad de evidencia independiente pero acotado (`min(1.0, 0.5 + 0.1 × cantidad)`, tope en 5+ piezas). Expuesto como campo `trust_score` en `RoadPassportResponse` (`GET /road-passport/me` y `/{id}`), sin endpoint ni permiso nuevo. Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-competency-trust-model-eng042-design.md`.

Incluye:

Nivel de confianza.
Fuente de evidencia.
Recencia de la evidencia.
Consistencia.
Reglas de degradación temporal.

Diferido:

Validez/expiración de evidencia individual (sigue diferida desde ENG-040; toda evidencia es válida indefinidamente en este alcance).
Desagregación del trust score por competencia individual (la evidencia actual es a nivel de curso/examen, no tiene desglose por competencia todavía — extender `Evidence` con eso sería un incremento propio).
Persistencia o historial del trust score (se recalcula siempre al vuelo).
ENG-043 — Credenciales y certificaciones

Estado: Completado

Nota (2026-08-26): nuevo módulo `Modules\Certification`, independiente del Pasaporte Vial, con el agregado `Certificate` (`id`, `userId`, `courseId`, `validationCode`, `status` `issued`/`revoked` — terminal, sin reactivación —, `issuedAt`, `expiresAt` opcional, `history`). `ValidationCode` genera un código de 12 caracteres alfanuméricos en mayúsculas agrupados `XXXX-XXXX-XXXX`, excluyendo caracteres ambiguos (`0`, `O`, `1`, `I`). Emisión **manual/administrativa** vía `certifications.manage` (mismo patrón que `RoadPassport` en ENG-040), rechazando un segundo certificado para el mismo usuario+curso (`CertificateAlreadyExists`, 409). CQRS completo (`IssueCertificateCommand`/`RevokeCertificateCommand`/`GetCertificateQuery`/`GetMyCertificatesQuery`) y API HTTP en `/api/v1/certification/certificates` protegida por pertenencia o los permisos nuevos `certifications.manage`/`certifications.view` (`SuperAdmin`+`InstitutionalAdmin`: ambos; `Teacher`: solo view; `Student`: ninguno). Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-credenciales-certificaciones-eng043-design.md`.

Incluye:

Certificados.
Credenciales verificables.
Códigos de validación.
Vigencia.
Revocación.
Historial.

Diferido:

Emisión automática disparada por evidencia del Pasaporte Vial (`course_completed`) — se mantienen como conceptos de dominio separados en este incremento.
Verificación pública por código (bullet propio de ENG-044).
Reemisión de un certificado revocado (mismo criterio que ENG-040 con el pasaporte).
ENG-044 — Consulta pública controlada

Estado: Completado

Nota (2026-08-26): endpoint público `GET /api/v1/certification/verify/{validationCode}` (sin autenticación), que expone únicamente los datos mínimos necesarios para verificar un certificado: código de validación, vigencia efectiva calculada (`valid`/`expired`/`revoked` — nueva regla de dominio `Certificate::effectiveStatus()`, distinta del `status` interno crudo, ya que considera `expiresAt`), fecha de emisión, fecha de vigencia, id y nombre del curso, y nombre del titular. No expone `user_id`, correo, historial de estados ni el id interno del certificado. Un código con formato inválido o inexistente responde igual (`CERTIFICATE_NOT_FOUND`, 404), sin distinguir el motivo. `VerifyCertificateHandler` depende directamente de `UserRepository` (Identity) y `CourseRepository` (Academic) para resolver nombre del titular y del curso — mismo precedente que `AssignRoleHandler` en `Authorization`. Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-consulta-publica-eng044-design.md`.

Incluye:

Verificación mediante código.
Datos mínimos.
Privacidad.
Vigencia.
Evidencia verificable.

Diferido:

Listado o enumeración pública de certificados (solo consulta puntual por código exacto).
Límite de tasa/anti-abuso del endpoint público (preocupación de infraestructura/gateway, fuera de alcance de este incremento).
15. Fase 9 — Integración con SIMUDRIVE
ENG-045 — Registro de simuladores

Estado: Completado

Nota (2026-08-26): nuevo módulo `Modules\Simulation`, registro administrativo de simuladores SIMUDRIVE autorizados. Agregado `Simulator` (`deviceIdentifier` único, `softwareVersion`, `location` opcional, `status` `active`/`suspended`/`retired` — terminal, sin reactivación desde `retired`, mismo criterio que `RoadPassport`/`Certificate` —, `integrationKey`, historial de transiciones). `IntegrationKey` genera un valor aleatorio de 32 bytes al registrar o rotar, devuelto **una única vez** en la respuesta HTTP; en base de datos solo se guarda su hash SHA-256 (mismo espíritu que los *personal access tokens* de Sanctum) — si se pierde, solo se puede rotar, no recuperar. CQRS completo (registrar, suspender, reactivar, retirar, rotar llave, consultar, listar) y API HTTP en `/api/v1/simulation/simulators` protegida por los permisos nuevos `simulators.manage`/`simulators.view` (`SuperAdmin`+`InstitutionalAdmin`: ambos; `Teacher`: solo view; `Student`: ninguno). Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-registro-simuladores-eng045-design.md`.

Incluye:

Simuladores autorizados.
Identificación del dispositivo.
Versión del software.
Ubicación.
Estado.
Llaves de integración.

Diferido:

Validación de sesiones/telemetría contra el simulador (ENG-046/047 — este incremento solo registra el simulador, no lo usa todavía).
Actualización de la versión de software reportada por heartbeat del propio dispositivo (la versión se fija al registrar, no hay canal de auto-reporte).
Geolocalización estructurada (`Ubicación` es texto libre, no coordenadas).
ENG-046 — Sesiones de simulación

Estado: Completado

Nota (2026-08-26): segundo agregado del módulo `Modules\Simulation`, `SimulationSession` — vincula usuario, simulador, vehículo y escenario (`vehicleType`/`scenario` como texto libre, sin catálogo propio en EDUDRIVE, el catálogo real vive en SIMUDRIVE), con ciclo de vida `scheduled` → `in_progress` (inicio real) → `completed` (fin real, duración efectiva calculada) o `cancelled` (solo desde `scheduled`). Programar una sesión es **autoservicio**: cualquier usuario autenticado programa la propia (el `userId` se toma del usuario autenticado, nunca del cuerpo de la petición); valida que el simulador exista y esté `active` (`SimulatorNotAvailable`, 422, reutilizando `SimulatorNotFound` de ENG-045 si no existe). El criterio de propiedad ya usado en consultas (`GetCertificateHandler`/`GetRoadPassportHandler`: dueño o permiso ampliado) se extiende por primera vez también a las transiciones de estado (`start`/`complete`/`cancel`), no solo a la lectura. CQRS completo y API HTTP en `/api/v1/simulation/sessions` protegida por pertenencia o los permisos nuevos `simulation_sessions.manage`/`simulation_sessions.view`. Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-sesiones-simulacion-eng046-design.md`.

Incluye:

Usuario.
Simulador.
Vehículo.
Escenario.
Fecha.
Duración.
Estado de la sesión.

Diferido:

Detección de conflictos de horario entre sesiones del mismo simulador.
Re-validación del estado del simulador al iniciar la sesión (solo se valida al programar).
Integración real con telemetría del simulador (ENG-047) y resultados prácticos (ENG-048).
ENG-047 — Telemetría

Estado: Completado

Nota (2026-08-26): dos entidades nuevas de solo-append en `Modules\Simulation`, sin invariantes de agregado — `TelemetrySample` (velocidad, frenado, aceleración, dirección + marca de tiempo) y `TelemetryEvent` (colisión/infracción/uso de señal/evento crítico, con detalle opcional + marca de tiempo). El simulador mismo reporta la telemetría por lotes, autenticado con su llave de integración (`Authorization: Bearer <llave>`, ENG-045) — primer mecanismo de autenticación máquina-a-máquina de este backend (`AuthenticateSimulator`, alias `simulator.auth`, busca el simulador por el hash SHA-256 de la llave recibida, mismo patrón que Sanctum para *personal access tokens*). Valida que la sesión exista, pertenezca a ese simulador y esté `InProgress` (si no, `SIMULATION_SESSION_NOT_FOUND`/`SIMULATION_SESSION_NOT_IN_PROGRESS`). La consulta de la telemetría ya registrada es para humanos, bajo `auth:sanctum`, con el mismo criterio de pertenencia que las sesiones (sin permiso nuevo — reutiliza `simulation_sessions.view`). Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-telemetria-eng047-design.md`.

Incluye:

Velocidad.
Frenado.
Aceleración.
Dirección.
Uso de señales.
Colisiones.
Infracciones.
Eventos críticos.

Diferido:

Procesamiento o agregación de la telemetría (ENG-048, Resultados prácticos).
Límites de tamaño de lote o límite de tasa del endpoint (preocupación de infraestructura).
Reintentos/idempotencia ante envíos duplicados del mismo lote.
ENG-048 — Resultados prácticos

Estado: Completado

Nota (2026-08-26): sin persistencia nueva — un servicio de dominio puro `PracticalResultCalculator` (mismo espíritu que `RoadPassportTrustCalculator`/`ExamAttemptGrader`) cuenta los `TelemetryEvent` (ENG-047) de una sesión `Completed` y deriva, en cada consulta, un puntaje (100 menos penalización por evento: colisión -30, infracción -10, evento crítico -20, `SignalUsage` no penaliza; piso en 0) y un resultado general `passed`/`failed` (umbral 70). "Competencias demostradas" es una lista de texto libre derivada del escenario, solo si `passed` (sin depender de `Competency` de Academic). "Evidencias asociadas" son los propios `errors` del resultado (tipo + marca de tiempo + detalle del `TelemetryEvent` que los originó), autocontenido dentro de `Modules\Simulation`. "Recomendaciones" son mensajes fijos, uno por cada tipo de error presente, sin duplicados. Solo disponible cuando la sesión está `Completed` (`PRACTICAL_RESULT_NOT_AVAILABLE`, 422, si se consulta antes). API HTTP en `GET /api/v1/simulation/sessions/{sessionId}/result`, dueño de la sesión o `simulation_sessions.view` (sin permiso nuevo). Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-resultados-practicos-eng048-design.md`.

Incluye:

Resultado general.
Errores.
Penalizaciones.
Competencias demostradas.
Recomendaciones.
Evidencias asociadas.

Diferido:

Registro manual de resultados por un evaluador humano.
Integración con el Pasaporte Vial (`RoadPassport::recordEvidence()`) a partir de un resultado práctico aprobado.
Referencias reales a `Competency` de Academic.
ENG-049 — SIMUDRIVE Decision Engine

Estado: Completado

Nota (2026-08-26): tercera entidad de solo-append en `Modules\Simulation`, `DecisionPoint` — el simulador reporta datos crudos por punto de decisión (contexto vial en texto libre, nivel de riesgo `low`/`medium`/`high` asignado por el diseño del escenario en SIMUDRIVE, y la reacción del conductor de un conjunto cerrado: `braked`/`accelerated`/`maintained`/`swerved`/`signaled`/`ignored` — necesario como conjunto cerrado, no texto libre, para que la evaluación sea determinística). Un servicio de dominio puro `DecisionEngineCalculator` evalúa, en cada consulta (sin persistir el resultado, mismo patrón que ENG-048), si la reacción fue apropiada para ese riesgo (regla fija: `ignored` nunca es apropiado; para `high` solo `braked`/`swerved`/`signaled`; para `medium` se suma `maintained`; para `low` cualquiera salvo `ignored`), genera retroalimentación fija por combinación riesgo+resultado, y calcula consistencia agrupando por nivel de riesgo dentro de la misma sesión (`consistency_score = grupos_consistentes / grupos_totales`). Envío por lotes autenticado con la llave de integración del simulador (`simulator.auth`, igual que ENG-047), exigiendo sesión `InProgress`. Consulta del resultado bajo `auth:sanctum`, dueño de la sesión o `simulation_sessions.view` (sin permiso nuevo), exige sesión `Completed`. Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-decision-engine-eng049-design.md`.

Incluye:

Evaluación de decisiones.
Contexto vial.
Riesgo.
Consistencia.
Respuesta del conductor.
Retroalimentación educativa.

Diferido:

Consistencia a través de múltiples sesiones o de todo el historial del usuario.
Que SIMUDRIVE reporte la evaluación ya calculada (delegar el criterio educativo a SIMUDRIVE).
Retroalimentación personalizada más allá de mensajes fijos por combinación riesgo+resultado.
ENG-050 — Sincronización offline

Estado: Completado

Nota (2026-08-26): la cola local y el manejo de la desconexión son responsabilidad del simulador (fuera de alcance de este backend, mismo criterio que el catálogo real de vehículos/escenarios en ENG-046) — el trabajo de EDUDRIVE fue hacer que `POST /sessions/{id}/telemetry` (ENG-047) y `POST /sessions/{id}/decisions` (ENG-049) acepten reenvíos de forma idempotente y toleren datos que llegan tarde. Cada lectura/evento/punto de decisión ahora incluye su propio `id` (UUID) generado por el simulador; al guardar el lote se usa `insertOrIgnore()` (Eloquent) en vez de `insert()` — un `id` ya existente se omite silenciosamente en lugar de duplicarse o fallar, y la respuesta (`samples_recorded`/`events_recorded`/`decisions_recorded`) refleja las filas realmente insertadas. La validación "la sesión debe estar `InProgress` en este momento" se amplió a "la lectura/decisión debe haber ocurrido durante el periodo real en que la sesión estuvo en curso" (nuevo método `SimulationSession::wasInProgressAt()`, comparando contra `startedAt`/`endedAt`) — acepta datos genuinos que llegan después de que la sesión ya se completó o se canceló por otro canal mientras el simulador estaba desconectado. Si cualquier ítem del lote cae fuera de esa ventana, se rechaza el lote completo. Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-sincronizacion-offline-eng050-design.md`.

Incluye:

Sesiones sin conexión.
Cola local.
Identificadores idempotentes.
Sincronización posterior.
Resolución de conflictos.

Diferido:

Modelar la sesión offline como un concepto de dominio propio (reportar una sesión completa programada+iniciada+completada en un solo envío retroactivo).
Una tabla de llaves de idempotencia por lote completo (se prefirió id por ítem, más simple y sin tabla nueva).
Resolución de conflictos más allá de la ventana temporal (ej. fusionar lecturas contradictorias).
16. Fase 10 — Gamificación
ENG-051 — Logros

Estado: Completado

Nota (2026-08-26): primera historia de la Fase 10 (Gamificación) — nuevo módulo `Modules\Gamification` con el agregado `Achievement` (catálogo con código único `AchievementCode` en mayúsculas, regla de obtención en texto libre, ciclo de vida `active`/`retired` sin reversión — sin lista de historial dedicada, a diferencia de `Simulator`/`RoadPassport`/`Certificate`, porque solo existe una transición posible) y la entidad `UserAchievement` (otorgamiento inmutable de solo-append: usuario, evidencia en texto libre, fecha de obtención). Otorgamiento manual vía `achievements.manage` (mismo criterio que `Certificate` en ENG-043) en vez de evaluación automática de reglas; el otorgamiento exige que el logro esté `active` y rechaza duplicados por usuario+logro. CQRS completo y API HTTP en `/api/v1/gamification/achievements`: catálogo (`achievements.view`) y gestión/otorgamiento (`achievements.manage`) igual que módulos previos, pero `achievements.view` se otorgó también a `Student` (a diferencia de `road_passports`/`certifications`/`simulators`) porque el catálogo es de navegación abierta, equivalente a `courses.view`; autoservicio en `GET /achievements/me` sin permiso nuevo. Campo `registeredAt` (no `createdAt`) para evitar colisión con las columnas de auditoría automáticas de Eloquent, mismo criterio que `Simulator::registeredAt()` (ENG-045). Revocación de un logro otorgado, consulta de los logros de otro usuario y evaluación automática de reglas diferidas explícitamente. Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-logros-eng051-design.md`.

Incluye:

Catálogo de logros.
Reglas de obtención.
Evidencias.
Estado.
Fecha de obtención.

Diferido:

Revocación de un logro ya otorgado.
Consulta de los logros obtenidos por otro usuario (solo autoservicio).
Evaluación automática de reglas de obtención (el otorgamiento es manual, vía `achievements.manage`).
ENG-052 — Insignias

Estado: Completado

Nota (2026-08-26): segunda historia de la Fase 10 (Gamificación), extiende `Modules\Gamification` con el agregado `Badge`, distinto de `Achievement` (ENG-051) en tres puntos. Categoría cerrada `BadgeCategory` (`educational`/`institutional`/`practical`, tal como las enumera el roadmap). Nivel fijo `BadgeLevel` (`bronze`/`silver`/`gold`) asignado a la insignia, sin sistema de progresión ni acumulación (eso corresponde a ENG-053, un concepto distinto: nivel del usuario, no de la insignia). Contenido editable mediante `updateContent()`, que incrementa un campo `version` (entero, inicia en 1) — sin conservar snapshots históricos completos, a diferencia de `CourseVersion` en Academic; el otorgamiento a un usuario (`UserBadge`) guarda `awardedVersion`, la versión vigente al momento de otorgarse. Edición bloqueada si la insignia está `retired` (`InvalidBadgeTransition`, 422, reutilizada también para "retirar dos veces"). Otorgamiento manual vía `badges.manage` (mismo criterio que `Achievement`/`Certificate`), rechazando insignias no `active` o duplicadas por usuario+insignia. CQRS completo y API HTTP en `/api/v1/gamification/badges`, incluyendo `PUT /badges/{badgeId}` para la edición de contenido; permisos nuevos `badges.manage`/`badges.view`, con `badges.view` otorgado también a `Student` (mismo criterio que `achievements.view`). Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-insignias-eng052-design.md`.

Incluye:

Insignias educativas.
Insignias institucionales.
Insignias prácticas.
Niveles.
Versionado.

Diferido:

Evaluación automática de reglas de otorgamiento (otorgamiento manual, igual que ENG-051).
Sistema de progresión de niveles por acumulación de insignias (corresponde a ENG-053).
Historial completo de snapshots por versión (solo se conserva el número de versión).
Revocación de una insignia ya otorgada.
Consulta de las insignias obtenidas por otro usuario (solo autoservicio).
ENG-053 — Experiencia y niveles

Estado: Completado

Nota (2026-08-26): tercera historia de la Fase 10, extiende `Modules\Gamification` con `ExperienceEntry`, un ledger de solo-append de puntos de experiencia (XP) — a diferencia de `Achievement`/`Badge`, no es un catálogo. Cada registro tiene `points` (entero estrictamente positivo, validado en el dominio), un `competencyId` opcional en texto libre (sin referencia real a `Competency` de Academic, mismo criterio que ENG-048/049) y un `reason` descriptivo. El nivel general y el nivel por competencia se derivan mediante un servicio de dominio puro, `ExperienceLevelCalculator`, calculado en cada consulta a partir de la suma de puntos acumulados (mismo patrón que `PracticalResultCalculator`/`DecisionEngineCalculator`/`RoadPassportTrustCalculator`) — el nivel no se persiste. Regla de progresión fija con umbral uniforme: `nivel = floor(xp_total / 100) + 1`, igual para el nivel general y cada nivel por competencia. Prevención de manipulación: el ledger es inmutable (sin edición ni borrado), solo se registra vía `experience.manage` (SuperAdmin/InstitutionalAdmin, sin autoservicio de registro), y los puntos deben ser estrictamente positivos. Solo existe autoservicio de consulta (`GET /experience/me`, `auth:sanctum` sin permiso adicional) — mismo criterio que `/achievements/me`/`/badges/me`: la consulta del resumen de experiencia de otro usuario queda diferida. Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-experiencia-niveles-eng053-design.md`.

Incluye:

Puntos de experiencia.
Nivel general.
Nivel por competencia.
Reglas de progresión.
Prevención de manipulación.

Diferido:

Integración automática reactiva con otros módulos (logros, insignias, cursos, exámenes) como fuente de XP.
Tabla de umbrales de progresión configurable por nivel (curva personalizada).
Consulta del resumen de experiencia de otro usuario (solo autoservicio).
Edición o borrado de un registro de experiencia ya creado.
Referencias reales a `Competency` de Academic.
ENG-054 — Retos y misiones

Estado: Completado

Nota (2026-08-26): cuarta y última historia de la Fase 10, extiende `Modules\Gamification` con el agregado `Challenge` y la entidad `ChallengeParticipation`. Retos individuales, grupales y misiones educativas se modelan con un solo agregado y un enum cerrado `ChallengeType` (`individual`/`group`/`educational`) — un reto "grupal" es simplemente uno en el que participan varios usuarios, cada uno con su propio registro de `ChallengeParticipation`, sin modelar un concepto nuevo de equipo/grupo con membresía propia. Las fechas de vigencia (`startsAt`/`endsAt`) restringen funcionalmente la participación: `Challenge::isWithinWindow()` rechaza una unión nueva fuera de esa ventana. La recompensa (`reward`) es texto libre descriptivo, sin vincularse a un `Achievement`/`Badge` real. El seguimiento se modela con `ChallengeParticipation`, distinta de `UserAchievement`/`UserBadge` en que no es un registro de solo-append inmutable: tiene su propia transición `Joined` → `Completed` (`InvalidChallengeParticipationTransition`, 422, si ya está completada). Todo el registro (unión y finalización) es manual vía `challenges.manage`, mismo criterio que `Achievement`/`Badge` — sin autoservicio de "unirse". CQRS completo y API HTTP en `/api/v1/gamification/challenges` con `challenges.view` extendido a `Student`; autoservicio de consulta en `GET /challenges/me`. Concepto real de equipo/grupo, autoservicio de unión, otorgamiento automático de un logro/insignia real y consulta de las participaciones de otro usuario diferidos explícitamente. Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-retos-misiones-eng054-design.md`.

Incluye:

Retos individuales.
Retos grupales.
Misiones educativas.
Fechas.
Recompensas.
Seguimiento.

Diferido:

Concepto real de equipo/grupo con membresía propia y seguimiento agregado a nivel de grupo.
Autoservicio de "unirse" a un reto/misión por parte del estudiante.
Otorgamiento automático de un Achievement/Badge real al completar un reto/misión.
Consulta de las participaciones de otro usuario (solo autoservicio).
Reversión de una participación ya completada.

Con esto cierra por completo la **Fase 10 — Gamificación** (ENG-051 a ENG-054).
ENG-055 — Tablas de clasificación

Estado: Diferido

Debe implementarse únicamente después de definir:

Protección de menores.
Privacidad.
Comparaciones justas.
Prevención de competencia negativa.
Configuración institucional.
17. Fase 11 — Comunicación y notificaciones
ENG-056 — Motor de notificaciones

Estado: Completado

Nota (2026-08-26): primera historia de la Fase 11 (Comunicación y notificaciones), nuevo módulo `Modules\Notification`. Solo registro y seguimiento de la notificación — el canal (`email`/`web`/`mobile`/`internal_message`) es un metadato; la entrega real por cada canal externo (SMTP, proveedor push) queda diferida como preocupación de infraestructura, mismo criterio que el catálogo real de vehículos/escenarios diferido en ENG-046. Agregado `Notification` con una sola transición de estado propia, `unread`→`read` (`InvalidNotificationTransition`, 422, si ya está leída) — no es un catálogo con grant separado como `Achievement`/`Badge`, es una entidad por notificación individual. Envío manual vía `notifications.manage` (SuperAdmin/InstitutionalAdmin), sin disparo automático desde otros módulos todavía. Cada notificación incluye una `category` en texto libre sin catálogo cerrado, pensada para que ENG-057 (Preferencias de notificación) decida cómo filtrar por ella. El propio destinatario marca sus notificaciones como leídas en autoservicio, con verificación de pertenencia (`NotificationNotFound` tanto si no existe como si no pertenece al solicitante, mismo patrón anti-fuga usado en `RoadPassport`/`SimulationSession`). API HTTP en `/api/v1/notification/notifications`, sin permiso `.view` (la consulta es autoservicio únicamente, mismo criterio que `experience.manage`). Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-motor-notificaciones-eng056-design.md`.

Canales previstos:

Correo electrónico.
Notificación web.
Notificación móvil.
Mensajes internos.

Diferido:

Integración real de entrega por cada canal (SMTP, proveedor push).
Disparo automático de notificaciones desde eventos de otros módulos.
Estado de entrega granular (pending/sent/delivered/failed) y reintentos.
Catálogo cerrado de categorías (corresponde a ENG-057).
ENG-057 — Preferencias de notificación

Estado: Completado

Nota (2026-08-26): segunda historia de la Fase 11, extiende `Modules\Notification` con un segundo agregado, `NotificationPreference` — un registro de configuración por usuario (no un catálogo ni un ledger de solo-append). Todo permitido por defecto con silenciamiento explícito: `allowedChannels` (subconjunto del enum cerrado, los cuatro por defecto) y `mutedCategories` (texto libre, vacío por defecto). `SendNotificationHandler` (ENG-056) ahora consulta la preferencia del destinatario antes de registrar la notificación — si el canal, la categoría o el consentimiento no lo permiten, la notificación se descarta silenciosamente (`handle()` retorna `null`, la API responde `200 OK` con `data: null` en vez de `201 Created`). Frecuencia (`immediate`/`daily`/`weekly`) y horario de silencio (`quietHoursStart`/`quietHoursEnd`, formato `HH:MM`) se almacenan como configuración pero no se aplican todavía — requieren un motor de programación/cola que no existe aún. Consentimiento como booleano simple `consentGiven`, otorgado por defecto (notificaciones operativas/educativas, no de marketing), con `consentUpdatedAt` registrando el último cambio explícito. Gestión 100% autoservicio (`auth:sanctum`, sin permiso nuevo) — un usuario solo administra sus propias preferencias. Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-preferencias-notificacion-eng057-design.md`.

Incluye:

Canales permitidos.
Categorías.
Frecuencia.
Horarios.
Consentimientos.

Diferido:

Aplicación real de la frecuencia (agregación en lotes/digest).
Aplicación real del horario de silencio (bloqueo o diferimiento del envío).
Catálogo cerrado de categorías silenciables.
Historial de consentimientos versionado por política legal.
Gestión administrativa de las preferencias de otro usuario.
ENG-058 — Plantillas de comunicación

Estado: Completado

Nota (2026-08-26): tercera y última historia de la Fase 11, extiende `Modules\Notification` con un tercer agregado, `CommunicationTemplate` — un catálogo versionado de plantillas de contenido, independiente del envío de notificaciones (ENG-056/057): `SendNotificationCommand` no se modificó para usar plantillas. Versionado simple (campo `version` que se incrementa al editar, sin snapshots históricos, mismo criterio que `Badge`). Variables con sintaxis `{{variable}}` sustituidas por `str_replace`, declaradas como una lista cerrada por plantilla — renderizar sin proveer todas las declaradas lanza `MissingTemplateVariable` (422); placeholders no declarados quedan como texto literal. Idiomas modelados como una fila por código+idioma, cada una con su propio ciclo de versión (único por código+idioma, no globalmente único). Marca institucional sin mecanismo nuevo — convención de variables reservadas (`{{institution_name}}`, etc.) que el llamador provee al renderizar. Vista previa (`POST /templates/{id}/preview`) bajo `communication_templates.view`, no requiere el permiso de gestión. `communication_templates.view` no se otorga a `Student` (herramienta interna administrativa/docente, mismo criterio que `road_passports.view`/`certifications.view`/`simulators.view`). Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-26-plantillas-comunicacion-eng058-design.md`.

Incluye:

Plantillas versionadas.
Variables.
Idiomas.
Marca institucional.
Vista previa.

Diferido:

Integración con `SendNotificationCommand` (uso de una plantilla al enviar una notificación real).
Plantillas específicas por organización con resolución en cascada.
Motor de plantillas real (condicionales, bucles, herencia).
Historial completo de versiones anteriores.

Con esto cierra por completo la **Fase 11 — Comunicación y notificaciones** (ENG-056 a ENG-058).
18. Fase 12 — Administración y operación
ENG-059 — Panel administrativo API

Estado: Completado

Nota (2026-08-27): primera historia de la Fase 12 (Administración y operación). Antes de implementar, se investigó qué ya existía (vía agentes de exploración) para siete áreas de madurez muy distinta. Cursos y Evaluaciones ya tenían CRUD completo en `Modules\Academic` — se reutilizan sin cambios. Usuarios y Organizaciones se extendieron directamente en sus módulos propietarios (`Modules\Identity`, `Modules\Organization`), mismo criterio que "cada permiso vive donde vive su agregado": Identity gana listar/ver detalle/activar/desactivar (reutilizando `User::activate()`/`deactivate()` ya existentes — "suspender" se mapea a `deactivate()`, sin introducir un concepto nuevo); Organization gana ver detalle y actualizar (renombrar). Reportes, Configuración y Operación del sistema eran completamente nuevos (*greenfield*) y se agrupan en un módulo nuevo, `Modules\Admin` (bounded context de la Fase 12): `SystemSetting` (almacén clave-valor simple), un resumen agregado de conteos (`SystemSummaryRepository`, que lee directamente los modelos Eloquent de otros módulos — excepción deliberada y documentada al aislamiento entre módulos, limitada a este reporte de solo lectura), salud agregada (solo conectividad a base de datos, ya que los demás módulos solo tienen un ping fijo sin señal real que agregar), y una API de lectura sobre `Modules\Audit` (que se extendió con un único método nuevo, `AuditRepository::all()`, antes solo tenía `save()`). Permisos nuevos: `users.manage`/`users.view` y `reports.view` (SuperAdmin + InstitutionalAdmin, mismo patrón que el resto de la sesión); `system_settings.manage`/`system_settings.view`/`system_operations.view` (únicamente SuperAdmin, mismo criterio que `roles.manage` — configuración y operación global, no una preocupación por institución). Detalle completo y alcance acordado explícitamente con el usuario en `docs/plans/2026-08-27-panel-administrativo-eng059-design.md`.

Incluye:

Usuarios.
Organizaciones.
Cursos.
Evaluaciones.
Reportes.
Configuración.
Operación del sistema.

Diferido:

Reseteo de contraseña administrativo, acciones masivas sobre usuarios, impersonación de cuenta.
Cambiar el tipo institucional de una organización o gestionar sedes desde el endpoint de actualización.
Motor de reportes configurable, filtros por fecha/organización, exportación.
Categorías/tipado de valores/historial de cambios en la configuración del sistema.
Salud real por módulo (más allá de la conectividad a base de datos).
Filtros de auditoría por usuario/entidad/rango de fechas.
ENG-060 — Gestión de archivos

Estado: Completado

Nota (2026-08-27): segunda historia de la Fase 12. A diferencia de ENG-059, introduce un concepto de dominio propio (archivos almacenados), no una vista administrativa sobre otro módulo — se le da un módulo nuevo, `Modules\FileStorage`, en vez de extender `Modules\Admin`: las fases del roadmap son agrupaciones de planificación, no límites de contexto acotado de DDD. Investigación previa: el contenedor MinIO ya existía en `compose.yaml` pero no estaba conectado (faltaba `league/flysystem-aws-s3-v3` y las variables `AWS_*`); no existía ningún servicio antivirus, ningún concepto de archivos/adjuntos ni ningún concepto de cuota — completamente *greenfield*. Alcance acordado: MinIO conectado de verdad (disco `s3` ya bien configurado, solo faltaba cablearlo); antivirus como un simple estado `pending`/`clean`/`infected` sin integración real con ningún motor; carga por el backend (multipart) y descarga vía URL temporal firmada (`Storage::disk('s3')->temporaryUrl()`, nunca reenviando bytes); cuota simple por usuario leída de un `SystemSetting` (`file_storage_quota_bytes`, con valor por defecto). Detalle completo en `docs/plans/2026-08-27-gestion-archivos-eng060-design.md`.

Incluye:

MinIO.
Carga segura.
Descarga autorizada.
Metadatos.
Antivirus.
URLs temporales.
Cuotas.

Diferido:

Integración real con un motor antivirus.
Carga directa con URL prefirmada (subida directo del cliente a MinIO).
Aplicación de una política de bloqueo de descarga según el estado de escaneo.
Cuotas por organización o por rol; límite de cuota configurable por archivo individual.
Consulta administrativa de todos los archivos de todos los usuarios.
Metadatos adicionales definidos por el usuario.
ENG-061 — Importaciones masivas

Estado: Completado

Nota (2026-08-27): a diferencia de ENG-059/ENG-060, no introduce un módulo nuevo: extiende `Modules\Identity` (usuarios/estudiantes) y `Modules\Academic` (cursos, preguntas) con un mecanismo de importación masiva por archivo CSV, mismo criterio arquitectónico que `CreateBulkEnrollmentsHandler` ya existente en `Modules\Academic` (el handler de lote llama directamente al handler de creación individual por cada fila y acumula un reporte `total`/`created`/`failed`/`results[]`, sin bus de mensajería adicional ni cola de trabajos). Investigación previa: "Estudiante" no es un concepto propio (es un `User` con el rol `Student` asignado); "Grupo" no existe en absoluto en el backend (ni agregado, ni tabla, ni concepto equivalente); Cursos y Preguntas ya tenían creación individual madura; no había ninguna librería CSV/Excel instalada. Alcance acordado: Grupos diferido por completo (ninguna base sobre la cual importar); Usuarios y Estudiantes unificados en un solo mecanismo (columna `role` opcional, `Student` por defecto); validación previa integrada en la misma operación (sin modo "solo validar" separado); procesamiento síncrono en la misma petición HTTP, sin colas. Detalle completo en `docs/plans/2026-08-27-importaciones-masivas-eng061-design.md`.

Incluye:

Usuarios.
Estudiantes.
Cursos.
Preguntas.
Validación previa.
Reporte de errores.

Diferido:

Grupos (no existe ningún concepto base; historia propia futura).
Soporte Excel/XLSX (solo CSV).
Procesamiento asíncrono / cola de trabajos / archivos grandes (miles de filas).
Modo "solo validar sin crear" como paso separado.
Persistencia del archivo de origen subido.
Plantillas de importación descargables, mapeo de columnas configurable.
ENG-062 — Exportaciones

Estado: Completado

Nota (2026-08-27): a diferencia de ENG-061, el roadmap no especifica QUÉ datos se exportan, solo los mecanismos. Investigación previa: no existía ninguna librería de XLSX ni PDF; `league/csv` (ya instalada en ENG-061 para leer) también sabe escribir CSV; no existía ningún `ShouldQueue` job en todo el backend. Alcance acordado: solo CSV (XLSX/PDF diferidos, cada uno requeriría una librería nueva); conjunto fijo y reducido de tres exportadores concretos — Auditoría (`Modules\Admin`), Cursos y Enrollments (`Modules\Academic`) — reutilizando sus consultas de listado ya existentes, en vez de un framework genérico; procesamiento síncrono en la misma petición HTTP, sin cola de trabajos; permiso nuevo y transversal `exports.view` (SuperAdmin + InstitutionalAdmin) en vez de reutilizar el `.view` de cada recurso, porque exportar todas las filas de una vez es un riesgo distinto a ver una lista paginada. Sin módulo nuevo: cada exportador vive en el módulo dueño de los datos, reutilizando `Modules\FileStorage` (ENG-060) para entregar una URL temporal de descarga y `Modules\Audit` para registrar cada exportación. Detalle completo en `docs/plans/2026-08-27-exportaciones-eng062-design.md`.

Incluye:

CSV.
Control de acceso.
Auditoría.

Diferido:

XLSX y PDF (requieren librerías nuevas y renderizado propio).
Exportaciones asíncronas / cola de trabajos.
Framework genérico de exportación de cualquier listado.
Paginación o filtros sobre los datos exportados.
Exportadores para Preguntas, Intentos de examen u otros listados.
Persistencia de un historial de exportaciones como agregado propio.
19. Fase 13 — Reportes y analítica
ENG-063 — Reportes académicos

Estado: Completado

Nota (2026-08-27): el roadmap listaba seis reportes sin especificar cómo se calculan ni sobre qué se agrupan. Investigación previa: ninguna consulta de listado en `Modules\Academic` hace agregación SQL — todo es traer filas y calcular en PHP (mismo patrón que `PracticalResultCalculator`/`RoadPassportTrustCalculator`); "Actividad" no tenía ningún dato base (`User` no registraba ninguna marca de tiempo de sesión); "Grupo" (cohorte/sección) no existe como concepto en el backend (confirmado también en ENG-061). A diferencia de toda la sesión anterior, el usuario rechazó explícitamente la propuesta de reducir a tres reportes y pidió los seis completos — única vez en la sesión que no se eligió la opción recomendada. Se construyeron cinco reportes (Rendimiento y Aprobación comparten la misma fuente de datos pero se exponen por separado, honrando el pedido); "Comparación por grupo" se reinterpretó como "por curso": cada reporte acepta una lista de `course_ids` en vez de existir un endpoint de "comparar" separado. Se agregó `User::recordLogin()`/`lastLoginAt` para dar base real a "Actividad". Calculado al vuelo sin persistencia; reutiliza el permiso `reports.view` ya existente. Detalle completo en `docs/plans/2026-08-27-reportes-academicos-eng063-design.md`.

Incluye:

Progreso.
Rendimiento.
Aprobación.
Competencias.
Actividad.
Comparación por grupo (vía `course_ids` en los cinco reportes anteriores).

Diferido:

Persistencia de reportes / recálculo programado.
Filtros de fecha o paginación sobre los datos agregados.
Un concepto real de "Grupo" (cohorte/sección) — la comparación es por curso.
Umbral de actividad configurable (fijo en 30 días).
Reportes por organización.
ENG-064 — Reportes de simulación

Estado: Completado

Nota (2026-08-28): mismo patrón ambiguo que ENG-063. Investigación previa: ningún repositorio de `Modules\Simulation` filtra por nada hoy (`SimulationSessionRepository::all()`/`allForUser()` sin parámetros; `TelemetryEventRepository`/`DecisionPointRepository` solo por sesión) — aún más plano que Academic antes de ENG-063. `TelemetryEventType` (enum cerrado `Collision`/`Infraction`/`SignalUsage`/`Critical`) ya distingue "Infracción" como su propio caso, por lo que Errores frecuentes e Infracciones comparten exactamente la misma fuente. "Competencias prácticas" es hoy solo una cadena de texto libre por sesión sin estructura real que agregar. Alcance acordado: cuatro reportes (Sesiones, Errores e infracciones unificados, Evolución, Riesgos detectados), agregados por usuario reutilizando `allForUser()` ya existente; Competencias prácticas y agregación por simulador diferidos. Calculado al vuelo sin persistencia; reutiliza `reports.view`. Detalle completo en `docs/plans/2026-08-28-reportes-simulacion-eng064-design.md`.

Incluye:

Sesiones.
Errores frecuentes.
Infracciones (unificado con Errores frecuentes).
Evolución.
Riesgos detectados.

Diferido:

Competencias prácticas (sin estructura real que agregar hoy).
Agregación por simulador (requeriría un método de repositorio nuevo).
Persistencia de reportes / recálculo programado.
Filtros de fecha o paginación.
ENG-065 — Indicadores institucionales

Estado: Completado

Nota (2026-08-28): tercera y última historia de la Fase 13. Mismo patrón ambiguo que ENG-063/064. Investigación previa: `Organization` tiene `Campus` como entidad hija con id propio, pero nada la referencia (ni `Enrollment` ni `RoleAssignment` tienen `campusId`, solo `organizationId`) — "Uso por sede" no tiene ningún dato hoy. `Certificate`/`RoadPassport`/los agregados de Gamification no tienen ningún campo de organización — "Impacto" no tiene ningún vínculo organizacional real en ninguna fuente candidata. `EnrollmentRepository::all()` ya filtra por `organizationId` a nivel SQL. Alcance acordado: cuatro indicadores — Participación, Finalización, Desempeño y Adopción — todos agregados por organización; Impacto y Uso por sede diferidos. Calculado al vuelo sin persistencia; reutiliza `reports.view`. Detalle completo en `docs/plans/2026-08-28-indicadores-institucionales-eng065-design.md`.

Incluye:

Participación.
Finalización.
Desempeño.
Adopción.

Diferido:

Impacto (ninguna fuente candidata tiene vínculo organizacional real).
Uso por sede (Campus no tiene ningún dato que agregar; requeriría agregar `campusId` a `Enrollment`/`RoleAssignment`).
Persistencia de indicadores / recálculo programado.
Filtros de fecha configurables sobre Adopción.
ENG-066 — Analítica nacional anonimizada

Estado: Diferido

Requiere previamente:

Gobierno de datos.
Anonimización.
Base jurídica.
Consentimientos.
Revisión ética.
Acuerdos institucionales.
20. Fase 14 — Seguridad y cumplimiento
ENG-067 — Rate limiting

Estado: Completado

Nota (2026-08-28): primera historia de la Fase 14 — Seguridad y cumplimiento. A diferencia de las historias recientes, Laravel ya trae soporte de primera clase para esto (`throttle:` + `RateLimiter::for()`) — completamente greenfield (no existía ningún rate limiting), pero mecánico de aplicar. Investigación previa: además de Login/Registro ya conocidos, se encontró que `POST /api/v1/auth/users/{userId}/activate` también es público (sin autenticación) — un endpoint no identificado antes de investigar. "Recuperación de contraseña" no existe en absoluto en el backend (ninguna ruta, controlador, ni lógica; `password_reset_tokens` es un artefacto sin usar del scaffold de Laravel) — no se puede aplicar rate limiting a una funcionalidad que no existe, así que se difirió por completo. Alcance acordado: Login, Registro (incluye la activación pública recién encontrada), Integraciones (las dos rutas `simulator.auth` de telemetría/decisiones) y Endpoints públicos (verificación de certificados). Limitadores nombrados registrados en `Modules\Foundation`; `login` limitado por correo+IP (no solo IP, patrón estándar contra *credential stuffing*); `simulator-integration` limitado por el simulador autenticado, no por IP (varios simuladores pueden compartir NAT/IP en un laboratorio). Respuesta 429 con el mismo formato `ApiErrorResponse` que el resto de errores (`TOO_MANY_REQUESTS`).

Incluye:

Login.
Registro.
Integraciones.
Endpoints públicos.

Diferido:

Recuperación de contraseña (la funcionalidad no existe; cuando se construya en una historia futura, debe incluir rate limiting desde el diseño inicial).
Cambiar `CACHE_STORE` a Redis para rate limiting de mayor rendimiento (el limitador funciona con el almacén `database` actual, solo más lento bajo carga alta).
Límites configurables por entorno o panel administrativo.
ENG-068 — Auditoría general

Estado: Completado

Nota (2026-08-28): segunda historia de la Fase 14. El módulo `Modules\Audit` ya existía con Actor/Acción/Recurso/Fecha bien conectados desde antes, pero solo 3 casos de uso lo llamaban (login, logout, logout-all, todos en `Modules\Identity`), IP nunca se persistía pese a tener columna en la base de datos, Correlation ID no existía en absoluto, "Resultado" no existía (solo se auditaban logins exitosos, nunca los fallidos — un hueco real de seguridad), y `LogoutUserUseCase` nunca registraba el `userId` del actor. Alcance acordado: autenticación (extendida a login fallido) + asignación de roles (`Modules\Authorization`) + cambios de configuración del sistema (`Modules\Admin`) — no los casi 90 comandos de escritura de todo el backend. Detalle completo en `docs/plans/2026-08-28-auditoria-general-eng068-design.md`.

Incluye:

Actor.
Acción.
Recurso.
Fecha.
IP.
Correlation ID.
Resultado.
Datos modificados.
ENG-069 — Gestión de secretos

Estado: Completado

Nota (2026-08-28): tercera historia de la Fase 14. Investigación previa encontró que "Rotación" y "Llaves de integraciones" ya estaban resueltos por el mecanismo de rotación de llaves de simuladores construido junto con ENG-067 (hash SHA-256, revelado único, ciclo de vida completo) — no había ninguna otra integración externa activa en el sistema. El hueco real de "Rotación" estaba en Sanctum: los tokens de acceso nunca expiraban. Tampoco existía ninguna validación de variables de entorno requeridas al arrancar, ni ningún mecanismo de escaneo de secretos en Git (cero CI en todo el repositorio). Alcance acordado: expiración de tokens Sanctum, validación de variables requeridas en producción (falla rápido), y un resguardo ligero de escaneo de secretos en Git (script + hook local) — sin construir un pipeline de CI completo ni integrar un gestor de secretos externo. Detalle completo en `docs/plans/2026-08-28-gestion-secretos-eng069-design.md`.

Incluye:

Variables de entorno.
Rotación.
Llaves de integraciones.
Prohibición de secretos en Git.
Gestión por ambiente.
ENG-070 — Protección de datos personales

Estado: Completado

Nota (2026-08-28): cuarta historia de la Fase 14, alcance completo (a diferencia de casi todas las historias previas de esta fase, donde se optó por el alcance reducido). Investigación previa confirmó que "Minimización" ya estaba satisfecha (ningún módulo recolecta datos de más). El resto requirió trabajo real y extenso: borrado físico real de cuentas (autoservicio `DELETE /api/v1/auth/me` y un job de retención `identity:purge-inactive-accounts` con 3 años de inactividad configurable), con dos correcciones de esquema (FK nueva en `authorization_role_assignments` con cascada, FK nueva en `audit_logs` con desvinculación) y un cambio de esquema (`certificates.user_id` pasa a nullable con desvinculación en vez de cascada, para que un certificado siga siendo verificable públicamente aunque la cuenta del titular se elimine — esto propagó un refactor de `userId` a `?string` en todo `Modules\Certification`). Nuevo módulo `Modules\Legal` para consentimiento versionado por política (no un simple booleano). Exportación de datos personales de autoservicio (`GET /api/v1/auth/me/data-export`) agregando datos de diez módulos. Detalle completo en `docs/plans/2026-08-28-proteccion-datos-personales-eng070-design.md`.

Incluye:

Minimización.
Retención.
Eliminación.
Anonimización.
Exportación de datos personales.
Consentimiento.
ENG-071 — Seguridad para menores de edad

Estado: Completado

Nota (2026-08-28): quinta y última historia planificada de la Fase 14, alcance reducido. Bloqueo real encontrado: no existía ningún campo de fecha de nacimiento en el sistema, así que "menor de edad" no tenía ninguna condición que lo disparara — se agregó `date_of_birth` opcional a `User` (nueva recolección justificada por el propósito de cumplimiento) con un método de dominio `isMinor()`. Consentimiento parental autodeclarado (el propio menor confirma contar con autorización de su tutor, sin verificar la identidad real de un tercero) vía un campo nuevo en `Modules\Legal`'s `UserConsent`. Se corrigió la única fuga de datos confirmada: la verificación pública de certificados dejó de exponer el nombre del titular cuando es menor de edad. "Protección de perfiles" se confirmó ya satisfecha (no existe ningún leaderboard ni perfil público). "Controles institucionales" se limitó a que una organización consulte el estado de consentimiento parental de sus propios estudiantes menores. Detalle completo en `docs/plans/2026-08-28-seguridad-menores-eng071-design.md`.

Incluye:

Consentimiento parental.
Datos mínimos.
Restricción de exposición.
Protección de perfiles.
Controles institucionales.
ENG-072 — Idempotencia

Estado: Completado

Nota (2026-08-28): sexta y última historia de la Fase 14 — Seguridad y cumplimiento (con esto cierra por completo la fase). "Registro de simulaciones" y "Sincronizaciones móviles" ya estaban resueltos desde ENG-050 (id generado por el cliente + `insertOrIgnore`, sin tabla de llaves de idempotencia separada). "Pagos" no tiene código que corregir — el módulo no existe aún (ENG-077, Fase 15+); el patrón establecido aquí servirá de plantilla cuando se construya. Huecos reales corregidos: `CreateEnrollmentHandler`/`CreateInstitutionalEnrollmentHandler` e `IssueCertificateHandler` lanzaban un error 409 al reintentar una operación ya completada en vez de devolver el recurso existente; `AssignRoleHandler` no tenía ninguna protección y creaba asignaciones de rol duplicadas silenciosamente ante un reintento. Diferido explícitamente: restricciones de unicidad a nivel de base de datos para condiciones de carrera bajo concurrencia real, y eliminación de cuenta idempotente (su explotabilidad práctica es limitada porque el token de autenticación se elimina en cascada tras el primer borrado exitoso). Detalle completo en `docs/plans/2026-08-28-idempotencia-eng072-design.md`.

Incluye:

Registro de simulaciones.
Pagos.
Inscripciones.
Sincronizaciones móviles.
Operaciones críticas.
21. Fase 15 — Integraciones
ENG-073 — API Keys para sistemas externos

Estado: Completado

Nota (2026-08-29): primera historia de la Fase 15 — Integraciones. No existía ningún mecanismo de API key para consumidores externos; el patrón más cercano era la llave de integración de simuladores de `Modules\Simulation` (ENG-067), que se clonó deliberadamente (sin extraer a un kernel compartido, por ser dos contextos acotados distintos) en un módulo nuevo `Modules\Integration`: agregado `ApiConsumer` con alcances (validados contra `Modules\Authorization`'s `Permission`), expiración opcional, ciclo de vida suspensión/reactivación/revocación terminal y rotación de llave; middleware de autenticación (`api_consumer.auth`) y de verificación de alcance (`scope:*`); limitador de tasa nombrado `external-integration` (60/min por consumidor); auditoría de las acciones administrativas (registrar/suspender/reactivar/revocar/rotar) con el id del administrador que las realiza, sin auditar cada autenticación de request. Alcance reducido acordado: se construyó el mecanismo completo más dos endpoints de humo (`GET /api/v1/external/status`, `GET /api/v1/external/reports/ping`) para probarlo de punta a punta; retrofitar alcances a la superficie de API existente se difirió explícitamente a ENG-076 (Integraciones institucionales), historia donde se decidirá caso por caso qué endpoints necesitan quedar expuestos a consumidores externos. Detalle completo en `docs/plans/2026-08-29-api-keys-sistemas-externos-eng073-design.md`.

Incluye:

Identificación del consumidor.
Alcances.
Revocación.
Expiración.
Rate limiting.
Auditoría.
ENG-074 — Webhooks

Estado: Completado

Nota (2026-08-29): segunda historia de la Fase 15 — Integraciones. Investigación previa encontró que esta historia era enteramente nueva: no existía ningún mecanismo de eventos de dominio, ningún cliente HTTP saliente, y la cola de Laravel estaba configurada pero nunca usada (cero `Job`/`ShouldQueue` en todo el repositorio) — este es el primer uso real de la cola. Se construyó un módulo nuevo `Modules\Webhook`: `WebhookSubscription` (url, secreto cifrado reversiblemente con `Crypt` — a diferencia del hash irreversible de `IntegrationKey`, un secreto de firma debe poder recuperarse en cada entrega —, alcance de eventos, ciclo de vida `Active/Suspended`) y `WebhookDelivery` (una fila por entrega: estado `Pending/Delivered/Failed/DeadLettered`, intentos, última respuesta, próximo reintento). Firma HMAC-SHA256 sobre el payload exacto enviado (`X-Webhook-Signature`, sin ventana anti-replay, mismo convenio que GitHub). Entrega vía `DeliverWebhookJob` real con reintentos por backoff exponencial (30s·2^n, techo 1h) gestionados por el propio dominio en vez de la maquinaria nativa de colas; dead-letter tras 5 intentos con recuperación manual vía endpoint administrativo. Idempotencia orientada al receptor mediante un header `X-Webhook-Delivery-Id` estable entre reintentos. Alcance reducido acordado: se cablearon solo dos eventos de dominio reales (`enrollment.created` en `Modules\Academic`, `certificate.issued` en `Modules\Certification`) como prueba de punta a punta, en vez de retrofitear un bus de eventos genérico a través de todos los módulos. Detalle completo en `docs/plans/2026-08-29-webhooks-eng074-design.md`.

Incluye:

Eventos.
Firmas.
Reintentos.
Idempotencia.
Registro de entregas.
Dead-letter handling.
ENG-075 — Integración con aplicaciones móviles

Estado: Completado

Nota (2026-08-29): tercera historia de la Fase 15 — Integraciones. Investigación previa encontró que ninguno de los cinco puntos tenía mecanismo real: el versionado era solo el prefijo fijo `api/v1/`, no existía ningún concepto de dispositivo (Sanctum solo guarda un nombre de texto libre), `Modules\Notification` nunca enviaba nada externamente pese a ya tener un canal `Mobile` en su enum, y "sincronización" solo existía para telemetría de simuladores. Se construyó un módulo nuevo `Modules\Mobile`: `MobileDevice` (identificación + push token opcional + versión de app, actualizado — no rechazado — al re-registrarse, a diferencia del patrón de idempotencia de ENG-072); compatibilidad vía una configuración `mobile_min_app_version` reutilizando el `SystemSetting` genérico ya existente de `Modules\Admin` más un middleware que compara el header `X-App-Version`; notificaciones push reales vía HTTP a un endpoint configurable (cola real, mismo patrón de `DeliverWebhookJob` de ENG-074, pero deliberadamente sin registro de entregas/reintento/dead-letter — un push best-effort no lo amerita); un único endpoint ilustrativo de sincronización incremental. Alcance reducido acordado: no se retrofiteó `updated_since`/cursor a través de todos los módulos existentes ni se integró un SDK real de FCM/APNs. Detalle completo en `docs/plans/2026-08-29-integracion-moviles-eng075-design.md`.

Incluye:

Versionado.
Compatibilidad.
Sincronización.
Tokens por dispositivo.
Notificaciones móviles.
ENG-076 — Integraciones institucionales

Estado: Completado

Nota (2026-08-29): cuarta y última historia planificada de la Fase 15 — Integraciones (con esto cierra por completo la fase). A diferencia de las anteriores, sus vinetas son tipos de institución, no capacidades técnicas. Se agregó `OrganizationType::University` (las otras tres — Centros educativos, Empresas, Entidades públicas — ya existían). El punto real de la historia era el que ENG-073 dejó explícitamente pendiente: retro-adaptar el control de alcances de `Modules\Integration` a la API de negocio real. La investigación encontró un hueco de seguridad — cualquier permiso del sistema (incluso `system_settings.manage`) podía otorgarse como alcance externo sin restricción — cerrado con `ExternalScopeAllowlist`, una lista curada de solo cinco alcances seguros para consumidores externos. Como interpretación concreta de "Sistemas académicos externos" se construyó un flujo real de punta a punta: matrícula institucional masiva vía API key (`POST /api/v1/external/institutional/enrollments`, alcance `enrollments.manage`), reutilizando el patrón ya establecido de matrícula institucional de `Modules\Academic`. El resto de endpoints administrativos existentes se quedan como están, solo para usuarios humanos. Detalle completo en `docs/plans/2026-08-29-integraciones-institucionales-eng076-design.md`.

Incluye:

Centros educativos.
Empresas.
Universidades.
Entidades públicas.
Sistemas académicos externos.
ENG-077 — Pagos y facturación

Estado: Diferido

Incluye posteriormente:

Pasarela de pagos.
Órdenes.
Transacciones.
Comprobantes.
Suscripciones.
Integración contable.
22. Fase 16 — Inteligencia artificial
ENG-078 — Gobierno de IA

Estado: Completado

Nota (2026-08-29): primera y única historia del roadmap con una nota explícita de alineación documental ("Debe alinearse con la documentación SEC y ARC correspondiente"). La investigación inicial no encontró documentación SEC/ARC en este repositorio ni ninguna funcionalidad de IA/LLM existente en el código — se trató como un bloqueo genuino y se preguntó al usuario en vez de adivinar el alcance. El usuario aportó los documentos, ubicados en el repositorio hermano `D:\vr506\EDUDRIVE\edudrive-framework`: SEC-024 — Seguridad de Inteligencia Artificial y Modelos (v0.1) y ARC-012 — Arquitectura de Inteligencia Artificial (10 subdocumentos). Con esa base se propuso alcance y el usuario eligió alcance completo. Se construyó `Modules\AiGovernance` con seis conceptos (Sistema de IA, Decisión de IA, Modelo, Prompt, Incidente, Evaluación de proveedor) más un AI Gateway síncrono. Las reglas de dominio citan secciones concretas de SEC-024: un sistema clasificado IA-4 exige aprobación extraordinaria antes de producción (§4.5); IA-3 o IA-4 exigen aprobación de comité (§28.2); sistemas que procesan datos de menores exigen nivel de supervisión humana `Proposes` o superior antes de producción (§8.4). `AiDecision` es el primer flujo de revisión humana ("requiere aprobación antes de finalizarse") de todo el repositorio, deliberadamente independiente de `Modules\Audit` (que asume siempre un actor humano). Los 20 usos prohibidos de SEC-024 §25 no se modelaron como enum cerrado por ser mayormente políticas organizacionales sin punto de aplicación en código; se tradujo en cambio la prohibición más concreta y verificable (IA-4 sin aprobación extraordinaria) en una invariante de dominio real. El AI Gateway (`AiGatewayClient`/`HttpAiGatewayClient`) no integra ningún proveedor de IA real — apunta a un endpoint HTTP genérico configurable — pero sí aplica de forma genuina la clasificación de gobierno del sistema invocado (deriva `requiresReview` del nivel de supervisión real del sistema) y registra cada invocación como una `AiDecision` real con observabilidad (tokens, costo, latencia) incorporada. Sin claves foráneas en los campos `*_owner_id`/`*_user_id` de gobernanza, por diseño. Detalle completo en `docs/plans/2026-08-29-gobierno-ia-eng078-design.md`.

Incluye:

Casos permitidos.
Casos prohibidos.
Supervisión humana.
Registro de decisiones.
Privacidad.
Evaluación de modelos.
ENG-079 — Asistente educativo

Estado: Diferido

Incluye:

Explicaciones.
Refuerzo.
Recomendaciones.
Límites pedagógicos.
Fuentes verificadas.
ENG-080 — Análisis de patrones de conducción

Estado: Diferido

Incluye:

Riesgos.
Errores repetitivos.
Tendencias.
Recomendaciones.
Explicabilidad.
23. Fase 17 — Plataforma y operación avanzada
ENG-081 — Colas y trabajos asíncronos

Estado: Completado

Nota (2026-08-29): historia sin documento de diseño previo, solo seis viñetas sueltas. La investigación encontró que Redis ya estaba completamente configurado (contenedor corriendo, conexiones en `config/queue.php`/`config/database.php`) pero sin usarse (`QUEUE_CONNECTION=database`), y sin ningún worker de cola en `compose.yaml` — gap ya documentado dos veces en el propio repo (cierres de ENG-062 y ENG-074). El usuario eligió alcance completo (la tercera vez en la sesión, tras ENG-070 y ENG-078). Trabajo en seis tramos: (A) se activó `QUEUE_CONNECTION=redis`, se corrigió `REDIS_HOST` (apuntaba a `127.0.0.1`, inalcanzable desde el contenedor `app`) y se agregó el primer worker de cola (`queue-worker`) a `compose.yaml`; (B) nuevo módulo `Modules\AsyncProcessing` con un `AsyncJob` genérico (Pending/Processing/Completed/Failed, autorización por propiedad vía `requestedByUserId`) y un único endpoint `GET /api/v1/async-jobs/{id}` reutilizado por todos los módulos consumidores; (C) las tres exportaciones CSV existentes (auditoría, cursos, matrículas) pasan de síncronas a async sobre ese mecanismo, cambiando su contrato HTTP a 202+sondeo (`ExportMyDataUseCase` de Identity queda fuera a propósito: es JSON de un usuario, no CSV administrativo); (D) los tres imports masivos existentes (usuarios, cursos, preguntas) siguen el mismo patrón; (E) `NotificationChannel::Email` —hasta ahora solo un valor de enum— gana un envío real (Mailable + Job + Mailpit activado, mismo patrón puerto/impl/job que el canal Mobile de ENG-075); (F) módulo nuevo `Modules\Analytics`, acotado deliberadamente a un snapshot asíncrono de conteos reales (matrículas/certificaciones/usuarios por estado) para no invadir el territorio de ENG-080 (Diferido). Los nuevos Jobs de exportación/importación/correo estandarizan el reintento nativo de Laravel (`$tries`/`backoff()`/`failed()`); el patrón de reintento a nivel de dominio de `DeliverWebhookJob` (ENG-074) no se toca, es una decisión ya cerrada. Detalle completo en `docs/plans/2026-08-29-colas-trabajos-asincronos-eng081-design.md`.

Incluye:

Redis.
Correos.
Exportaciones.
Procesamiento de archivos.
Analítica.
Reintentos.
ENG-082 — Scheduler

Estado: Completado

Nota (2026-08-29): historia sin documento de diseño previo, solo cinco viñetas sueltas. La investigación encontró que ya existía un `Schedule::command('identity:purge-inactive-accounts')->daily()` en `routes/console.php`, pero ningún proceso ejecutaba `schedule:run`/`schedule:work` — exactamente el mismo tipo de gap que ENG-081 resolvió para colas (Redis configurado, sin worker), aquí para el scheduler. El usuario eligió el alcance reducido recomendado (a diferencia de ENG-070/078/081, donde había elegido el completo). Se activó el scheduler real: nuevo servicio `scheduler` en `compose.yaml` (`php artisan schedule:work`, mismo patrón que `queue-worker` de ENG-081). "Limpieza de tokens" se resolvió sin código nuevo: Sanctum ya trae de fábrica `sanctum:prune-expired`, solo se programó (`->daily()`, junto al comando de cuentas inactivas ya existente). "Mantenimiento" cerró un gap real encontrado en la investigación: `ExportFileWriter` (ENG-062/081) generaba una URL firmada de 15 minutos pero nunca borraba el objeto subyacente, dejando archivos huérfanos indefinidamente en MinIO/S3 — nuevo comando `async-processing:cleanup` (`Modules\AsyncProcessing`) que borra esos archivos y purga los `AsyncJob`s terminales más allá de la retención configurada (`ASYNC_JOB_RETENTION_HOURS`, default 24h). "Notificaciones" (digest `NotificationFrequency::Daily/Weekly`), "Reportes programados" (cron sobre `Modules\Analytics`) y "Expiración" proactiva (avisar antes de que venza un `Certificate`/`ApiConsumer`) quedaron fuera de alcance a propósito — son features de negocio nuevas, no solo activación de infraestructura ya presente. Detalle completo en `docs/plans/2026-08-29-scheduler-eng082-design.md`.

Incluye:

Limpieza de tokens.
Notificaciones.
Reportes programados.
Expiración.
Mantenimiento.
ENG-083 — Observabilidad

Estado: Completado

Nota (2026-08-29): historia sin documento de diseño previo, solo seis viñetas sueltas. La investigación encontró que Correlation ID ya existía (`Modules\Foundation\Presentation\Http\Middleware\CorrelationId`, header `X-Correlation-ID`) pero solo lo consumía explícitamente `Modules\Audit`; los logs eran texto plano; y Métricas, Trazas y Dashboards eran inexistentes, cada una requiriendo infraestructura externa nueva (Prometheus, OpenTelemetry+collector, Grafana) a diferencia de ENG-081/082 donde bastaba activar algo ya presente. El usuario eligió el alcance reducido recomendado. Se activaron logs JSON estructurados reales (`Monolog\Formatter\JsonFormatter` en los canales `single`/`daily`); el correlation_id se reforzó para aparecer explícitamente en el payload de error (`ApiErrorResponse`) y en los 9 `Log::warning` existentes de Jobs en cola (ENG-081/082) — capturado en el constructor de cada Job vía `Context::get()`, ya que los Jobs corren en un proceso de worker separado sin el contexto de la request HTTP original; las excepciones no manejadas ganan contexto rico (`correlation_id`, `url`, `method`, `user_id`) vía `$exceptions->context(...)` de Laravel 11+; y se activó el canal Slack ya boilerplate como alerta real para errores críticos (solo si `LOG_SLACK_WEBHOOK_URL` está configurado), corrigiendo de paso un bug pre-existente donde ese canal heredaba el nivel genérico `LOG_LEVEL` en vez de tener su propio nivel dedicado. Métricas, trazas distribuidas y dashboards quedaron fuera de alcance a propósito. Detalle completo en `docs/plans/2026-08-29-observabilidad-eng083-design.md`.

Incluye:

Logs estructurados.
Métricas.
Trazas.
Correlation ID.
Alertas.
Dashboards.
ENG-084 — Backups y recuperación

Estado: Completado

Nota (2026-08-29): a diferencia de ENG-081/082/083, la investigación confirmó que aquí no existía absolutamente nada construido: sin paquete de backup, sin `pg_dump`/`pg_restore` en el `Dockerfile`, sin versionado en el bucket de MinIO, sin política de RPO/RTO documentada en ningún lugar del repo. El usuario eligió el alcance reducido recomendado. Se construyó `Modules\Backup` (sin capa Domain, es infraestructura pura sin invariantes de negocio) con `backup:database` (`pg_dump -Fc` real, sube el dump al mismo storage S3/MinIO ya configurado bajo `backups/postgres/`, programado diario vía el scheduler de ENG-082) y `backup:restore {path}` (`pg_restore --clean --if-exists`, exige confirmación explícita por ser destructivo). `Modules\FileStorage` ganó `readToLocalFile()` (no existía forma de leer contenido de vuelta) y `files:ensure-bucket` ahora habilita versionado de objetos en el bucket. Restricción real encontrada durante el diseño: la suite de Pest usa SQLite en memoria, así que `pg_dump`/`pg_restore` reales no son ejecutables dentro de ella — se probó en dos niveles, automatizado (fakes verificando la orquestación) y manual real (backup+restore real contra Postgres de desarrollo, ~10.5s medidos), confirmando además que `pg_restore --clean --if-exists` no purga objetos creados después del backup que no formen parte de él. Política de RPO/RTO documentada en `docs/operaciones/backups-rpo-rto.md`. Detalle completo en `docs/plans/2026-08-29-backups-recuperacion-eng084-design.md`.

Incluye:

PostgreSQL.
MinIO.
Configuración.
Restauración.
Pruebas de recuperación.
RPO y RTO.
ENG-085 — Despliegue y ambientes

Estado: Completado

Nota (2026-08-29): a diferencia de las historias anteriores de esta fase, ENG-085 no traía ninguna viñeta "Incluye", solo cinco ambientes previstos. Se interpretó como configuración y documentación real por ambiente, no el pipeline de despliegue en sí (eso es ENG-086, la siguiente historia). El usuario eligió el alcance reducido recomendado. Se publicó `config/cors.php` (no existía; el comportamiento CORS dependía de defaults internos sin declarar), con orígenes configurables vía `CORS_ALLOWED_ORIGINS` — verificado manualmente contra nginx real que la restricción por dominio funciona. Se actualizó `.env.example` para reflejar fielmente el setup Docker real (antes era el stock genérico de Laravel, desactualizado). Se encontró y corrigió un bug real preexistente: `DatabaseSeeder` llamaba a `User::factory()`, pero el modelo real no tiene `HasFactory` desde el refactor de ENG-009 — el seeder estaba roto, sin ningún test que lo cubriera. Se reescribió usando el patrón de dominio real, con guard de ambiente para que la cuenta de prueba nunca se cree fuera de local/testing. Se documentó la matriz real de diferencias entre los 5 ambientes en `docs/operaciones/ambientes.md`, atando lo ya construido en ENG-069/083/084. Detalle completo en `docs/plans/2026-08-29-despliegue-ambientes-eng085-design.md`.

Ambientes previstos:

Local.
Desarrollo.
QA.
Staging.
Producción.
ENG-086 — CI/CD

Estado: Completado

Nota (2026-08-29): la investigación confirmó que no existía ningún workflow de CI/CD propio del proyecto (`.github/workflows/*.yml`), ningún proveedor de hosting, registro de contenedores o servidor de destino real decidido en ningún documento, ni scripts de despliegue/rollback, ni migraciones automatizadas en el ciclo de vida del contenedor — a diferencia de ENG-081/082/083/084, aquí ni siquiera existía infraestructura parcial que activar. El usuario eligió el alcance reducido recomendado: no automatizar "Despliegue"/"Rollback" contra un servidor inventado, ya que no hay ninguna decisión de infraestructura real en el proyecto. Se creó `.github/workflows/ci.yml` con tres jobs: `quality` (Pint vía `composer format:test` + Larastan vía `composer analyse`), `test` (Pest, suite completa `tests/`+`modules/`, que corre contra SQLite en memoria sin necesitar Postgres/Redis/MinIO reales) y `build-image` (solo en push a `main`, solo si los dos anteriores pasan) que construye `docker/php/Dockerfile` — el mismo usado en desarrollo, sin duplicar uno "de producción" — y lo publica en `ghcr.io/edudrive-official/edudrive-api` taggeado por SHA inmutable y `latest`, sin secretos nuevos (usa el `GITHUB_TOKEN` nativo). Validar los tres gates realmente en este repo encontró y corrigió dos bugs reales preexistentes no relacionados: un problema de estilo de Pint en un test de `Modules\Learning`, y un bug de dominio en `Modules\Academic\Domain\ValueObjects\GradingResult` — el constructor confiaba en que `array_map` con closures tipadas lanzara el error correcto ante un elemento de tipo inválido en los breakdowns, pero PHP con `strict_types` lanza `TypeError`, no la `InvalidArgumentException` que el dominio y su test esperan; corregido con un guard `instanceof` explícito, siguiendo la convención de supresión `@phpstan-ignore instanceof.alwaysTrue` ya usada en `Modules\Organization\Domain\Aggregates\Organization`. Migraciones controladas, Despliegue y Rollback quedaron documentados como runbook manual en `docs/operaciones/ci-cd.md` (respaldo previo a migrar, imagen taggeada por SHA como base de rollback), listos para conectarse a automatización real el día que exista una decisión de infraestructura. Detalle completo en `docs/plans/2026-08-29-ci-cd-eng086-design.md`.

Incluye:

Pint.
Larastan.
Pest.
Construcción de imágenes.
Migraciones controladas.
Despliegue.
Rollback.
24. Reglas de priorización

El orden general será:

Seguridad e identidad.
Autorización.
Organizaciones.
Perfiles.
Catálogo educativo.
Evaluaciones.
Progreso.
Pasaporte Vial.
Integración SIMUDRIVE.
Gamificación.
Reportes.
Integraciones externas.
Inteligencia artificial.

No se debe iniciar una fase si depende de estructuras esenciales todavía no implementadas.

25. Historia técnica activa

Actualizado 2026-07-29: ENG-008 (Autenticación con Sanctum) y su auditoría básica (auth.login, auth.logout, auth.logout_all) están completados. Academic se adelantó parcialmente (ver nota en la sección 11) y queda en pausa.

La historia activa del proyecto pasa a la Fase 2 — Autorización y gobierno de acceso, con un alcance inicial reducido respecto al listado completo de ENG-012 a ENG-015 (detalle en `docs/plans/2026-07-29-consolidacion-autorizacion-organizaciones-design.md`):

- Roles mínimos viables: Superadministrador, Administrador institucional, Docente/Instructor, Estudiante.
- Catálogo simple de permisos, asignación a roles y middleware de verificación.
- Organización + Sede (Fase 3, ENG-016/017) con un enum simple de tipo institucional.
- Membresía organizacional (usuario–organización–rol).

Quedan diferidos explícitamente: roles adicionales (Coordinador, Evaluador, Soporte, Integración SIMUDRIVE), políticas de acceso complejas, historial de membresía, grupos/cohortes (ENG-019) y consentimientos (ENG-023).

Después de esta historia, se retoma Academic (Fase 5) donde quedó.

Actualizado 2026-07-31: la historia de Autorización y Organizaciones con alcance reducido descrita arriba está **Completado**. Se implementaron los módulos `Organization` (aggregate `Organization`, entidad `Campus`, endpoints de creación de organización, agregado de sede y listado) y `Authorization` (catálogo de roles/permisos con los 4 roles mínimos viables, `RoleAssignment` con asociación opcional a una organización, `PermissionChecker`, middleware `permission` y comando de consola `authorization:assign-role` para el arranque inicial), más la integración entre ambos: el permiso `organizations.manage` protege los endpoints de escritura de `Organization`. Detalle completo en `docs/engineering/ENG-LOG.md` (IMP-022) y en el plan `docs/plans/2026-07-29-autorizacion-organizaciones-alcance-reducido.md`.

Esto **no** equivale a completar ENG-012 a ENG-019 en su alcance íntegro. Quedan diferidos y pendientes para una historia futura:

- Roles adicionales a los 4 construidos (Coordinador, Evaluador, Soporte, Integración SIMUDRIVE) — ENG-012.
- Permisos con alcance por organización (`PermissionChecker` hoy es un sí/no global por usuario, no filtrado por `organization_id`) — ENG-013/ENG-014.
- Historial de membresía y revocación de una asignación de rol (el modelo actual es de solo inserción, sin endpoint `DELETE`) — ENG-018.
- Grupos y cohortes — ENG-019.
- Consentimientos y privacidad — ENG-023.
- Un flujo HTTP de autoservicio para crear el primer administrador (el arranque hoy es exclusivamente por CLI, aceptable en local/desarrollo pero es una brecha real antes de cualquier despliegue a producción).

La historia técnica activa vuelve a la Fase 5 — Catálogo educativo (Academic), retomándola donde quedó según la nota de la sección 11.

Actualizado 2026-08-01: antes de retomar Academic, se construyó un panel web administrativo mínimo (login con sesión + listar/crear organizaciones) sobre los endpoints de `Organization`/`Authorization` ya completados el 2026-07-31 — diseño en `docs/plans/2026-08-01-panel-organizaciones-web-design.md`, plan de implementación en `docs/plans/2026-08-01-panel-organizaciones-web.md`, detalle en `docs/engineering/ENG-LOG.md` (IMP-023). Este trabajo es presentación web (Blade), no una historia técnica nueva de este roadmap (que cubre específicamente el backend): no se modificó ningún módulo `Domain`/`Application` salvo la corrección de `EnsurePermission` ya registrada en la nota de ENG-013. Por eso no se le asigna un ENG-XXX propio, siguiendo el mismo criterio ya aplicado a los componentes del design system (`docs/plans/2026-07-31-design-system-web-componentes-design.md`), que tampoco aparecen en este roadmap.

Con esto cerrado, la historia técnica activa **sigue siendo** la Fase 5 — Catálogo educativo (Academic), sin cambios respecto a la nota anterior.

Actualizado 2026-08-02: se completó ENG-026 (Cursos) — ver la nota de la sección 11 y `docs/engineering/ENG-LOG.md` (IMP-024) para el detalle completo. Quedan explícitamente diferidos: el versionado curricular real (ENG-029, su propia historia futura), un endpoint de edición general de un curso ya existente, y ENG-024 (catálogo de competencias), ENG-025 (programas educativos), ENG-027 (módulos y unidades), ENG-028 (lecciones) — historias separadas, no tocadas aquí.

Actualizado 2026-08-03: se completó ENG-024 (Catálogo de competencias) — ver la nota de la sección 11 y `docs/engineering/ENG-LOG.md` (IMP-025). El incremento entrega el núcleo regional jerárquico; quedan fuera perfiles por país, asociaciones con cursos/evaluaciones/SIMUDRIVE y versionado curricular.

Actualizado 2026-08-03: se completó ENG-025 (Programas educativos) — ver la nota de la sección 11 y `docs/engineering/ENG-LOG.md` (IMP-026). El incremento entrega plantillas regionales con audiencia combinable, cursos existentes ordenados y ciclo de vida publicable/archivable, sin propiedad organizacional ni reglas legales nacionales.

Actualizado 2026-08-04: se completó ENG-027 (Módulos y unidades) — ver la nota de la sección 11 y `docs/engineering/ENG-LOG.md` (IMP-027). El incremento entrega una estructura curricular regional ordenada, transaccional y controlada por el agregado `Course`, sin adelantar lecciones, versionado ni seguimiento de progreso.

Actualizado 2026-08-08: se completó ENG-028 (Lecciones y contenido accesible) — ver la nota de la sección 11, `docs/engineering/ENG-LOG.md` y el plan de diseño `docs/plans/2026-08-05-lecciones-contenido.md`. El incremento entrega lecciones y bloques tipados con metadatos de accesibilidad validados, persistencia normalizada y atomicidad transaccional.

Actualizado 2026-08-10: se completó ENG-029 (Publicación y versionado curricular) — ver la nota de la sección 11, `docs/engineering/ENG-LOG.md` y el plan de diseño `docs/plans/2026-08-10-publicacion-versionado-curricular-design.md`. El incremento entrega el ciclo de vida draft → under_review → approved → published con snapshots inmutables por publicación, reapertura para construir la siguiente versión y endpoints de historial protegidos.

La historia técnica activa queda **Pendiente de decisión** entre volver a Fase 4 — Perfiles o iniciar ENG-031 (Exámenes y cuestionarios).

Actualizado 2026-08-10: se completó ENG-030 (Banco de preguntas) — ver la nota de la sección 12, `docs/engineering/ENG-LOG.md` y el plan de implementación `docs/plans/2026-08-10-banco-preguntas-eng030-implementation.md`. El incremento entrega el agregado `Question` con respuesta tipada por tipo, persistencia en `academic_questions`/`academic_question_options` y API protegida por los permisos `questions.manage`/`questions.view`.

La historia técnica activa queda **Pendiente de decisión** entre volver a Fase 4 — Perfiles o iniciar ENG-031 (Exámenes y cuestionarios).

Actualizado 2026-08-12: se completó ENG-033 (Motor de calificación) — ver la nota de la sección 12, `docs/engineering/ENG-LOG.md` y el plan de implementación `docs/plans/2026-08-12-motor-calificacion-eng033-implementation.md`. El incremento incorpora `ExamAttemptGrader`, `GradingPolicy`, `GradingResult`, breakdown por pregunta y por competencia, partial credit por tipo, penalizaciones acotadas y persistencia JSON del grading sobre el intento. ENG-034 (examen teórico de conducción) queda diferido y ya puede construirse sobre este motor.

Actualizado 2026-08-13: ENG-034 (Examen teórico de conducción) quedó implementado y verificado técnicamente sobre la base de `ENG-031` + `ENG-032` + `ENG-033`. El incremento entrega metadatos teóricos en `Question` y `Exam`, validación de banco oficial/categoría, política de grading derivada del examen, recomendaciones de estudio, endpoints `theory-exams` y `theory-attempts`, e historial teórico por usuario/categoría. Se mantiene en **En validación** hasta consolidar commits. Ver la nota de la sección 12, `docs/engineering/ENG-LOG.md` (IMP-034) y `docs/plans/2026-08-12-examen-teorico-conduccion-eng034-implementation.md`.
26. Definición de terminado

Una historia se considera terminada cuando cumple:

Código implementado.
Arquitectura respetada.
Validaciones aplicadas.
Manejo de errores definido.
Pruebas unitarias creadas.
Pruebas de integración creadas cuando corresponda.
Documentación actualizada.
composer format ejecutado.
composer quality aprobado.
Migraciones verificadas.
Commit realizado.
Estado actualizado en este roadmap.
27. Comandos de validación
docker compose exec app composer format
docker compose exec app composer quality

Para migraciones:

docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:status

Para rutas:

docker compose exec app php artisan route:list

Para limpiar cachés:

docker compose exec app php artisan optimize:clear
28. Control de cambios
Versión	Fecha	Descripción
1.0.0	2026-07-25	Creación del roadmap técnico oficial del backend
1.1.0	2026-07-29	Reconciliación con ENG-LOG.md: ENG-008 y subtareas marcadas como Completado, nota de avance adelantado de Academic (Fase 5), historia técnica activa actualizada a Autorización y Organizaciones con alcance reducido
1.1.1	2026-07-29	Corrección: ENG-008.8 (pruebas de autenticación) revertida a Pendiente al confirmar que no existen pruebas Feature automatizadas para login/me/logout/logout-all, solo un test de integración del repositorio de usuarios
1.2.0	2026-07-31	Cierre de la historia técnica de Autorización y Organizaciones con alcance reducido (Completado), con detalle de lo diferido para ENG-012 a ENG-019; historia técnica activa vuelve a Academic (Fase 5)
1.3.0	2026-08-01	Corrección de `EnsurePermission` (ENG-013) registrada; panel web de Organizaciones (login + listar/crear) documentado como trabajo de presentación fuera del alcance de este roadmap (IMP-023 en ENG-LOG.md); historia técnica activa confirmada sin cambios (Academic, Fase 5)
1.4.0	2026-08-02	Cierre de ENG-026 (Cursos): campos nuevos, endpoints publish/archive, permisos courses.manage/courses.view, corrección del manejo de excepciones de Academic (IMP-024 en ENG-LOG.md); catálogo de permisos actualizado de 3 a 5; historia técnica activa pasa a pendiente de decisión
1.5.0	2026-08-03	Cierre de ENG-024 (Catálogo de competencias): agregado jerárquico, persistencia, casos de uso, API protegida y permisos competencies.manage/competencies.view (IMP-025 en ENG-LOG.md); alcance futuro diferido explícitamente
1.6.0	2026-08-03	Cierre de ENG-025 (Programas educativos): plantillas regionales, audiencia combinable, cursos ordenados, ciclo de vida, persistencia normalizada, API protegida y permisos programs.manage/programs.view (IMP-026 en ENG-LOG.md); alcance futuro diferido explícitamente
1.7.0	2026-08-04	Cierre de ENG-027 (Módulos y unidades): currículo regional jerárquico dentro del agregado Course, persistencia transaccional, prerrequisitos, API protegida con courses.view/courses.manage y compatibilidad con cursos publicados legacy (IMP-027 en ENG-LOG.md); alcance futuro diferido explícitamente
1.8.0	2026-08-08	Cierre de ENG-028 (Lecciones y contenido accesible): incorporación de lecciones y bloques de contenido accesible tipados (texto, imagen, video, audio, interactivos, descargas), consulta/reemplazo atómico por unidad, y validación de cobertura de lecciones al publicar.
1.9.0	2026-08-10	Cierre de ENG-029 (Publicación y versionado curricular): estados `under_review`/`approved`, `publish` exige aprobación, snapshots inmutables por publicación en `academic_course_versions`, `reopen` para la siguiente versión, API de ciclo de vida e historial protegida por `courses.manage`/`courses.view`, y errores públicos de transición, reapertura y versión inexistente (IMP-029 en ENG-LOG.md)
1.10.0	2026-08-10	Cierre de ENG-030 (Banco de preguntas): agregado `Question` con respuesta tipada por tipo (single_choice, multi_select, true_false, matching, ordering, situacional), persistencia en `academic_questions`/`academic_question_options`, CQRS completo, media estrictamente `https` y API protegida por los permisos `questions.manage`/`questions.view` (IMP-030 en ENG-LOG.md)
1.11.0	2026-08-11	Cierre de ENG-031 (Exámenes y cuestionarios): agregado `Exam` anclado a un curso, lista ordenada de preguntas del banco con puntaje, configuración de duración/intentos/regla de aprobación/barajado/retroalimentación, CQRS completo (create/update/delete/get/list), respuesta de detalle enriquecida con `ref_id`/`type` y API protegida por los permisos `exams.manage`/`exams.view` (IMP-031 en ENG-LOG.md)
1.12.0	2026-08-12	Cierre de ENG-032 (Intentos de evaluación): agregado `ExamAttempt` con snapshot inmutable, estados y resultado básico, persistencia normalizada, CQRS start/answer/submit/cancel/get/list, API HTTP de intentos y permiso `exam_attempts.view` para lectura ampliada (IMP-032 en ENG-LOG.md)
1.13.0	2026-08-12	Cierre de ENG-033 (Motor de calificación): `ExamAttemptGrader`, `GradingPolicy`, `GradingResult`, breakdown por pregunta y competencia, partial credit/penalizaciones por tipo, persistencia JSON del grading y exposición controlada en submit/show (IMP-033 en ENG-LOG.md)
1.14.0	2026-08-13	ENG-034 pasa a En validación: metadata teórica en preguntas/exámenes, validación de banco oficial y categoría, grading derivado del examen, recomendaciones de estudio, endpoints `theory-exams`/`theory-attempts` e historial teórico; validación técnica focalizada completa en verde, pendiente consolidación de commits (IMP-034 en ENG-LOG.md)
1.15.0	2026-08-16	Cierre de ENG-036 (Seguimiento de progreso): agregado `EnrollmentProgress` con completitud de lecciones, `EnrollmentProgressCalculator` (porcentaje, tiempo invertido, evaluaciones y última actividad), CQRS completo (`CompleteLessonCommand`/`GetEnrollmentProgressQuery`) y API HTTP protegida por pertenencia o `enrollments.view` (IMP-036 en ENG-LOG.md)
1.16.0	2026-08-26	Consolidación de deuda de commits: ENG-032/033/034 (intentos de evaluación, motor de calificación, examen teórico) comiteados juntos en `1d6d90b` por compartir los mismos archivos; ENG-034 pasa de "En validación" a **Completado**. ENG-035 (Inscripciones) pasa de "Pendiente" a **Completado**: dominio, aplicación y persistencia de `Enrollment` comiteados en `e3e2186` (la API HTTP ya estaba comiteada desde antes)
1.17.0	2026-08-26	Cierre de ENG-039 (Recomendaciones de aprendizaje): `EnrollmentLearningRecommendationService` (próxima lección, refuerzo de competencias agregado por curso, exámenes para reintentar) y `GET /enrollments/{enrollmentId}/recommendations` protegido por pertenencia o `enrollments.view`; SIMUDRIVE y recomendaciones por pregunta individual diferidas explícitamente
1.18.0	2026-08-26	Cierre de ENG-040 (Núcleo del Pasaporte Vial): nuevo módulo `Modules\RoadPassport` con el agregado `RoadPassport` (identidad, estado, nivel, historial propio), CQRS completo y API HTTP en `/api/v1/road-passport` protegida por pertenencia o los permisos nuevos `road_passports.manage`/`road_passports.view`; vigencia, agregación de evidencias (ENG-041) y cálculo de confianza (ENG-042) diferidos explícitamente
1.19.0	2026-08-26	Cierre de ENG-041 (Evidencias del Pasaporte Vial): `RoadPassport::recordEvidence()` idempotente y registro reactivo de evidencia `course_completed`/`exam_passed` desde `Academic` (`CompleteEnrollmentHandler`/`SubmitExamAttemptHandler`), expuesta en `RoadPassportResponse`; prácticas/simulaciones (SIMUDRIVE), certificaciones y cálculo de confianza (ENG-042) diferidos explícitamente
1.20.0	2026-08-26	Cierre de ENG-042 (Competency Trust Model): `RoadPassportTrustCalculator` calcula un `trust_score` (0-100) global por pasaporte a partir de su evidencia (peso por fuente, decaimiento por recencia con piso mínimo, multiplicador de consistencia acotado), expuesto en `RoadPassportResponse` sin persistirse; desagregación por competencia, validez/expiración de evidencia y persistencia del score diferidos explícitamente
1.21.0	2026-08-26	Cierre de ENG-043 (Credenciales y certificaciones): nuevo módulo `Modules\Certification` con el agregado `Certificate` (código de validación `ValidationCode` con formato `XXXX-XXXX-XXXX`, estado `issued`/`revoked` terminal, vigencia opcional, historial), emisión manual vía `certifications.manage`, CQRS completo y API HTTP en `/api/v1/certification/certificates` protegida por pertenencia o `certifications.manage`/`certifications.view`; emisión automática desde evidencia del Pasaporte Vial, verificación pública por código (ENG-044) y reemisión tras revocación diferidos explícitamente (IMP-043 en ENG-LOG.md)
1.22.0	2026-08-26	Cierre de ENG-044 (Consulta pública controlada): endpoint público `GET /api/v1/certification/verify/{validationCode}` sin autenticación, con vigencia efectiva calculada (`valid`/`expired`/`revoked`) vía `Certificate::effectiveStatus()`, datos mínimos (código, curso, titular, fechas — sin `user_id` ni historial), y error uniforme `CERTIFICATE_NOT_FOUND` para código inválido o inexistente; listado público y límite de tasa diferidos explícitamente (IMP-044 en ENG-LOG.md)
1.23.0	2026-08-26	Cierre de ENG-045 (Registro de simuladores), primera historia de la Fase 9 (Integración con SIMUDRIVE): nuevo módulo `Modules\Simulation` con el agregado `Simulator` (identificador de dispositivo único, versión de software, ubicación opcional, estado `active`/`suspended`/`retired`, historial), llave de integración generada al registrar/rotar y devuelta una única vez (solo se persiste su hash SHA-256), CQRS completo y API HTTP en `/api/v1/simulation/simulators` protegida por `simulators.manage`/`simulators.view`; validación de sesiones/telemetría contra el simulador (ENG-046/047) diferida explícitamente (IMP-045 en ENG-LOG.md)
1.24.0	2026-08-26	Cierre de ENG-046 (Sesiones de simulación): nuevo agregado `SimulationSession` en `Modules\Simulation` (usuario, simulador, vehículo y escenario en texto libre, ciclo de vida `scheduled`/`in_progress`/`completed`/`cancelled`, duración planeada y efectiva), programación en autoservicio validando que el simulador esté `active`, criterio de propiedad extendido por primera vez a mutaciones (no solo consultas), CQRS completo y API HTTP en `/api/v1/simulation/sessions` protegida por pertenencia o `simulation_sessions.manage`/`simulation_sessions.view`; detección de conflictos de horario e integración con telemetría (ENG-047) diferidas explícitamente (IMP-046 en ENG-LOG.md)
1.25.0	2026-08-26	Cierre de ENG-047 (Telemetría): entidades `TelemetrySample`/`TelemetryEvent` (solo-append, sin invariantes de agregado) en `Modules\Simulation`; primer mecanismo de autenticación máquina-a-máquina del backend (`AuthenticateSimulator`, alias `simulator.auth`, llave de integración por *bearer token* contra el hash SHA-256 almacenado); envío por lotes validado contra sesión↔simulador y estado `InProgress`; consulta para humanos bajo `auth:sanctum` reutilizando `simulation_sessions.view` sin permiso nuevo; procesamiento/agregación (ENG-048) diferido explícitamente (IMP-047 en ENG-LOG.md)
1.26.0	2026-08-26	Cierre de ENG-048 (Resultados prácticos): servicio de dominio puro `PracticalResultCalculator` deriva un resultado `passed`/`failed` (puntaje 0-100, penalización por tipo de `TelemetryEvent`) en cada consulta a partir de la telemetría ya persistida de una sesión `Completed`, sin tabla ni migración nueva (mismo espíritu que `RoadPassportTrustCalculator`); competencias demostradas (texto libre) y evidencias asociadas (los propios errores) autocontenidos en `Modules\Simulation`; API HTTP en `GET /api/v1/simulation/sessions/{sessionId}/result` reutilizando `simulation_sessions.view`; registro manual, integración con el Pasaporte Vial y referencias a `Competency` de Academic diferidos explícitamente (IMP-048 en ENG-LOG.md)
1.27.0	2026-08-26	Cierre de ENG-049 (SIMUDRIVE Decision Engine): entidad `DecisionPoint` (solo-append, reacción del conductor como conjunto cerrado para permitir evaluación determinística) y servicio de dominio puro `DecisionEngineCalculator` que evalúa apropiación por riesgo, genera retroalimentación fija y calcula consistencia agrupando por riesgo dentro de la sesión, sin persistir el resultado (mismo patrón que ENG-048); envío por lotes autenticado con `simulator.auth` (ENG-047), consulta bajo `auth:sanctum` reutilizando `simulation_sessions.view`; consistencia entre sesiones y evaluación delegada a SIMUDRIVE diferidas explícitamente (IMP-049 en ENG-LOG.md)
1.28.0	2026-08-26	Cierre de ENG-050 (Sincronización offline) — última historia de la Fase 9: `POST /sessions/{id}/telemetry` y `POST /sessions/{id}/decisions` aceptan reenvíos idempotentes (id por ítem generado por el simulador, `insertOrIgnore()` en vez de `insert()`, conteo de filas realmente insertadas en la respuesta) y toleran datos tardíos (`SimulationSession::wasInProgressAt()` compara la marca de tiempo del dato contra `startedAt`/`endedAt` en vez de exigir que la sesión esté `InProgress` en el momento de la petición); la cola local es responsabilidad del simulador, fuera de alcance; modelar la sesión offline como concepto propio y una tabla de llaves de idempotencia por lote diferidos explícitamente (IMP-050 en ENG-LOG.md). Con esto cierra por completo la Fase 9 — Integración con SIMUDRIVE (ENG-045 a ENG-050)
1.29.0	2026-08-26	Cierre de ENG-051 (Logros), primera historia de la Fase 10 (Gamificación): nuevo módulo `Modules\Gamification` con el agregado `Achievement` (catálogo, código único, ciclo de vida `active`/`retired` sin reversión) y la entidad de solo-append `UserAchievement` (otorgamiento manual con evidencia y fecha); otorgamiento vía `achievements.manage` (mismo criterio que `Certificate`), CQRS completo y API HTTP en `/api/v1/gamification/achievements` con `achievements.view` extendido a `Student` por ser catálogo de navegación abierta (a diferencia de módulos previos); revocación de logros, consulta de logros de otro usuario y evaluación automática de reglas diferidas explícitamente (IMP-051 en ENG-LOG.md)
1.30.0	2026-08-26	Cierre de ENG-052 (Insignias), segunda historia de la Fase 10: agregado `Badge` en `Modules\Gamification`, con categoría cerrada `BadgeCategory` (educativa/institucional/práctica), nivel fijo `BadgeLevel` (bronce/plata/oro) y contenido editable vía `updateContent()` que incrementa un campo `version` (sin snapshots históricos); el otorgamiento (`UserBadge`) guarda `awardedVersion`, la versión vigente al momento de otorgarse; edición bloqueada si la insignia está retirada (`InvalidBadgeTransition`); otorgamiento manual vía `badges.manage` (mismo criterio que `Achievement`), CQRS completo y API HTTP en `/api/v1/gamification/badges` (incluye `PUT` para editar contenido) con `badges.view` extendido a `Student`; sistema de progresión de niveles (corresponde a ENG-053), historial completo de versiones, revocación y consulta de insignias de otro usuario diferidos explícitamente (IMP-052 en ENG-LOG.md)
1.31.0	2026-08-26	Cierre de ENG-053 (Experiencia y niveles), tercera historia de la Fase 10: ledger de solo-append `ExperienceEntry` en `Modules\Gamification` (puntos estrictamente positivos, competencia opcional en texto libre, motivo); nivel general y nivel por competencia derivados por el servicio de dominio puro `ExperienceLevelCalculator` en cada consulta (`nivel = floor(xp_total / 100) + 1`, mismo patrón que `PracticalResultCalculator`/`DecisionEngineCalculator`), sin persistir el nivel; registro manual vía `experience.manage` (sin autoservicio de registro, ledger inmutable) y autoservicio de consulta en `GET /experience/me` (mismo criterio que `/achievements/me`/`/badges/me`); integración automática con otros módulos, tabla de umbrales configurable y consulta del resumen de otro usuario diferidos explícitamente (IMP-053 en ENG-LOG.md)
1.32.0	2026-08-26	Cierre de ENG-054 (Retos y misiones) — última historia de la Fase 10: agregado `Challenge` (retos individuales/grupales y misiones educativas unificados bajo un enum cerrado `ChallengeType`, sin concepto de equipo/grupo propio) y entidad `ChallengeParticipation` con transición propia `Joined`→`Completed` (a diferencia de `UserAchievement`/`UserBadge`, no es un registro de solo-append inmutable); las fechas de vigencia restringen funcionalmente la unión (`Challenge::isWithinWindow()`); recompensa en texto libre sin vincularse a un logro/insignia real; todo el registro (unión y finalización) es manual vía `challenges.manage`, sin autoservicio; CQRS completo y API HTTP en `/api/v1/gamification/challenges` con `challenges.view` extendido a `Student` y autoservicio de consulta en `/challenges/me`; concepto de equipo/grupo, autoservicio de unión, otorgamiento automático de logros/insignias y consulta de participaciones de otro usuario diferidos explícitamente (IMP-054 en ENG-LOG.md). Con esto cierra por completo la Fase 10 — Gamificación (ENG-051 a ENG-054)
1.33.0	2026-08-26	Cierre de ENG-056 (Motor de notificaciones), primera historia de la Fase 11 (Comunicación y notificaciones): nuevo módulo `Modules\Notification` con el agregado `Notification` (canal `email`/`web`/`mobile`/`internal_message` como metadato, categoría en texto libre, transición propia `unread`→`read`); solo registro y seguimiento, sin integración real de entrega por canal (SMTP, proveedor push) ni disparo automático desde otros módulos; envío manual vía `notifications.manage`, autoservicio de consulta y de marcado como leída con verificación de pertenencia (`NotificationNotFound` anti-fuga, mismo criterio que `RoadPassport`/`SimulationSession`); CQRS completo y API HTTP en `/api/v1/notification/notifications`, sin permiso `.view` (autoservicio únicamente); entrega real, disparo automático, estado de entrega granular y catálogo cerrado de categorías diferidos explícitamente (IMP-056 en ENG-LOG.md)
1.34.0	2026-08-26	Cierre de ENG-057 (Preferencias de notificación), segunda historia de la Fase 11: agregado `NotificationPreference` en `Modules\Notification` (registro de configuración por usuario, no catálogo ni ledger) con `allowedChannels`/`mutedCategories` (todo permitido por defecto, silenciamiento explícito), `frequency` y horario de silencio almacenados sin aplicarse todavía, y consentimiento booleano simple otorgado por defecto; `SendNotificationHandler` ahora consulta la preferencia del destinatario y descarta silenciosamente el envío si no lo permite (`handle()` retorna `null`, la API responde `200 OK` con `data: null`); gestión 100% autoservicio sin permiso nuevo; aplicación real de frecuencia/horario de silencio, catálogo cerrado de categorías y versionado legal de consentimientos diferidos explícitamente (IMP-057 en ENG-LOG.md)
1.35.0	2026-08-26	Cierre de ENG-058 (Plantillas de comunicación) — última historia de la Fase 11: agregado `CommunicationTemplate` en `Modules\Notification`, independiente del envío de notificaciones (ENG-056/057 no se modificaron); versionado simple sin snapshots (mismo criterio que `Badge`); variables `{{variable}}` sustituidas por `str_replace`, declaradas como lista cerrada (`MissingTemplateVariable`, 422, si falta alguna al renderizar); idiomas modelados como fila por código+idioma, cada una versionada por separado; marca institucional como convención de variables reservadas, sin mecanismo nuevo; vista previa (`POST /templates/{id}/preview`) bajo `communication_templates.view`; `communication_templates.view` sin acceso de `Student` (herramienta administrativa/docente); integración con el envío, plantillas por organización, motor de plantillas real e historial de versiones diferidos explícitamente (IMP-058 en ENG-LOG.md). Con esto cierra por completo la Fase 11 — Comunicación y notificaciones (ENG-056 a ENG-058)
1.36.0	2026-08-27	Cierre de ENG-059 (Panel administrativo API), primera historia de la Fase 12 (Administración y operación): Cursos/Evaluaciones reutilizados sin cambios (ya maduros en `Modules\Academic`); `Modules\Identity` gana listar/ver detalle/activar/desactivar usuarios (reutilizando `activate()`/`deactivate()` ya existentes); `Modules\Organization` gana ver detalle y actualizar (renombrar); nuevo módulo `Modules\Admin` con `SystemSetting` (clave-valor), un resumen agregado de conteos que lee directamente los modelos Eloquent de otros módulos (excepción documentada al aislamiento entre módulos, solo para este reporte de lectura), salud agregada (solo conectividad a base de datos) y lectura de auditoría (`Modules\Audit` extendido con `AuditRepository::all()`, antes solo tenía `save()`); permisos nuevos `users.manage`/`users.view`/`reports.view` (SuperAdmin + InstitutionalAdmin) y `system_settings.manage`/`system_settings.view`/`system_operations.view` (únicamente SuperAdmin, mismo criterio que `roles.manage`); reseteo de contraseña administrativo, acciones masivas, motor de reportes configurable y salud real por módulo diferidos explícitamente (IMP-059 en ENG-LOG.md)
1.37.0	2026-08-27	Cierre de ENG-060 (Gestión de archivos), segunda historia de la Fase 12: nuevo módulo `Modules\FileStorage` (concepto de dominio propio, no una vista sobre `Modules\Admin`) con el agregado `StoredFile` (metadatos, estado de escaneo `pending`/`clean`/`infected` sin integración real con ningún motor antivirus); MinIO conectado de verdad vía `league/flysystem-aws-s3-v3` sobre el disco `s3` ya configurado (`S3FileStorage`), con `php artisan files:ensure-bucket` como paso de aprovisionamiento explícito e idempotente (verificado end-to-end contra el contenedor real); carga por el backend con límite de 20 MB por archivo, descarga vía URL temporal firmada (nunca reenviando bytes), cuota simple por usuario verificada antes de escribir en MinIO (lee `SystemSetting` `file_storage_quota_bytes` de `Modules\Admin`, con valor por defecto); eliminación real (no un estado "retirado", a diferencia de `Achievement`/`Badge`/`Challenge`) tanto en base de datos como en MinIO; patrón anti-fuga de pertenencia (`FileNotFound` uniforme) reutilizado para consulta/descarga/eliminación de un archivo ajeno; permisos nuevos `files.manage`/`files.view` (SuperAdmin + InstitutionalAdmin); integración real con un motor antivirus, carga directa con URL prefirmada, aplicación de política de bloqueo por estado de escaneo, cuotas por organización/rol y consulta administrativa de todos los archivos diferidos explícitamente (IMP-060 en ENG-LOG.md)
1.38.0	2026-08-27	Cierre de ENG-061 (Importaciones masivas): sin módulo nuevo — extiende `Modules\Identity` (usuarios/estudiantes unificados en un solo mecanismo, columna `role` opcional con `Student` por defecto, transacción por fila para no dejar usuarios huérfanos sin rol) y `Modules\Academic` (cursos; preguntas, resolviendo `competency_code` a id y usando celdas JSON para `response`/`options`/`media`/`license_categories` dado que una fila CSV es intrínsecamente plana); cada importador reutiliza directamente el handler de creación individual por fila (mismo criterio que `CreateBulkEnrollmentsHandler` ya existente), acumulando un reporte `total`/`created`/`failed`/`results[]` con éxito parcial y `error_code` por fila (`league/csv` para el parseo, límite de 500 filas por archivo); Grupos diferido por completo (no existe ningún concepto base en el backend); procesamiento síncrono sin colas, sin modo "solo validar" separado, sin soporte Excel/XLSX (IMP-061 en ENG-LOG.md)
1.39.0	2026-08-27	Cierre de ENG-062 (Exportaciones): sin módulo nuevo — tres exportadores concretos reutilizando las consultas de listado ya existentes (`Modules\Admin` para Auditoría, `Modules\Academic` para Cursos y Enrollments), en vez de un framework genérico; nueva infraestructura compartida en `Modules\Foundation` (`CsvWriter` sobre `league/csv`, `ExportFileWriter` que sube el CSV generado a `Modules\FileStorage` vía su contrato de bajo nivel — sin pasar por el agregado `StoredFile` ni su cuota, porque un archivo exportado no es un adjunto de usuario — y devuelve una URL temporal de 15 minutos, `ExportResponse` como DTO de respuesta genérico); cada exportación se registra en `Modules\Audit` (`export.audit_logs`/`export.courses`/`export.enrollments`, con conteo de filas); permiso nuevo y transversal `exports.view` (SuperAdmin + InstitutionalAdmin, mismo patrón que `reports.view`) protege los tres endpoints en vez de reutilizar el `.view` de cada recurso; procesamiento síncrono sin cola de trabajos (sería el primer `ShouldQueue` del backend); XLSX, PDF, exportaciones asíncronas, framework genérico y paginación/filtros sobre los datos exportados diferidos explícitamente (IMP-062 en ENG-LOG.md)
1.40.0	2026-08-27	Cierre de ENG-063 (Reportes académicos), primera historia de la Fase 13 (Reportes y analítica): cinco reportes calculados al vuelo sin persistencia (`GetCourseProgressReportQuery`/`GetCoursePerformanceReportQuery`/`GetCourseApprovalReportQuery`/`GetCourseCompetencyReportQuery`/`GetCourseActivityReportQuery`), cada uno aceptando una lista de `course_ids` — sin `course_ids` cubre todos los cursos; "comparación por grupo" se reinterpretó como comparación por curso, ya que "Grupo" no existe como concepto en el backend; nuevo `User::recordLogin()`/`lastLoginAt` en `Modules\Identity` (poblado por `LoginUserUseCase` en cada login exitoso, vía web o API) da base real al reporte de Actividad, que antes no tenía ningún dato; servicios compartidos nuevos en `Modules\Academic` (`CourseExamAttemptsLookup`, reutilizado por Rendimiento/Aprobación/Competencias para no traer los intentos de examen dos veces; `ReportCourseResolver` para la resolución común de `course_ids` → `Course`); reutiliza el permiso `reports.view` ya existente (SuperAdmin + InstitutionalAdmin), sin permiso nuevo; única historia de la sesión en la que el usuario rechazó explícitamente la propuesta de alcance reducido y pidió los seis puntos del roadmap completos; persistencia de reportes, filtros de fecha, un concepto real de "Grupo" y reportes por organización diferidos explícitamente (IMP-063 en ENG-LOG.md)
1.41.0	2026-08-28	Cierre de ENG-064 (Reportes de simulación), segunda historia de la Fase 13: cuatro reportes calculados al vuelo sin persistencia en `Modules\Simulation` (`GetUserSessionsReportQuery`/`GetUserTelemetryReportQuery`/`GetUserEvolutionReportQuery`/`GetUserRiskReportQuery`), cada uno aceptando una lista de `user_ids` — sin `user_ids` descubre todos los usuarios con sesiones existentes; "Errores frecuentes" e "Infracciones" se unificaron en un solo reporte de telemetría (`TelemetryEventType` ya distinguía Infracción como su propio caso del mismo enum); "Evolución" reutiliza `PracticalResultCalculator` por sesión para construir la secuencia cronológica; "Riesgos detectados" reutiliza `DecisionEngineCalculator` agregando reacciones inapropiadas por nivel de riesgo entre sesiones; `ReportUserIdsResolver` nuevo (análogo a `ReportCourseResolver` de ENG-063, pero sin validar existencia de usuario ya que no hay un agregado `User` que consultar desde este módulo); reutiliza `reports.view`, sin permiso nuevo; "Competencias prácticas" diferido por completo (hoy solo una cadena de texto libre por sesión, sin estructura real que agregar) y agregación por simulador diferida (requeriría un método de repositorio nuevo, hoy inexistente) (IMP-064 en ENG-LOG.md)
1.42.0	2026-08-28	Cierre de ENG-065 (Indicadores institucionales), tercera y última historia de la Fase 13: cuatro indicadores calculados al vuelo sin persistencia en `Modules\Academic` (`GetOrganizationParticipationReportQuery`/`GetOrganizationCompletionReportQuery`/`GetOrganizationPerformanceReportQuery`/`GetOrganizationAdoptionReportQuery`), cada uno aceptando una lista de `organization_ids` — sin `organization_ids` cubre todas las organizaciones; `ReportOrganizationResolver` nuevo (análogo a `ReportCourseResolver`/`ReportUserIdsResolver`, pero sí valida existencia vía `OrganizationRepository::findById()`, reutilizando `Organization\Application\Exceptions\OrganizationNotFound` tal cual desde `Modules\Organization` — dependencia entre módulos documentada, mismo criterio que el reporte de Actividad de ENG-063 dependiendo de `Identity\UserRepository`); Desempeño reutiliza `CourseExamAttemptsLookup` (ya construido en ENG-063) filtrando los intentos a solo los usuarios inscritos institucionalmente en esa organización para cada curso, para no contar intentos de estudiantes de otras instituciones o autoinscritos; Adopción es la primera serie temporal de la Fase 13 (inscripciones nuevas agrupadas por mes vía `enrolledAt`); Participación distingue inscripción de participación real (al menos una lección completada, no solo estar activa); reutiliza `reports.view`, sin permiso nuevo; Impacto (ningún vínculo organizacional real en Certification/RoadPassport/Gamification) y Uso por sede (`Campus` existe con id propio pero nada lo referencia — ni `Enrollment` ni `RoleAssignment` tienen `campusId`) diferidos explícitamente. Con esto cierra por completo la Fase 13 — Reportes y analítica (ENG-063 a ENG-065) (IMP-065 en ENG-LOG.md)
1.43.0	2026-08-28	Cierre de ENG-067 (Rate limiting), primera historia de la Fase 14 (Seguridad y cumplimiento): limitadores nombrados de Laravel (`RateLimiter::for()`) registrados en `Modules\Foundation`, aplicados vía middleware `throttle:<nombre>` — `login` (5/min por correo+IP, no solo IP), `register` (5/min por IP), `activate` (10/min por IP, incluye `POST /api/v1/auth/users/{userId}/activate` — endpoint público no identificado antes de investigar), `public-verification` (30/min por IP, verificación de certificados) y `simulator-integration` (60/min por simulador autenticado, no por IP, ya que varios simuladores pueden compartir NAT/IP); nuevo manejador dedicado para `ThrottleRequestsException` en `bootstrap/app.php` (código `TOO_MANY_REQUESTS`, mismo patrón que los demás manejadores de excepciones ya existentes); Recuperación de contraseña diferida por completo (la funcionalidad no existe en absoluto en el backend — `password_reset_tokens` es un artefacto sin usar del scaffold de Laravel); cambiar `CACHE_STORE` a Redis y límites configurables diferidos explícitamente (IMP-067 en ENG-LOG.md)
