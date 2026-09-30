# Interfaz campus — segunda etapa

Se extiende la identidad del catálogo al detalle y aula de cursos, perfil, pasaporte y pantallas centrales de administración (organizaciones, usuarios, roles, resumen, operaciones y configuración).

- Cabeceras con jerarquía visual y fondo coherente con el catálogo.
- Perfil en dos columnas en escritorio; formularios y tablas conservan ancho completo y orden de lectura.
- Tarjetas y tablas compartidas con más espacio, foco visible y desplazamiento horizontal por teclado.
- Aula conserva paginación, animaciones, estados de respuesta y avance existentes.
- Pasaporte conserva evidencias, QR, advertencias y reglas de impresión. Cambios CSS visuales limitados a pantalla.
- No se modifican permisos, matrículas, contenidos curriculares ni datos de producción.

Verificación: compilación Vite correcta; 42 pruebas y 280 aserciones en base SQLite aislada: navegación, perfil, pasaporte, administración, organizaciones, matrícula y prácticas paginadas.

Estas modificaciones no representan una validación pedagógica ni una revisión visual exhaustiva de todas las pantallas secundarias de administración.
