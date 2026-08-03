# Diseño: ENG-025 — Programas educativos regionales

## Objetivo

Crear plantillas educativas regionales reutilizables que organicen cursos existentes y describan el público objetivo sin incorporar reglas legales específicas de un país ni propiedad organizacional.

## Decisión

`Academic` será dueño de un único agregado `EducationalProgram`. Un programa combinará criterios regionales de audiencia y una secuencia ordenada de cursos.

Los programas institucionales y corporativos serán tipos de plantilla del catálogo Academic. ENG-025 no agregará `organization_id`, porque la autorización con alcance por organización sigue diferida a ENG-014.

## Modelo de dominio

### EducationalProgram

Campos principales:

- UUID.
- Código único y normalizado.
- Nombre.
- Descripción.
- Estado: `draft`, `published` o `archived`.
- Criterios de público objetivo.
- Lista ordenada de cursos.

Un programa archivado será inmutable. La publicación exigirá al menos un curso y una secuencia sin duplicados.

### ProgramAudience

El público objetivo admitirá criterios opcionales y combinables:

- Edad mínima y máxima.
- Etapas de licencia regionales: `unlicensed`, `learner`, `licensed`, `professional`.
- Contextos: `general`, `institutional`, `corporate`.
- Vehículos: `motorcycle`, `automobile`.

Las etapas de licencia son neutrales. Categorías legales locales como A1, B1 o C pertenecerán a futuros perfiles nacionales.

### ProgramCourse

Cada elemento referenciará un `CourseId` y conservará una posición explícita. Un mismo curso no podrá aparecer dos veces dentro del programa.

Los borradores podrán construirse con cursos en cualquier estado. Para publicar, todos los cursos referenciados deberán existir y estar publicados.

## Persistencia

Se usará un esquema normalizado:

- `academic_programs`: datos generales, rango etario y estado.
- `academic_program_courses`: cursos y posición.
- Tablas relacionadas para etapas de licencia, contextos y vehículos.

El repositorio guardará el agregado completo en una transacción y lo reconstruirá sin exponer modelos Eloquent. El orden de cursos y la unicidad de criterios se reforzarán también en la base de datos.

## Casos de uso y API

Casos de uso:

- Crear un programa.
- Listar programas con su audiencia y cursos ordenados.
- Actualizar los criterios de audiencia.
- Reemplazar atómicamente la secuencia completa de cursos.
- Publicar un programa.
- Archivar un programa.

Endpoints protegidos:

```text
GET   /api/v1/academic/programs
POST  /api/v1/academic/programs
PATCH /api/v1/academic/programs/{programId}/audience
PUT   /api/v1/academic/programs/{programId}/courses
POST  /api/v1/academic/programs/{programId}/publish
POST  /api/v1/academic/programs/{programId}/archive
```

Se agregarán los permisos `programs.view` y `programs.manage`. `SuperAdmin` recibirá ambos; `InstitutionalAdmin`, `Teacher` y `Student` recibirán solo consulta, siguiendo el patrón actual de Cursos y Competencias.

## Flujo de publicación

1. Un administrador crea el programa en borrador con sus datos y audiencia.
2. Reemplaza la secuencia de cursos mediante una operación atómica.
3. Al publicar, Application carga todos los cursos referenciados y verifica que existan y estén publicados.
4. El dominio confirma que la secuencia no esté vacía y cambia el estado a `published`.
5. El programa puede archivarse; desde entonces no admite modificaciones.

## Manejo de errores

La API expondrá errores de dominio específicos para:

- Código de programa duplicado.
- Programa inexistente.
- Curso inexistente.
- Curso duplicado en la secuencia.
- Rango etario inválido.
- Programa sin cursos al publicar.
- Curso no publicado al publicar el programa.
- Modificación de un programa archivado.

Las validaciones de estructura, tipos y enums permanecerán en Form Requests; las invariantes del programa vivirán en el dominio.

## Pruebas

- Unitarias: rango etario, normalización de criterios, unicidad y orden de cursos, publicación y archivo.
- Application: código duplicado, cursos inexistentes o no publicados y respuestas públicas.
- Integración: persistencia y reconstrucción completa del agregado.
- Feature: endpoints, enums, autenticación, permisos y errores HTTP.
- Verificación final con Pint, PHPStan y la suite completa.

## Fuera de alcance

- Propiedad o personalización por organización.
- Categorías legales de licencia específicas de un país.
- Perfiles normativos nacionales.
- Asociaciones directas con evaluaciones o SIMUDRIVE.
- Módulos, unidades y lecciones (ENG-027 y ENG-028).
- Versionado e historial curricular (ENG-029).
- Inscripciones, progreso, recomendaciones o Pasaporte Vial.

## Criterio de éxito

EDUDRIVE puede administrar y publicar programas regionales con criterios combinables y cursos ordenados —por ejemplo, un programa para aprendices de motocicleta de 16 a 18 años— sin duplicar reglas por país ni adelantar historias posteriores.
