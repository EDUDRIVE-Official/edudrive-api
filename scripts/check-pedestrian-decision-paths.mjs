import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { CatmullRomCurve3, Vector3 } from 'three';
import { pedestrianDecisionPoints } from '../resources/js/pedestrian-decision-paths.js';

const expected = {
    parked: [[.2, 0, 6.5], [16, 0, 6.5], [16, 0, -6.5], [10, 0, -8]],
    visibility: [[1.7, 0, 7], [17, 0, 7], [17, 0, -7]],
    route: [[-14, .3, 5.8], [-10, .3, 5.8], [-10, .3, -5.8], [14, .3, -5.8]],
    ramp: [[-6, .3, 6], [13, .3, 6], [13, .3, -6], [18, .3, -6]],
};
let samples = 0;
for (const [scene, points] of Object.entries(expected)) {
    assert.deepEqual(pedestrianDecisionPoints(scene), points, `${scene}: preserve legacy path`);
    for (const value of [false, 'true', 1, null]) {
        assert.deepEqual(pedestrianDecisionPoints(scene, value), points, 'Only boolean true opts in');
    }
    const waiting = pedestrianDecisionPoints(scene, true);
    assert.deepEqual(waiting, points.slice(0, 2));
    const curve = new CatmullRomCurve3(waiting.map(p => new Vector3(...p)), false, 'catmullrom', scene === 'route' ? .15 : .08);
    for (let i = 0; i <= 1000; i++) {
        const p = curve.getPointAt(i / 1000);
        assert(Number.isFinite(p.x) && Number.isFinite(p.y) && Number.isFinite(p.z));
        assert(Math.abs(p.z - points[0][2]) < 1e-9, `${scene}: must remain on same sidewalk`);
        assert(p.x >= Math.min(points[0][0], points[1][0]) - 1e-9 && p.x <= Math.max(points[0][0], points[1][0]) + 1e-9);
        samples++;
    }
    waiting[0][2] = -100;
    assert.deepEqual(pedestrianDecisionPoints(scene, true), points.slice(0, 2), 'No shared mutation');
}
assert.throws(() => pedestrianDecisionPoints('unknown'));
const draft = JSON.parse(readFileSync(new URL('../docs/product/BORRADOR-BLOQUES-CAMINO-PASAJERO-v1.json', import.meta.url)));
const enabled = draft.lessons.flatMap(l => l.blocks).filter(b => b.payload.stop_at_decision_point === true);
assert.deepEqual(enabled.map(b => b.payload.title).sort(), ['El atajo entre automóviles', 'La esquina con poca visibilidad', 'Dos caminos a la escuela', 'La rampa está bloqueada'].sort());
assert(draft.lessons.every(l => l.source_blocks.every(b => !b.payload.stop_at_decision_point)), 'Source snapshot not modified');
console.log(`PASS: 4 opt-in paths, ${samples} curve samples, legacy coordinates unchanged. Not a browser or visual test.`);
