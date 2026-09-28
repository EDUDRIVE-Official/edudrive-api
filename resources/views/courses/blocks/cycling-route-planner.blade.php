<section
    class="overflow-hidden rounded-lg border border-border bg-surface"
    x-data="{
        weather: 'seco',
        selected: null,
        routes: {
            corta: { name: 'Ruta corta', distance: '1,2 km', conflicts: 6, surface: 'Variable', visibility: 'Media' },
            tranquila: { name: 'Ruta tranquila', distance: '1,7 km', conflicts: 2, surface: 'Regular', visibility: 'Alta' },
        },
        score(key) {
            const route = this.routes[key];
            let value = 100 - route.conflicts * 10;
            if (route.visibility === 'Media') value -= 15;
            if (route.surface === 'Variable') value -= this.weather === 'lluvia' ? 30 : 10;
            if (this.weather === 'lluvia' && key === 'corta') value -= 10;
            return Math.max(0, value);
        },
        verdict(key) {
            if (key === 'tranquila') return 'Ofrece menos conflictos y mejor visibilidad. La distancia adicional conserva más opciones.';
            return this.weather === 'lluvia'
                ? 'La superficie variable y la lluvia reducen demasiado el margen, aunque el trayecto sea corto.'
                : 'Es más corta, pero reúne más entradas, cruces y decisiones simultáneas.';
        },
    }"
>
    <header class="border-b border-border px-4 py-3">
        <p class="text-xs font-bold uppercase tracking-wide text-primary">Planificador interactivo</p>
        <h4 class="mt-1 font-heading text-lg font-bold">Compará la ruta, no solo la distancia</h4>
        <p class="mt-1 text-sm leading-6 text-text-secondary">Cambiá el clima, revisá las pistas y justificá una elección.</p>
    </header>

    <div class="relative min-h-64 overflow-hidden transition-colors duration-500 motion-reduce:transition-none" :class="weather === 'lluvia' ? 'bg-slate-400' : 'bg-emerald-100'" role="img" :aria-label="weather === 'lluvia' ? 'Mapa esquemático de dos rutas ciclistas bajo lluvia.' : 'Mapa esquemático de dos rutas ciclistas con clima seco.'">
        <div class="absolute left-8 top-1/2 h-16 w-16 -translate-y-1/2 rounded-lg bg-surface shadow-md" aria-hidden="true"><span class="grid h-full place-items-center text-3xl">🏠</span></div>
        <div class="absolute right-8 top-1/2 h-16 w-16 -translate-y-1/2 rounded-lg bg-surface shadow-md" aria-hidden="true"><span class="grid h-full place-items-center text-3xl">🏫</span></div>
        <div class="absolute left-24 right-24 top-[34%] h-2 rotate-[-7deg] bg-rose-400" aria-hidden="true"></div>
        <div class="absolute left-24 right-24 top-[63%] h-2 rotate-[8deg] bg-emerald-600" aria-hidden="true"></div>
        <span class="absolute left-[44%] top-[21%] text-3xl" aria-hidden="true">🚦</span><span class="absolute left-[58%] top-[28%] text-3xl" aria-hidden="true">🚌</span><span class="absolute left-[34%] top-[56%] text-3xl" aria-hidden="true">🌳</span>
        <span x-show="weather === 'lluvia'" x-cloak class="absolute inset-0 bg-[repeating-linear-gradient(110deg,transparent,transparent_13px,rgba(255,255,255,.48)_14px,transparent_16px)]" aria-hidden="true"></span>
        <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 rounded-full bg-surface/95 p-1 shadow-sm">
            <button type="button" class="min-h-10 rounded-full px-4 text-sm font-bold" :class="weather === 'seco' ? 'bg-primary text-white' : 'text-text'" @click="weather='seco';selected=null">☀️ Seco</button>
            <button type="button" class="min-h-10 rounded-full px-4 text-sm font-bold" :class="weather === 'lluvia' ? 'bg-primary text-white' : 'text-text'" @click="weather='lluvia';selected=null">🌧️ Lluvia</button>
        </div>
    </div>

    <div class="grid gap-3 p-4 md:grid-cols-2">
        <template x-for="(route, key) in routes" :key="key">
            <button type="button" class="rounded-lg border p-4 text-left transition-colors" :class="selected === key ? 'border-primary bg-primary/5' : 'border-border bg-background'" @click="selected=key" :aria-pressed="(selected === key).toString()">
                <div class="flex items-center justify-between gap-2"><p class="font-heading font-bold" x-text="route.name"></p><span class="rounded-full px-2 py-1 text-xs font-bold" :class="score(key) >= 60 ? 'bg-success/10 text-success-text' : 'bg-warning/10 text-warning-text'" x-text="`${score(key)} puntos de margen`"></span></div>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-xs"><div><dt class="text-text-secondary">Distancia</dt><dd class="font-bold" x-text="route.distance"></dd></div><div><dt class="text-text-secondary">Conflictos</dt><dd class="font-bold" x-text="route.conflicts"></dd></div><div><dt class="text-text-secondary">Superficie</dt><dd class="font-bold" x-text="route.surface"></dd></div><div><dt class="text-text-secondary">Visibilidad</dt><dd class="font-bold" x-text="route.visibility"></dd></div></dl>
                <p x-show="selected === key" x-cloak class="mt-3 border-t border-border pt-3 text-sm leading-6 text-text-secondary" x-text="verdict(key)"></p>
            </button>
        </template>
    </div>
    <p class="border-t border-border px-4 py-3 text-xs leading-5 text-text-secondary">Los puntos ayudan a comparar esta práctica; no son una calificación. En un recorrido real también importan tu edad, experiencia, bicicleta y acompañamiento.</p>
</section>
