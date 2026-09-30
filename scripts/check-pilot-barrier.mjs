import assert from 'node:assert/strict';
import { pilotBarrierState } from '../resources/js/pilot-barrier-path.js';

for(let t=0;t<=6;t+=.02){
    const safe=pilotBarrierState(t,'wait');
    assert(safe.lunaZ>=3.4&&safe.adultZ>=3.4,'Safe choice keeps both people on sidewalk');
    assert(safe.lunaX<1&&safe.adultX<1,'Safe choice does not pass through barrier');
    const exposed=pilotBarrierState(t,'go');
    assert(exposed.lunaZ>=2.7,'Unsafe demonstration freezes before traffic lane');
    assert(exposed.lunaX<1,'Unsafe demonstration goes around, not through, barrier');
}
assert(pilotBarrierState(4,'go').lunaZ<3,'Unsafe choice reaches road edge');
assert(pilotBarrierState(4,'wait').message,'Safe choice communicates a plan change');
assert.deepEqual(pilotBarrierState(4,'go'),pilotBarrierState(8,'go'),'Risk demonstration freezes');
console.log('Barrier paths: protected retreat, no clipping and road-edge freeze verified.');
