export function spanishVoice(voices) {
    const spanish = voices.filter(voice => /^es(?:[-_]|$)/i.test(voice.lang));
    return spanish.find(voice => /^es[-_]CR$/i.test(voice.lang))
        ?? spanish.find(voice => voice.localService)
        ?? spanish[0]
        ?? null;
}

export function createNarrator({ synth, Utterance, onState, schedule = setTimeout, unschedule = clearTimeout }) {
    const supported = !!synth && typeof Utterance === 'function';
    let active = null;
    let generation = 0;
    let timer = null;
    let disposed = false;
    let phase = 'idle';
    const voice = () => {
        try { return supported ? spanishVoice(synth.getVoices()) : null; }
        catch { return null; }
    };
    const emit = (next, available = !!voice()) => {
        phase = next;
        if (!disposed) onState({ phase: next, available });
    };
    const clearTimer = () => {
        if (timer !== null) unschedule(timer);
        timer = null;
    };
    const stop = () => {
        generation++;
        clearTimer();
        active = null;
        if (supported) {
            try { synth.cancel(); } catch { /* Text and forms remain usable. */ }
        }
        emit(voice() ? 'idle' : 'unavailable');
    };
    const refresh = () => {
        if (!active && !disposed) emit(voice() ? 'idle' : 'unavailable');
    };
    const read = text => {
        if (disposed) return;
        stop();
        const selected = voice();
        if (!selected || !text.trim()) {
            emit(selected ? 'idle' : 'unavailable');
            return;
        }
        const ticket = generation;
        try {
            active = new Utterance(text);
            active.voice = selected;
            active.lang = selected.lang;
            active.rate = 0.9;
            active.pitch = 1;
            active.onstart = () => {
                if (ticket !== generation || disposed) return;
                clearTimer();
                emit('speaking');
            };
            active.onend = () => {
                if (ticket !== generation || disposed) return;
                clearTimer();
                generation++;
                active = null;
                emit('done');
            };
            active.onerror = () => {
                if (ticket !== generation || disposed) return;
                stop();
                emit('error');
            };
            emit('loading');
            // Some browsers expose speech synthesis but never begin playback.
            timer = schedule(() => {
                if (ticket !== generation || phase !== 'loading' || disposed) return;
                stop();
                emit('error');
            }, 6000);
            synth.speak(active);
        } catch {
            stop();
            emit('error');
        }
    };
    synth?.addEventListener?.('voiceschanged', refresh);
    refresh();
    return {
        read,
        stop,
        destroy() {
            stop();
            disposed = true;
            synth?.removeEventListener?.('voiceschanged', refresh);
        },
    };
}

export function visibleReadingText(root) {
    return [...root.querySelectorAll('[data-dc-read]')]
        .filter(node => node.getClientRects().length > 0)
        .map(node => node.innerText.trim())
        .filter(Boolean)
        .join('. ');
}

export function mountNarrator(root, host = window) {
    const panel = root.querySelector('[data-dc-audio]');
    if (!panel) return;
    const listen = panel.querySelector('[data-dc-listen]');
    const stopButton = panel.querySelector('[data-dc-stop]');
    const status = panel.querySelector('[data-dc-audio-status]');
    const narrator = createNarrator({
        synth: host.speechSynthesis,
        Utterance: host.SpeechSynthesisUtterance,
        onState({ phase, available }) {
            listen.disabled = !available;
            const playing = phase === 'speaking' || phase === 'loading';
            stopButton.disabled = !playing;
            listen.textContent = playing || phase === 'done' ? 'Escuchar otra vez' : 'Escuchar esta pantalla';
            status.textContent = {
                idle: 'Escuchá a tu ritmo. También podés pedirle a tu acompañante que lea.',
                unavailable: 'Este navegador no tiene una voz en español disponible. Podés seguir con la lectura de tu acompañante.',
                loading: 'Preparando la lectura…',
                speaking: 'Leyendo esta pantalla…',
                done: 'Lectura terminada. Podés escucharla otra vez.',
                error: 'No se pudo iniciar o completar la lectura. Podés volver a intentar o leer con tu acompañante.',
            }[phase];
        },
    });
    panel.hidden = false;
    listen.addEventListener('click', () => narrator.read(visibleReadingText(root)));
    stopButton.addEventListener('click', narrator.stop);
    root.addEventListener('submit', narrator.stop);
    root.addEventListener('click', event => { if (event.target.closest('a')) narrator.stop(); });
    host.addEventListener('pagehide', narrator.stop);
    host.document.addEventListener('visibilitychange', () => {
        if (host.document.hidden) narrator.stop();
    });
    return narrator;
}

if (typeof document !== 'undefined') {
    const root = document.querySelector('[data-dc-reader]');
    if (root) mountNarrator(root);
}
