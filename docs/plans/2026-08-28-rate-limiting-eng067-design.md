# ENG-067 — Rate limiting: alcance acordado

Primera historia de la Fase 14 — Seguridad y cumplimiento. A diferencia de las historias de reportes recientes, Laravel ya trae soporte de primera clase para esto (`throttle:` + `RateLimiter::for()`), por lo que esta historia es mecánica y no requiere construir infraestructura nueva de agregación.

## Estado previo encontrado (investigación, no una decisión del usuario)

- **No existe ningún rate limiting hoy**: sin `throttle` en ninguna ruta, sin `RateLimiter::for()`, sin alias registrado en `bootstrap/app.php` — completamente *greenfield*.
- **Login**: `POST /api/v1/auth/login` (API) y `POST /login` (web, `LoginWebController`), ambos sin autenticación previa (esperado).
- **Registro**: `POST /api/v1/auth/register`; además se encontró `POST /api/v1/auth/users/{userId}/activate` (`ActivateUserController`) **también sin autenticación** — un endpoint público no identificado antes de investigar, presumiblemente para un flujo de activación por enlace de correo. `POST /api/v1/users/import` (bulk, ENG-061) es un caso distinto: requiere `auth:sanctum` + `users.manage`, es aprovisionamiento por un administrador autenticado, no autoservicio anónimo — no comparte el mismo modelo de amenaza que "Registro".
- **Recuperación de contraseña no existe en absoluto**: ninguna ruta, controlador, ni lógica la implementa; `password_reset_tokens` es una tabla del scaffold de Laravel sin ningún consumidor.
- **Integraciones**: exactamente dos rutas autenticadas con `simulator.auth` — `POST /api/v1/simulation/sessions/{sessionId}/telemetry` y `.../decisions`. `AuthenticateSimulator` ya adjunta `authenticated_simulator_id` a los atributos de la petición tras autenticar, disponible para limitar por simulador en vez de por IP.
- **Endpoints públicos**: `GET /api/v1/certification/verify/{validationCode}` (verificación pública de certificados) además de login/registro/activación (ya cubiertos arriba). Los `/status` de cada módulo se excluyen (health checks informativos).
- `CACHE_STORE` de producción es `database` (no Redis, aunque el contenedor ya corre); en pruebas es `array` (aislado por prueba, ya que Laravel crea una aplicación nueva por método de prueba).

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Recuperación de contraseña**: diferida por completo — no se puede aplicar rate limiting a una funcionalidad que no existe. Documentado para que, cuando se construya en una historia futura, incluya rate limiting desde el diseño inicial.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Limitadores nombrados registrados en `Modules\Foundation`** (`FoundationServiceProvider::boot()`, ya existente y hasta ahora vacío) — mismo criterio que otras infraestructuras compartidas de la sesión (`CsvWriter`, bus de comandos/consultas): es una preocupación transversal, no de un módulo de negocio específico.
- **`login` limitado por correo + IP** (`Limit::perMinute(5)->by(email|ip)`), no solo por IP — mismo patrón estándar de Laravel contra *credential stuffing* dirigido a una cuenta específica desde múltiples IPs, o contra múltiples cuentas desde una IP.
- **`register`/`activate` limitados por IP** (5/min y 10/min respectivamente — activación es más permisiva porque un usuario legítimo puede reintentar un enlace de correo varias veces sin ser un ataque).
- **`public-verification` limitado por IP** (30/min) para la verificación pública de certificados.
- **`simulator-integration` limitado por el simulador autenticado**, no por IP (60/min) — varios simuladores en un mismo laboratorio comparten NAT/IP; limitar por IP penalizaría a todos por igual. Se aplica después de `simulator.auth` en la cadena de middleware para poder leer `authenticated_simulator_id`.
- **Respuesta 429 con el mismo formato `ApiErrorResponse`** que el resto de errores (código `TOO_MANY_REQUESTS`) — nuevo manejador dedicado en `bootstrap/app.php` para `Illuminate\Http\Exceptions\ThrottleRequestsException`, mismo patrón que los manejadores ya existentes de `ValidationException`/`AuthenticationException`/`DomainException`. No se reenvían los encabezados `Retry-After`/`X-RateLimit-*` del limitador — ningún manejador existente en este archivo reenvía encabezados tampoco, así que se mantiene la misma convención en vez de introducir un caso especial.
- **No se cambia `CACHE_STORE` de la aplicación**: cambiar el almacén de caché global es una decisión de infraestructura más amplia que "agregar rate limiting" a rutas específicas; el limitador funciona correctamente con cualquier almacén, solo más lento bajo carga alta con `database` — queda como una optimización diferida, no un bloqueante.

## Incluye (del roadmap)

- Login.
- Registro.
- Integraciones.
- Endpoints públicos.

## Diferido explícitamente

- Recuperación de contraseña (la funcionalidad no existe).
- Cambiar `CACHE_STORE` a Redis para rate limiting de mayor rendimiento.
- Límites configurables por entorno/panel administrativo (los valores quedan fijos en código).
