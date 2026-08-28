# ENG-071 — Seguridad para menores de edad: alcance acordado

Quinta y última historia planificada de la Fase 14 — Seguridad y cumplimiento. El roadmap pide cinco puntos: Consentimiento parental, Datos mínimos, Restricción de exposición, Protección de perfiles, Controles institucionales.

## Estado previo encontrado (investigación, no una decisión del usuario)

- **Bloqueo real**: no existe ningún campo de fecha de nacimiento ni edad en absolutamente ningún módulo (`Modules\Identity`'s `User`, `Modules\Academic`'s `Enrollment`, `Modules\Organization`) — sin ese dato, "menor de edad" no tiene ninguna condición que lo dispare.
- **Consentimiento parental**: el módulo `Modules\Legal` recién construido en ENG-070 ata cada `UserConsent` exactamente a un `userId` — el que acepta y el titular de la cuenta son siempre la misma persona. No existe ningún concepto de tutor/guardián.
- **Datos mínimos**: reconfirmado limpio — ningún módulo recolecta de más.
- **Restricción de exposición**: una sola fuga confirmada — `GET /api/v1/certification/verify/{code}` (pública, sin autenticación) expone el nombre completo del titular del certificado.
- **Protección de perfiles**: ya satisfecha hoy — no existe ningún leaderboard, ranking, ni perfil público entre estudiantes; todos los listados de logros/insignias/retos de `Modules\Gamification` están limitados a "mis propios datos" o detrás de permisos de staff.
- **Controles institucionales**: hallazgo adyacente (no pedido explícitamente por el roadmap) — `InstitutionalAdmin` hoy puede listar/activar/desactivar cualquier usuario del sistema completo, sin ningún límite por organización (`ListUsersUseCase`/`DeactivateUserUseCase` no aplican ningún filtro de organización). Es un error de alcance de permisos preexistente, no específico de menores.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance reducido**: se agrega fecha de nacimiento opcional a `User` (nueva recolección de dato justificada por el propósito de cumplimiento). Consentimiento parental **autodeclarado** (el propio menor confirma que cuenta con autorización de su madre/padre/tutor; no se verifica la identidad real de un tercero). Se corrige la única fuga confirmada (nombre del titular en verificación pública, suprimido para menores). "Protección de perfiles" se documenta como ya satisfecha, sin cambios. "Controles institucionales" se limita a que una organización consulte el estado de consentimiento parental de sus propios estudiantes menores — **no** se corrige el error de alcance de `InstitutionalAdmin` sobre gestión general de usuarios (fuera del alcance de esta historia, es un problema de autorización general, no específico de menores).

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Fecha de nacimiento nullable y opcional**: `User` gana `?DateTimeImmutable $dateOfBirth` y un método de dominio `isMinor(?DateTimeImmutable $asOf = null): bool` (menor de 18 años). Nullable porque no se retroalimenta a cuentas existentes ni a la importación masiva de estudiantes (`Modules\Identity`'s bulk import, ENG-061) — cambiar ese formato de CSV excede esta historia. Solo el registro directo (`POST /api/v1/auth/register`) gana un campo opcional `date_of_birth`. Un usuario sin fecha de nacimiento registrada nunca se considera menor (`isMinor()` retorna `false` cuando es `null`) — es una limitación conocida y documentada, no un error silencioso.
- **Consentimiento parental como campo del propio `UserConsent`, no un agregado nuevo**: `UserConsent` gana `?string $guardianDeclaration` (nombre del madre/padre/tutor declarado). `RecordConsentHandler` pasa a depender de `Modules\Identity`'s `UserRepository` (mismo patrón de dependencia cruzada ya usado en el resto de la sesión) — si el usuario que acepta es menor según su fecha de nacimiento actual, `guardian_declaration` es obligatorio en la petición; si no lo es, se ignora aunque se envíe. No se modela un "tutor" como entidad propia con su propia cuenta o verificación — es una declaración de texto libre, consistente con el alcance acordado.
- **Restricción de exposición vía edad actual, no edad al momento de emisión**: `VerifyCertificateHandler` suprime `holderName` (ya nullable desde ENG-070) cuando `$holder->isMinor()` es verdadero *hoy*, no en la fecha de emisión del certificado — protege a la persona mientras siga siendo menor, incluso si el certificado se emitió antes. Reutiliza exactamente el mismo campo nullable que ya existe para el caso "titular eliminado" — la respuesta pública ya no distingue "el titular es menor" de "el titular eliminó su cuenta"; ambos casos simplemente omiten el nombre.
- **Controles institucionales**: nuevo permiso `organization_consents.view` (SuperAdmin + InstitutionalAdmin, mismo patrón que otros permisos `*.view` de esta fase). Nueva consulta en `Modules\Legal` que, dado un `organizationId`, resuelve los usuarios inscritos en esa organización vía `Modules\Academic`'s `EnrollmentRepository::all(organizationId:)` (dependencia cruzada de solo lectura, mismo patrón que ENG-063/065), filtra a los que son menores según su fecha de nacimiento, y devuelve su historial de consentimiento. Solo incluye menores — un adulto inscrito en la organización no aparece en este listado, porque el propósito específico es supervisión de consentimiento parental, no un listado general de estudiantes (eso ya existe vía `users.view`).

## Incluye (del roadmap)

- Consentimiento parental (autodeclarado).
- Datos mínimos (fecha de nacimiento opcional, única adición, justificada).
- Restricción de exposición (nombre del titular suprimido para menores en verificación pública).
- Protección de perfiles (confirmación de que ya está satisfecha).
- Controles institucionales (consulta de estado de consentimiento parental por organización).

## Diferido explícitamente

- Verificación real de identidad de un tutor/guardián (flujo de invitación, cuenta propia del tutor, correo verificado).
- Captura de fecha de nacimiento en la importación masiva de usuarios (ENG-061) o en cualquier flujo administrativo de creación de cuentas.
- Corrección del error de alcance de `InstitutionalAdmin` sobre gestión general de usuarios (problema de autorización general, no específico de menores — candidato a una historia futura).
- Cualquier sistema de protección de perfiles públicos o leaderboards, dado que ninguno existe hoy.
