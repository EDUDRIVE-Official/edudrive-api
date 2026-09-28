import test from 'node:test';
import assert from 'node:assert/strict';
import { createNarrator, spanishVoice, visibleReadingText } from '../../public/js/descubro-read-aloud.js';

const es = { lang: 'es-CR', localService: true };
function harness(voices = [es]) {
    const states = [], spoken = [], tasks = new Set();
    const synth = new EventTarget();
    synth.getVoices = () => voices;
    synth.cancel = () => {};
    synth.speak = utterance => spoken.push(utterance);
    const narrator = createNarrator({
        synth,
        Utterance: class { constructor(text) { this.text = text; } },
        onState: state => states.push(state),
        schedule: callback => { tasks.add(callback); return callback; },
        unschedule: callback => tasks.delete(callback),
    });
    return { narrator, synth, states, spoken, tasks, voices, state: () => states.at(-1) };
}

test('does not speak on load, requires Spanish, and handles voices arriving later', () => {
    const h = harness([{ lang: 'en-US' }]);
    assert.equal(h.spoken.length, 0);
    assert.equal(h.state().phase, 'unavailable');
    h.narrator.read('Tito espera.');
    assert.equal(h.spoken.length, 0);
    h.voices.push(es);
    h.synth.dispatchEvent(new Event('voiceschanged'));
    assert.equal(h.state().available, true);
    h.narrator.read('Tito espera.');
    assert.equal(h.spoken[0].text, 'Tito espera.');
    assert.equal(h.spoken[0].voice, es);
});

test('repeating and stopping ignore late callbacks from an older reading', () => {
    const h = harness();
    h.narrator.read('Primera lectura.');
    const first = h.spoken[0];
    h.narrator.read('Segunda lectura.');
    const second = h.spoken[1];
    second.onstart();
    first.onerror();
    first.onend();
    assert.equal(h.state().phase, 'speaking');
    h.narrator.stop();
    second.onstart();
    second.onend();
    assert.equal(h.state().phase, 'idle');
    assert.equal(h.tasks.size, 0);
});

test('reports silent startup failure and does not let late events overwrite it', () => {
    const h = harness();
    h.narrator.read('Una consigna.');
    const timeout = [...h.tasks][0];
    timeout();
    h.spoken[0].onstart();
    h.spoken[0].onend();
    assert.equal(h.state().phase, 'error');
    assert.equal(h.tasks.size, 0);
});

test('reports playback errors, permits retry, and completes successfully', () => {
    const h = harness();
    h.narrator.read('Una consigna.');
    h.spoken[0].onerror();
    h.spoken[0].onend();
    assert.equal(h.state().phase, 'error');
    h.narrator.read('Una consigna.');
    h.spoken[1].onstart();
    assert.equal(h.tasks.size, 0);
    h.spoken[1].onend();
    assert.equal(h.state().phase, 'done');
});

test('unsupported browsers stay usable and do not throw', () => {
    const states = [];
    const narrator = createNarrator({ onState: state => states.push(state) });
    narrator.read('Tito espera.');
    narrator.stop();
    narrator.destroy();
    assert.equal(states.at(-1).available, false);
    assert.equal(states.at(-1).phase, 'unavailable');
});

test('reads only visible marked text, without hidden options or form values', () => {
    const node = (innerText, visible) => ({ innerText, getClientRects: () => visible ? [{}] : [] });
    const root = { querySelectorAll(selector) {
        assert.equal(selector, '[data-dc-read]');
        return [node(' ¿Por dónde caminamos? ', true), node('Una respuesta oculta', false), node('Por la acera', true), node('   ', true)];
    } };
    assert.equal(visibleReadingText(root), '¿Por dónde caminamos?. Por la acera');
    assert.equal(spanishVoice([{ lang: 'en-US', localService: true }]), null);
    assert.equal(spanishVoice([{ lang: 'es-ES', localService: true }, es]), es);
});
