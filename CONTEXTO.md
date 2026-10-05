# CONTEXTO — EDUDRIVE

Actualizado: **5 de octubre de 2026**, America/Costa_Rica. Edición documental 1.3. Producción descrita según los registros de entrega del 29 de septiembre; no se realizó un nuevo despliegue al actualizar este documento.

Este es el punto de entrada para entender y continuar el sistema. Resume el estado conocido y enlaza las fuentes; no sustituye el código, los registros de publicación ni la matriz curricular. Las fechas de comprobación importan: una función presente en el repositorio no demuestra que esté publicada, y una prueba técnica no demuestra eficacia pedagógica.

## Integrado en main, sin desplegar: recorridos curriculares

[PR #2](https://github.com/EDUDRIVE-Official/edudrive-api/pull/2), rama `codex/recorridos-matriz-20260930`, integrado en `main` el 4 de octubre mediante `0347b278cfb12b8bebfbacf2382fb38fea5af3cb`. CI de `main` (ejecución `37233856509`): Pint + Larastan, Pest y construcción/publicación de imagen aprobados. Antes del merge se corrigieron dos aserciones con tildes mal codificadas y un espacio de estilo detectado por Pint. Las pruebas de Academic e Identity se ejecutaron en local (1310 aprobadas y 2 fallos, ya corregidos); no se completó una suite local global. Perfil y catálogo muestran las cuatro etapas de la matriz y sus franjas internas. Propósito opcional 17+ persistido en `student_profiles.learning_purpose`: movilidad, automóvil o motocicleta. Migración aditiva pendiente de producción. Se conservan matrículas, historial y clasificación original de cursos. Cursos adultos sin correspondencia explícita de rol no se recomiendan automáticamente. El diagnóstico curricular y el motor completo de progresión por evidencia siguen pendientes; la pantalla lo indica. No se amplía el acceso restringido a DESCUBRO. **No está desplegada:** producción sigue en `editorial-approved-20260929r1`, sin la migración `learning_purpose` ni las etapas E1–E4. Pendientes técnicos conocidos: dos vocabularios de etapa conviven (`E1`–`E4` y `explore`…`teach`), las edades 3–4, 7–8 y 17+ no reciben recomendación automática de curso, y el propósito 17+ se valida con cadenas repetidas en lugar de un enum.

## Rediseño visual «Vial vibrante»: integrado en main, sin desplegar

Entre el 4 y el 5 de octubre se aplicó en tres fases la propuesta B de la exploración visual a la interfaz web, con una pull request por fase: [PR #4](https://github.com/EDUDRIVE-Official/edudrive-api/pull/4) (fase 1, `41b99cc`), [PR #5](https://github.com/EDUDRIVE-Official/edudrive-api/pull/5) (fase 2, `5b380d7`) y [PR #6](https://github.com/EDUDRIVE-Official/edudrive-api/pull/6) (fase 3, `a3c24fc`). El CI de `main` quedó en verde tras cada merge (ejecuciones `37247614387`, `37249861575` y `37252312887`). **No está desplegado**: producción sigue en `editorial-approved-20260929r1`.

- **Identidad:** primario negro asfalto `#14161A` con amarillo de seguridad `#FFC20E` como acento, verde `#00703C` y rojo `#C8102E` de señal; bordes de 3 px, sombras duras y divisores de cebra; sin degradados bajo texto. Tipografías **Barlow** y **Barlow Condensed autoalojadas** con `@fontsource` (dependencias nuevas de `package.json`; sin contacto con terceros en cada visita). Se conservan el tema claro y el oscuro.
- **Fase 1, cimientos:** tokens y estilos en `resources/css/app.css`, navegación con iconos SVG y pestaña activa en flecha, botones y campos de 48 px, y los componentes nuevos `ui/icon` y `ui/stage-plate` (placas E1–E4 con forma y etiqueta propias, nunca solo color).
- **Fase 2, pantallas principales:** ingreso, perfil con la jerarquía de la propuesta, catálogo con la ruta E1–E4 y un pictograma por curso (`ui/pictogram`), ficha del curso y cabecera de la lección, formulario de nuevo curso y resumen DESCUBRO. En las lecciones, los bloques 3D de decisión reciben su estilo por CSS global (opciones A/B/C como placas, icono de acierto o revisión en la respuesta), no un rediseño plantilla por plantilla.
- **Fase 3, resto de pantallas de estudiantes:** certificados, pasaporte, progreso, notificaciones, acompañamiento, consentimientos y la página 403. Se corrigió una regresión de la fase 1 (la regla de `label` estiraba casillas y `select`), se normalizaron tarjetas heredadas, se sustituyeron emojis decorativos por iconos y los colores de marca antiguos escritos a mano por tokens.
- **Alcance:** presentación y marcado de las vistas Blade y CSS (por ejemplo iconos SVG, atributos `autocomplete` en el ingreso y un `<main>` anidado corregido). No cambiaron rutas, permisos, textos funcionales, lógica PHP, migraciones ni los accesos restringidos (DESCUBRO sigue limitado a `super_admin`).
- **Evidencia:** pruebas locales aprobadas en cada fase sobre los módulos afectados (por ejemplo 1333 en `tests`, Academic e Identity tras las fases 1 y 2, y 1032 y 451 en los módulos de la fase 3); **no se ejecutó la suite completa en local**, solo en el CI. Mediciones en el navegador con cuentas de prueba (sobre todo E2; E4 en perfil y catálogo; no E1 ni E3): contraste de texto AA, objetivos táctiles menores de 44 px reducidos a uno (el enlace «Saltar al contenido», solo para lector de pantalla) y sin desbordes a 390 px. No hubo evaluación con personas usuarias.
- **Pendiente:** *fase 4* del plan (administración y páginas internas de piloto: solo heredan los tokens y quedan sin revisión visual completa; las reglas CSS globales de las fases 2 y 3 también les afectan). No se vieron `learning/activity` ni `certificates/show` por falta de cuentas de prueba con matrícula o certificado. Muchos cursos usan el pictograma genérico de ruta porque sus títulos no coinciden con ninguna palabra clave. El material de la exploración (`propuestas-diseno/`: diagnóstico, tres propuestas, comparador, plan de cambios y herramientas de medición) **no está versionado** y vive solo en la copia de trabajo.

## 1. Estado actual en una página

- Última entrega registrada: **`editorial-approved-20260929r1`**, posterior a `consolidacion-20260929r1`. App: [EDUDRIVE](https://app.edudrive.vr506.com/login). [Registro de publicación y recuperación](docs/engineering/PUBLICACION-CURSOS-2026-09-29.md).
- Publicados **Camino Seguro (27 lecciones)** y **Pasajero Responsable (12)**, versión 2, con aprobación declarada por el usuario. Cinco lecciones ampliadas; IDs, avances e historial conservados. Esto no constituye validación pedagógica con participantes.
- Cierre técnico del 29: **2605 pruebas PHP, 8585 aserciones y cero fallos** en SQLite aislado; PHPStan nivel 8 sin errores y Pint aprobado en los 63 PHP modificados. Comprobación focalizada PostgreSQL: **64 pruebas y 206 aserciones**. La integración editorial posterior pasó **192 pruebas y 825 aserciones**; no se repitió la suite general tras esa integración.
- Respaldo restaurado en PostgreSQL aislado y 86 fechas comprobadas antes de la publicación técnica. Los requisitos previos de respaldo/restauración quedaron cumplidos en esa entrega. Persisten las limitaciones de cobertura descritas en [el registro](docs/engineering/PUBLICACION-2026-09-29.md).
- DESCUBRO «Cruzar con acompañamiento» está disponible en producción **solo para cuentas activas con rol global `super_admin`**. Esa restricción corresponde a esta experiencia; no describe el acceso de toda la app.
- El recorrido tiene tres explicaciones, cuatro decisiones, retroalimentación, avance persistente, repaso, narración opcional, resumen de habilidades y guía para acompañantes.
- La matriz 3–80 vigente de trabajo es **2.0.0-borrador.1**: 60 fichas, 180 subcompetencias y 18 comparaciones THINK!. Sigue en revisión; faltan anclas específicas en 48 fichas.
- No se ha realizado el piloto curricular ni una aplicación con participantes en este trabajo. Fecha, sede y equipo de campo siguen sin definir.
- Hay código previo de Learning, Decision Engine y cálculo de confianza del Pasaporte. **No debe confundirse con la implementación completa del modelo curricular nuevo.** DESCUBRO mantiene su progreso separado de esas acreditaciones.
- **Git sincronizado el 30 de septiembre:** código pendiente registrado en `944100f`; Pest y Pint + Larastan aprobados en GitHub (ejecución `36732163263`). [PR #1](https://github.com/EDUDRIVE-Official/edudrive-api/pull/1) integrado en `main`, commit `6d4741522fda8adab82aa88160662e8bfc65e8b2`. Sin nuevo despliegue.
- **Git al 5 de octubre:** `main` en `a3c24fcd1699bd2afeea6e934db7c20cd73ac790`, con los PR #2 a #6 integrados (recorridos curriculares, actualización de este documento y las tres fases del rediseño). Todo está en `main`; nada de ello está desplegado.

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
| Interfaz | Blade, Tailwind 4, Alpine 3, Vite 7; Three.js para escenas; tipografías Barlow y Barlow Condensed autoalojadas (`@fontsource`) |
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
| 28 septiembre | Consolidación GitHub | Rama subida y PR #1 creado. Primeros fallos de CI históricos; no describen el cierre local del 29. |

| 29 septiembre | Consolidación y fechas | 2605 pruebas SQLite y 64 focalizadas PostgreSQL aprobadas; publicación técnica con respaldo restaurado. |
| 29 septiembre | Cursos versión 2 | Camino Seguro y Pasajero Responsable publicados; 192 pruebas focalizadas. |
| 30 septiembre | Contexto 1.1 | Estado documental reconciliado; sin nuevo despliegue. |
| 4 octubre | PR #2 en `main` | Recorridos curriculares y propósito 17+ integrados (`0347b27`); CI de `main` en verde e imagen publicada en GHCR; sin despliegue. |
| 4–5 octubre | Rediseño visual fase 1 (PR #4) | Tokens, tipografía, navegación y componentes base (`41b99cc`); CI de `main` en verde; sin despliegue. |
| 5 octubre | Rediseño visual fase 2 (PR #5) | Ingreso, perfil, catálogo, curso y lección (`5b380d7`); CI de `main` en verde; sin despliegue. |
| 5 octubre | Rediseño visual fase 3 (PR #6) | Resto de pantallas de estudiantes y página 403 (`a3c24fc`); CI de `main` en verde; sin despliegue. |

Los resultados pertenecen a cada entrega y no se suman como si fueran una suite nueva. No se ejecutó una auditoría funcional completa del sistema al redactar este documento.

Fuentes recientes:

- [Estado y antecedentes](docs/contexto/ESTADO_ACTUAL.md).
- [Publicación administrativa y respaldo](docs/contexto/DESCUBRO_PUBLICACION_ADMIN_20260927.md).
- [Comprobación del recorrido publicado](docs/contexto/QA_DESCUBRO_EN_LINEA_20260927.md).
- [Ajustes del Pasaporte](docs/contexto/DESCUBRO_AJUSTE_TEXTOS_20260927.md).
- [Guía acompañada publicada](docs/contexto/DESCUBRO_GUIA_ACOMPANADA_20260928.md).
- [Campus](docs/product/INTERFAZ-CAMPUS-2026-09-24.md), [cursos por páginas](docs/product/CURSOS-POR-PAGINAS-2026-09-24.md), [transición pedagógica](docs/product/TRANSICION-PEDAGOGICA-v0.2.md).

## 7. Producción, respaldo y reversión

Último estado registrado el 29 de septiembre; no constituye monitoreo continuo.

| Componente | Versión registrada |
| --- | --- |
| App / worker / scheduler | `edudrive-api:editorial-approved-20260929r1` |
| ID de app | `sha256:3398e960e160f19824a82673012041e47a7af8cbec57c380612e9d916b3dcb92` |
| nginx | `edudrive-nginx:editorial-approved-20260929r1` |
| ID de nginx | `sha256:af48146799d4fff4a845f706e57b0c7e6dea6eacf5b0f3b984c9d747b0d5283d` |
| Compose activo | `compose.prod.yaml` + `compose.bootstrap.yaml` + `compose.descubro-review.yaml` |
| Entrega | `/opt/edudrive/releases/editorial-approved-20260929r1` |
| Evidencias | `validation.json`, `publication.json`, `activated-at.txt` |

El código reside en las imágenes. La entrega técnica del 29 partió del árbol local; la editorial añadió un paquete incremental. GitHub aún no reproduce esas entregas. No hubo migraciones ni seeders en ambas publicaciones del 29.

El `before.dump` de la entrega editorial se restauró íntegramente en PostgreSQL aislado. SHA256: `c9a17f440f3f540efd23cddc98277323e0efea9e0a057bc645a3ef6acf22b019`. Se conservaron versiones 1 y 2 de los cursos y se compararon huellas del progreso antes/después.

**Recuperación:** la última entrega no encontró las imágenes anteriores `consolidacion-20260929r1` al intentar revertir. No asumir que `compose.before.yaml` basta para recuperar; verificar o reconstruir las imágenes primero. Evaluar escrituras posteriores antes de restaurar datos y mantener compatibilidad con los campos `supplement`. Consultar [el registro editorial](docs/engineering/PUBLICACION-CURSOS-2026-09-29.md). No repetir el script de publicación sobre los cursos ya actualizados.

Al cierre de esa entrega `/up` y `/login` respondían 200 y nginx estaba saludable. La revisión visual autenticada quedó pendiente por falta de sesión; las comprobaciones del kernel HTTP no se presentan como navegación visual.

No ejecutar indiscriminadamente el despliegue genérico ni todas las migraciones pendientes: el flujo reciente necesita el override de revisión. No ejecutar seeders para actualizar cursos persistidos; pueden reemplazar contenido o reabrirlo. Fuente general: [despliegue VPS](docs/operaciones/despliegue-vps.md), [ambientes](docs/operaciones/ambientes.md), [CI/CD](docs/operaciones/ci-cd.md), [respaldos](docs/operaciones/backups-rpo-rto.md). Los registros recientes describen las excepciones actuales a esos runbooks.

**Próximo despliegue:** llevará juntos el rediseño visual (PR #4 a #6), las etapas E1–E4 y la migración aditiva `learning_purpose` del PR #2. El Dockerfile ejecuta `npm ci` y `npm run build`, de modo que las fuentes autoalojadas se instalan al construir la imagen. Antes de desplegar: aplicar la migración con respaldo previo, usar el override de revisión, verificar las imágenes de recuperación y repasar visualmente en un entorno de revisión las pantallas de administración (fase 4 pendiente).

## 8. Estado de Git y documentación heredada

El código pendiente del 29 quedó registrado en `944100f`. GitHub aprobó Pest y Pint + Larastan sobre ese commit; CI compila ahora los recursos Vite antes de Pest. El PR #1 quedó integrado en `main` mediante `6d4741522fda8adab82aa88160662e8bfc65e8b2` el 30 de septiembre, usando la cuenta EDUDRIVE-Official.

La copia de trabajo se sincronizó con ese merge sobre `codex/consolidacion-20260928`. La antigua rama local llamada `main` tiene historia divergente y se conservó intacta; no confundirla con `origin/main`. Las referencias de entrega mantienen sus fechas originales. El código publicado procede del árbol y paquetes del 29; este merge registra sus fuentes y no afirma que la imagen activa se haya reconstruido desde el merge.

El workflow de main construye/publica una imagen después de sus controles; no despliega automáticamente al servidor. La versión operativa registrada sigue siendo `editorial-approved-20260929r1`. No hay cambios de aplicación pendientes de commit en este cierre. Tras los PR #2 a #6, `main` está en `a3c24fcd1699bd2afeea6e934db7c20cd73ac790`; la imagen publicada por el workflow de `main` no equivale a un despliegue.

[SESION.md](docs/engineering/SESION.md) contiene un estado histórico del 16 de agosto. [ENG-LOG](docs/engineering/ENG-LOG.md) conserva cierres anteriores y [roadmap](docs/roadmap/ENG-000-roadmap-tecnico-backend.md) combina planificación e incrementos posteriores. No tomar sus «pendiente» o «completado» aislados como prueba del estado actual; contrastar fecha, código, pruebas y entrega. Las instrucciones históricas sobre herramientas o próximos pasos no sustituyen el alcance acordado actualmente.

## 9. Pendientes y orden de continuidad propuesto

1. **Consolidación técnica cerrada:** código registrado, CI del PR aprobado e integración en main completada. En el próximo despliegue, registrar la imagen por commit e incluir el override activo; verificar previamente las imágenes de recuperación.
2. **Cierre curricular:** completar anclas en las 48 fichas restantes y alineación de subcompetencias, actividad, evidencia y evaluación. Cerrar verificación normativa, manuales, recursos y escenarios. La matriz sigue siendo borrador.
3. **Derivados:** reeditar instrumentos del piloto, retención e identificadores para que correspondan a la misma edición. PIL-01 y P912 son materiales distintos; no mezclar sus resultados ni criterios.
4. **Modelo y software:** revisar la distancia entre Learning OS/CTM/Decision Engine propuestos y el código existente; diseñar contratos y pruebas por competencia antes de conectar nuevas acreditaciones.
5. **Validación y público:** organizar revisión especializada y aplicación posterior cuando los materiales estén cerrados; definir fecha, sede y equipo. La apertura a estudiantes es una decisión pendiente, no consecuencia automática de publicar una pantalla.
6. **Interfaz:** completar la fase 4 del rediseño (administración y páginas internas de piloto), revisar con cuentas que tengan matrícula y certificado las pantallas que no se pudieron ver, y decidir si el material de `propuestas-diseno/` se versiona. Ninguna prueba de interfaz sustituye una evaluación con personas usuarias de distintas edades.

Limitaciones conocidas: comprobación de voz limitada al equipo revisado; pruebas visuales parciales; restauración de PostgreSQL comprobada, sin ensayo integral de recuperación de objetos/roles/ACL; no hay evaluación pedagógica con participantes; no existe en este documento una auditoría exhaustiva de todos los módulos o configuraciones de producción.

## 10. Cómo mantener este contexto

Actualizar este archivo al cerrar un cambio significativo: fecha, alcance, código local frente a publicación, versión/imagen, pruebas, limitaciones, pendientes y enlaces. Conservar el historial detallado en el registro de cada entrega. Si cambia la arquitectura curricular, registrar su versión explícitamente.

Mantener un solo documento principal en la raíz del repositorio. El `CONTEXTO.md` del espacio documental es un enlace de entrada, no una segunda copia. Al retomar, leer este archivo, el último registro de entrega y comprobar el estado real antes de modificar. No tratar un plan, una captura de prototipo o un registro histórico como evidencia de publicación o de dominio.
