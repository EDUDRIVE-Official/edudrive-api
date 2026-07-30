# ENG-000 — Roadmap Técnico del Backend EDUDRIVE

## 1. Información del documento

| Campo | Valor |
|---|---|
| Código | ENG-000 |
| Nombre | Roadmap Técnico del Backend EDUDRIVE |
| Proyecto | EDUDRIVE |
| Componente | edudrive-api |
| Estado | Activo |
| Versión | 1.1.0 |
| Fecha | 2026-07-29 |
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

Estado: Completado

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

Estado: Pendiente

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

Estado: Pendiente

Incluye:

Catálogo de permisos.
Asignación de permisos a roles.
Verificación mediante middleware.
Políticas de acceso.
Pruebas de autorización.
ENG-014 — Contexto organizacional

Estado: Pendiente

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

Estado: Pendiente

Tipos previstos:

Centros educativos.
Escuelas de manejo.
Empresas.
Instituciones públicas.
Universidades.
Asociaciones.
Operadores EDUDRIVE.
ENG-017 — Sedes

Estado: Pendiente

Incluye:

Sedes por organización.
Información de contacto.
Ubicación.
Estado operativo.
Configuración regional.
ENG-018 — Membresías organizacionales

Estado: Pendiente

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

Estado: Pendiente

Incluye:

Competencias viales.
Subcompetencias.
Indicadores.
Niveles de dominio.
Relación con teoría y práctica.
ENG-025 — Programas educativos

Estado: Pendiente

Incluye:

Programas por edad.
Programas por licencia.
Programas institucionales.
Programas corporativos.
Programas para motocicleta y automóvil.
ENG-026 — Cursos

Estado: Pendiente

Incluye:

Datos generales.
Objetivos.
Requisitos.
Duración.
Modalidad.
Estado de publicación.
Versionado.
ENG-027 — Módulos y unidades

Estado: Pendiente

Incluye:

Organización jerárquica.
Orden.
Dependencias.
Requisitos de avance.
Contenido asociado.
ENG-028 — Lecciones

Estado: Pendiente

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