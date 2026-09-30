import assert from 'node:assert/strict';
import {noisePose,noiseCaption} from '../resources/js/construction-noise-decision-3d.js';
for(const mode of ['intro','oido','margen-visual','rapido']){
    let last=noisePose(mode,0);
    for(let t=.02;t<=14;t+=.02){
        const p=noisePose(mode,t);
        assert(Math.hypot(p.x-last.x,p.z-last.z)<.07,'No position jumps');
        if(mode!=='rapido')assert(p.z>=7.3&&p.z<=11.7,'Remain within the sidewalk');
        if(mode==='margen-visual')assert(p.x<=-8||t<3,'Move away from the worksite');
        assert(p.stowed&&p.read===0,'No phone distraction in this scenario');
        assert(noiseCaption(mode,t).length>0);
        last=p;
    }
}
assert.equal(noisePose('margen-visual',14).x,-17);
assert.equal(noisePose('margen-visual',14).walking,false);
assert(noisePose('margen-visual',10).look>0);
assert(noisePose('margen-visual',11.5).look<0);
assert(noisePose('margen-visual',13).look>0);
assert(noisePose('rapido',6).z<7);
assert.equal(noisePose('rapido',6).walking,false);
console.log('Construction scene: all three outcomes and protected route verified.');
