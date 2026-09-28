import assert from 'node:assert/strict';
import { pilotTurnState } from '../resources/js/pilot-turn-path.js';

for (let t = 0; t <= 9; t += .01) {
    const s = pilotTurnState(t, 'wait');
    // Check oriented vehicle footprint, not only its centre.
    for (const x of [-.75,.75]) for (const z of [-1.3,1.3]) {
        const worldX = s.carX + x*Math.cos(s.carYaw) + z*Math.sin(s.carYaw);
        const worldZ = s.carZ - x*Math.sin(s.carYaw) + z*Math.cos(s.carYaw);
        assert.ok(Math.abs(worldX) <= 3 || Math.abs(worldZ) <= 3, 'Vehicle leaves roadway');
        assert.ok(worldZ < 4.4, 'Vehicle reaches stop line/crossing');
        assert.ok(worldZ > 0, 'Vehicle enters opposing east/west lane');
    }
    if (t <= 4) assert.equal(s.crossing, 0, 'Pedestrian moves before the stop');
    if (t >= 3) assert.equal(s.carZ, pilotTurnState(3, 'wait').carZ);
    const early = pilotTurnState(t, 'go');
    if (t > 0) assert.ok(early.lunaX < 4.3, 'Early choice must start crossing');
    assert.ok(early.lunaX >= 1.7 && early.adultX >= 2, 'Freeze before reaching vehicle lane');
    if (t >= 2.5) assert.deepEqual(early, pilotTurnState(2.5,'go'), 'Entire scene must freeze');
}
assert.ok(pilotTurnState(9,'wait').lunaX < -3);
assert.ok(pilotTurnState(9,'wait').adultX < -3);
assert.ok(pilotTurnState(2.5,'go').lunaX < 3);
assert.ok(pilotTurnState(2.5,'go').adultX < 3);
assert.ok(pilotTurnState(2.5,'go').carZ < pilotTurnState(3,'wait').carZ);
console.log('Pilot turn: safe crossing follows stop; early choice enters crosswalk and freezes both actors before contact, while car is still turning.');
