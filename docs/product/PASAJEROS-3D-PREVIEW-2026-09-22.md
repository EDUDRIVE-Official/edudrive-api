# Escenas de pasajeros en la vista editorial

Las páginas 2 y 3 de las lecciones 4 y 5 ahora usan `passenger-preview-3d` exclusivamente en el borrador administrativo. No se cambió el renderizador de cursos publicados ni se migraron respuestas o avances.

Cuatro variantes: autobús en movimiento, acceso por el lado del tránsito, descenso entre vehículos y descenso de autobús seguido de nueva observación. Se conservan las opciones y explicaciones originales. Cada ID (`segura`, `impulso`, `copiar`) selecciona un movimiento distinto; el orden visual no determina la acción.

Controles: abrir bajo demanda, pausa, repetición, dos cámaras y reintento. Si una opción se elige antes de abrir, el motor recupera esa selección. Con preferencia de movimiento reducido se muestra el estado final sin animación. Las explicaciones textuales permiten responder sin WebGL. Se libera el renderizador al salir de la página.

La maqueta simplifica vehículos, ocupantes y movimiento. Las conductas de riesgo se interrumpen sin impactos; no representa una maniobra segura de cruce. La decisión correcta después del autobús termina en la acera, no al otro lado de la calle.

Pruebas: `node scripts/check-passenger-preview.mjs` verifica 1616 estados y las invariantes de las cuatro escenas; `EditorialPreviewTest` verifica las 25 páginas y el componente 3D en las cuatro situaciones de pasajeros (319 aserciones). Compilación Vite correcta; persiste advertencia de tamaño de Three.js.

Pendiente: aceptación visual del usuario y revisión pedagógica especializada. Esta versión sustituye la nota anterior sobre representaciones básicas 2D en estas cuatro páginas del borrador.
