# Diseño — Consolidación de trabajo pendiente y arranque de Autorización/Organizaciones

## 1. Información del documento

| Campo | Valor |
|---|---|
| Fecha | 2026-07-29 |
| Proyecto | EDUDRIVE2026 |
| Componentes afectados | `edudrive-api`, `edudrive-design-system` |
| Tipo | Diseño de retoma de trabajo (brainstorming) |
| Estado | Aprobado por el usuario, pendiente de plan de implementación |

## 2. Contexto

Se retomó el trabajo en `EDUDRIVE2026` después de una pausa. El usuario había avanzado trabajo adicional en `edudrive-api` usando otra herramienta de IA; ese trabajo ya fue copiado a esta carpeta y corresponde exactamente al incremento **IMP-021 (Bloque 1)** registrado en `docs/engineering/ENG-LOG.md`, actualmente sin commitear.

Además, se detectó que `docs/roadmap/ENG-000-roadmap-tecnico-backend.md` está desincronizado con la realidad: marca `ENG-008.2` en adelante como "Pendiente" cuando el `ENG-LOG.md` confirma que login, logout, logout-all, sesiones y auditoría básica ya están implementados y probados. El equipo también avanzó el módulo Academic (Fase 5 — Catálogo educativo) sin haber completado Fase 2 (Autorización) ni Fase 3 (Organizaciones), que el propio roadmap ubica antes en su orden de prioridad.

## 3. Objetivo

1. No perder ni dejar sin registrar el trabajo ya hecho (Bloque 1 de IMP-021).
2. Dejar la documentación de ingeniería (`ENG-000`, `ENG-LOG.md`) alineada con el estado real del código.
3. Definir un alcance mínimo viable (YAGNI) para la siguiente historia técnica activa: Autorización + Organizaciones, priorizada por ser la pieza de mayor apalancamiento (casi todo lo demás depende de "quién puede hacer qué, en qué institución").
4. Dejar planteado (no implementado todavía) el siguiente hito grande del ecosistema: los primeros componentes reales del design system, en `ui/web`.

## 4. Alcance

### 4.1 Consolidación inmediata (edudrive-api)

- Commitear el Bloque 1 pendiente de IMP-021 tal cual está (`CommandBus`/`QueryBus` en Foundation, `ListCoursesHandler`, migración de `CreateCourseCommand` al bus, `ENG-LOG.md` actualizado). Ya pasó `composer test`, `composer analyse` y `composer quality` según el propio log — no se modifica código en este paso.
- Actualizar `ENG-000-roadmap-tecnico-backend.md`:
  - Marcar `ENG-008.2` a `ENG-008.8` como **Completado**.
  - Documentar el avance real de Academic (Fase 5, adelantado fuera de orden bajo IMP-020/IMP-021).
  - Actualizar la sección "Historia técnica activa" para apuntar a la nueva historia de Autorización/Organizaciones (sección 4.2).

### 4.2 Nueva historia activa: Autorización + Organizaciones (alcance reducido)

Alcance deliberadamente recortado frente a las ~9 historias completas que el roadmap original prevé para las Fases 2 y 3:

**Incluido:**
- Roles mínimos viables: `Superadministrador`, `Administrador institucional`, `Docente/Instructor`, `Estudiante`.
- Catálogo simple de permisos + asignación de permisos a roles + middleware de verificación de permisos.
- Entidad `Organización` + `Sede`, con un enum simple de tipo de organización (no un modelo distinto por cada tipo institucional previsto).
- Membresía organizacional: relación usuario–organización–rol.

**Explícitamente fuera de alcance por ahora:**
- Roles adicionales (`Coordinador`, `Evaluador`, `Soporte`, `Integración SIMUDRIVE`) — se agregan cuando exista un caso de uso real.
- Políticas de acceso complejas más allá de permisos simples por rol.
- Historial de cambios de membresía.
- Grupos y cohortes (`ENG-019`) — se difieren hasta que Academic los necesite.
- Auditoría de accesos avanzada (ya existe auditoría básica de eventos de auth) y consentimientos/privacidad (`ENG-023`).

### 4.3 Próximo hito grande del ecosistema: Design System (`ui/web`)

- Arranca **después** de tener funcionando los primeros endpoints de Organizaciones/Roles, para construir contra API real en vez de mockups aislados.
- Se ataca primero `ui/web` (panel admin/institucional), no `ui/mobile` ni `ui/simudrive` en paralelo.
- Alcance inicial: componentes atómicos sobre los tokens ya definidos (botón, input, card, tabla, badge de estado) — no páginas completas todavía.
- `ui/mobile` y `ui/simudrive` quedan fuera de este ciclo.

## 5. Fuera de alcance de este diseño

- Arrancar el repositorio de SIMUDRIVE (Unity/C#): la propia arquitectura (`ARC-006`) indica que debe construirse después de que exista identidad/competencias/Pasaporte Vial compartidos, que aún están varias fases adelante.
- Cualquier cambio de código en el Bloque 1 ya validado de IMP-021.

## 6. Validación y criterios de terminado

Se mantienen las convenciones ya establecidas en `ENG-000`:
- `composer format` y `composer quality` (Pint, Larastan/PHPStan, Pest) para cada historia backend.
- Migraciones verificadas (`php artisan migrate:status`).
- Commit por bloque funcional y actualización del estado en `ENG-000`/`ENG-LOG.md`.

## 7. Siguiente paso

Este diseño pasa a un plan de implementación detallado (vía la skill `writing-plans`) que desglosará la sección 4.2 en historias técnicas concretas (p. ej. `ENG-012` a `ENG-018` recortadas) con sus tareas, pruebas y comandos de validación.
