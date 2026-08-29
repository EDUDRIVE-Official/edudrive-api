# ENG-078 — Gobierno de IA: alcance acordado

Primera historia de la Fase 16 — Inteligencia artificial. A diferencia de todas las historias anteriores, esta trae una nota explícita: *"Debe alinearse con la documentación SEC y ARC correspondiente."* Esa documentación no vive en este repositorio sino en `D:\vr506\EDUDRIVE\edudrive-framework` (proporcionada por el usuario). Los documentos base son:

- **ARC-012 — Arquitectura de Inteligencia Artificial** (`docs/11-arquitectura-solucion/ARC-012-Arquitectura-Inteligencia-Artificial/`, 10 documentos: Estrategia, Arquitectura de Modelos, Ciclo de Vida de Modelos, MLOps, Integración de IA, **Gobierno de IA**, IA Responsable y Ética, Observabilidad de Modelos, Seguridad y Privacidad, Roadmap).
- **SEC-024 — Seguridad de Inteligencia Artificial y Modelos** (`docs/seguridad/SEC-024_Seguridad_de_Inteligencia_Artificial_y_Modelos_v0.1.md.md`, política de 39 secciones, Estado: Borrador para revisión v0.1).

## Estado previo encontrado (investigación, no una decisión del usuario)

- No existe ninguna funcionalidad de IA/LLM en todo el repositorio (confirmado por investigación exhaustiva) — ENG-079 (Asistente educativo) y ENG-080 (Análisis de patrones de conducción) están **Diferido**. Por tanto, "Gobierno de IA" aquí significa construir el **riel institucional** (catálogo, clasificación de riesgo, registro de decisiones, aprobación humana) al que cualquier capacidad de IA futura deberá conectarse — no gobernar una IA que ya exista.
- `Modules\Audit`'s `AuditEntry` asume siempre un actor humano (`userId` del usuario autenticado); no existe ningún concepto de "decisión automatizada/de IA" distinto de una acción administrativa humana. SEC-024 §22 y ARC-012/06 §16 exigen un registro de decisiones de IA con campos propios (modelo, prompt, confianza, revisión humana, tokens, costo) — no es reutilizable tal cual.
- No existe ningún flujo de "requiere aprobación humana antes de surtir efecto" en todo el codebase. SEC-024 §15 y ARC-012/07 §7 (niveles de supervisión 1-4) exigen exactamente esto para decisiones de riesgo medio/alto/crítico.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance completo**: además del riel mínimo (catálogo de sistemas de IA + registro de decisiones con aprobación humana), se construyen los componentes adicionales de ARC-012 explícitamente ofrecidos: AI Gateway real (proxy institucional, sin integración con ningún proveedor real todavía), Model Registry y Prompt Registry como catálogos separados, observabilidad de costos/tokens/latencia (integrada en el registro de decisiones, no como subsistema aparte), gestión de incidentes de IA, y evaluación de proveedores externos.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión y grounding en SEC-024/ARC-012)

### Módulo nuevo `Modules\AiGovernance`

Un único par de permisos `ai_governance.manage` / `ai_governance.view` (SuperAdmin únicamente) cubre los seis conceptos del módulo — todos igualmente sensibles (gobierno institucional de IA), sin necesidad de separar permisos por sub-recurso; se puede dividir más adelante si surge una necesidad real (mismo criterio de simplificación deliberada usado en otras historias de esta sesión).

**Sin FK hacia `users` en ningún campo `*_owner_id`/`*_user_id` de este módulo**: son metadatos de gobierno (quién es responsable, quién revisó), no registros académicos ligados a la identidad de un usuario — se guardan como string/UUID sin restricción referencial, evitando el problema de integridad referencial ya encontrado en historias previas de esta sesión (ENG-076) y evitando acoplar el ciclo de vida de estos registros al de una cuenta de usuario.

### `AiSystem` (Inventario Oficial de IA — SEC-024 §5 / Catálogo Institucional — ARC-012/06 §8)

Agregado con: `name`, `purpose`, `functionalOwnerId`, `technicalOwnerId` (opcional), `riskLevel` (`AiRiskLevel`: IA-0 a IA-4, SEC-024 §4), `supervisionLevel` (`AiSupervisionLevel`: 1-Informa, 2-Recomienda, 3-Propone, 4-Automatiza, ARC-012/07 §7), `dataCategories` (lista de `AiDataCategory`: Pública/Interna/Personal/Menores/Sensible — ARC-012/09 §6 + SEC-024 §8.4), `status` (`AiSystemStatus`: Evaluación/Piloto/Producción/Suspendido/Retirado — SEC-024 §5.2), banderas `extraordinaryApprovalGranted` y `committeeApproved` con sus fechas.

Reglas de dominio (invariantes reales, no solo documentación):

- **IA-4 no puede promoverse a Producción sin `extraordinaryApprovalGranted`** (SEC-024 §4.5: *"Los usos IA-4 estarán prohibidos salvo aprobación extraordinaria..."*) → `AiSystemRequiresExtraordinaryApproval`.
- **IA-3 o IA-4 no pueden promoverse a Producción sin `committeeApproved`** (SEC-024 §28.2: *"Los sistemas IA-3 requieren aprobación del Comité de Gobierno de IA. Los sistemas IA-4 requieren además aprobación de Dirección, Seguridad, Legal..."*, simplificado aquí a una sola bandera de aprobación del comité) → `AiSystemRequiresCommitteeApproval`.
- **Un sistema que procesa datos de menores no puede promoverse a Producción con nivel de supervisión menor a 3 (Propone)** (SEC-024 §8.4: *"revisión humana de resultados relevantes"* para datos de menores) → `AiSystemRequiresHumanSupervisionForMinors`.
- Transiciones de estado válidas: `Evaluación → Piloto → Producción ⇄ Suspendido`, y cualquier estado no terminal `→ Retirado` (terminal) — inválida en cualquier otro caso → `InvalidAiSystemTransition`.

"Casos prohibidos" (bullet del roadmap) se interpreta como estas reglas de dominio (SEC-024 §25 enumera 20 usos prohibidos en prosa; en vez de modelarlos como una lista cerrada de strings sin ninguna consecuencia ejecutable, se traduce el caso más concreto y verificable —IA-4 sin aprobación extraordinaria— en una invariante real del agregado). "Casos permitidos" es el reverso: todo lo que supera el registro, la clasificación y las aprobaciones requeridas.

### `AiDecision` (Registro de decisiones — SEC-024 §22 / Auditoría — ARC-012/06 §16, más observabilidad de costos/tokens/latencia — ARC-012/08 §8 y §15)

Entidad ligada a un `AiSystem`: `requestedByUserId` (opcional), `inputSummary`, `outputSummary`, `confidenceLevel` (opcional), `tokensInput`/`tokensOutput`/`costAmount`/`latencyMs` (opcionales — la observabilidad de costos se integra aquí en vez de como subsistema aparte, dado que no hay volumen real de decisiones todavía que justifique un almacén de métricas separado), `reviewStatus` (`NotRequired|Pending|Approved|Rejected`), `reviewedByUserId`/`reviewedAt`.

`reviewStatus` se decide en la Capa de Aplicación (no en la entidad) a partir del `supervisionLevel` del `AiSystem` al momento de registrar la decisión: `Pending` si `supervisionLevel >= Proposes (3)`, `NotRequired` en caso contrario — implementando directamente ARC-012/07 §7 (Nivel 3 exige aprobación humana) y SEC-024 §15. Una decisión `Pending` debe ser aprobada o rechazada por un humano (`approve()`/`reject()`) antes de considerarse definitiva — el primer flujo real de aprobación humana de todo el repositorio.

### AI Gateway (ARC-012/03 §12, ARC-012/06 §13, SEC-024 §11 "AI Gateway")

Puerto de Aplicación `AiGatewayClient::invoke(AiGatewayRequest): AiGatewayResponse`, implementado por `HttpAiGatewayClient` (Infraestructura): valida que el `AiSystem` exista y esté `Producción` o `Piloto` (rechaza `Evaluación`/`Suspendido`/`Retirado`), aplica un limitador de tasa nombrado `ai-gateway` (mismo patrón que `external-integration`/`simulator-integration`), invoca un endpoint HTTP configurable (`config('ai_governance.gateway_endpoint')` — genérico y reemplazable, mismo patrón que el endpoint de push de ENG-075; **sin integración real con ningún proveedor de IA**, ya que no existe ninguno todavía) y registra automáticamente un `AiDecision` con el resultado. Es una llamada síncrona (petición/respuesta), no una cola — a diferencia de webhooks/push, quien invoca el gateway espera la respuesta de la IA en la misma petición.

Se expone un único endpoint administrativo de demostración (`POST /api/v1/ai-governance/gateway/invoke`) para probar el mecanismo de punta a punta, mismo criterio que el endpoint mínimo de verificación de ENG-073 — no hay ningún consumidor real todavía (ENG-079/080 lo serán en el futuro).

### `AiModel` (Model Registry — ARC-012/03 §9) y `AiPrompt` (Prompt Registry — ARC-012/03 §10)

Catálogos separados del `AiSystem` (un sistema de IA puede usar varios modelos/prompts a lo largo de su vida). `AiModel`: `name`, `provider`, `version`, `ownerId`, `useCase`, `status` (`Registered → Approved → Deprecated → Retired`), `knownRisks`. `AiPrompt`: `identifier`, `purpose`, `modelId` (opcional), `version` (se incrementa en cada cambio de contenido — mismo patrón de versionado que `CommunicationTemplate` de `Modules\Notification`), `authorId`, `content`, `status` (`Draft → Approved → Retired`).

### `AiIncident` (Gestión de incidentes de IA — SEC-024 §24) y `AiProviderEvaluation` (Evaluación de proveedores — SEC-024 §21)

`AiIncident`: ligado a un `AiSystem`, `severity`, `description`, `status` (`Open → Investigating → Resolved`), `correctiveActions`. `AiProviderEvaluation`: `providerName`, `dataLocation`, `retentionPolicy`, `securityReviewNotes`, `approvalStatus` (`PendingReview → Approved/Rejected/RequiresReevaluation`), `reviewedAt`, `nextReviewDueAt`. `AiSystem` puede referenciar opcionalmente una `AiProviderEvaluation` (`providerEvaluationId`).

## Incluye (del roadmap)

- Casos permitidos / Casos prohibidos → reglas de dominio de `AiSystem` (IA-4 sin aprobación extraordinaria, IA-3/IA-4 sin aprobación de comité, datos de menores sin supervisión suficiente).
- Supervisión humana → niveles 1-4 en `AiSystem` + flujo de aprobación/rechazo de `AiDecision`.
- Registro de decisiones → `AiDecision`, con observabilidad de costos/tokens/latencia integrada.
- Privacidad → `dataCategories` en `AiSystem`, con la regla de menores como invariante ejecutable.
- Evaluación de modelos → `AiModel` (Model Registry) + `AiPrompt` (Prompt Registry) + `AiProviderEvaluation`.

## Diferido explícitamente

- Integración real con cualquier proveedor de IA (OpenAI, Anthropic, etc.) — el AI Gateway apunta a un endpoint HTTP genérico y configurable, reemplazable cuando exista una integración real.
- Bases vectoriales, Knowledge Graph, MCP Servers, AgentOps — no hay ningún agente ni flujo RAG que gobernar todavía.
- Dashboards de observabilidad — los datos de costos/tokens/latencia se registran en `AiDecision`, pero no se construye ningún tablero.
- Comité de Gobierno de IA como flujo organizacional real (reuniones, actas) — se modela únicamente como una bandera booleana de aprobación en `AiSystem`.
