import assert from 'node:assert/strict';
import {busExitPose,busCaption} from '../resources/js/headphones-bus-decision-3d.js';
for(const mode of ['intro','seguir','pausar','correr']) {
    let previous=busExitPose(mode,0);
    for(let t=.02;t<=24;t+=.02) {
        const p=busExitPose(mode,t);
        assert(Math.hypot(p.x-previous.x,p.z-previous.z)<.1,'Continuous trajectory');
        assert(Math.abs(p.y-previous.y)<.04,'Continuous descent');
        if(t<4) assert.equal(p.x,27,'Exit through door');
        if(t>=4 && mode==='pausar') assert(p.x>=7.3 && p.z>=7.3,'Safe route stays on sidewalk');
        if(t>=4 && p.z<7.3) assert(p.x<=18,'Must clear rear before entering roadway');
        if(mode==='pausar'&&p.look!==0) assert(p.stowed,'Observe after stowing distractions');
        assert(busCaption(mode,t).length>0);
        previous=p;
    }
}
assert.equal(busExitPose('intro',4.2).z,8.8);
assert.equal(busExitPose('intro',4.2).walking,false);
assert.equal(busExitPose('pausar',24).z,15);
assert.equal(busExitPose('pausar',24).x,13);
assert(busExitPose('correr',12).z<busExitPose('seguir',16).z);
console.log('Bus: doorway, continuous descent, rear clearance and safe route verified.');
