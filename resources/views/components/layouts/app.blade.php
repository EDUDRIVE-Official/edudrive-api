@props(['title' => 'EDUDRIVE'])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14161A">
    <title>{{ $title }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>
        (function () {
            var stored = localStorage.getItem('edudrive-theme');
            var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="edudrive-shell min-h-screen bg-background font-sans text-text">
    @unless(request()->routeIs('pilot-instruments.*'))
        <a href="#app-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-surface focus:p-4">Saltar al contenido</a>
    @endunless
    @if(request()->routeIs('pilot-instruments.*'))
        <a href="#pilot-main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-surface focus:p-4 focus:text-text focus:outline">Saltar al contenido del ensayo</a>
    @endif
    <header class="platform-header" x-data="{ mobileMenuOpen: false }" @keydown.escape.window="mobileMenuOpen = false">
        <div class="platform-header-inner mx-auto max-w-7xl px-6">
            <div class="flex min-h-16 items-center justify-between gap-6 py-3">
                <a href="{{ url('/') }}" class="shrink-0 rounded-sm focus-visible:outline-none focus-visible:shadow-focus" aria-label="EDUDRIVE — Inicio">
                    <span class="block sm:hidden">
                        <img src="{{ asset('brand/edudrive-mark.svg') }}" alt="EDUDRIVE" class="brand-logo-light h-11 w-11">
                        <img src="{{ asset('brand/edudrive-mark-dark.svg') }}" alt="EDUDRIVE" class="brand-logo-dark h-11 w-11">
                    </span>
                    <span class="hidden sm:block">
                        <img src="{{ asset('brand/edudrive-logo.svg') }}" alt="EDUDRIVE — Educación vial para la vida" class="brand-logo-light h-12 w-auto">
                        <img src="{{ asset('brand/edudrive-logo-dark.svg') }}" alt="EDUDRIVE — Educación vial para la vida" class="brand-logo-dark h-12 w-auto">
                    </span>
                </a>
                <div class="ml-auto flex items-center gap-3">
                    @auth
                        <span class="hidden max-w-48 truncate text-sm text-text-secondary lg:inline" title="{{ auth()->user()->email }}">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" size="sm">Cerrar sesión</x-ui.button>
                        </form>
                    @endauth
                    <button
                        type="button"
                        x-data="{ theme: document.documentElement.getAttribute('data-theme') }"
                        x-init="$watch('theme', value => { document.documentElement.setAttribute('data-theme', value); localStorage.setItem('edudrive-theme', value); })"
                        @click="theme = theme === 'dark' ? 'light' : 'dark'"
                        :aria-pressed="theme === 'dark'"
                        aria-label="Cambiar entre modo claro y oscuro"
                        class="inline-flex min-h-12 shrink-0 items-center gap-2 rounded-sm border-2 border-border px-3 text-base font-semibold text-text hover:border-text hover:bg-background focus-visible:outline-none focus-visible:shadow-focus"
                    >
                        <span aria-hidden="true" x-show="theme === 'dark'"><x-ui.icon name="sun" /></span>
                        <span aria-hidden="true" x-show="theme !== 'dark'"><x-ui.icon name="moon" /></span>
                        <span class="hidden sm:inline" x-text="theme === 'dark' ? 'Pasar a modo claro' : 'Pasar a modo oscuro'"></span>
                    </button>
                    @auth
                        <button
                            type="button"
                            class="inline-flex min-h-12 items-center gap-2 rounded-sm border-[3px] border-text px-4 text-base font-bold text-text hover:bg-background focus-visible:outline-none focus-visible:shadow-focus md:hidden"
                            @click="mobileMenuOpen = ! mobileMenuOpen"
                            :aria-expanded="mobileMenuOpen"
                            aria-controls="mobile-navigation"
                        >
                            <x-ui.icon name="menu" />
                            <span>Menú</span>
                        </button>
                    @endauth
                </div>
            </div>
            @auth
                @php
                    $permissionChecker = app(\Modules\Authorization\Application\Services\PermissionChecker::class);
                    $authenticatedUserId = (string) auth()->id();
                    $hasPermission = static fn (\Modules\Authorization\Domain\Enums\Permission $permission): bool => $permissionChecker->userHasPermission($authenticatedUserId, $permission);
                    $personalLinks = array_values(array_filter([
                        ['Mi perfil', route('student-profile.show'), request()->routeIs('student-profile.*')],
                        $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ViewCourses) ? ['Cursos', route('courses.index'), request()->routeIs('courses.*')] : null,
                        ['Pasaporte', route('road-passport.show'), request()->routeIs('road-passport.show')],
                        ['Certificados', route('certificates.index'), request()->routeIs('certificates.index', 'certificates.show')],
                        ['Mi progreso', route('gamification.dashboard'), request()->routeIs('gamification.*')],
                        ['Notificaciones', route('notifications.index'), request()->routeIs('notifications.index')],
                    ]));
                    $moreLinks = [
                        ['Simulaciones', route('simulations.mine')],
                        ['Mi acompañamiento', route('guardians.web.index')],
                        ['Consentimientos', route('legal.consents.index')],
                        ['Dispositivos', route('mobile.devices.index')],
                        ['Archivos', route('files.index')],
                    ];
                    $adminLinks = array_values(array_filter([
                            $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ViewOrganizations) ? ['Organizaciones', route('organizations.index')] : null,
                            $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ViewUsers) ? ['Usuarios', route('users.index')] : null,
                            $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ManageRoleAssignments) ? ['Asignar roles', route('roles.assign')] : null,
                            $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ViewRoadPassports) ? ['Pasaportes viales', route('road-passport.admin.search')] : null,
                            $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ViewCertifications) ? ['Certificados', route('certificates.admin.search')] : null,
                            $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ViewReports) ? ['Resumen del sistema', route('admin.system.summary')] : null,
                            $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ViewApiConsumers) ? ['Integraciones', route('integrations.index')] : null,
                            $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ViewWebhooks) ? ['Webhooks', route('webhooks.index')] : null,
                            $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ViewAiGovernance) ? ['Gobierno de IA', route('ai-governance.dashboard')] : null,
                            $hasPermission(\Modules\Authorization\Domain\Enums\Permission::ViewAnalytics) ? ['Analítica', route('analytics.index')] : null,
                    ]));
                @endphp
                <div class="platform-navigation hidden md:block">
                    <nav class="flex flex-wrap items-center gap-1" aria-label="Navegación principal">
                        @foreach ($personalLinks as [$label, $url, $active])
                            <a href="{{ $url }}" @if ($active) aria-current="page" @endif>
                                <span class="navigation-symbol" aria-hidden="true"><x-ui.icon :name="match($label) { 'Mi perfil' => 'user', 'Cursos' => 'book', 'Pasaporte' => 'passport', 'Certificados' => 'certificate', 'Mi progreso' => 'progress', default => 'bell' }" /></span>
                                <span>{{ $label }}</span>
                                @if ($label === 'Notificaciones' && $unreadNotificationCount > 0)
                                    <span class="inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-danger px-1.5 text-xs font-bold text-white" aria-label="{{ $unreadNotificationCount }} {{ $unreadNotificationCount === 1 ? 'notificación sin leer' : 'notificaciones sin leer' }}">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                                @endif
                            </a>
                        @endforeach
                        <details class="relative navigation-dropdown" @click.outside="$el.open = false" @keydown.escape.stop="$el.open = false; $el.querySelector('summary').focus()">
                            <summary class="navigation-trigger" @if(collect($moreLinks)->contains(fn ($link) => $link[1] === url()->current())) data-active="true" @endif>
                                <span class="navigation-symbol" aria-hidden="true"><x-ui.icon name="more" /></span>
                                <span>Más <span class="navigation-chevron" aria-hidden="true"><x-ui.icon name="chevron" size="sm" class="rotate-90" /></span></span>
                            </summary>
                            <div class="absolute left-0 z-20 mt-2 flex min-w-48 flex-col rounded-md border border-border bg-surface p-2 shadow-lg">
                                @foreach ($moreLinks as [$label, $url])<a href="{{ $url }}" class="rounded-sm px-3 py-2 text-sm hover:bg-background">{{ $label }}</a>@endforeach
                            </div>
                        </details>
                        @if (count($adminLinks) > 0)
                            <details class="relative navigation-dropdown" @click.outside="$el.open = false" @keydown.escape.stop="$el.open = false; $el.querySelector('summary').focus()">
                                <summary class="navigation-trigger" @if(collect($adminLinks)->contains(fn ($link) => $link[1] === url()->current())) data-active="true" @endif>
                                    <span class="navigation-symbol" aria-hidden="true"><x-ui.icon name="admin" /></span>
                                    <span>Administración <span class="navigation-chevron" aria-hidden="true"><x-ui.icon name="chevron" size="sm" class="rotate-90" /></span></span>
                                </summary>
                                <div class="absolute right-0 z-20 mt-2 flex min-w-56 flex-col rounded-md border border-border bg-surface p-2 shadow-lg">
                                    @foreach ($adminLinks as [$label, $url])
                                        <a href="{{ $url }}" class="rounded-sm px-3 py-2 text-sm hover:bg-background">{{ $label }}</a>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </nav>
                </div>
                <nav
                    id="mobile-navigation"
                    x-cloak
                    x-show="mobileMenuOpen"
                    @click.outside="mobileMenuOpen = false"
                    class="border-t border-border py-4 md:hidden"
                    aria-label="Navegación móvil"
                >
                    <p class="mb-3 truncate text-sm font-semibold text-text">{{ auth()->user()->name }}</p>
                    <div class="grid gap-1">
                        @foreach ($personalLinks as [$label, $url, $active])
                            <a href="{{ $url }}" @class(['flex min-h-12 items-center justify-between rounded-md px-3 py-2 text-base font-semibold', 'bg-accent text-[#14161a]' => $active, 'text-text hover:bg-background' => ! $active]) @if ($active) aria-current="page" @endif>
                                <span>{{ $label }}</span>
                                @if ($label === 'Notificaciones' && $unreadNotificationCount > 0)<span class="rounded-full bg-danger px-2 py-0.5 text-xs font-bold text-white" aria-label="{{ $unreadNotificationCount }} {{ $unreadNotificationCount === 1 ? 'notificación sin leer' : 'notificaciones sin leer' }}">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@endif
                            </a>
                        @endforeach
                    </div>
                    <p class="mb-1 mt-4 px-3 text-xs font-bold uppercase tracking-wider text-text-secondary">Más opciones</p>
                    <div class="grid gap-1">@foreach ($moreLinks as [$label, $url])<a href="{{ $url }}" class="flex min-h-12 items-center rounded-md px-3 py-2.5 text-base text-text hover:bg-background">{{ $label }}</a>@endforeach</div>
                    @if (count($adminLinks) > 0)
                        <p class="mb-1 mt-4 px-3 text-xs font-bold uppercase tracking-wider text-primary">Administración</p>
                        <div class="grid gap-1">@foreach ($adminLinks as [$label, $url])<a href="{{ $url }}" class="flex min-h-12 items-center rounded-md px-3 py-2.5 text-base text-text hover:bg-background">{{ $label }}</a>@endforeach</div>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="mt-4 border-t border-border pt-4">
                        @csrf
                        <button type="submit" class="min-h-12 w-full rounded-md border-2 border-border px-4 text-left text-base font-semibold text-text hover:bg-background">Cerrar sesión</button>
                    </form>
                </nav>
            @endauth
        </div>
    </header>
    <main @if(request()->routeIs('pilot-instruments.*')) id="pilot-main" @else id="app-content" @endif tabindex="-1" class="platform-content mx-auto max-w-5xl px-6 py-8">
        {{ $slot }}
    </main>
    <footer class="platform-footer"><span>EDUDRIVE</span> Educación vial para la vida <span aria-hidden="true">·</span> Observar. Decidir. Convivir.</footer>
</body>
</html>
