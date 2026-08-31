<x-layouts.app title="EDUDRIVE — Nuevo curso">
    <div class="mx-auto flex max-w-xl flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Nuevo curso</h1>

        <x-ui.card>
            <form method="POST" action="{{ route('courses.store') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.input
                    name="code"
                    label="Código"
                    value="{{ old('code') }}"
                    :error="$errors->first('code')"
                />

                <x-ui.input
                    name="title"
                    label="Título"
                    value="{{ old('title') }}"
                    :error="$errors->first('title')"
                />

                <div class="flex flex-col gap-1">
                    <label for="description" class="font-sans text-sm font-medium text-text">Descripción</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        class="rounded-sm border border-border bg-surface px-3 py-2 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="objectives" class="font-sans text-sm font-medium text-text">Objetivos</label>
                    <textarea
                        id="objectives"
                        name="objectives"
                        rows="3"
                        class="rounded-sm border border-border bg-surface px-3 py-2 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >{{ old('objectives') }}</textarea>
                    @error('objectives')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="prerequisites" class="font-sans text-sm font-medium text-text">Requisitos</label>
                    <textarea
                        id="prerequisites"
                        name="prerequisites"
                        rows="3"
                        class="rounded-sm border border-border bg-surface px-3 py-2 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >{{ old('prerequisites') }}</textarea>
                    @error('prerequisites')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="modality" class="font-sans text-sm font-medium text-text">Modalidad</label>
                    <select
                        id="modality"
                        name="modality"
                        class="min-h-[44px] rounded-sm border border-border bg-surface px-3 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >
                        <option value="" @selected(old('modality') === null)>Sin especificar</option>
                        @foreach ($modalities as $modality)
                            <option value="{{ $modality->value }}" @selected(old('modality') === $modality->value)>
                                {{ $modality->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('modality')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.input
                    name="duration_hours"
                    type="number"
                    label="Duración (horas)"
                    value="{{ old('duration_hours') }}"
                    :error="$errors->first('duration_hours')"
                />

                <x-ui.button type="submit" variant="primary">Crear</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
