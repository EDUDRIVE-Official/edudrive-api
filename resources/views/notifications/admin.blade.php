<x-layouts.app title="EDUDRIVE — Enviar notificación">
    <div class="mx-auto max-w-2xl space-y-6">
        <div><h1 class="font-heading text-2xl font-bold">Enviar notificación</h1><p class="mt-1 text-sm text-text-secondary">Creá un aviso directo para una persona usuaria.</p></div>
        @if (session('status'))<p class="text-sm text-success-text">{{ session('status') }}</p>@endif
        @if (session('error'))<p class="text-sm text-danger-text">{{ session('error') }}</p>@endif
        <x-ui.card>
            <form method="POST" action="{{ route('notifications.admin.store') }}" class="space-y-4">
                @csrf
                <label class="flex flex-col gap-1 text-sm font-medium">Destinatario
                    <select name="user_id" required class="min-h-[44px] rounded-sm border border-border bg-surface px-3 text-base">
                        <option value="">Seleccioná una persona</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') === $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                        @endforeach
                    </select>
                    @error('user_id')<span class="text-sm text-danger-text">{{ $message }}</span>@enderror
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium">Canal
                    <select name="channel" required class="min-h-[44px] rounded-sm border border-border bg-surface px-3 text-base">
                        @foreach ($channels as $channel)<option value="{{ $channel->value }}" @selected(old('channel', 'web') === $channel->value)>{{ $channelLabels[$channel->value] }}</option>@endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium">Propósito del aviso
                    <select name="category" required class="min-h-[44px] rounded-sm border border-border bg-surface px-3 text-base">
                        <option value="">Seleccioná un propósito</option>
                        @foreach ($categoryLabels as $category => $label)<option value="{{ $category }}" @selected(old('category') === $category)>{{ $label }}</option>@endforeach
                    </select>
                    @error('category')<span class="text-sm text-danger-text">{{ $message }}</span>@enderror
                </label>
                <x-ui.input name="subject" label="Asunto" value="{{ old('subject') }}" maxlength="255" required :error="$errors->first('subject')" />
                <label class="flex flex-col gap-1 text-sm font-medium">Mensaje
                    <textarea name="body" rows="6" required class="rounded-sm border border-border bg-surface px-3 py-2 text-base">{{ old('body') }}</textarea>
                    @error('body')<span class="text-sm text-danger-text">{{ $message }}</span>@enderror
                </label>
                <x-ui.button type="submit">Enviar notificación</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
