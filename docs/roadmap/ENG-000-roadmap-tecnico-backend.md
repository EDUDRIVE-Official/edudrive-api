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

Estado: Pendiente

Incluye:

Borradores.
Revisión.
Aprobación.
Publicación.
Versiones.
Historial curricular.
12. Fase 6 — Evaluaciones
ENG-030 — Banco de preguntas

Estado: Pendiente

Incluye:

Preguntas de selección única.
Selección múltiple.
Verdadero o falso.
Asociación.
Ordenamiento.
Preguntas situacionales.
Recursos multimedia.
ENG-031 — Exámenes y cuestionarios

Estado: Pendiente

Incluye:

Plantillas.
Aleatorización.
Tiempo límite.
Intentos.
Reglas de aprobación.
Retroalimentación.
ENG-032 — Intentos de evaluación

Estado: Pendiente

Incluye:

Inicio.
Respuestas.
Guardado progresivo.
Finalización.
Resultado.
Estado del intento.
Prevención de duplicados.
ENG-033 — Motor de calificación

Estado: Pendiente

Incluye:

Puntaje.
Porcentajes.
Penalizaciones.
Competencias evaluadas.
Resultados parciales.
Reglas configurables.
ENG-034 — Examen teórico de conducción

Estado: Pendiente

Incluye:

Simulación de examen.
Categorías de licencia.
Reglas configurables.
Banco oficial autorizado.
Historial de intentos.
Recomendaciones de estudio.
13. Fase 7 — Progreso y Learning OS
ENG-035 — Inscripciones

Estado: Pendiente

Incluye:

Inscripción individual.
Inscripción masiva.
Asignación institucional.
Fechas de inicio y cierre.
Estados de matrícula.
ENG-036 — Seguimiento de progreso

Estado: Pendiente

Incluye:

Lecciones completadas.
Tiempo invertido.
Evaluaciones realizadas.
Porcentaje de avance.
Última actividad.
ENG-037 — Reglas de avance

Estado: Pendiente

Incluye:

Prerrequisitos.
Puntaje mínimo.
Actividades obligatorias.
Bloqueo y desbloqueo.
Rutas adaptativas.
ENG-038 — Learning Record Store interno

Estado: Pendiente

Incluye:

Eventos de aprendizaje.
Acciones del estudiante.
Evidencias.
Origen web, móvil o simulador.
Trazabilidad histórica.
ENG-039 — Recomendaciones de aprendizaje

Estado: Pendiente

Incluye:

Recomendación de lecciones.
Refuerzo de competencias.
Repetición de evaluaciones.
Recomendaciones según errores.
Preparación para SIMUDRIVE.
14. Fase 8 — Pasaporte Vial
ENG-040 — Núcleo del Pasaporte Vial

Estado: Pendiente

Incluye:

Identificador del Pasaporte Vial.
Propietario.
Estado.
Nivel.
Historial formativo.
Vigencia.
ENG-041 — Evidencias del Pasaporte Vial

Estado: Pendiente

Incluye:

Cursos.
Evaluaciones.
Prácticas.
Simulaciones.
Certificaciones.
Evidencias externas autorizadas.
ENG-042 — Competency Trust Model

Estado: Pendiente

Incluye:

Nivel de confianza.
Fuente de evidencia.
Recencia de la evidencia.
Consistencia.
Validez.
Reglas de degradación temporal.
ENG-043 — Credenciales y certificaciones

Estado: Pendiente

Incluye:

Certificados.
Credenciales verificables.
Códigos de validación.
Vigencia.
Revocación.
Historial.
ENG-044 — Consulta pública controlada

Estado: Pendiente

Incluye:

Verificación mediante código.
Datos mínimos.
Privacidad.
Vigencia.
Evidencia verificable.
15. Fase 9 — Integración con SIMUDRIVE
ENG-045 — Registro de simuladores

Estado: Pendiente

Incluye:

Simuladores autorizados.
Identificación del dispositivo.
Versión del software.
Ubicación.
Estado.
Llaves de integración.
ENG-046 — Sesiones de simulación

Estado: Pendiente

Incluye:

Usuario.
Simulador.
Vehículo.
Escenario.
Fecha.
Duración.
Estado de la sesión.
ENG-047 — Telemetría

Estado: Pendiente

Incluye:

Velocidad.
Frenado.
Aceleración.
Dirección.
Uso de señales.
Colisiones.
Infracciones.
Eventos críticos.
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

La historia técnica activa queda **Pendiente de decisión** entre ENG-029 o volver a Fase 4 — Perfiles.
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
