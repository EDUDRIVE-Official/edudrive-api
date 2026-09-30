import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const root = new URL('../', import.meta.url);
const app = readFileSync(new URL('resources/js/app.js', root), 'utf8');
const draft = JSON.parse(readFileSync(new URL('docs/product/BORRADOR-BLOQUES-CAMINO-PASAJERO-v1.json', root)));
const scenarios = draft.lessons.flatMap(l => l.blocks).filter(b => b.type === 'scenario').map(b => b.payload);
const specs = [
    ['parkedCarsShortcutDecision3d', 'mountParkedCarsShortcut', 'El atajo entre automóviles', 'parked-cars-shortcut-decision-3d', { paso: 'safe', correr: 'run', atajo: 'shortcut' }],
    ['lowVisibilityCornerDecision3d', 'mountLowVisibilityCorner', 'La esquina con poca visibilidad', 'low-visibility-corner-decision-3d', { 'buscar-visible': 'visible', 'asomarse-calzada': 'peek', 'usar-igual': 'blocked' }],
    ['routeDecision3d', 'mountRouteComparison', 'Dos caminos a la escuela', 'route-decision-3d', { protegida: 'safe', corta: 'short', calzada: 'short' }],
    ['blockedRampDecision3d', 'mountBlockedRamp', 'La rampa está bloqueada', 'blocked-ramp-decision-3d', { preguntar: 'ask', empujar: 'push', calzada: 'road' }],
];
const permutations = [[0,1,2], [0,2,1], [1,0,2], [1,2,0], [2,0,1], [2,1,0]];
let checked = 0;
for (const [factory, mount, title, view, outcomes] of specs) {
    const start = app.indexOf(`Alpine.data('${factory}',`);
    assert(start >= 0);
    const end = app.indexOf('\nAlpine.data(', start + 1);
    const code = app.slice(start, end < 0 ? undefined : end).replace(/import\(/g, '__load(');
    const blade = readFileSync(new URL(`resources/views/courses/blocks/${view}.blade.php`, root), 'utf8');
    assert(blade.includes("@js(($scenario['stop_at_decision_point'] ?? false) === true)"));
    const scenario = scenarios.find(s => s.title === title);
    for (const order of permutations) {
        let create, mountedOptions, state;
        const engine = { setView() {}, setPaused() {}, dispose() {}, setOutcome(v) { state = v; }, setRoute(v) { state = v; } };
        vm.runInNewContext(code, {
            Alpine: { data(name, fn) { assert.equal(name, factory); create = fn; } },
            __load: async () => ({ [mount]: (host, options) => { mountedOptions = options; return engine; } }),
        });
        const component = create(order.map(i => scenario.choices[i]), 'block-under-test', true);
        const events = [];
        component.$refs = { viewport: {} };
        component.$dispatch = (name, detail) => events.push({ name, detail });
        for (const choice of scenario.choices) {
            await component.choose(choice.id);
            assert.equal(mountedOptions.stopAtDecisionPoint, true);
            assert.equal(component.selected.id, choice.id);
            assert.equal(state, outcomes[choice.id]);
            assert.equal(events.at(-1).detail.correct, choice.correct);
            assert.equal(events.at(-1).detail.id, 'block-under-test');
            component.retry();
            assert.equal(component.selected, null);
            assert.equal(state, factory === 'routeDecision3d' ? 'none' : 'intro');
            checked++;
        }
        component.close();
        const legacy = create(scenario.choices, 'legacy');
        legacy.$refs = { viewport: {} };
        await legacy.open();
        assert.equal(mountedOptions.stopAtDecisionPoint, false, 'Old callers must not opt in');
    }
}
console.log(`PASS: ${checked} reordered choices + retry, flag forwarding and legacy defaults. Engines mocked; no visual QA claimed.`);
