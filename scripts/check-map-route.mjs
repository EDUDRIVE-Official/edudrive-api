import assert from 'node:assert/strict';
import { mapRoutePose, mapRouteCaption } from '../resources/js/map-route-decision-3d.js';
import { messagePose } from '../resources/js/message-crossing-decision-3d.js';

for (const outcome of ['intro','detenerse','seguir-pantalla','copiar']) {
    for (let t=0;t<=12;t+=.025) {
        const p=mapRoutePose(outcome,t);
        assert(p.x>=7.3 && p.z>=7.3,'Luna must remain within the sidewalk');
        assert(p.read>=0 && p.read<=1,'Phone pose must stay bounded');
        if(outcome==='detenerse' && p.read>0) {
            assert(!p.walking,'Stop before reading');
            assert(p.x===13 && p.z===15,'Read only in the protected location');
        }
        if(outcome==='detenerse' && p.look!==0) assert(p.stowed && p.read===0);
        assert(mapRouteCaption(outcome,t).length>0);
    }
}
assert(mapRoutePose('detenerse',9).look>0);
assert(mapRoutePose('detenerse',10.5).look<0);
assert(mapRoutePose('detenerse',12).look>0);
assert(mapRoutePose('seguir-pantalla',7).walking && mapRoutePose('seguir-pantalla',7).read===1);
assert(mapRoutePose('seguir-pantalla',8).x>10,'Option A must actually turn');
assert(mapRoutePose('copiar',7).stowed,'Following someone is a separate distraction');
assert.notDeepEqual(mapRoutePose('copiar',7),mapRoutePose('seguir-pantalla',7));
assert.equal(messagePose('protegido',3).read,0,'Previous exercise must still defer reading');
assert.equal(messagePose('protegido',5).read,1);
console.log('Map route: trajectories, distinct outcomes, phone sequence and observation checks passed.');
