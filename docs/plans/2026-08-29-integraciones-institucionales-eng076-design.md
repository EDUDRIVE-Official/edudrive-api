# ENG-076 — Integraciones institucionales: alcance acordado

Cuarta y última historia planificada de la Fase 15 — Integraciones. A diferencia de las historias anteriores, sus cinco puntos no son capacidades técnicas sino **tipos de institución**: Centros educativos, Empresas, Universidades, Entidades públicas, Sistemas académicos externos.

## Estado previo encontrado (investigación, no una decisión del usuario)

- `Modules\Organization`'s enum `OrganizationType` ya existe con `EducationalCenter`, `DrivingSchool`, `Company`, `PublicInstitution`, `Other` — cubre directamente tres de los cinco puntos (Centros educativos, Empresas, Entidades públicas). Falta un caso propio para "Universidades" (hoy caería en `EducationalCenter` u `Other`).
- **El punto real detrás de esta historia** es el que el propio diseño de ENG-073 dejó explícitamente pendiente: *"No se retro-adapta el control de alcances al resto de la API existente ... eso lo decidirá ENG-076 caso por caso, endpoint por endpoint."* Es decir, ENG-076 es donde corresponde decidir qué endpoints reales de negocio quedan accesibles a un consumidor externo institucional vía el mecanismo de `Modules\Integration` (ENG-073).
- **Hallazgo de seguridad**: hoy `RegisterApiConsumerHandler` valida cada alcance solicitado únicamente contra `Permission::tryFrom()` — es decir, **cualquier** permiso del sistema (incluyendo `system_settings.manage`, `legal_policies.manage`, `users.manage`) puede otorgarse como alcance a un consumidor externo, sin ninguna lista segura que lo impida. Esto es un hueco real, no solo un vacío de alcance.
- No existe ningún endpoint hoy alcanzable por un consumidor externo salvo los dos de humo de ENG-073 (`/external/status`, `/external/reports/ping`). Los endpoints reales que una institución externa querría usar (matrícula masiva, verificación de certificados, reportes, pasaporte vial) existen pero solo bajo `auth:sanctum` + `permission:*` (usuarios humanos).
- No existe ningún concepto de "sistema académico externo" (SIS/LMS) en el repositorio — es terreno enteramente nuevo.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance reducido**: se agrega `OrganizationType::University`; se introduce una lista seleccionada de alcances externos permitidos (en vez del enum `Permission` completo); se retro-adapta un único flujo real de punta a punta (matrícula institucional masiva vía API key) como la interpretación concreta de "Sistemas académicos externos". El resto de endpoints administrativos existentes (import de cursos/preguntas/usuarios, ciclo de vida de certificados y pasaporte vial) se quedan como están, solo para usuarios humanos.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **`OrganizationType::University = 'university'`** con etiqueta `'Universidad'` en el `match` de `label()` (exhaustivo, debía actualizarse). La regla de validación (`new Enum(OrganizationType::class)`) y el listado para el formulario web (`OrganizationType::cases()`) ya son genéricos — no requieren ningún cambio adicional.
- **Lista de alcances externos permitidos**: `Modules\Integration\Domain\Services\ExternalScopeAllowlist` (mismo patrón estático que `Modules\Authorization\Domain\Services\RolePermissions`), con un método `allows(string $scope): bool`. Alcances permitidos: `enrollments.manage`, `enrollments.view`, `certifications.view`, `road_passports.view`, `reports.view` — deliberadamente de solo lectura salvo matrícula (la única escritura que un sistema externo institucional necesita realmente), excluyendo cualquier permiso administrativo, de sistema, legal o de gestión de usuarios/archivos. `RegisterApiConsumerHandler` cambia de `Permission::tryFrom($scope) === null` a `! ExternalScopeAllowlist::allows($scope)` — mismo error `InvalidApiConsumerScope` (422), mensaje reformulado ("no es un alcance externo válido" en vez de "no es un permiso válido", ya que ahora un valor puede ser un `Permission` legítimo y aun así no ser un alcance externo permitido).
- **Matrícula institucional masiva vía API key** (la interpretación concreta de "Sistemas académicos externos"): nuevo `CreateBulkInstitutionalEnrollmentsCommand`/`Handler` en `Modules\Academic`, mismo patrón exacto que `CreateBulkEnrollmentsHandler` (chequeo previo de duplicados por fila, reportados como `ENROLLMENT_ALREADY_EXISTS` en vez de silenciarse — patrón de importación masiva ya establecido desde ENG-061, distinto del patrón de idempotencia de una sola petición de ENG-072) pero delegando a `CreateInstitutionalEnrollmentHandler` por cada usuario en vez de `CreateEnrollmentHandler`, exigiendo `organizationId`. **Deliberadamente sin publicar el evento de webhook** `enrollment.created` — consistente con la limitación ya documentada en el cierre de ENG-074 (`CreateInstitutionalEnrollmentHandler` no está cableado a webhooks; esta historia no amplía esa cobertura, solo reutiliza el handler tal cual).
- **Nuevo endpoint externo**: `POST /api/v1/external/institutional/enrollments` (bajo el prefijo `api/v1/external` ya existente de `Modules\Integration`, junto a los dos endpoints de humo de ENG-073), gateado por `api_consumer.auth` + `throttle:external-integration` + `scope:enrollments.manage`. El controlador vive en `Modules\Integration\Presentation\Http\Controllers` (construye y despacha el `Command` de `Modules\Academic` vía `CommandBus` — misma convención ya usada en toda la sesión de que un controlador puede construir un Command de otro módulo sin acoplarse a su Dominio/handlers).
- **Sin cambios en `ApiConsumer` para llevar un `organizationId` propio**: el `organization_id` de la matrícula institucional se recibe explícitamente en el cuerpo de la petición (no se infiere de la identidad del consumidor) — evita agregar un campo nuevo a `ApiConsumer` solo para este flujo único; una integración real probablemente representa una sola institución de todas formas, pero forzarlo en el payload es más simple y no menos correcto.

## Incluye (del roadmap)

- Centros educativos, Empresas, Entidades públicas (ya cubiertos por `OrganizationType`, sin cambios).
- Universidades (`OrganizationType::University`, caso nuevo).
- Sistemas académicos externos (matrícula institucional masiva vía API key con alcance `enrollments.manage`, más el cierre del hueco de seguridad de alcances externos sin restricción).

## Diferido explícitamente

- Retro-adaptar el control de alcances a cualquier otro endpoint existente (import de cursos/preguntas/usuarios, emisión/revocación de certificados, ciclo de vida del pasaporte vial, el resto de reportes) — se decidirá cuando exista una integración real concreta que lo necesite.
- Cualquier regla de negocio distinta según el tipo de institución (`OrganizationType`) — sigue siendo un dato descriptivo, no condiciona ningún flujo.
- Un campo `organizationId` propio en `ApiConsumer`.
- Cablear el evento de webhook `enrollment.created` a `CreateInstitutionalEnrollmentHandler` (y por tanto a esta nueva ruta masiva) — limitación ya documentada en ENG-074, no ampliada aquí.
