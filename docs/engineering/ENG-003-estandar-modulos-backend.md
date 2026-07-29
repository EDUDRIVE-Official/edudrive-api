# ENG-003 — Estándar de Módulos Backend

## 1. Propósito

Este documento define la estructura, las responsabilidades y las convenciones oficiales para los módulos del backend de EDUDRIVE.

Su objetivo es garantizar que todos los módulos:

* utilicen una estructura consistente;
* respeten la separación de responsabilidades;
* puedan evolucionar de forma independiente;
* sean fáciles de probar;
* reduzcan el acoplamiento entre dominios;
* mantengan una arquitectura modular sostenible.

Este estándar aplica a todos los módulos ubicados en:

```text
modules/
```

---

## 2. Principios arquitectónicos

Los módulos del backend deben construirse siguiendo estos principios:

1. Separación clara entre dominio, aplicación, infraestructura y presentación.
2. El dominio no debe depender de Laravel.
3. La lógica de negocio no debe ubicarse en controladores.
4. Los casos de uso deben coordinar el comportamiento del sistema.
5. Las interfaces deben pertenecer a las capas internas.
6. Las implementaciones técnicas deben ubicarse en infraestructura.
7. Los módulos deben comunicarse mediante contratos explícitos.
8. Cada módulo debe poder probarse de forma aislada.
9. Las dependencias deben apuntar hacia el dominio.
10. Los detalles técnicos no deben contaminar las reglas de negocio.

---

## 3. Estructura oficial de un módulo

La estructura base oficial será:

```text
modules/{ModuleName}/
├── Application/
│   ├── Commands/
│   ├── Queries/
│   ├── DTOs/
│   ├── Exceptions/
│   └── Services/
│
├── Domain/
│   ├── Aggregates/
│   ├── Entities/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Repositories/
│   ├── Services/
│   └── ValueObjects/
│
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Eloquent/
│   │   │   ├── Models/
│   │   │   └── Repositories/
│   │   └── Migrations/
│   │
│   ├── Providers/
│   └── Services/
│
├── Presentation/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   │
│   └── Routes/
│
└── Tests/
    ├── Unit/
    ├── Feature/
    └── Integration/
```

Solo deben crearse los directorios que el módulo realmente necesite.

No se deben agregar carpetas vacías únicamente para completar la estructura.

---

## 4. Namespace raíz

Todos los módulos utilizarán el namespace:

```php
Modules\{ModuleName}
```

Ejemplos:

```php
Modules\Academic
Modules\Identity
Modules\Audit
Modules\Foundation
```

La ruta física y el namespace deben coincidir.

Ejemplo:

```text
modules/Academic/Domain/Entities/Course.php
```

Namespace:

```php
namespace Modules\Academic\Domain\Entities;
```

---

## 5. Capa Domain

La capa `Domain` contiene las reglas fundamentales del negocio.

No debe depender de:

* Laravel;
* Eloquent;
* controladores;
* HTTP;
* bases de datos;
* servicios externos;
* variables de entorno;
* contenedores de dependencias.

Puede contener:

* agregados;
* entidades;
* objetos de valor;
* eventos de dominio;
* enumeraciones;
* contratos de repositorios;
* servicios de dominio;
* excepciones de dominio.

### 5.1 Agregados

Los Aggregate Roots protegen las invariantes principales del dominio.

Ejemplo:

```text
Domain/Aggregates/Course.php
```

El agregado debe controlar:

* su creación;
* sus cambios válidos;
* sus estados;
* sus reglas de negocio;
* los eventos que produce.

No debe exponer modificaciones indiscriminadas de sus propiedades.

### 5.2 Entidades

Las entidades tienen identidad propia y pueden cambiar durante su ciclo de vida.

Su identidad no depende exclusivamente de sus atributos actuales.

### 5.3 Objetos de valor

Los objetos de valor:

* son inmutables;
* se comparan por su valor;
* validan su propia consistencia;
* no poseen identidad independiente.

Ejemplos:

```text
CourseId
CourseCode
CourseTitle
Email
```

### 5.4 Repositorios de dominio

Las interfaces de repositorio se ubican en:

```text
Domain/Repositories/
```

Ejemplo:

```php
interface CourseRepository
{
    public function save(Course $course): void;

    public function findById(CourseId $id): ?Course;
}
```

El dominio define lo que necesita, mientras que infraestructura implementa cómo se realiza.

---

## 6. Capa Application

La capa `Application` coordina los casos de uso.

Puede depender de `Domain`, pero no debe depender directamente de controladores o detalles HTTP.

Puede contener:

* comandos;
* consultas;
* manejadores;
* DTO;
* servicios de aplicación;
* excepciones de aplicación.

### 6.1 Commands

Los comandos representan una intención de modificar el sistema.

Ejemplos:

```text
CreateCourse
PublishCourse
ArchiveCourse
```

### 6.2 Queries

Las consultas representan solicitudes de información.

Ejemplos:

```text
GetCourseById
ListCourses
GetPublishedCourses
```

Una consulta no debe modificar el estado del dominio.

### 6.3 DTO

Los DTO transportan información entre capas.

No deben contener lógica de negocio compleja.

---

## 7. Capa Infrastructure

La capa `Infrastructure` contiene implementaciones técnicas.

Puede depender de Laravel, Eloquent, PostgreSQL, Redis, MinIO u otros servicios externos.

Puede contener:

* modelos Eloquent;
* repositorios Eloquent;
* migraciones;
* adaptadores;
* proveedores de servicios;
* integraciones externas;
* implementaciones de contratos.

### 7.1 Modelos Eloquent

Los modelos Eloquent se ubican en:

```text
Infrastructure/Persistence/Eloquent/Models/
```

Los modelos representan persistencia, no el dominio.

No deben convertirse en el lugar principal de las reglas de negocio.

### 7.2 Repositorios Eloquent

Las implementaciones se ubican en:

```text
Infrastructure/Persistence/Eloquent/Repositories/
```

Ejemplo:

```text
EloquentCourseRepository.php
```

Este repositorio implementará el contrato definido por el dominio.

### 7.3 Migraciones

Las migraciones propias del módulo se ubican en:

```text
Infrastructure/Persistence/Migrations/
```

El `ServiceProvider` del módulo será responsable de cargarlas.

---

## 8. Capa Presentation

La capa `Presentation` expone las capacidades del módulo.

Puede contener:

* controladores;
* requests;
* resources;
* middleware;
* rutas;
* adaptadores de entrada.

### 8.1 Controladores

Los controladores deben ser delgados.

Sus responsabilidades son:

1. recibir la solicitud;
2. validar o delegar la validación;
3. construir comandos o consultas;
4. ejecutar el caso de uso;
5. devolver una respuesta.

No deben contener reglas de negocio.

### 8.2 Form Requests

La validación de estructura de entrada debe colocarse en:

```text
Presentation/Http/Requests/
```

Las validaciones de negocio deben permanecer dentro del dominio o del caso de uso correspondiente.

### 8.3 API Resources

La serialización de respuestas HTTP debe colocarse en:

```text
Presentation/Http/Resources/
```

No se deben devolver modelos Eloquent directamente desde los controladores.

### 8.4 Rutas

Las rutas del módulo se ubican en:

```text
Presentation/Routes/api.php
```

El módulo puede tener archivos adicionales cuando sea necesario:

```text
Presentation/Routes/web.php
Presentation/Routes/console.php
```

---

## 9. ServiceProvider del módulo

Todos los módulos deben registrar su proveedor en:

```text
Infrastructure/Providers/{ModuleName}ServiceProvider.php
```

Ejemplo:

```text
modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php
```

Namespace:

```php
namespace Modules\Academic\Infrastructure\Providers;
```

El proveedor puede encargarse de:

* cargar rutas;
* cargar migraciones;
* registrar bindings;
* registrar servicios;
* registrar comandos;
* registrar listeners;
* registrar políticas.

Ejemplo base:

```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class AcademicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar contratos e implementaciones.
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(
            __DIR__.'/../../Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            __DIR__.'/../Persistence/Migrations',
        );
    }
}
```

Los proveedores se registran en:

```text
bootstrap/providers.php
```

Convención oficial:

```text
Modules\{ModuleName}\Infrastructure\Providers\{ModuleName}ServiceProvider
```

Los módulos existentes que utilicen otra ubicación deberán alinearse gradualmente mediante refactorizaciones controladas.

---

## 10. Convención de rutas API

Las rutas públicas del backend utilizarán el prefijo:

```text
/api/v1
```

Cada módulo tendrá su propio segmento.

Ejemplos:

```text
/api/v1/academic
/api/v1/identity
/api/v1/audit
```

Los nombres de ruta seguirán esta convención:

```text
api.v1.{module}.{resource}.{action}
```

Ejemplo:

```php
->name('api.v1.academic.courses.store');
```

Para endpoints REST convencionales pueden utilizarse nombres como:

```text
api.v1.academic.courses.index
api.v1.academic.courses.show
api.v1.academic.courses.store
api.v1.academic.courses.update
api.v1.academic.courses.destroy
```

---

## 11. Convención de base de datos

Las tablas pertenecientes a un módulo deben utilizar un prefijo de dominio.

Ejemplos:

```text
academic_courses
academic_modules
academic_lessons
identity_users
audit_logs
```

Esto permite identificar claramente la propiedad de cada tabla.

Las claves primarias utilizarán UUID, salvo que exista una decisión arquitectónica documentada que justifique otro formato.

Las fechas se almacenarán utilizando las capacidades estándar de Laravel y PostgreSQL.

---

## 12. Convenciones de nombres

### Clases

Se utilizará PascalCase:

```text
Course
CourseId
CreateCourse
EloquentCourseRepository
AcademicServiceProvider
```

### Métodos y variables

Se utilizará camelCase:

```php
createCourse()
findById()
courseId
publicationStatus
```

### Tablas y columnas

Se utilizará snake_case:

```text
academic_courses
course_id
published_at
created_at
```

### Interfaces

Las interfaces utilizarán nombres basados en su responsabilidad:

```text
CourseRepository
CourseReader
CoursePublisher
```

No es obligatorio agregar el sufijo `Interface`.

---

## 13. Excepciones

Las excepciones deben ubicarse según su responsabilidad.

### Excepciones de dominio

```text
Domain/Exceptions/
```

Ejemplos:

```text
InvalidCourseTitle
CourseAlreadyPublished
CourseCannotBeArchived
```

### Excepciones de aplicación

```text
Application/Exceptions/
```

Ejemplos:

```text
CourseNotFound
CourseCreationFailed
```

Las excepciones técnicas deben ser transformadas antes de cruzar hacia capas superiores cuando sea necesario.

---

## 14. Pruebas

Cada módulo utilizará:

```text
Tests/Unit/
Tests/Feature/
Tests/Integration/
```

### 14.1 Pruebas unitarias

Las pruebas unitarias validan:

* entidades;
* agregados;
* objetos de valor;
* reglas de negocio;
* servicios de dominio.

No deben depender de:

* Laravel;
* base de datos;
* red;
* servicios externos.

### 14.2 Pruebas Feature

Las pruebas Feature validan:

* endpoints;
* autenticación;
* autorización;
* validación de solicitudes;
* respuestas HTTP;
* comportamiento completo de un caso de uso.

### 14.3 Pruebas de integración

Las pruebas de integración validan:

* repositorios;
* PostgreSQL;
* Redis;
* proveedores externos;
* adaptadores;
* persistencia y reconstrucción de agregados.

Las pruebas del módulo deben ser detectadas por la configuración global de Pest.

---

## 15. Calidad obligatoria

Antes de confirmar un cambio, deben ejecutarse:

```bash
docker compose exec app composer test
docker compose exec app composer analyse
docker compose exec app composer quality
```

El cambio no debe confirmarse en Git mientras alguna validación permanezca en rojo.

También puede ejecutarse Pint sobre un módulo específico:

```bash
docker compose exec app php vendor/bin/pint modules/Academic
```

---

## 16. Comunicación entre módulos

Un módulo no debe acceder directamente a:

* tablas internas de otro módulo;
* modelos Eloquent internos de otro módulo;
* clases de infraestructura de otro módulo.

La comunicación deberá realizarse mediante:

* contratos públicos;
* servicios de aplicación;
* eventos;
* DTO compartidos y explícitamente aprobados;
* interfaces definidas para integración.

Ejemplo incorrecto:

```php
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
```

desde el módulo `Academic`.

Ejemplo recomendado:

```php
use Modules\Identity\Application\Contracts\UserDirectory;
```

Esto protege la autonomía de los módulos.

---

## 17. Dependencias permitidas

Las dependencias generales seguirán esta dirección:

```text
Presentation
    ↓
Application
    ↓
Domain
```

Infrastructure puede implementar contratos de Domain y Application:

```text
Infrastructure
    ↓
Application / Domain
```

El dominio nunca debe depender de infraestructura.

---

## 18. Estado inicial de un módulo

Un módulo nuevo debe incluir como mínimo:

```text
modules/{ModuleName}/
├── Infrastructure/
│   └── Providers/
│       └── {ModuleName}ServiceProvider.php
│
├── Presentation/
│   └── Routes/
│       └── api.php
│
└── Tests/
    └── Feature/
```

También debe:

1. registrarse en `bootstrap/providers.php`;
2. exponer un endpoint de estado durante su incorporación inicial;
3. incluir una prueba Feature;
4. pasar Composer, Pest, PHPStan y Pint;
5. documentarse en la bitácora de ingeniería.

El endpoint de estado podrá eliminarse posteriormente cuando el módulo tenga endpoints funcionales y observabilidad suficiente.

---

## 19. Módulo Academic como referencia

El módulo `Academic` será el primer módulo alineado completamente con este estándar.

Su proveedor oficial será:

```text
Modules\Academic\Infrastructure\Providers\AcademicServiceProvider
```

Su endpoint inicial es:

```text
GET /api/v1/academic/status
```

Este módulo servirá como referencia para la creación y normalización de los módulos futuros.

---

## 20. Decisiones pendientes

Las siguientes decisiones se documentarán en entregas posteriores:

* bus de comandos y consultas;
* estrategia definitiva de CQRS;
* publicación de eventos de dominio;
* transacciones entre agregados;
* consistencia eventual;
* contratos entre módulos;
* versionado de eventos;
* outbox pattern;
* factories y builders de pruebas.

Hasta que exista una decisión formal, se preferirán implementaciones simples, explícitas y fáciles de probar.

---

## 21. Criterio de cumplimiento

Un módulo cumple este estándar cuando:

* respeta las capas definidas;
* mantiene las reglas de negocio fuera de Laravel;
* registra su proveedor correctamente;
* carga sus propias rutas y migraciones;
* no accede a detalles internos de otros módulos;
* posee pruebas apropiadas;
* pasa todas las verificaciones de calidad;
* mantiene namespaces y rutas consistentes.

---

## 22. Control de cambios

| Versión | Fecha      | Descripción                                                      |
| ------- | ---------- | ---------------------------------------------------------------- |
| 1.0.0   | 2026-07-29 | Definición inicial del estándar modular del backend de EDUDRIVE. |
