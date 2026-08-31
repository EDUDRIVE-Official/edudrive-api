<x-layouts.app title="EDUDRIVE — Cursos">
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <h1 class="font-heading text-2xl font-bold">Cursos</h1>
            @if ($canManage)
                <a
                    href="{{ route('courses.create') }}"
                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-sm bg-primary px-4 font-sans font-medium text-white transition hover:bg-secondary focus-visible:outline-none focus-visible:shadow-focus"
                >
                    Nuevo curso
                </a>
            @endif
        </div>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-4 py-2">Código</th>
                    <th scope="col" class="px-4 py-2">Título</th>
                    <th scope="col" class="px-4 py-2">Modalidad</th>
                    <th scope="col" class="px-4 py-2">Duración</th>
                    <th scope="col" class="px-4 py-2">Estado</th>
                </tr>
            </x-slot:head>
            @forelse ($courses as $course)
                <tr>
                    <td class="px-4 py-2">{{ $course['code'] }}</td>
                    <td class="px-4 py-2">{{ $course['title'] }}</td>
                    <td class="px-4 py-2">
                        {{ $course['modality'] ? \Modules\Academic\Domain\Enums\CourseModality::from($course['modality'])->label() : '—' }}
                    </td>
                    <td class="px-4 py-2">{{ $course['duration_hours'] ?? '—' }}</td>
                    <td class="px-4 py-2">
                        @php
                            $status = \Modules\Academic\Domain\Enums\CourseStatus::from($course['status']);
                            $statusVariant = match ($status) {
                                \Modules\Academic\Domain\Enums\CourseStatus::Draft => 'warning',
                                \Modules\Academic\Domain\Enums\CourseStatus::UnderReview => 'info',
                                \Modules\Academic\Domain\Enums\CourseStatus::Approved => 'info',
                                \Modules\Academic\Domain\Enums\CourseStatus::Published => 'success',
                                \Modules\Academic\Domain\Enums\CourseStatus::Archived => 'danger',
                            };
                        @endphp
                        <x-ui.badge :variant="$statusVariant">{{ $status->label() }}</x-ui.badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-2 text-text-secondary" colspan="5">Todavía no hay cursos registrados.</td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>
</x-layouts.app>
