import * as THREE from 'three';
import { pilotTurnState } from './pilot-turn-path';

// World: east +x, south +z. Eastbound lane z=+1.5, southbound lane x=-1.5.
// Right turn stays in those lanes. South-arm crossing is outside the junction.
export function mountPilotTurn(host, onFinish) {
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#cfe8f1');
    const camera = new THREE.PerspectiveCamera(42, 1, .1, 100);
    camera.position.set(16, 19, 23); camera.lookAt(0, 0, 3);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio || 1, 2));
    renderer.shadowMap.enabled = true;
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Luna y un adulto esperan en la acera junto al paso del lado sur. Un carro con direccional derecha comienza a girar hacia ese paso.');
    host.appendChild(renderer.domElement);
    scene.add(new THREE.HemisphereLight(0xffffff, 0x647652, 2.5));
    const sun = new THREE.DirectionalLight(0xfff1d9, 3); sun.position.set(8, 20, 9); sun.castShadow = true; scene.add(sun);
    const materials = [];
    const material = (color, extra = {}) => { const m = new THREE.MeshStandardMaterial({ color, roughness: .65, ...extra }); materials.push(m); return m; };
    const asphalt = material('#45535d'), concrete = material('#d9d6c9'), white = material('#fffdf1'), metal = material('#303d47');
    function mesh(geo, mat, x, y, z, parent = scene) {
        const m = new THREE.Mesh(geo, mat); m.position.set(x,y,z); m.castShadow = m.receiveShadow = true; parent.add(m); return m;
    }
    const box = (w,h,d,mat,x,y,z,parent) => mesh(new THREE.BoxGeometry(w,h,d),mat,x,y,z,parent);
    box(40,.2,40,material('#80a574'),0,-.3,0);
    box(36,.1,6,asphalt,0,-.1,0); box(6,.1,36,asphalt,0,-.1,0);
    // Only corner blocks: no sidewalks across the roads.
    for (const x of [-10,10]) for (const z of [-10,10]) {
        box(14,.18,14,concrete,x,0,z);
        box(5,3.8,5,material(x*z>0?'#d9b991':'#b7cbd7'),x,2,z);
        for (const dx of [-1.4,0,1.4]) box(.8,1,.04,material('#344f63'),x+dx,2.5,z+2.53);
    }
    const yellow = material('#f1c94c');
    for(let p=-16;p<=16;p+=3) if(Math.abs(p)>4) {
        box(1.6,.015,.09,yellow,p,.01,0);
        if(Math.abs(p-5.8)>2) box(.09,.015,1.6,yellow,0,.01,p);
    }
    for(let x=-2.7;x<3;x+=.8) box(.42,.025,2,white,x,.025,5.8);
    box(2.7,.02,.14,white,-1.5,.025,4.4);
    // Level landing aligns with the crossing; adjacent raised sidewalks remain visible.
    for(const x of [-3.65,3.65]) box(1.3,.06,2,concrete,x,.02,5.8);
    function person(x,z,scale,color) {
        const g=new THREE.Group(); scene.add(g); g.position.set(x,.14,z); g.scale.setScalar(scale);
        mesh(new THREE.SphereGeometry(.18,16,12),material('#be8964'),0,1.35,0,g);
        box(.38,.5,.24,material(color),0,.91,0,g);
        const legs=[];
        for(const side of [-1,1]) {
            legs.push(box(.13,.55,.16,material('#293b54'),side*.12,.37,0,g));
            box(.11,.44,.14,material(color),side*.26,.86,0,g);
        }
        return {g,legs};
    }
    const luna=person(4.3,5.5,.85,'#f0aa24'), adult=person(4.9,6.3,1.1,'#685482');
    const car = new THREE.Group(); scene.add(car);
    const paint=material('#167faa',{metalness:.5}), glass=material('#203d51',{metalness:.35});
    box(1.25,.45,2.4,paint,0,.6,0,car); box(1.08,.48,1.2,glass,0,1,.05,car);
    box(1.12,.08,1.15,paint,0,1.27,.05,car);
    for(const x of [-.67,.67]) for(const z of [-.75,.75]) {
        const wheel=mesh(new THREE.CylinderGeometry(.29,.29,.16,24),material('#20272d'),x,.33,z,car); wheel.rotation.z=Math.PI/2;
    }
    const lamp=material('#fff8d5',{emissive:'#fff1ad',emissiveIntensity:1});
    for(const x of [-.43,.43]) box(.28,.13,.05,lamp,x,.69,1.22,car);
    const indicator=material('#ffb32e',{emissive:'#ff9e0a',emissiveIntensity:2});
    // Local forward is +z; local right is -x.
    box(.14,.14,.06,indicator,-.57,.67,1.24,car); box(.14,.14,.06,indicator,-.57,.67,-1.24,car);
    function pedestrianSignal(x,rotation) {
        const g=new THREE.Group(); scene.add(g); g.position.set(x,0,7.1); g.rotation.y=rotation;
        box(.1,2.4,.1,metal,0,1.2,0,g); box(.55,.8,.22,metal,0,2.7,0,g);
        const green=material('#37f080',{emissive:'#24d56a',emissiveIntensity:1.4});
        mesh(new THREE.SphereGeometry(.065,12,8),green,0,2.93,.13,g);
        box(.06,.22,.025,green,0,2.74,.13,g);
        for(const side of [-1,1]) { const leg=box(.035,.2,.025,green,side*.055,2.56,.13,g); leg.rotation.z=side*.4; }
    }
    pedestrianSignal(-3.7,Math.PI/2); pedestrianSignal(3.7,-Math.PI/2);
    let time=0, running=false, paused=false, choice='', frame, disposed=false;
    // Stop on curve when front bumper is still before the stop line.
    function update(t) {
        const state = pilotTurnState(t, choice);
        car.position.set(state.carX,0,state.carZ); car.rotation.y=state.carYaw;
        indicator.emissiveIntensity=running ? (Math.floor(t*2)%2 ? .2:2.5):2;
        const progress=state.crossing;
        luna.g.position.x=state.lunaX; adult.g.position.x=state.adultX;
        for(const p of [luna,adult]) p.legs.forEach((leg,i)=>{leg.rotation.x=progress>0&&progress<1?Math.sin(t*8+i*Math.PI)*.25:0;});
    }
    const observer=new ResizeObserver(()=>{const w=host.clientWidth,h=host.clientHeight;if(!w||!h)return; renderer.setSize(w,h,false);camera.aspect=w/h;camera.updateProjectionMatrix();});observer.observe(host);
    let previous=performance.now();
    function render(now) {
        if(disposed)return;
        const delta=Math.min((now-previous)/1000,.05);previous=now;
        if(running&&!paused){time=Math.min(time+delta,choice==='go'?2.5:9);update(time);if(time>=(choice==='go'?2.5:9)){running=false;onFinish();}}
        renderer.render(scene,camera);frame=requestAnimationFrame(render);
    }
    update(0);frame=requestAnimationFrame(render);
    return {
        play(value){choice=value;time=0;running=true;paused=false;update(0);},
        pause(value){paused=value;},
        finish(value){choice=value;running=false;time=choice==='go'?2.5:9;update(time);},
        reset(){choice='';running=false;time=0;update(0);},
        dispose(){disposed=true;cancelAnimationFrame(frame);observer.disconnect();scene.traverse(o=>o.geometry?.dispose());materials.forEach(m=>m.dispose());renderer.dispose();renderer.domElement.remove();},
    };
}
