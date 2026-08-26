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

Estado: Pendiente

Nota (2026-07-29): los endpoints de login, /me, logout y logout-all funcionan y fueron validados manualmente, pero no existen todavía pruebas Feature automatizadas para ellos (solo hay un test de integración del repositorio de usuarios). Queda pendiente.

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

Estado: Pendiente

Incluye:

Resultado general.
Errores.
Penalizaciones.
Competencias demostradas.
Recomendaciones.
Evidencias asociadas.
ENG-049 — SIMUDRIVE Decision Engine

Estado: Pendiente

Incluye:

Evaluación de decisiones.
Contexto vial.
Riesgo.
Consistencia.
Respuesta del conductor.
Retroalimentación educativa.
ENG-050 — Sincronización offline

Estado: Pendiente

Incluye:

Sesiones sin conexión.
Cola local.
Identificadores idempotentes.
Sincronización posterior.
Resolución de conflictos.
16. Fase 10 — Gamificación
ENG-051 — Logros

Estado: Pendiente

Incluye:

Catálogo de logros.
Reglas de obtención.
Evidencias.
Estado.
Fecha de obtención.
ENG-052 — Insignias

Estado: Pendiente

Incluye:

Insignias educativas.
Insignias institucionales.
Insignias prácticas.
Niveles.
Versionado.
ENG-053 — Experiencia y niveles

Estado: Pendiente

Incluye:

Puntos de experiencia.
Nivel general.
Nivel por competencia.
Reglas de progresión.
Prevención de manipulación.
ENG-054 — Retos y misiones

Estado: Pendiente

Incluye:

Retos individuales.
Retos grupales.
Misiones educativas.
Fechas.
Recompensas.
Seguimiento.
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

Estado: Pendiente

Canales previstos:

Correo electrónico.
Notificación web.
Notificación móvil.
Mensajes internos.
ENG-057 — Preferencias de notificación

Estado: Pendiente

Incluye:

Canales permitidos.
Categorías.
Frecuencia.
Horarios.
Consentimientos.
ENG-058 — Plantillas de comunicación

Estado: Pendiente

Incluye:

Plantillas versionadas.
Variables.
Idiomas.
Marca institucional.
Vista previa.
18. Fase 12 — Administración y operación
ENG-059 — Panel administrativo API

Estado: Pendiente

Incluye:

Usuarios.
Organizaciones.
Cursos.
Evaluaciones.
Reportes.
Configuración.
Operación del sistema.
ENG-060 — Gestión de archivos

Estado: Pendiente

Incluye:

MinIO.
Carga segura.
Descarga autorizada.
Metadatos.
Antivirus.
URLs temporales.
Cuotas.
ENG-061 — Importaciones masivas

Estado: Pendiente

Incluye:

Usuarios.
Estudiantes.
Grupos.
Cursos.
Preguntas.
Validación previa.
Reporte de errores.
ENG-062 — Exportaciones

Estado: Pendiente

Incluye:

CSV.
XLSX.
PDF.
Exportaciones asíncronas.
Control de acceso.
Auditoría.
19. Fase 13 — Reportes y analítica
ENG-063 — Reportes académicos

Estado: Pendiente

Incluye:

Progreso.
Rendimiento.
Aprobación.
Competencias.
Actividad.
Comparación por grupo.
ENG-064 — Reportes de simulación

Estado: Pendiente

Incluye:

Sesiones.
Errores frecuentes.
Infracciones.
Evolución.
Competencias prácticas.
Riesgos detectados.
ENG-065 — Indicadores institucionales

Estado: Pendiente

Incluye:

Participación.
Finalización.
Desempeño.
Impacto.
Adopción.
Uso por sede.
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

Estado: Pendiente

Incluye:

Login.
Registro.
Recuperación de contraseña.
Integraciones.
Endpoints públicos.
ENG-068 — Auditoría general

Estado: Pendiente

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

Estado: Pendiente

Incluye:

Variables de entorno.
Rotación.
Llaves de integraciones.
Prohibición de secretos en Git.
Gestión por ambiente.
ENG-070 — Protección de datos personales

Estado: Pendiente

Incluye:

Minimización.
Retención.
Eliminación.
Anonimización.
Exportación de datos personales.
Consentimiento.
ENG-071 — Seguridad para menores de edad

Estado: Pendiente

Incluye:

Consentimiento parental.
Datos mínimos.
Restricción de exposición.
Protección de perfiles.
Controles institucionales.
ENG-072 — Idempotencia

Estado: Pendiente

Incluye:

Registro de simulaciones.
Pagos.
Inscripciones.
Sincronizaciones móviles.
Operaciones críticas.
21. Fase 15 — Integraciones
ENG-073 — API Keys para sistemas externos

Estado: Pendiente

Incluye:

Identificación del consumidor.
Alcances.
Revocación.
Expiración.
Rate limiting.
Auditoría.
ENG-074 — Webhooks

Estado: Pendiente

Incluye:

Eventos.
Firmas.
Reintentos.
Idempotencia.
Registro de entregas.
Dead-letter handling.
ENG-075 — Integración con aplicaciones móviles

Estado: Pendiente

Incluye:

Versionado.
Compatibilidad.
Sincronización.
Tokens por dispositivo.
Notificaciones móviles.
ENG-076 — Integraciones institucionales

Estado: Pendiente

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

Estado: Pendiente

Debe alinearse con la documentación SEC y ARC correspondiente.

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

Estado: Pendiente

Incluye:

Redis.
Correos.
Exportaciones.
Procesamiento de archivos.
Analítica.
Reintentos.
ENG-082 — Scheduler

Estado: Pendiente

Incluye:

Limpieza de tokens.
Notificaciones.
Reportes programados.
Expiración.
Mantenimiento.
ENG-083 — Observabilidad

Estado: Pendiente

Incluye:

Logs estructurados.
Métricas.
Trazas.
Correlation ID.
Alertas.
Dashboards.
ENG-084 — Backups y recuperación

Estado: Pendiente

Incluye:

PostgreSQL.
MinIO.
Configuración.
Restauración.
Pruebas de recuperación.
RPO y RTO.
ENG-085 — Despliegue y ambientes

Estado: Pendiente

Ambientes previstos:

Local.
Desarrollo.
QA.
Staging.
Producción.
ENG-086 — CI/CD

Estado: Pendiente

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
