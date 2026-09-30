import assert from 'node:assert/strict';
import { passengerPose, passengerModes, passengerChoices } from '../resources/js/passenger-preview-path.js';
for (const mode of passengerModes) {
    for (const choice of ['', ...passengerChoices]) {
        for(let i=0;i<=100;i++) {
            const p=passengerPose(mode,choice,i/10);
            for(const k of ['x','y','z','busX','door']) assert.ok(Number.isFinite(p[k]),`${mode}/${choice}/${k}`);
            assert.ok(p.caption.length>10);
            if(choice==='segura') {
                if(mode==='descent') {assert.equal(p.z,.35);assert.equal(p.door,0);}
                else assert.ok(p.z>=6);
            }
            if(choice && mode!=='descent') assert.ok(p.z>=4.05,'No unsafe path enters traffic');
        }
    }
    assert.notDeepEqual(passengerPose(mode,'impulso',5),passengerPose(mode,'copiar',5));
}
assert.equal(passengerPose('after','',0).y,.78);
assert.equal(passengerPose('after','',10).z,6);
assert.equal(passengerPose('moving','segura',10).busX,0);
assert.throws(()=>passengerPose('invalid','',0));
console.log('Passenger trajectories passed: 4 scenes, 12 decisions, 1616 sampled poses.');
