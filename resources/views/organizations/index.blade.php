<x-layouts.app title="EDUDRIVE — Organizaciones">
    <div class="flex flex-col gap-6">
        <div class="campus-heading flex flex-wrap items-center justify-between gap-4">
            <h1 class="font-heading text-2xl font-bold">Organizaciones</h1>
            @if ($canManage)
                <a
                    href="{{ route('organizations.create') }}"
                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-sm bg-primary px-4 font-sans font-medium text-white transition hover:bg-secondary focus-visible:outline-none focus-visible:shadow-focus"
                >
                    Nueva organización
                </a>
            @endif
        </div>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        @if ($canReviewMemberships && count($membershipRequests) > 0)
            <section class="rounded-xl border border-warning/30 bg-safety/10 p-5" aria-labelledby="membership-requests-title">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wide text-warning-text">Requieren revisión</p><h2 id="membership-requests-title" class="mt-1 font-heading text-xl font-bold">Solicitudes de estudiantes</h2></div><span class="rounded-full bg-warning/15 px-3 py-1 text-xs font-bold text-warning-text">{{ count($membershipRequests) }} pendiente{{ count($membershipRequests) === 1 ? '' : 's' }}</span></div>
                <div class="mt-4 space-y-2">
                    @foreach ($membershipRequests as $membershipRequest)
                        <article class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border bg-surface p-4"><div><p class="font-bold text-text">{{ $membershipRequest['student_name'] }}</p><p class="mt-1 text-sm text-text-secondary">{{ $membershipRequest['organization_name'] }} · solicitada {{ $membershipRequest['requested_at'] }}</p></div><div class="flex gap-2"><form method="POST" action="{{ route('organizations.memberships.approve', $membershipRequest['id']) }}">@csrf<button type="submit" class="min-h-10 rounded-md bg-primary px-4 text-sm font-bold text-white hover:bg-secondary">Aprobar</button></form><form method="POST" action="{{ route('organizations.memberships.reject', $membershipRequest['id']) }}">@csrf<button type="submit" class="min-h-10 rounded-md border border-danger px-4 text-sm font-bold text-danger-text hover:bg-danger/10">Rechazar</button></form></div></article>
                    @endforeach
                </div>
            </section>
        @endif

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-4 py-2">Nombre</th>
                    <th scope="col" class="px-4 py-2">Tipo</th>
                    <th scope="col" class="px-4 py-2">Administración responsable</th>
                    <th scope="col" class="px-4 py-2">Estudiantes</th>
                    <th scope="col" class="px-4 py-2">Sedes</th>
                </tr>
            </x-slot:head>
            @forelse ($organizations as $organization)
                <tr>
                    <td class="px-4 py-2">{{ $organization['name'] }}</td>
                    <td class="px-4 py-2">{{ \Modules\Organization\Domain\Enums\OrganizationType::from($organization['type'])->label() }}</td>
                    <td class="px-4 py-2 text-sm">
                        @forelse ($organization['administrators'] as $administrator)
                            <strong>{{ $administrator['name'] }}</strong>@if ($administrator['email'])<br><span class="text-xs text-text-secondary">{{ $administrator['email'] }}</span>@endif
                        @empty
                            <span class="font-medium text-warning-text">Sin administrador asignado</span>
                        @endforelse
                    </td>
                    <td class="px-4 py-2">{{ $organization['student_count'] }}</td>
                    <td class="px-4 py-2">{{ count($organization['campuses']) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-2 text-text-secondary" colspan="5">Todavía no hay organizaciones registradas.</td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>
</x-layouts.app>
