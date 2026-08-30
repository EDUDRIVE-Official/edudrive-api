# ENG-020 — Perfil del estudiante: diseño

**Fase:** 4 — Perfiles educativos.
**Alcance acordado:** texto libre validado (sin enums inventados) para
nivel educativo/accesibilidad/preferencias; nuevo `StudentProfile` en
`Modules\Identity` + endpoint compuesto `GET/PUT /api/v1/auth/me/profile`
— ambas decisiones recomendadas y elegidas por el usuario.

## Contexto y hallazgos de la investigación

Un agente en background confirmó que dos de las seis viñetas **ya
estaban cubiertas** sin trabajo nuevo:

- **Edad o rango etario**: ya derivable de `User::dateOfBirth()`/
  `isMinor()` (ENG-071).
- **Estado del Pasaporte Vial**: `Modules\RoadPassport` ya lo resuelve
  por completo (`RoadPassportRepository::findByUserId()`,
  `RoadPassport::status()`/`level()`/`issuedAt()`), incluyendo su propio
  endpoint (`GET /api/v1/road-passport/me`). Solo hace falta componerlo.

Las otras cuatro (información académica, nivel educativo, necesidades
de accesibilidad, preferencias de aprendizaje) no tienen ningún
precedente en el proyecto — ni enum, ni columna, ni entidad. El usuario
decidió no inventar taxonomías fijas para nivel educativo/accesibilidad/
aprendizaje (texto libre validado) y sí construir un endpoint compuesto
que agregue todo, mismo patrón de composición cross-módulo ya usado por
`ExportMyDataUseCase` (ENG-070): `UserRepository` + `RoadPassportRepository`
+ `EnrollmentRepository::all(userId: ...)`.

## Decisión de diseño

### Domain (`modules/Identity/Domain/`)

- **`Entities/StudentProfile.php`**: `userId`, `educationLevel` (`?string`,
  texto libre), `accessibilityNeeds` (`?string`), `learningPreferences`
  (`?string`), `updatedAt`. `create()`/`restore()`, `update(?string,
  ?string, ?string, DateTimeImmutable)`. Sin invariantes de dominio más
  allá de longitud (validada en el `FormRequest`, no en el dominio —
  son campos descriptivos, no reglas de negocio).
- **`Repositories/StudentProfileRepository.php`**: `save()`,
  `findByUserId(string): ?StudentProfile`.

### Application (`modules/Identity/Application/`)

- **`UpdateStudentProfileHandler`**: busca el perfil existente por
  `userId`; si no existe, lo crea (`StudentProfile::create()`); aplica
  `update()` con los campos recibidos; guarda. Semántica de upsert —
  el estudiante no necesita "crear" su perfil explícitamente antes de
  poder editarlo.
- **`GetMyStudentProfileHandler`**: compone la respuesta a partir de
  `UserRepository` (nombre, `dateOfBirth`, `isMinor()`),
  `StudentProfileRepository` (los 3 campos libres, `null` si el
  estudiante nunca los ha completado), `RoadPassportRepository`
  (`null` si no tiene pasaporte vial emitido), y
  `EnrollmentRepository::all(userId: ...)` (resumen de matrículas:
  `course_id`, `status`, `enrolled_at` por cada una — sin duplicar
  lógica de reportes ya existente, solo lista lo que ya devuelve el
  repositorio).

### Infrastructure

- Nueva tabla `student_profiles`: `user_id` (uuid, PK, FK real a
  `users`, `cascadeOnDelete`), `education_level` (string, nullable),
  `accessibility_needs` (text, nullable), `learning_preferences` (text,
  nullable), timestamps.
- `StudentProfileModel`/`EloquentStudentProfileRepository`
  (`Infrastructure/Persistence/{Eloquent/Models,Repositories}`, mismo
  patrón que `PasswordResetToken`/`EmailVerificationToken` de ENG-009/010
  dentro de `Identity`).

### Presentation

- **`GET /api/v1/auth/me/profile`**: `GetMyStudentProfileController`,
  dentro del grupo `auth:sanctum` ya existente (junto a `/me`,
  `/me/data-export`), sin permiso adicional — es siempre sobre el
  propio usuario autenticado.
- **`PUT /api/v1/auth/me/profile`**: `UpdateStudentProfileController`,
  mismo grupo. `UpdateStudentProfileRequest` valida los 3 campos como
  texto libre opcional con límite de longitud (`education_level`
  máx. 255, `accessibility_needs`/`learning_preferences` máx. 2000).

## Fuera de alcance (documentado explícitamente)

- Cualquier enum fijo para nivel educativo, accesibilidad o
  preferencias de aprendizaje — texto libre por decisión del usuario.
- Un endpoint para que un administrador/docente consulte el perfil de
  OTRO estudiante (`GET /api/v1/students/{userId}/profile`) — no lo
  piden las viñetas; el endpoint construido es exclusivamente sobre el
  propio usuario autenticado (`/me`), igual que `/auth/me`.
- Cualquier lógica nueva de reportes académicos — "información
  académica" reutiliza `EnrollmentRepository::all()` tal cual, sin
  agregar cálculos nuevos (conteos, porcentajes) que no pidieron las
  viñetas.

## Plan de verificación

TDD por capa: Domain → Application → Infrastructure → Presentation. Pint
y PHPStan (`--memory-limit=512M`) tras cada capa y sobre el repo completo
al final. Suite de `Modules\Identity` completa vía `./vendor/bin/pest`.
