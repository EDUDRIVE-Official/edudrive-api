import './bootstrap';
import Alpine from 'alpinejs';
import { registerPassengerPreview } from './passenger-preview-controller';
import { registerPilotVisual } from './pilot-visual';

registerPilotVisual(Alpine);

window.Alpine = Alpine;
registerPassengerPreview(Alpine);
Alpine.data('crossing3d', (mode = 'crossing') => {
    let engine = null;
    return {
        ready: false, loading: false, error: '', step: 0, view: 'overview', paused: false,
        get engine() { return engine; },
        steps: mode === 'actors' ? [
            ['1. Una vía compartida', 'Identificá al peatón, al ciclista, al autobús y a la persona en silla de ruedas. Cambiá de cámara para comparar sus puntos de vista.'],
            ['2. El autobús oculta parte de la vía', 'Desde la acera el autobús tapa al ciclista del carril opuesto. La zona naranja ilustra esa visibilidad bloqueada; no representa una medida exacta. Esperá en la acera.'],
            ['3. Recuperar visibilidad no es permiso para cruzar', 'El autobús se retira y el ciclista vuelve a quedar visible. Seguí esperando: revisá ambos sentidos y confirmá que el paso esté libre antes de cruzar.'],
        ] : [
            ['1. Elegí un lugar visible', 'Ubicá el paso peatonal y las aceras de ambos lados.'],
            ['2. Detenete en la acera', 'Esperá antes del borde. Los vehículos todavía están circulando.'],
            ['3. Mirá y escuchá', 'Revisá ambos sentidos y otras entradas de vehículos. Todavía no crucés.'],
            ['4. Confirmá que se detuvieron', 'En esta escena ambos vehículos frenan antes del paso. Esperá hasta verlos completamente detenidos.'],
            ['5. Cruzá con atención', 'Caminá por el paso peatonal hasta la otra acera. Seguí observando el entorno.'],
        ],
        async open() {
            if (this.loading || engine) return;
            this.loading = true; this.error = '';
            try {
                const { mountCrossing } = await import('./crossing-3d');
                engine = mountCrossing(this.$refs.viewport, mode);
                engine.setStep(this.step); engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir la escena 3D. Podés seguir la explicación con los botones o intentar de nuevo.'; }
            finally { this.loading = false; }
        },
        sync() { this.paused = false; engine?.setPaused(false); engine?.setStep(this.step); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        next() { this.step = Math.min(this.steps.length - 1, this.step + 1); this.sync(); },
        changeView(value) { this.view = value; engine?.setView(value); },
        previous() { this.step = Math.max(0, this.step - 1); this.sync(); },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('busStopDecision3d', (choices, blockId) => {
    let engine = null;
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (this.loading || engine) return;
            this.loading = true; this.error = '';
            try {
                const { mountCrossing } = await import('./crossing-3d');
                engine = mountCrossing(this.$refs.viewport, 'bus-stop');
                engine.setStep(0); engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando el texto de la situación.'; }
            finally { this.loading = false; }
        },
        choose(id) {
            this.selected = this.choices.find(choice => choice.id === id);
            this.paused = false;
            engine?.setPaused(false); engine?.setStep(this.selected.correct ? 2 : 1);
            this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct });
        },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        retry() {
            this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setStep(0);
            this.$dispatch('scenario-answered', { id: blockId, correct: false });
        },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('crossingMovementsDecision3d', (choices, blockId) => {
    let engine = null;
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (this.loading || engine) return;
            this.loading = true; this.error = '';
            try {
                const { mountCrossing } = await import('./crossing-3d');
                engine = mountCrossing(this.$refs.viewport, 'crossing-movements');
                engine.setStep(0); engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción de la situación.'; }
            finally { this.loading = false; }
        },
        choose(id) {
            this.selected = this.choices.find(choice => choice.id === id);
            const result = id === 'revisar-todos' ? 2 : id === 'avisar' ? 3 : 1;
            this.paused = false; engine?.setPaused(false); engine?.setStep(result);
            this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct });
        },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        retry() {
            this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setStep(0);
            this.$dispatch('scenario-answered', { id: blockId, correct: false });
        },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('routeComparison3d', () => {
    let engine = null;
    return {
        route: 'none', ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (engine || this.loading) return;
            this.loading = true; this.error = '';
            try {
                const { mountRouteComparison } = await import('./route-comparison-3d');
                engine = mountRouteComparison(this.$refs.viewport); engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir el mapa 3D. Podés comparar las rutas con la explicación escrita.'; }
            finally { this.loading = false; }
        },
        async choose(route) { await this.open(); this.route = route; this.paused = false; engine?.setPaused(false); engine?.setRoute(route); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        changeView(value) { this.view = value; engine?.setView(value); },
        replay() { engine?.setRoute(this.route); this.paused = false; engine?.setPaused(false); },
        close() { engine?.dispose(); engine = null; this.ready = false; this.route = 'none'; },
        destroy() { this.close(); },
    };
});
Alpine.data('routeDecision3d', (choices, blockId, stopAtDecisionPoint = false) => {
    let engine = null;
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (engine || this.loading) return;
            this.loading = true; this.error = '';
            try {
                const { mountRouteComparison } = await import('./route-comparison-3d');
                engine = mountRouteComparison(this.$refs.viewport, { stopAtDecisionPoint });
                engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; }
            finally { this.loading = false; }
        },
        async choose(id) {
            await this.open();
            this.selected = this.choices.find(choice => choice.id === id);
            const route = id === 'protegida' ? 'safe' : 'short';
            this.paused = false; engine?.setPaused(false); engine?.setRoute(route);
            this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct });
        },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        replay() {
            if (!this.selected) return;
            engine?.setRoute(this.selected.id === 'protegida' ? 'safe' : 'short');
            this.paused = false; engine?.setPaused(false);
        },
        retry() {
            this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setRoute('none');
            this.$dispatch('scenario-answered', { id: blockId, correct: false });
        },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('blockedRampDecision3d', (choices, blockId, stopAtDecisionPoint = false) => {
    let engine = null;
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (engine || this.loading) return;
            this.loading = true; this.error = '';
            try {
                const { mountBlockedRamp } = await import('./blocked-ramp-3d');
                engine = mountBlockedRamp(this.$refs.viewport, { stopAtDecisionPoint });
                engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; }
            finally { this.loading = false; }
        },
        async choose(id) {
            await this.open();
            this.selected = this.choices.find(choice => choice.id === id);
            const outcome = id === 'preguntar' ? 'ask' : id === 'empujar' ? 'push' : 'road';
            this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcome);
            this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct });
        },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        replay() {
            if (!this.selected) return;
            const outcome = this.selected.id === 'preguntar' ? 'ask' : this.selected.id === 'empujar' ? 'push' : 'road';
            engine?.setOutcome(outcome); this.paused = false; engine?.setPaused(false);
        },
        retry() {
            this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro');
            this.$dispatch('scenario-answered', { id: blockId, correct: false });
        },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('signalChangeDecision3d', (choices, blockId) => {
    let engine = null;
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (engine || this.loading) return;
            this.loading = true; this.error = '';
            try {
                const { mountSignalChange } = await import('./signal-change-3d');
                engine = mountSignalChange(this.$refs.viewport);
                engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; }
            finally { this.loading = false; }
        },
        async choose(id) {
            await this.open();
            this.selected = this.choices.find(choice => choice.id === id);
            const outcome = id === 'confirmar' ? 'confirm' : id === 'telefono' ? 'phone' : 'cross';
            this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcome);
            this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct });
        },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        replay() {
            if (!this.selected) return;
            const outcome = this.selected.id === 'confirmar' ? 'confirm' : this.selected.id === 'telefono' ? 'phone' : 'cross';
            engine?.setOutcome(outcome); this.paused = false; engine?.setPaused(false);
        },
        retry() {
            this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro');
            this.$dispatch('scenario-answered', { id: blockId, correct: false });
        },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('parkedCarsShortcutDecision3d', (choices, blockId, stopAtDecisionPoint = false) => {
    let engine = null;
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (engine || this.loading) return;
            this.loading = true; this.error = '';
            try {
                const { mountParkedCarsShortcut } = await import('./parked-cars-shortcut-3d');
                engine = mountParkedCarsShortcut(this.$refs.viewport, { stopAtDecisionPoint }); engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; }
            finally { this.loading = false; }
        },
        async choose(id) {
            await this.open(); this.selected = this.choices.find(choice => choice.id === id);
            const outcome = id === 'paso' ? 'safe' : id === 'correr' ? 'run' : 'shortcut';
            this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcome);
            this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct });
        },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        replay() {
            if (!this.selected) return;
            const outcome = this.selected.id === 'paso' ? 'safe' : this.selected.id === 'correr' ? 'run' : 'shortcut';
            engine?.setOutcome(outcome); this.paused = false; engine?.setPaused(false);
        },
        retry() {
            this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro');
            this.$dispatch('scenario-answered', { id: blockId, correct: false });
        },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('lowVisibilityCornerDecision3d', (choices, blockId, stopAtDecisionPoint = false) => {
    let engine = null;
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (engine || this.loading) return;
            this.loading = true; this.error = '';
            try {
                const { mountLowVisibilityCorner } = await import('./low-visibility-corner-3d');
                engine = mountLowVisibilityCorner(this.$refs.viewport, { stopAtDecisionPoint }); engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; }
            finally { this.loading = false; }
        },
        async choose(id) {
            await this.open(); this.selected = this.choices.find(choice => choice.id === id);
            const outcome = id === 'buscar-visible' ? 'visible' : id === 'asomarse-calzada' ? 'peek' : 'blocked';
            this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcome);
            this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct });
        },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        replay() {
            if (!this.selected) return;
            const outcome = this.selected.id === 'buscar-visible' ? 'visible' : this.selected.id === 'asomarse-calzada' ? 'peek' : 'blocked';
            engine?.setOutcome(outcome); this.paused = false; engine?.setPaused(false);
        },
        retry() {
            this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro');
            this.$dispatch('scenario-answered', { id: blockId, correct: false });
        },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('hiddenLaneDecision3d', (choices, blockId) => {
    let engine = null;
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (engine || this.loading) return;
            this.loading = true; this.error = '';
            try {
                const { mountHiddenLane } = await import('./hidden-lane-3d');
                engine = mountHiddenLane(this.$refs.viewport); engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; }
            finally { this.loading = false; }
        },
        async choose(id) {
            await this.open(); this.selected = this.choices.find(choice => choice.id === id);
            const outcome = id === 'todos-movimientos' ? 'check' : id === 'seguir-persona' ? 'follow' : 'cross';
            this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcome);
            this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct });
        },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        replay() {
            if (!this.selected) return;
            const outcome = this.selected.id === 'todos-movimientos' ? 'check' : this.selected.id === 'seguir-persona' ? 'follow' : 'cross';
            engine?.setOutcome(outcome); this.paused = false; engine?.setPaused(false);
        },
        retry() {
            this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro');
            this.$dispatch('scenario-answered', { id: blockId, correct: false });
        },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('changingConditions3d', () => {
    let engine = null;
    return {
        condition: 'clear', ready: false, loading: false, error: '', paused: false, view: 'overview',
        messages: {
            clear: 'Con buena visibilidad igualmente debés detenerte y comprobar.',
            rain: 'Con lluvia: esperá más, recuperá visibilidad y cuidá el equilibrio.',
            night: 'De noche: buscá iluminación, hacete visible y no asumás que te vieron.',
        },
        descriptions: {
            clear: 'Cruce durante un día despejado. El paso, las aceras y los vehículos se distinguen con claridad, pero todavía es necesario comprobar.',
            rain: 'El mismo cruce bajo lluvia, con pavimento reflectante, menor contraste y alcance visual reducido.',
            night: 'El mismo cruce de noche, iluminado por faroles y faros. La banda reflectante ayuda a que la persona sea visible.',
        },
        async open() {
            if (engine || this.loading) return;
            this.loading = true; this.error = '';
            try {
                const { mountChangingConditions } = await import('./changing-conditions-3d');
                engine = mountChangingConditions(this.$refs.viewport); engine.setView(this.view); engine.setCondition(this.condition); this.ready = true;
            } catch { this.error = 'No se pudo abrir el laboratorio 3D. Podés comparar las condiciones con la explicación escrita.'; }
            finally { this.loading = false; }
        },
        async choose(value) { await this.open(); this.condition = value; engine?.setCondition(value); },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('rainyCrossingDecision3d', (choices, blockId) => {
    let engine = null;
    const outcomeFor = id => id === 'ajustar' ? 'adjust' : id === 'seguir' ? 'follow' : 'rush';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (engine || this.loading) return;
            this.loading = true; this.error = '';
            try {
                const { mountRainyCrossingDecision } = await import('./rainy-crossing-decision-3d');
                engine = mountRainyCrossingDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true;
            } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; }
            finally { this.loading = false; }
        },
        async choose(id) {
            await this.open(); this.selected = this.choices.find(choice => choice.id === id);
            this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id));
            this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct });
        },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } },
        retry() {
            this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro');
            this.$dispatch('scenario-answered', { id: blockId, correct: false });
        },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('duskCrossingDecision3d', (choices, blockId) => {
    let engine = null;
    const outcomeFor = id => id === 'visible' ? 'visible' : id === 'telefono' ? 'phone' : 'right';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() {
            if (engine || this.loading) return;
            this.loading = true; this.error = '';
            try { const { mountDuskCrossingDecision } = await import('./dusk-crossing-decision-3d'); engine = mountDuskCrossingDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; }
            catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; }
            finally { this.loading = false; }
        },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } },
        retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; },
        destroy() { this.close(); },
    };
});
Alpine.data('speedDistance3d', () => {
    let engine = null;
    return {
        mode: 'slow', ready: false, loading: false, error: '', paused: false, view: 'overview', reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        get message() { return this.mode === 'slow' ? 'Modo lento: hay más tiempo para percibir y decidir, pero todavía se debe comprobar.' : 'Modo rápido: la misma distancia desaparece antes. Nunca intentés competir contra un vehículo.'; },
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountSpeedDistance } = await import('./speed-distance-3d'); engine = mountSpeedDistance(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la demostración 3D. Podés estudiar la comparación escrita.'; } finally { this.loading = false; } },
        async run(value) { if (this.reduced) return; await this.open(); this.mode = value; this.paused = false; engine?.setMode(value); },
        reset() { this.paused = false; engine?.reset(); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, changeView(value) { this.view = value; engine?.setView(value); },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('perceivedDistanceDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'esperar' ? 'wait' : id === 'competir' ? 'compete' : 'calculate';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountPerceivedDistanceDecision } = await import('./perceived-distance-decision-3d'); engine = mountPerceivedDistanceDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } },
        retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('downhillBicycleDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'margen' ? 'margin' : id === 'correr' ? 'run' : 'silence';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountDownhillBicycleDecision } = await import('./downhill-bicycle-decision-3d'); engine = mountDownhillBicycleDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('ballChallengeDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'seguir' ? 'continue' : id === 'detener' ? 'stop' : 'return';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountBallChallengeDecision } = await import('./ball-challenge-decision-3d'); engine = mountBallChallengeDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('crossingSignalTransition3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'continuar-calma' ? 'continue' : id === 'correr' ? 'run' : 'return';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountCrossingSignalTransition } = await import('./crossing-signal-transition-3d'); engine = mountCrossingSignalTransition(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('turningVehicleDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'pausa' ? 'pause' : id === 'sorpresa' ? 'surprise' : 'priority';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountTurningVehicleDecision } = await import('./turning-vehicle-decision-3d'); engine = mountTurningVehicleDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('ambulanceDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => ['protegida', 'detener'].includes(id) ? 'protected' : ['correr', 'grupo'].includes(id) ? 'run' : 'signal';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountAmbulanceDecision } = await import('./ambulance-decision-3d'); engine = mountAmbulanceDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('schoolExitDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'integrar' ? 'integrate' : id === 'grupo' ? 'group' : 'gesture';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountSchoolExitDecision } = await import('./school-exit-decision-3d'); engine = mountSchoolExitDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('blockedSidewalkDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => ['alternativa', 'ruta'].includes(id) ? 'alternative' : ['separarse', 'solo'].includes(id) ? 'separate' : 'road';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountBlockedSidewalkDecision } = await import('./blocked-sidewalk-decision-3d'); engine = mountBlockedSidewalkDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('silentVehicleDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'confirmar' ? 'confirm' : id === 'sonido' ? 'silence' : 'signal';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountSilentVehicleDecision } = await import('./silent-vehicle-decision-3d'); engine = mountSilentVehicleDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('riskRadar3d', () => {
    let engine = null;
    return {
        scene: 'oculto', revealed: false, ready: false, loading: false, error: '', paused: false, view: 'overview',
        scenes: {
            oculto: { title: 'Punto ciego', clue: 'El autobús bloquea la vista. ¿Qué podría aparecer detrás?', answer: 'Una motocicleta, bicicleta o persona puede quedar oculta. Esperá en el espacio protegido hasta recuperar visibilidad.', status: 'Amarillo: falta información' },
            lluvia: { title: 'Aguacero', clue: 'La lluvia reduce la visibilidad y forma agua junto al borde.', answer: 'Aumentá el margen, alejate del borde y reevaluá el recorrido. La ruta conocida ya no tiene las mismas condiciones.', status: 'Rojo: cambiá el plan' },
            puerta: { title: 'Puerta inesperada', clue: 'Hay movimiento dentro del automóvil estacionado.', answer: 'La puerta podría abrirse. Conservá distancia y una salida segura, aunque el automóvil no esté circulando.', status: 'Amarillo: anticipá movimiento' },
        },
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountRiskRadar } = await import('./risk-radar-3d'); engine = mountRiskRadar(this.$refs.viewport); engine.setScene(this.scene, false); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir el radar 3D. Podés estudiar las explicaciones escritas.'; } finally { this.loading = false; } },
        async choose(value) { await this.open(); this.scene = value; this.revealed = false; this.paused = false; engine?.setPaused(false); engine?.setScene(value, false); },
        async toggleReveal() { await this.open(); this.revealed = !this.revealed; this.paused = false; engine?.setPaused(false); engine?.reveal(this.revealed); },
        replay() { this.revealed = true; this.paused = false; engine?.setPaused(false); engine?.setScene(this.scene, false); engine?.reveal(true); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('gateBallDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'anticipar' ? 'anticipate' : id === 'mover' ? 'move' : 'ignore';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountGateBallDecision } = await import('./gate-ball-decision-3d'); engine = mountGateBallDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('darkRoadMarksDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'relacionar' ? 'relate' : id === 'probar' ? 'test' : 'isolate';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountDarkRoadMarksDecision } = await import('./dark-road-marks-decision-3d'); engine = mountDarkRoadMarksDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('ruralCurveDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'protegido' ? 'protected' : id === 'silencio' ? 'silence' : 'peek';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountRuralCurveDecision } = await import('./rural-curve-decision-3d'); engine = mountRuralCurveDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('betweenParkedVehiclesDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'otro-cruce' ? 'safe' : id === 'oido' ? 'sound' : 'advance';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountBetweenParkedVehiclesDecision } = await import('./between-parked-vehicles-decision-3d'); engine = mountBetweenParkedVehiclesDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('schoolQueueDoorDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'puerta' ? 'anticipate' : id === 'pasar' ? 'close' : 'ignore';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountSchoolQueueDoorDecision } = await import('./school-queue-door-decision-3d'); engine = mountSchoolQueueDoorDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('turnedWheelsCornerDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'giro' ? 'confirm' : id === 'seguro' ? 'trust' : 'stationary';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountTurnedWheelsCornerDecision } = await import('./turned-wheels-corner-decision-3d'); engine = mountTurnedWheelsCornerDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('downpourExitDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'reevaluar' ? 'reevaluate' : id === 'correr' ? 'run' : 'routine';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountDownpourExitDecision } = await import('./downpour-exit-decision-3d'); engine = mountDownpourExitDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.data('neighborhoodBlackoutDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'alternativa' ? 'alternative' : id === 'luz-telefono' ? 'phone' : 'habit';
    return { choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview', async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountNeighborhoodBlackoutDecision } = await import('./neighborhood-blackout-decision-3d'); engine = mountNeighborhoodBlackoutDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } }, async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); } };
});
Alpine.data('departingBusDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'siguiente' ? 'crossing' : id === 'senal' ? 'gesture' : 'run';
    return { choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview', async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountDepartingBusDecision } = await import('./departing-bus-decision-3d'); engine = mountDepartingBusDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } }, async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); } };
});
Alpine.data('disappearingSpaceDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'detener-plan' ? 'retreat' : id === 'confiar' ? 'trust' : 'squeeze';
    return { choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview', async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountDisappearingSpaceDecision } = await import('./disappearing-space-decision-3d'); engine = mountDisappearingSpaceDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } }, async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); } };
});
Alpine.data('blockedStopRainDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'esperar' ? 'wait' : id === 'escuchar' ? 'listen' : 'front';
    return { choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview', async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountBlockedStopRainDecision } = await import('./blocked-stop-rain-decision-3d'); engine = mountBlockedStopRainDecision(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } }, async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); } };
});
Alpine.data('coexistenceSignals3d', () => {
    let engine = null;
    return {
        scene: 'normal', answer: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        scenes: {
            normal: { question: 'La señal permite cruzar y los vehículos están detenidos. ¿Qué hacés?', safe: 'Observar nuevamente y cruzar con calma', unsafe: 'Cruzar sin mirar porque tengo prioridad', feedback: 'La señal y la situación coinciden. Aun así, una última comprobación mantiene la decisión segura.' },
            conflict: { question: 'La señal permite cruzar, pero una motocicleta sigue acercándose. ¿Qué hacés?', safe: 'Esperar en la acera hasta que se detenga', unsafe: 'Avanzar para ejercer mi prioridad', feedback: 'La prioridad organiza el turno, pero el peligro real exige esperar en un espacio protegido.' },
            failure: { question: 'No funciona el semáforo y varias personas quieren avanzar. ¿Qué hacés?', safe: 'Detenerme, observar y ceder ante la duda', unsafe: 'Seguir al vehículo más grande', feedback: 'Cuando falta una capa de información, aumentamos la cautela y hacemos movimientos previsibles.' },
        },
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountCoexistenceSignals3d } = await import('./coexistence-signals-3d'); engine = mountCoexistenceSignals3d(this.$refs.viewport); engine.setScene(this.scene); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir el laboratorio 3D. Podés usar las explicaciones escritas.'; } finally { this.loading = false; } },
        async chooseScene(value) { await this.open(); this.scene = value; this.answer = null; this.paused = false; engine?.setPaused(false); engine?.setScene(value); },
        async decide(value) { await this.open(); this.answer = value; this.paused = false; engine?.setPaused(false); engine?.setDecision(value); },
        replay() { if (this.answer) { engine?.setDecision(this.answer); this.paused = false; engine?.setPaused(false); } },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, close() { engine?.dispose(); engine = null; this.ready = false; }, destroy() { this.close(); },
    };
});
Alpine.data('unknownSignDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'precaucion' ? 'caution' : id === 'copiar' ? 'copy' : 'ignore';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountUnknownSignDecision3d } = await import('./unknown-sign-decision-3d'); engine = mountUnknownSignDecision3d(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, close() { engine?.dispose(); engine = null; this.ready = false; }, destroy() { this.close(); },
    };
});
Alpine.data('schoolConesDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'cambio' ? 'detour' : id === 'entre-conos' ? 'squeeze' : 'ignore';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountSchoolConesDecision3d } = await import('./school-cones-decision-3d'); engine = mountSchoolConesDecision3d(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, close() { engine?.dispose(); engine = null; this.ready = false; }, destroy() { this.close(); },
    };
});
Alpine.data('occupiedCrossingDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'esperar' ? 'wait' : id === 'sonar' ? 'horn' : 'advance';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountOccupiedCrossingDecision3d } = await import('./occupied-crossing-decision-3d'); engine = mountOccupiedCrossingDecision3d(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, close() { engine?.dispose(); engine = null; this.ready = false; }, destroy() { this.close(); },
    };
});
Alpine.data('conflictingSignalsDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'esperar-peatonal' ? 'wait' : id === 'grupo' ? 'follow' : 'cross';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountConflictingSignalsDecision3d } = await import('./conflicting-signals-decision-3d'); engine = mountConflictingSignalsDecision3d(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, close() { engine?.dispose(); engine = null; this.ready = false; }, destroy() { this.close(); },
    };
});
Alpine.data('realCrosswalkDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'confirmar' ? 'confirm' : id === 'renunciar' ? 'leave' : 'enter';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountRealCrosswalkDecision3d } = await import('./real-crosswalk-decision-3d'); engine = mountRealCrosswalkDecision3d(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, close() { engine?.dispose(); engine = null; this.ready = false; }, destroy() { this.close(); },
    };
});
Alpine.data('cycleTrackCrossingDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'comprobar' ? 'check' : id === 'detener-bici' ? 'block' : 'priority';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountCycleTrackCrossingDecision3d } = await import('./cycle-track-crossing-decision-3d'); engine = mountCycleTrackCrossingDecision3d(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, close() { engine?.dispose(); engine = null; this.ready = false; }, destroy() { this.close(); },
    };
});
Alpine.data('courtesySignalDecision3d', (choices, blockId) => {
    let engine = null; const outcomeFor = id => id === 'comprobar' ? 'check' : id === 'espalda' ? 'turn-away' : 'cross';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountCourtesySignalDecision3d } = await import('./courtesy-signal-decision-3d'); engine = mountCourtesySignalDecision3d(this.$refs.viewport); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        replay() { if (this.selected) { engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, close() { engine?.dispose(); engine = null; this.ready = false; }, destroy() { this.close(); },
    };
});
Alpine.data('visibleCyclingScenario3d', (choices, blockId, title) => {
    let engine = null;
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview',
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const { mountVisibleCyclingScenario3d } = await import('./visible-cycling-scenario-3d'); engine = mountVisibleCyclingScenario3d(this.$refs.viewport, title); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.paused = false; engine?.setPaused(false); engine?.setOutcome(this.selected.correct ? 'safe' : 'unsafe'); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        replay() { if (this.selected) { engine?.setOutcome(this.selected.correct ? 'safe' : 'unsafe'); this.paused = false; engine?.setPaused(false); } }, retry() { this.selected = null; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); }, changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); }, close() { engine?.dispose(); engine = null; this.ready = false; }, destroy() { this.close(); },
    };
});
Alpine.data('messageCrossingDecision3d', (choices, blockId, isMap = false, isBus = false, isNoise = false, isAngry = false) => {
    let engine = null, opening = null, destroyed = false;
    return {
        choices, selected: null, ready: false, loading: false, error: '', caption: '', paused: false, view: 'overview',
        async open() {
            if (engine) return;
            if (opening) return opening;
            this.loading = true;
            opening = (async () => {
                try {
                    const mount = isAngry ? (await import('./angry-shop-decision-3d')).mountAngryShop : isNoise ? (await import('./construction-noise-decision-3d')).mountConstructionNoise : isBus ? (await import('./headphones-bus-decision-3d')).mountHeadphonesBus : isMap
                        ? (await import('./map-route-decision-3d')).mountMapRoute
                        : (await import('./message-crossing-decision-3d')).mountMessageCrossing;
                    if (destroyed) return;
                    engine = mount(this.$refs.viewport, text => this.caption = text);
                    this.paused = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    engine.setPaused(this.paused); engine.setView(this.view); this.ready = true; this.error = '';
                } catch { this.error = 'No se pudo abrir la escena. Podés responder con la descripción escrita.'; }
                finally { this.loading = false; opening = null; }
            })();
            return opening;
        },
        async choose(id) { this.selected = choices.find(c => c.id === id); if (!this.selected) return; this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); await this.open(); if (destroyed) return; engine?.setOutcome(this.selected.id); engine?.setPaused(this.paused); },
        togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        changeView(value) { this.view = value; engine?.setView(value); },
        replay() { engine?.setOutcome(this.selected?.id || 'intro'); },
        retry() { this.selected = null; engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); },
        destroy() { destroyed = true; engine?.dispose(); engine = null; },
    };
});
const incidentScenes = {
    'fallen-bicycle': async () => (await import('./fallen-bicycle-decision-3d')).mountFallenBicycleDecision,
    'unknown-cable': async () => (await import('./unknown-cable-decision-3d')).mountUnknownCableDecision,
};
Alpine.data('incidentDecision3d', (choices, blockId, sceneKey) => {
    let engine = null; const outcomeFor = id => id === 'segura' ? 'protected' : id === 'grabar' ? 'record' : 'expose';
    return {
        choices, selected: null, ready: false, loading: false, error: '', paused: false, view: 'overview', step: 0,
        async open() { if (engine || this.loading) return; this.loading = true; this.error = ''; try { const mount = await incidentScenes[sceneKey](); engine = mount(this.$refs.viewport, index => { this.step = index; }); engine.setView(this.view); this.ready = true; } catch { this.error = 'No se pudo abrir la práctica 3D. Podés responder usando la descripción escrita.'; } finally { this.loading = false; } },
        async choose(id) { await this.open(); this.selected = this.choices.find(choice => choice.id === id); this.step = 0; this.paused = false; engine?.setPaused(false); engine?.setOutcome(outcomeFor(id)); this.$dispatch('scenario-answered', { id: blockId, correct: this.selected.correct }); },
        goStep(index) { if (!this.selected) return; this.step = Math.max(0, Math.min(2, index)); this.paused = true; engine?.setStep(this.step); },
        changeView(value) { this.view = value; engine?.setView(value); }, togglePause() { this.paused = !this.paused; engine?.setPaused(this.paused); },
        replay() { if (this.selected) { this.step = 0; engine?.setOutcome(outcomeFor(this.selected.id)); this.paused = false; engine?.setPaused(false); } },
        retry() { this.selected = null; this.step = 0; this.paused = false; engine?.setPaused(false); engine?.setOutcome('intro'); this.$dispatch('scenario-answered', { id: blockId, correct: false }); },
        close() { engine?.dispose(); engine = null; this.ready = false; this.paused = false; }, destroy() { this.close(); },
    };
});
Alpine.start();
