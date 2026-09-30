<x-layouts.app title="EDUDRIVE — Mis notificaciones">
    @php
        $notificationCollection = collect($notifications);
        $unreadCount = $notificationCollection->where('status', 'unread')->count();
        $categoryLabels = ['curso' => 'Curso', 'logro' => 'Logro', 'certificado' => 'Certificado', 'practica_lista' => 'Práctica familiar', 'acompañamiento' => 'Acompañamiento', 'reflexion_acompañamiento' => 'Reflexión del estudiante', 'sistema' => 'Sistema', 'recordatorio' => 'Recordatorio', 'seguridad' => 'Seguridad'];
        $channelLabels = ['web' => 'En EDUDRIVE', 'email' => 'Correo electrónico', 'mobile' => 'Aviso móvil', 'internal_message' => 'Mensaje interno'];
        $categoryIcons = ['curso' => '📘', 'logro' => '🏆', 'certificado' => '🏅', 'practica_lista' => '🧭', 'acompañamiento' => '🤝', 'reflexion_acompañamiento' => '💭', 'sistema' => 'ℹ️', 'recordatorio' => '⏰', 'seguridad' => '🛡️'];
    @endphp
    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <section class="campus-hero relative overflow-hidden rounded-xl bg-[#0b3a6e] px-6 py-8 text-white shadow-md sm:px-8" aria-labelledby="notifications-title">
            <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-[#008a78]/55" aria-hidden="true"></div>
            <div class="absolute -bottom-24 right-32 h-48 w-48 rounded-full bg-[#f5b700]/20" aria-hidden="true"></div>
            <div class="relative flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                <div><p class="text-sm font-bold uppercase tracking-[0.16em] text-[#5bd6c0]">Tu actividad</p><h1 id="notifications-title" class="mt-2 font-heading text-3xl font-bold sm:text-4xl">Mis notificaciones</h1><p class="mt-3 max-w-2xl text-sm leading-6 text-white/85">Avisos útiles para continuar cursos, reconocer avances y completar prácticas o reflexiones pendientes.</p></div>
                <div class="flex shrink-0 gap-3 text-center"><div class="rounded-xl bg-white/10 px-5 py-3"><p class="font-heading text-3xl font-bold">{{ count($notifications) }}</p><p class="text-xs text-white/70">Recibidas</p></div><div class="rounded-xl bg-white/10 px-5 py-3"><p class="font-heading text-3xl font-bold text-[#ffd45a]">{{ $unreadCount }}</p><p class="text-xs text-white/70">Sin leer</p></div></div>
            </div>
        </section>

        @if (session('status'))
            <p class="text-sm text-success-text">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="text-sm text-danger-text">{{ session('error') }}</p>
        @endif

        <section class="space-y-3" aria-labelledby="inbox-title">
            <div class="flex items-center justify-between">
                <h2 id="inbox-title" class="font-heading text-xl font-bold">Bandeja</h2>
                <span class="rounded-full bg-primary/10 px-3 py-1 text-sm font-semibold text-primary">{{ $unreadCount }} sin leer</span>
            </div>
            @forelse ($notifications as $notification)
                <article @class(['rounded-xl border bg-surface p-5 shadow-sm transition hover:shadow-md', 'border-l-4 border-l-primary border-y-border border-r-border' => $notification['status'] === 'unread', 'border-border opacity-85' => $notification['status'] !== 'unread'])>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xl" aria-hidden="true">{{ $categoryIcons[$notification['category']] ?? '🔔' }}</span>
                            <div>
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <h3 class="font-heading font-bold">{{ $notification['subject'] }}</h3>
                                <x-ui.badge :variant="$notification['status'] === 'unread' ? 'warning' : 'success'">
                                    {{ $notification['status'] === 'unread' ? 'Sin leer' : 'Leída' }}
                                </x-ui.badge>
                            </div>
                            <p class="text-xs text-text-secondary">{{ $categoryLabels[$notification['category']] ?? 'Información' }} · {{ $channelLabels[$notification['channel']] ?? 'Canal interno' }} · {{ \Illuminate\Support\Carbon::parse($notification['sent_at'])->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        @if ($notification['status'] === 'unread')
                            <form method="POST" action="{{ route('notifications.read', $notification['id']) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" size="sm">Marcar como leída</x-ui.button>
                            </form>
                        @endif
                    </div>
                    <p class="mt-4 whitespace-pre-line border-t border-border pt-4 text-sm leading-6 text-text-secondary">{{ $notification['body'] }}</p>
                    @if ($notification['action_url'] !== null)
                        <a href="{{ route('notifications.open', $notification['id']) }}" class="mt-3 inline-flex min-h-[44px] items-center text-sm font-bold text-primary hover:underline">Abrir actividad →</a>
                    @elseif ($notification['category'] === 'acompañamiento')
                        <a href="{{ route('road-passport.show') }}" class="mt-3 inline-flex text-sm font-bold text-primary hover:underline">Ver en mi Pasaporte Vial →</a>
                    @elseif ($notification['category'] === 'reflexion_acompañamiento')
                        <a href="{{ route('guardians.web.index') }}" class="mt-3 inline-flex text-sm font-bold text-primary hover:underline">Ver en Mi acompañamiento →</a>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-border bg-surface p-8 text-center"><p class="text-4xl" aria-hidden="true">🔔</p><h3 class="mt-3 font-heading font-bold">Tu bandeja está al día</h3><p class="mt-1 text-sm text-text-secondary">Todavía no tenés notificaciones.</p></div>
            @endforelse
        </section>

        <x-ui.card>
            <div><p class="text-xs font-bold uppercase tracking-wide text-primary">Control de comunicaciones</p><h2 class="mt-1 font-heading text-xl font-bold">Preferencias</h2><p class="mt-1 text-sm text-text-secondary">Elegí qué querés recibir, por cuál medio y en qué momento. Los avisos esenciales de seguridad pueden conservarse en la plataforma.</p></div>
            <form method="POST" action="{{ route('notifications.preferences.update') }}" class="mt-4 space-y-5">
                @csrf
                @method('PUT')
                <fieldset>
                    <legend class="mb-2 text-sm font-medium">Canales permitidos</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($channels as $channel)
                            <label class="flex min-h-[52px] cursor-pointer items-center gap-3 rounded-md border border-border bg-background px-3 hover:border-primary">
                                <input type="checkbox" name="allowed_channels[]" value="{{ $channel->value }}" @checked(in_array($channel->value, old('allowed_channels', $preference['allowed_channels']), true))>
                                <span class="font-medium">{{ $channelLabels[$channel->value] ?? 'Canal interno' }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('allowed_channels')<p class="mt-1 text-sm text-danger-text">{{ $message }}</p>@enderror
                </fieldset>

                <fieldset>
                    <legend class="mb-2 text-sm font-medium">Silenciar categorías</legend>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach (['logro', 'certificado', 'curso'] as $category)
                            <label class="flex min-h-[48px] cursor-pointer items-center gap-3 rounded-md border border-border bg-background px-3 hover:border-primary">
                                <input type="checkbox" name="muted_categories[]" value="{{ $category }}" @checked(in_array($category, old('muted_categories', $preference['muted_categories']), true))>
                                <span>{{ str($category)->title() }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="flex flex-col gap-1 text-sm font-medium">Frecuencia
                        <select name="frequency" class="min-h-[44px] rounded-sm border border-border bg-surface px-3 text-base">
                            @foreach ($frequencies as $frequency)
                                <option value="{{ $frequency->value }}" @selected(old('frequency', $preference['frequency']) === $frequency->value)>{{ ['immediate' => 'Inmediata', 'daily' => 'Diaria', 'weekly' => 'Semanal'][$frequency->value] ?? 'Inmediata' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <x-ui.input name="quiet_hours_start" label="Silencio desde" type="time" :value="old('quiet_hours_start', $preference['quiet_hours_start'])" :error="$errors->first('quiet_hours_start')" />
                    <x-ui.input name="quiet_hours_end" label="Silencio hasta" type="time" :value="old('quiet_hours_end', $preference['quiet_hours_end'])" :error="$errors->first('quiet_hours_end')" />
                </div>
                <x-ui.button type="submit">Guardar preferencias</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card class="border border-primary/20">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div><p class="text-xs font-bold uppercase tracking-wide text-primary">Privacidad</p><h2 class="mt-1 font-heading text-lg font-bold">Consentimiento de notificaciones</h2><p class="mt-1 text-sm text-text-secondary">Estado: <strong class="{{ $preference['consent_given'] ? 'text-success-text' : 'text-warning-text' }}">{{ $preference['consent_given'] ? 'activo' : 'desactivado' }}</strong></p></div>
                @if ($preference['consent_given'])
                    <form method="POST" action="{{ route('notifications.consent.revoke') }}">@csrf @method('DELETE')<x-ui.button type="submit" variant="danger">Desactivar</x-ui.button></form>
                @else
                    <form method="POST" action="{{ route('notifications.consent.give') }}">@csrf <x-ui.button type="submit">Activar</x-ui.button></form>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
