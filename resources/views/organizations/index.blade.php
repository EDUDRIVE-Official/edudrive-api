<x-layouts.app title="EDUDRIVE — Organizaciones">
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
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

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-4 py-2">Nombre</th>
                    <th scope="col" class="px-4 py-2">Tipo</th>
                    <th scope="col" class="px-4 py-2">Sedes</th>
                </tr>
            </x-slot:head>
            @forelse ($organizations as $organization)
                <tr>
                    <td class="px-4 py-2">{{ $organization['name'] }}</td>
                    <td class="px-4 py-2">{{ $organization['type'] }}</td>
                    <td class="px-4 py-2">{{ count($organization['campuses']) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-2 text-text-secondary" colspan="3">Todavía no hay organizaciones registradas.</td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>
</x-layouts.app>
