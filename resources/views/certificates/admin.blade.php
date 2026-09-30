<x-layouts.app title="EDUDRIVE — Gestión de certificados">
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Gestión de certificados</h1>

        @if (session('status'))
            <p class="font-sans text-sm text-success-text">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="font-sans text-sm text-danger-text">{{ session('error') }}</p>
        @endif

        <x-ui.card>
            <h2 class="mb-3 font-heading text-lg font-bold">Buscar certificado</h2>
            <form method="GET" action="{{ route('certificates.admin.search') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex flex-1 flex-col gap-1">
                    <label for="certificate_id" class="font-sans text-sm font-medium text-text">Certificado</label>
                    <select id="certificate_id" name="certificate_id" required class="min-h-[44px] rounded-sm border border-border bg-surface px-3">
                        <option value="">Selecciona un certificado</option>
                        @foreach ($certificateOptions as $option)
                            <option value="{{ $option['id'] }}" @selected($searchedCertificateId === $option['id'])>
                                {{ $option['student'] }} — {{ $option['course'] }} — {{ $option['code'] }}{{ $option['status'] === 'revoked' ? ' (revocado)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <x-ui.button type="submit">Buscar</x-ui.button>
            </form>
        </x-ui.card>

        @if ($notFound)
            <x-ui.card><p class="font-sans text-sm text-danger-text">No encontramos ese certificado.</p></x-ui.card>
        @endif

        @if ($certificate)
            @php
                $statusLabels = ['issued' => 'Emitido', 'revoked' => 'Revocado'];
                $statusVariants = ['issued' => 'success', 'revoked' => 'danger'];
            @endphp
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1 font-sans text-sm">
                        <p><span class="text-text-secondary">Estudiante:</span> {{ $certificateUserName ?? 'Usuario eliminado' }}</p>
                        <p><span class="text-text-secondary">Curso:</span> {{ $certificateCourseName ?? 'Curso no disponible' }}</p>
                        <p><span class="text-text-secondary">Código:</span> {{ $certificate['validation_code'] }}</p>
                        <p><span class="text-text-secondary">Emitido:</span> {{ $certificate['issued_at'] }}</p>
                        <p><span class="text-text-secondary">Vence:</span> {{ $certificate['expires_at'] ?? 'Sin vencimiento' }}</p>
                    </div>
                    <x-ui.badge :variant="$statusVariants[$certificate['status']] ?? 'info'">{{ $statusLabels[$certificate['status']] ?? $certificate['status'] }}</x-ui.badge>
                </div>

                @if ($canManage && $certificate['status'] !== 'revoked')
                    <form method="POST" action="{{ route('certificates.admin.revoke', $certificate['id']) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end" onsubmit="return confirm('¿Seguro que querés revocar este certificado?');">
                        @csrf
                        <div class="flex-1"><x-ui.input name="reason" label="Motivo (opcional)" /></div>
                        <x-ui.button type="submit" variant="danger">Revocar</x-ui.button>
                    </form>
                @endif
            </x-ui.card>
        @endif

        @if ($canManage)
            <x-ui.card>
                <h2 class="mb-3 font-heading text-lg font-bold">Emitir certificado</h2>
                <form method="POST" action="{{ route('certificates.admin.issue') }}" class="grid gap-3 sm:grid-cols-2">
                    @csrf
                    <div class="flex flex-col gap-1">
                        <label for="user_id" class="font-sans text-sm font-medium text-text">Estudiante</label>
                        <select id="user_id" name="user_id" required class="min-h-[44px] rounded-sm border border-border bg-surface px-3">
                            <option value="">Selecciona un estudiante</option>
                            @foreach ($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') === $user->id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach
                        </select>
                        @error('user_id')<p class="font-sans text-sm text-danger-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="course_id" class="font-sans text-sm font-medium text-text">Curso</label>
                        <select id="course_id" name="course_id" required class="min-h-[44px] rounded-sm border border-border bg-surface px-3">
                            <option value="">Selecciona un curso</option>
                            @foreach ($courses as $course)<option value="{{ $course->id }}" @selected(old('course_id') === $course->id)>{{ $course->title }}</option>@endforeach
                        </select>
                        @error('course_id')<p class="font-sans text-sm text-danger-text">{{ $message }}</p>@enderror
                    </div>
                    <x-ui.input name="expires_at" type="date" label="Fecha de vencimiento (opcional)" value="{{ old('expires_at') }}" :error="$errors->first('expires_at')" />
                    <div class="flex items-end"><x-ui.button type="submit">Emitir certificado</x-ui.button></div>
                </form>
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
