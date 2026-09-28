# CONTEXTO — EDUDRIVE

Actualizado: **28 de septiembre de 2026**, zona horaria America/Costa_Rica. Edición documental 1.0.

Este es el punto de entrada para entender y continuar el sistema. Resume el estado conocido y enlaza las fuentes; no sustituye el código, los registros de publicación ni la matriz curricular. Las fechas de comprobación importan: una función presente en el repositorio no demuestra que esté publicada, y una prueba técnica no demuestra eficacia pedagógica.

## 1. Estado actual en una página

- App existente: [app.edudrive.vr506.com](https://app.edudrive.vr506.com/login). El proyecto combina administración educativa, cursos, seguimiento, Pasaporte Vial y componentes de simulación.
- Última entrega comprobada en este trabajo: **`descubro-review-20260928r1`**, publicada el 28 de septiembre. Amplía la guía acompañada de DESCUBRO y conserva los ajustes anteriores del Pasaporte.
- DESCUBRO «Cruzar con acompañamiento» está disponible en producción **solo para cuentas activas con rol global `super_admin`**. Esa restricción corresponde a esta experiencia; no describe el acceso de toda la app.
- El recorrido tiene tres explicaciones, cuatro decisiones, retroalimentación, avance persistente, repaso, narración opcional, resumen de habilidades y guía para acompañantes.
- La matriz 3–80 vigente de trabajo es **2.0.0-borrador.1**: 60 fichas, 180 subcompetencias y 18 comparaciones THINK!. Sigue en revisión; faltan anclas específicas en 48 fichas.
- No se ha realizado el piloto curricular ni una aplicación con participantes en este trabajo. Fecha, sede y equipo de campo siguen sin definir.
- Hay código previo de Learning, Decision Engine y cálculo de confianza del Pasaporte. **No debe confundirse con la implementación completa del modelo curricular nuevo.** DESCUBRO mantiene su progreso separado de esas acreditaciones.
- El trabajo acumulado se registró localmente en la rama `codex/consolidacion-20260928`; la subida está pendiente por falta de permiso de escritura de la cuenta Git. La suite general todavía requiere resolver hallazgos antes de integrar en `main`. Ver [informe de consolidación](docs/engineering/CONSOLIDACION-2026-09-28.md).

## 2. Dónde vive cada cosa

| Recurso | Ubicación y función |
| --- | --- |
| Repositorio de aplicación | `D:/vr506/EDUDRIVE/edudrive-api` |
| Este documento principal | `D:/vr506/EDUDRIVE/edudrive-api/CONTEXTO.md` |
| Espacio documental de esta conversación | `C:/Users/AbelCamposPaniagua/.codex/.chatgpt-projects/g-p-68c4befaf0548191b9821b0276aaa74d` |
| Entregables curriculares y registros recientes | Subdirectorio `output/edudrive` del espacio documental |
| Referencias sincronizadas | `sources/` del espacio documental; solo lectura, pueden ser reemplazadas por la sincronización |
| Desarrollo local | `http://localhost:8080`; requiere servicios locales activos |
| Producción | `https://app.edudrive.vr506.com` |
| Servidor documentado | VPS Contabo, directorio `/opt/edudrive`, proyecto Compose `edudrive-prod` |
| Entregas recientes del servidor | `/opt/edudrive/releases/` |

Los documentos curriculares y registros recientes de esta consolidación se incluyen en `docs/contexto/` para que sus fuentes principales viajen con Git. Los artefactos binarios auxiliares y respaldos operativos permanecen en sus ubicaciones originales. No incluir credenciales ni archivos `.env` en este contexto.

## 3. Arquitectura y tecnologías

Aplicación Laravel organizada como monolito modular. El estándar separa `Domain`, `Application`, `Infrastructure`, `Presentation` y `Tests`; usa contratos de repositorios, casos de uso y persistencia Eloquent. El panel web usa Blade y recursos compilados con Vite. La API usa Sanctum; el panel tiene autenticación de sesión.

| Capa | Evidencia local revisada |
| --- | --- |
| Backend | Laravel `^12.0`; Composer admite PHP `^8.2`; Dockerfile usa PHP 8.4 FPM |
| Interfaz | Blade, Tailwind 4, Alpine 3, Vite 7; dependencia Three.js para escenas |
| Datos y servicios | Compose de producción declara PostgreSQL 17, Redis 7, MinIO, nginx, app, worker y scheduler |
| Pruebas y calidad | Pest 3 / PHPUnit 11, Pint, Larastan/PHPStan; comandos en `composer.json` |
| Recursos y ejecución | Dockerfile con etapas de assets Node 20, nginx y app; código de producción incorporado a la imagen |

Referencias: [estándar modular](docs/engineering/ENG-003-estandar-modulos-backend.md), [Composer](composer.json), [frontend](package.json), [Dockerfile](docker/php/Dockerfile), [proveedores registrados](bootstrap/providers.php).

### Mapa de módulos

Los siguientes 22 módulos están presentes y sus proveedores aparecen registrados en el código local. Este inventario describe responsabilidad y presencia, no certifica cada flujo en producción.

| Módulo | Responsabilidad |
| --- | --- |
| Foundation | Contratos y elementos comunes de dominio e infraestructura |
| Identity | Usuarios, perfiles, autenticación y relaciones tutor-menor |
| Authorization | Roles, permisos y asignaciones con alcance |
| Organization | Organizaciones, estructura y contexto institucional |
| Academic | Cursos, currículo, competencias, evaluaciones, matrículas y progreso; experiencia DESCUBRO |
| Learning | Registro y consulta de eventos de aprendizaje |
| RoadPassport | Pasaporte, evidencias, consulta y cálculo de confianza |
| Certification | Credenciales y certificados |
| Simulation | Simuladores, sesiones, telemetría, resultados y puntos de decisión |
| Gamification | Logros, insignias, experiencia y retos |
| Notification | Notificaciones, preferencias y plantillas |
| Admin | Vistas y operaciones administrativas |
| Analytics | Reportes e indicadores |
| FileStorage | Gestión de archivos |
| Integration | Integraciones externas y procesamiento de intercambios |
| Webhook | Webhooks |
| Mobile | Integración para clientes móviles; no prueba que exista una app nativa publicada |
| Legal | Políticas y consentimientos |
| Audit | Auditoría |
| AiGovernance | Gobierno de funciones de IA |
| AsyncProcessing | Procesamiento asíncrono |
| Backup | Funciones relacionadas con respaldo |

Roles definidos: `super_admin`, `institutional_admin`, `teacher`, `student`. La relación tutor-menor se representa en Identity; no existe un rol adicional «tutor» en el enum revisado. Fuente: [Role](modules/Authorization/Domain/Enums/Role.php), [diseño de tutores](docs/plans/2026-08-30-tutores-consentimientos-eng022-eng023-design.md).

## 4. Arquitectura curricular y relación con el software

| Etapa | Edad | Orientación |
| --- | --- | --- |
| DESCUBRO | 3–6 | Reconocer el entorno y actuar con acompañamiento |
| COMPRENDO | 7–12 | Reconocer riesgos y construir autonomía progresiva |
| DECIDO | 13–16 | Anticipar consecuencias y tomar decisiones responsables |
| CONDUZCO | 17+ | Formación de conductores/motociclistas y aprendizaje permanente |

La filosofía **3–80** expresa aprendizaje durante la vida. El grupo 17+ es una etapa curricular, no una afirmación de habilitación legal para una licencia. THINK! 3–6, 7–12 y 13–16 funciona como referencia comparativa; los contenidos se adaptan a Costa Rica. Las verificaciones normativas pendientes deben cerrarse antes de presentar el material como validado.

La progresión se organiza por competencia y subcompetencia, contexto, ayudas, evidencia, criterio versionado y observación; completar un curso no equivale automáticamente a demostrar dominio.

| Componente | Diseño curricular y realidad observada |
| --- | --- |
| Learning OS | Arquitectura propuesta para organizar recorrido, evidencia y siguientes pasos. Academic y Learning aportan bases existentes; no se ha demostrado una integración completa de la matriz 3–80. |
| Decision Engine SIMUDRIVE | Hay calculador, puntos de decisión, handlers, rutas y pruebas en Simulation. El calculador local clasifica reacciones por nivel de riesgo; su mera existencia no demuestra adecuación contextual de todos los escenarios curriculares. |
| Competency Trust Model | La matriz propone juicio por competencia, alcance y evidencia. El cálculo local del Pasaporte usa tipos de evidencia, antigüedad, calidad en práctica observada y cantidad, produciendo un puntaje hasta 100. Ese puntaje no sustituye la demostración de cada subcompetencia ni la diversidad de contextos. |
| Pasaporte Vial | Existe como registro de evidencias y presentación al usuario. El resumen interno de DESCUBRO aparece aparte: no emite evidencias, no cambia nivel y no acredita dominio. |

Fuentes de código: [DecisionEngineCalculator](modules/Simulation/Domain/Services/DecisionEngineCalculator.php), [RoadPassportTrustCalculator](modules/RoadPassport/Domain/Services/RoadPassportTrustCalculator.php), [rutas de Learning](modules/Learning/Presentation/Routes/api.php).

Fuente curricular de trabajo: [matriz integrada 2.0.0-borrador.1](docs/contexto/edicion-2.0.0-borrador.1/EDF_Matriz_Curricular_EDUDRIVE_3-80_v2.0.0-borrador.1.md). Su JSON paralelo contiene la misma edición. La versión 1.0.0 es antecedente histórico. Los materiales P912 y pasajeros del repositorio pertenecen a otros incrementos y no deben equipararse automáticamente a esta edición.

## 5. DESCUBRO: comportamiento publicado

Entrada: [Cruzar con acompañamiento](https://app.edudrive.vr506.com/descubro/cruzar-acompanado). Competencia de referencia: `EDU-PED-001.E1`, subcompetencias S1–S3, criterio `2.0.0-borrador.1`.

1. Enseñanza: reconocer acera/calzada, detenerse/comprobar y cruzar junto a la persona adulta.
2. Práctica: cuatro decisiones con respuesta, explicación y reintento. La secuencia guardada determina el avance.
3. Resultado y resumen: «Por practicar», «En práctica» y «Practicado en pantalla». S2 requiere sus dos decisiones. Una vuelta completada se conserva al repetir.
4. Repaso: `?page=review&lesson=0`, `1` o `2`; consultar explicaciones no reinicia la práctica.
5. Acompañantes: `?page=adult`; guía de circuito `?page=circuit`, ampliada el 28 de septiembre.
6. Narración: escuchar, repetir y detener mediante voces españolas disponibles en el navegador/equipo. No hay reproducción automática; funciona sin narración. Se confirmó voz audible en el equipo del usuario, no en todos los dispositivos.

Persistencia: tabla `academic_descubro_progress`, por usuario/experiencia/versión; guarda fase, explicación, pregunta, retroalimentación, elección, intentos, finalización y revisión. La revisión evita sobrescrituras desde pestañas antiguas. El servicio de reinicio administrativo integra ese progreso con el archivo/reinicio del historial. No ejecutar un reinicio como parte de una comprobación de lectura.

Acceso: local/testing habilitado; staging requiere `DESCUBRO_STAGING_ENABLED`; producción requiere `DESCUBRO_PRODUCTION_REVIEW_ENABLED=true`, cuenta activa y asignación global SuperAdmin. Ambos interruptores son falsos por defecto. En producción se conserva `APP_ENV=production` y `APP_DEBUG=false`.

La revisión en la cuenta administrativa dejó una práctica completada, cuatro elecciones correctas y una incorrecta seguida de reintento. Es un registro de comprobación, no un resultado de participante. El Pasaporte de esa cuenta se observó pendiente de emisión.

Archivos clave: [controlador](modules/Academic/Presentation/Http/Controllers/DescubroCrossingController.php), [persistencia](modules/Academic/Infrastructure/Services/DescubroPracticeProgress.php), [resumen](modules/Academic/Presentation/ViewModels/DescubroPracticeSummary.php), [vista](resources/views/descubro/crossing.blade.php), [lector](public/js/descubro-read-aloud.js), [configuración](config/descubro.php).

## 6. Últimos cambios y evidencia

| Fecha | Entrega | Estado comprobado |
| --- | --- | --- |
| 24 septiembre | Campus y cursos por páginas | Documentados en el repositorio; no se revalidaron íntegramente en esta consolidación. |
| 25–27 septiembre | DESCUBRO local, persistencia, habilidades, narración y repaso | Integrados y probados en incrementos; registros en ESTADO_ACTUAL. |
| 27 septiembre | `descubro-review-20260927r1` | Publicación restringida, migración específica y respaldo. Pruebas de entrega: 10/149 aserciones; recorrido en línea revisado posteriormente. |
| 27 septiembre | `descubro-review-20260927r2` | Pasaporte pendiente de emisión y eliminación del rótulo duplicado. Cuatro pruebas/69 aserciones y revisión visual. |
| 28 septiembre | `descubro-review-20260928r1` | Guía acompañada ampliada. Cuatro pruebas/81 aserciones, compilación de vistas, salud y navegación en línea comprobadas. |
| 28 septiembre | Este CONTEXTO | Consolidación documental y enlaces a fuentes versionadas. |
| 28 septiembre | Consolidación GitHub | Rama local con trabajo acumulado y correcciones de calidad; pendiente permiso de escritura y revisión de la suite general. Sin despliegue. |

Los resultados pertenecen a cada entrega y no se suman como si fueran una suite nueva. No se ejecutó una auditoría funcional completa del sistema al redactar este documento.

Fuentes recientes:

- [Estado y antecedentes](docs/contexto/ESTADO_ACTUAL.md).
- [Publicación administrativa y respaldo](docs/contexto/DESCUBRO_PUBLICACION_ADMIN_20260927.md).
- [Comprobación del recorrido publicado](docs/contexto/QA_DESCUBRO_EN_LINEA_20260927.md).
- [Ajustes del Pasaporte](docs/contexto/DESCUBRO_AJUSTE_TEXTOS_20260927.md).
- [Guía acompañada publicada](docs/contexto/DESCUBRO_GUIA_ACOMPANADA_20260928.md).
- [Campus](docs/product/INTERFAZ-CAMPUS-2026-09-24.md), [cursos por páginas](docs/product/CURSOS-POR-PAGINAS-2026-09-24.md), [transición pedagógica](docs/product/TRANSICION-PEDAGOGICA-v0.2.md).

## 7. Producción, respaldo y reversión

Estado verificado durante la publicación del 28 de septiembre; no constituye monitoreo continuo.

| Componente | Versión registrada |
| --- | --- |
| App / worker / scheduler | `edudrive-api:descubro-review-20260928r1` |
| ID de imagen de app | `sha256:af548196f23e7da6a998a2dcb5a433c7ebe261be315d67673576a566f9239399` |
| nginx | `edudrive-nginx:descubro-review-20260927r1` |
| Compose activo | `compose.prod.yaml` + `compose.bootstrap.yaml` + **`compose.descubro-review.yaml`** |
| Entrega actual | `/opt/edudrive/releases/descubro-review-20260928r1` |
| Registro de éxito | `deployment-complete.json` en esa entrega |

El código está dentro de las imágenes; editar el repositorio local no publica cambios. El volumen de app monta `storage`, no el código fuente. Las entregas recientes añadieron archivos concretos sobre imágenes conocidas; no se publicó todo el árbol local pendiente de consolidación.

Respaldo de datos previo a la publicación inicial: `/opt/edudrive/releases/descubro-review-20260927r1/before-descubro.dump`, conservado en el servidor. Se comprobó su listado mediante `pg_restore --list`; no se realizó un ensayo de restauración. Solo se aplicó la migración `2026_09_25_000001_create_academic_descubro_progress_table.php`. Los ajustes r2 y del día 28 no requirieron migraciones.

La última entrega conserva `compose.before.yaml` y las imágenes anteriores. Para una reversión operativa, comprobar primero la versión activa, restaurar el override anterior, recrear app/worker/scheduler/nginx y regenerar cachés. Esa reversión de imágenes no restaura datos; no se ejecutó durante la entrega. Ver los registros enlazados antes de actuar.

No ejecutar indiscriminadamente el despliegue genérico ni todas las migraciones pendientes: el flujo reciente necesita el override de revisión. No ejecutar seeders para actualizar cursos persistidos; pueden reemplazar contenido o reabrirlo. Fuente general: [despliegue VPS](docs/operaciones/despliegue-vps.md), [ambientes](docs/operaciones/ambientes.md), [CI/CD](docs/operaciones/ci-cd.md), [respaldos](docs/operaciones/backups-rpo-rto.md). Los registros recientes describen las excepciones actuales a esos runbooks.

## 8. Estado de Git y documentación heredada

Base remota comprobada el 28 de septiembre: `main` en `a6803b7`, `feat(deploy): add single-VPS production deployment (Docker Compose + scripted runbook)`. El trabajo local acumulado se registró después en `codex/consolidacion-20260928`, con primer commit `f40041d`. GitHub rechazó la simulación de subida con HTTP 403 para `AbelCampos2025`; no hay rama remota ni PR de esta consolidación todavía. **La rama de consolidación contiene también trabajo que no se verificó como publicado; no representa una reproducción exacta de las imágenes activas.**

Preservar el trabajo existente, revisar archivos concretos y comprobar el índice antes de un commit. No usar `git add .`, `git add -A`, reset masivo ni regenerar todos los contenidos para consolidar esta entrega. La preparación local resuelve el registro del trabajo; siguen pendientes el acceso de escritura, la publicación de la rama y la revisión de los fallos de la suite general.

[SESION.md](docs/engineering/SESION.md) contiene un estado histórico del 16 de agosto. [ENG-LOG](docs/engineering/ENG-LOG.md) conserva cierres anteriores y [roadmap](docs/roadmap/ENG-000-roadmap-tecnico-backend.md) combina planificación e incrementos posteriores. No tomar sus «pendiente» o «completado» aislados como prueba del estado actual; contrastar fecha, código, pruebas y entrega. Las instrucciones históricas sobre herramientas o próximos pasos no sustituyen el alcance acordado actualmente.

## 9. Pendientes y orden de continuidad propuesto

1. **Consolidación técnica:** reconciliar cambios locales con las imágenes publicadas y preparar una revisión reproducible en Git; registrar pruebas y actualizar el proceso de despliegue para incluir el override activo. El registro local y las fuentes documentales ya se prepararon; la publicación en GitHub y la integración validada siguen pendientes.
2. **Cierre curricular:** completar anclas en las 48 fichas restantes y alineación de subcompetencias, actividad, evidencia y evaluación. Cerrar verificación normativa, manuales, recursos y escenarios. La matriz sigue siendo borrador.
3. **Derivados:** reeditar instrumentos del piloto, retención e identificadores para que correspondan a la misma edición. PIL-01 y P912 son materiales distintos; no mezclar sus resultados ni criterios.
4. **Modelo y software:** revisar la distancia entre Learning OS/CTM/Decision Engine propuestos y el código existente; diseñar contratos y pruebas por competencia antes de conectar nuevas acreditaciones.
5. **Validación y público:** organizar revisión especializada y aplicación posterior cuando los materiales estén cerrados; definir fecha, sede y equipo. La apertura a estudiantes es una decisión pendiente, no consecuencia automática de publicar una pantalla.

Limitaciones conocidas: comprobación de voz limitada al equipo revisado; pruebas visuales parciales; falta ensayo de restauración; no hay evaluación pedagógica con participantes; no existe en este documento una auditoría exhaustiva de todos los módulos o configuraciones de producción.

## 10. Cómo mantener este contexto

Actualizar este archivo al cerrar un cambio significativo: fecha, alcance, código local frente a publicación, versión/imagen, pruebas, limitaciones, pendientes y enlaces. Conservar el historial detallado en el registro de cada entrega. Si cambia la arquitectura curricular, registrar su versión explícitamente.

Mantener un solo documento principal en la raíz del repositorio. El `CONTEXTO.md` del espacio documental es un enlace de entrada, no una segunda copia. Al retomar, leer este archivo, el último registro de entrega y comprobar el estado real antes de modificar. No tratar un plan, una captura de prototipo o un registro histórico como evidencia de publicación o de dominio.
