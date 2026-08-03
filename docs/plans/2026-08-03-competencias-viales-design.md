# Diseño: ENG-024 — Catálogo regional de competencias viales

## Objetivo

Crear el núcleo pedagógico común de EDUDRIVE para Latinoamérica. Debe permitir relacionar formación, evaluación, simulación y evidencia de conducción segura sin duplicar el catálogo por país.

## Decisión

El catálogo tendrá un núcleo regional compartido y perfiles nacionales separados.

El núcleo define qué capacidad vial debe desarrollarse y cómo se observa. Los perfiles nacionales, que no forman parte de este primer incremento, asociarán referencias normativas, condiciones locales y vigencia sin modificar las competencias compartidas.

## Modelo del primer incremento

### Competencia

Representa una capacidad vial amplia y estable. Incluye código, nombre, descripción, categoría y nivel de dominio esperado.

Ejemplo: `RISK-001` — Anticipación y gestión del riesgo vial.

### Subcompetencia

Descompone una competencia en una capacidad concreta que puede desarrollarse y evaluarse. Mantiene orden y pertenencia a una única competencia.

Ejemplo: Identificar riesgos generados por velocidad, distancia y entorno.

### Indicador

Define una conducta o decisión observable. Será la unidad que, en incrementos posteriores, podrán referenciar lecciones, exámenes y eventos de SIMUDRIVE.

Ejemplo: Ajusta la velocidad a la visibilidad, señalización y condiciones del entorno.

## Flujo de información

1. Un administrador académico crea una competencia regional.
2. Agrega subcompetencias e indicadores observables.
3. Los cursos, evaluaciones y simuladores podrán asociar su contenido o evidencia a esos indicadores en historias posteriores.
4. Un perfil país futuro complementará referencias normativas aplicables sin ramificar el catálogo.

## Alcance de ENG-024

- Gestión de competencias, subcompetencias e indicadores.
- Códigos estables y únicos para integración futura.
- Consulta jerárquica del catálogo.
- Validación, autorización y trazabilidad acorde al estándar modular.

## Fuera de alcance

- Perfiles normativos por país.
- Asociación de cursos, lecciones, exámenes o simulaciones.
- Cálculo de dominio, recomendaciones o Pasaporte Vial.
- Versionado curricular, que pertenece a ENG-029.

## Riesgos y decisiones futuras

- Las categorías y niveles de dominio se modelarán como vocabularios controlados para evitar taxonomías incompatibles.
- Los perfiles país deberán versionarse y tener vigencia cuando se incorporen.
- Los indicadores deben permanecer tecnológicos y pedagógicamente neutrales para que una misma competencia aplique a distintos países y medios de transporte.

## Criterio de éxito

EDUDRIVE puede administrar un catálogo regional legible y jerárquico de competencias, listo para vincularse a contenido y evidencia práctica sin que las diferencias normativas nacionales obliguen a duplicarlo.
