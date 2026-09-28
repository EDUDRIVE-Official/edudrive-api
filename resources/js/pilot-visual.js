export function registerPilotVisual(Alpine) {
    const progressKey = 'edudrive.p912.visual-sequence.v1';

    Alpine.data('pilotSequence', () => ({
        completed: [],
        init() {
            try { this.completed = JSON.parse(sessionStorage.getItem(progressKey) || '[]'); }
            catch { this.completed = []; }
        },
        has(scene) { return this.completed.includes(scene); },
        resetProgress() { sessionStorage.removeItem(progressKey); this.completed = []; },
    }));

    Alpine.data('pilotVisual', (scenario = 'turn') => {
        let engine;
        return {
            ready: false, error: '', choice: '', result: false, paused: false,
            reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            async init() {
                try {
                    const mount = scenario === 'van'
                        ? (await import('./pilot-van-3d')).mountPilotVan
                        : scenario === 'descent'
                        ? (await import('./pilot-descent-3d')).mountPilotDescent
                        : scenario === 'barrier'
                            ? (await import('./pilot-barrier-3d')).mountPilotBarrier
                            : (await import('./pilot-turn-3d')).mountPilotTurn;
                    engine = mount(this.$refs.scene, () => { this.result = true; this.paused = false; this.completeLocal(); });
                    this.ready = true;
                    if (this.choice) engine.finish(this.choice);
                } catch { this.error = 'La vista 3D no está disponible. Podés decidir con la descripción de la escena.'; }
            },
            choose(value) {
                if (this.choice) return;
                this.choice = value;
                if (engine && !this.reduced) engine.play(value);
                else { engine?.finish(value); this.result = true; this.completeLocal(); }
            },
            pause() { this.paused = !this.paused; engine?.pause(this.paused); },
            finish() { engine?.finish(this.choice); this.result = true; this.paused = false; this.completeLocal(); },
            replay() { if (!engine) return; this.result = false; this.paused = false; engine.play(this.choice); },
            reset() { engine?.reset(); this.choice = ''; this.result = false; this.paused = false; },
            completeLocal() {
                try {
                    const completed = JSON.parse(sessionStorage.getItem(progressKey) || '[]');
                    if (!completed.includes(scenario)) sessionStorage.setItem(progressKey, JSON.stringify([...completed, scenario]));
                } catch { /* El recorrido funciona aunque el navegador no permita almacenamiento temporal. */ }
            },
            narrate() {
                if (!('speechSynthesis' in window)) return;
                window.speechSynthesis.cancel();
                const voice = new SpeechSynthesisUtterance(scenario === 'van'
                    ? 'Luna quiere llegar a la acera de enfrente con una persona adulta. Una van estacionada tapa parte de la vía. A la derecha hay espacio para moverse por la acera. ¿Dónde te ubicarías para observar antes de decidir cómo cruzar?'
                    : scenario === 'descent'
                        ? 'El transporte se detuvo, pero la puerta quedó frente a un borde sin acera. Luna todavía está dentro con una persona adulta. ¿Qué comunicaría antes de bajar?'
                        : scenario === 'barrier'
                            ? 'Una obra bloquea toda la acera. Luna y una persona adulta siguen en el espacio peatonal y no ven una alternativa protegida para continuar. ¿Qué harías?'
                        : 'Luna y una persona adulta quieren llegar a la acera de enfrente. Aún están en la acera. La señal peatonal está en verde. Un carro comienza a girar hacia el paso de cebra. ¿Qué harías ahora?');
                voice.lang = 'es-CR'; window.speechSynthesis.speak(voice);
            },
            stopVoice() { window.speechSynthesis?.cancel(); },
            destroy() { engine?.dispose(); window.speechSynthesis?.cancel(); },
        };
    });
}
