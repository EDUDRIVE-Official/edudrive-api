import assert from 'node:assert/strict';
import { pilotDescentState } from '../resources/js/pilot-descent-path.js';

for (let t=0;t<=6;t+=.02) {
    const safe=pilotDescentState(t,'wait');
    assert(safe.lunaZ < 3, 'Safe choice keeps Luna inside the vehicle');
    const exposed=pilotDescentState(t,'go');
    assert(exposed.lunaZ <= 3.8, 'Unsafe representation stops on the shoulder');
}
assert(pilotDescentState(4,'go').lunaZ > 3, 'Unsafe choice demonstrates the exposed descent');
assert(pilotDescentState(4,'wait').message, 'Safe choice communicates the missing sidewalk');
assert.deepEqual(pilotDescentState(4,'go'),pilotDescentState(10,'go'),'Scene freezes after four seconds');
console.log('Descent paths: protected wait, shoulder freeze and communication verified.');
