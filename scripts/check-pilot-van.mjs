import assert from 'node:assert/strict';
import { pilotVanState } from '../resources/js/pilot-van-path.js';

for (let t = 0; t <= 6; t += .02) {
    const safe = pilotVanState(t, 'wait');
    assert(safe.z >= 3.4 && safe.adultZ >= 3.4, 'Both remain inside sidewalk');
    for (const choice of ['wait', 'go']) {
        const state = pilotVanState(t, choice);
        assert(state.x - .3 > 1.35 && state.adultX - .4 > 1.35, 'No overlap with van');
    }
}
assert(pilotVanState(4, 'go').z < 3, 'Unsafe choice starts entering roadway');
assert.deepEqual(pilotVanState(4, 'go'), pilotVanState(10, 'go'), 'Unsafe scene freezes');
assert.equal(pilotVanState(4, 'wait').x, 7);
console.log('Van paths: sidewalk, vehicle clearance and freeze verified.');
