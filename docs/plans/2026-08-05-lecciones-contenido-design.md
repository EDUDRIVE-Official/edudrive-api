# ENG-028 — Lecciones y contenido accesible — Diseño

**Fecha:** 2026-08-05  
**Estado:** Aprobado  
**Ámbito:** Contenido educativo regional asociado a unidades de curso

## Objetivo

Agregar contenido didáctico estructurado a las unidades curriculares creadas en ENG-027:

```text
Curso → Módulos → Unidades → Lecciones ordenadas → Bloques ordenados
```

ENG-028 incorpora texto, imágenes, video, audio, recursos interactivos y descargables mediante referencias externas. También establece requisitos mínimos de accesibilidad y hace que la publicación de un curso nuevo dependa de que todas sus unidades tengan contenido completo.

El backend no subirá, descargará, transformará ni almacenará archivos binarios.

## Decisiones principales

- Cada unidad puede tener un único agregado `UnitContent`.
- `UnitContent` contiene lecciones ordenadas; cada lección contiene bloques tipados y ordenados.
- Lecciones y bloques pertenecen exclusivamente a una unidad y no se reutilizan entre cursos.
- El contenido completo de una unidad se consulta y reemplaza de forma atómica.
- Solo un curso en estado `draft` permite reemplazar contenido.
- Publicar un curso nuevo exige al menos una lección por unidad y al menos un bloque válido por lección.
- Los metadatos de accesibilidad son obligatorios según el tipo de bloque.
- Los recursos externos deben usar HTTPS. No habrá una lista de proveedores en este incremento.
- Los interactivos se exponen como enlaces; el backend no autoriza su inclusión automática mediante `iframe`.
- Los cursos publicados antes de ENG-028 pueden seguir consultándose sin lecciones para conservar compatibilidad.

## Arquitectura y límites de agregados

`UnitContent` será un agregado separado dentro del módulo `Academic`, identificado por el UUID global de `CourseUnit`.

```text
CourseUnit
└── UnitContent
    ├── Lesson 1
    │   ├── ContentBlock 1
    │   └── ContentBlock 2
    └── Lesson 2
        └── ContentBlock 1
```

No se agregará el árbol completo de lecciones y bloques al agregado `Course`. Esto evita que consultar o modificar una sola unidad obligue a reconstruir todo el contenido del curso y mantiene acotados los payloads y la persistencia.

`Course` continúa siendo la autoridad del ciclo de vida. La coordinación entre ambos agregados se realiza bajo el bloqueo transaccional de la fila del curso:

1. El reemplazo de contenido bloquea el curso.
2. Confirma que el curso permanece en `draft`.
3. Confirma que la unidad pertenece a ese curso.
4. Valida completamente el `UnitContent` candidato.
5. Sincroniza lecciones y bloques dentro de la misma transacción.
6. Recarga y devuelve una representación canónica antes de liberar el bloqueo.

La publicación usa el mismo bloqueo. Dentro de la transacción obtiene un `UnitContentCoverage` con los UUID de las unidades cuyo contenido está completo. `Course` comprueba que todas sus unidades estén cubiertas antes de cambiar a `published`. Así se evita una carrera entre reemplazar contenido y publicar o archivar.

## Modelo de dominio

### UnitContent

- Identidad: el mismo UUID de la `CourseUnit` propietaria.
- Colección ordenada de lecciones.
- Al menos una lección para considerarse completo.
- Reemplazo completo y atómico.

### Lesson

- UUID técnico global suministrado por el cliente.
- Código normalizado, único dentro de la unidad.
- Título obligatorio, máximo 180 caracteres.
- Resumen opcional.
- Duración estimada opcional en minutos, siempre positiva.
- Posición consecutiva desde 1.
- Al menos un bloque.

### ContentBlock

- UUID técnico global suministrado por el cliente.
- Tipo obligatorio.
- Posición consecutiva desde 1 dentro de la lección.
- Payload tipado según el tipo; no se admite un documento JSON arbitrario.

Tipos iniciales:

| Tipo | Datos obligatorios | Datos opcionales |
|---|---|---|
| `text` | Texto Markdown seguro | Título |
| `image` | URL HTTPS, texto alternativo | Pie o descripción |
| `video` | URL HTTPS, URL HTTPS de subtítulos, transcripción | Título o descripción |
| `audio` | URL HTTPS, transcripción | Título o descripción |
| `interactive` | URL HTTPS, alternativa accesible textual o enlazada | Título o descripción |
| `download` | URL HTTPS, nombre visible, tipo MIME | Descripción, nombre de archivo, tamaño declarado |

El Markdown no admite HTML arbitrario. ENG-028 almacena el texto y su contrato; el renderizado y la sanitización visual pertenecen a un frontend futuro.

La alternativa accesible de un interactivo debe ser texto suficiente o una URL HTTPS hacia un recurso alternativo. El backend no intenta ejecutar ni inspeccionar el recurso remoto.

## Invariantes

- Posiciones de lecciones consecutivas desde 1.
- Posiciones de bloques consecutivas desde 1 dentro de cada lección.
- UUID de lección único globalmente.
- UUID de bloque único globalmente.
- Código de lección único dentro de la unidad, sin distinguir mayúsculas y minúsculas.
- Una lección debe contener al menos un bloque.
- Todos los campos obligatorios por tipo deben estar presentes y no vacíos.
- URLs externas únicamente HTTPS y con longitud acotada.
- Duraciones y tamaños declarados, cuando existan, deben ser positivos.
- El candidato completo se valida antes de mutar el agregado o la persistencia.
- Cualquier error conserva exactamente el contenido anterior.

## Persistencia

Se agregarán tres tablas normalizadas:

```text
academic_unit_contents
  unit_id uuid PK/FK academic_course_units.id ON DELETE CASCADE
  timestampsTz

academic_lessons
  id uuid PK
  unit_id uuid FK academic_unit_contents.unit_id ON DELETE CASCADE
  code varchar(60)
  title varchar(180)
  summary text nullable
  duration_minutes integer nullable CHECK > 0
  position integer
  timestampsTz
  UNIQUE(unit_id, code)
  UNIQUE(unit_id, position)

academic_lesson_blocks
  id uuid PK
  lesson_id uuid FK academic_lessons.id ON DELETE CASCADE
  type varchar(30)
  position integer
  payload json/jsonb
  timestampsTz
  UNIQUE(lesson_id, position)
```

La columna `payload` contiene únicamente la representación persistente de uno de los tipos soportados. El dominio la crea y restaura mediante clases tipadas por tipo; no se expone como JSON libre a la aplicación.

La sincronización preserva las filas cuyos UUID continúan presentes, admite reordenamientos sin colisiones transitorias, elimina elementos obsoletos y traduce conflictos globales de UUID a un error público 409. Todo ocurre dentro de la transacción que mantiene bloqueado el curso propietario.

La carga de contenido usa eager loading y orden explícito. Consultar una unidad no debe producir N+1.

## API

### Consulta

```http
GET /api/v1/academic/courses/{courseId}/units/{unitId}/content
```

- Requiere `auth:sanctum` y `courses.view`.
- Confirma que la unidad pertenece al curso.
- Devuelve curso, unidad, estado y contenido canónico.
- Una unidad sin contenido devuelve `lessons: []`.

### Reemplazo atómico

```http
PUT /api/v1/academic/courses/{courseId}/units/{unitId}/content
```

- Requiere `auth:sanctum` y `courses.manage`.
- Recibe todas las lecciones y bloques de la unidad.
- Solo se permite cuando el curso continúa en `draft` bajo bloqueo.
- Devuelve la representación recargada y canónica.

No se agregarán endpoints granulares para lecciones o bloques ni endpoints de subida de archivos.

## Límites HTTP

- Máximo 100 lecciones por unidad.
- Máximo 200 bloques por lección.
- Máximo 1.000 bloques totales por unidad.
- Máximo 2.048 caracteres por URL.
- Código máximo de 60 caracteres.
- Título máximo de 180 caracteres.
- Textos extensos, transcripciones, alternativas y descripciones tendrán límites explícitos compatibles con el máximo global del body HTTP.
- Los límites agregados se comprueban antes de expandir reglas anidadas.

Las reglas HTTP protegen forma, tipos, tamaños y requisitos por bloque. Las invariantes de posiciones, pertenencia, ciclo de vida y atomicidad permanecen en dominio/aplicación.

## Flujo de aplicación

### Reemplazo

1. Parsear `CourseId` y `CourseUnitId`.
2. Construir lecciones y bloques tipados a partir del payload validado.
3. Iniciar transacción y bloquear el curso.
4. Restaurar el curso y comprobar estado `draft`.
5. Comprobar que la unidad pertenece al curso.
6. Reemplazar el `UnitContent` completo.
7. Sincronizar persistencia preservando UUID.
8. Recargar y devolver respuesta canónica.

### Publicación

1. Bloquear el curso mediante la operación atómica existente.
2. Consultar dentro de la transacción qué unidades tienen contenido completo.
3. Crear `UnitContentCoverage`.
4. Pedir a `Course` que publique usando esa cobertura.
5. Rechazar si falta contenido en alguna unidad, sin mutación parcial.

## Errores públicos

- `COURSE_UNIT_NOT_FOUND` — 404 cuando la unidad no existe o no pertenece al curso indicado.
- `COURSE_CONTENT_CANNOT_BE_MODIFIED` — 422 para cursos publicados o archivados.
- `COURSE_UNIT_CONTENT_REQUIRED` — 422 al publicar con unidades sin contenido completo.
- `INVALID_LESSON_POSITION` — 422.
- `INVALID_BLOCK_POSITION` — 422.
- `INVALID_CONTENT_BLOCK` — 422 para payload/tipo inconsistente.
- `CONTENT_ACCESSIBILITY_REQUIRED` — 422.
- `COURSE_CONTENT_ID_CONFLICT` — 409 cuando un UUID de lección o bloque pertenece a otra unidad.

Las validaciones de forma usan el envelope estándar `VALIDATION_ERROR`. No se distingue entre unidad inexistente y unidad ajena en la respuesta pública.

## Seguridad y confiabilidad

- El servidor almacena URLs pero nunca descarga los recursos; no existe fetch remoto ni superficie SSRF en ENG-028.
- Las URLs deben usar HTTPS.
- El backend no renderiza Markdown ni contenido interactivo.
- Los interactivos se tratan como enlaces no confiables.
- Los payloads malformados deben producir 422, nunca errores 500.
- Los topes totales se calculan en tiempo lineal y cortan antes de construir reglas dinámicas.
- Reemplazo, publicación y archivado se serializan mediante el bloqueo de la fila del curso.
- La persistencia usa restricciones, claves foráneas y rollback transaccional.

## Estrategia de pruebas

El incremento se implementará con TDD:

1. **Dominio:** creación de lecciones y cada tipo de bloque, accesibilidad obligatoria, URL HTTPS, posiciones, duplicados, contenido vacío y atomicidad.
2. **Aplicación:** consulta, reemplazo, unidad inexistente/ajena, curso no modificable y publicación con cobertura completa o incompleta.
3. **Persistencia:** ida y vuelta, orden canónico, reordenamiento preservando UUID, eliminación de obsoletos, conflictos globales, rollback y ausencia de N+1.
4. **HTTP:** autenticación, permisos, payloads malformados, límites tempranos, campos por tipo, HTTPS y flujo PUT/GET/publicación.
5. **Concurrencia:** reemplazo frente a publicación o archivado con resultados consistentes bajo bloqueo.
6. **Compatibilidad:** cursos publicados legacy sin contenido consultables, pero no modificables.

Flujo de aceptación:

1. Crear un curso en borrador con módulos y unidades.
2. Reemplazar el contenido de una unidad con varias lecciones y tipos de bloque.
3. Consultar la misma estructura en orden canónico.
4. Rechazar contenido inaccesible o referencias no HTTPS sin modificar el estado previo.
5. Rechazar publicación mientras alguna unidad no tenga contenido completo.
6. Completar todas las unidades y publicar.
7. Rechazar reemplazos posteriores a publicación.

## Fuera de alcance

- Subida, almacenamiento, proxy, descarga o transformación de archivos.
- Validación remota de disponibilidad, MIME real o contenido de una URL.
- Lista fija o configurable de proveedores autorizados.
- Editor, reproductor o interfaz web.
- Renderizado y sanitización visual de Markdown.
- Revisión, aprobación, publicación independiente de lecciones o historial de versiones (ENG-029).
- Progreso, finalización y reglas de avance (ENG-035 a ENG-037).
- Reutilización de lecciones o bloques entre unidades.
- Asociación con evaluaciones, competencias o SIMUDRIVE.
- Perfiles o reglas normativas por país.
