import assert from 'node:assert/strict';
import {angryShopPose,shopTrafficPose} from '../resources/js/angry-shop-decision-3d.js';
for(const mode of ['intro','normal','pausa','rapido']){
    let last=angryShopPose(mode,0);
    for(let t=.02;t<=18;t+=.02){
        const p=angryShopPose(mode,t);
        assert(Math.hypot(p.x-last.x,p.z-last.z)<.09,'Continuous route');
        assert(p.z>=7.6,'Keep entire body off the roadway');
        if(p.z>=14.7)assert(p.x===-12,'Use actual shop doorway');
        assert(p.tension>=0&&p.tension<=1);
        last=p;
    }
}
assert.equal(angryShopPose('pausa',12).tension,0);
assert.equal(angryShopPose('pausa',12).walking,false);
assert.equal(angryShopPose('pausa',12).z,11.5);
assert(angryShopPose('rapido',8).x>angryShopPose('normal',8).x);
for(let i=0;i<6;i++){
    assert.equal(shopTrafficPose(i,1).heading,0);
    assert(shopTrafficPose(i,1.1).x>shopTrafficPose(i,1).x,'Both lanes move in positive x');
    assert.equal(Math.abs(shopTrafficPose(i,1).z),3.5);
}
console.log('Shop: doorway, safe pause, distinct speeds and one-way traffic verified.');
