import * as THREE from 'three';
import { passengerPose } from './passenger-preview-path.js';

export function mountPassengerPreview(host, mode, onCaption) {
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#cee7f5');
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 150);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.shadowMap.enabled = true; renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.domElement.style.cssText = 'display:block;width:100%;height:100%';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Maqueta 3D de Luna y su acompañante, vehículos, acera y acceso de pasajeros. La descripción del movimiento aparece debajo.');
    host.appendChild(renderer.domElement);
    scene.add(new THREE.HemisphereLight(0xffffff, 0x657856, 2.4));
    const sun = new THREE.DirectionalLight(0xffefd5, 3); sun.position.set(8, 20, 12); sun.castShadow = true; scene.add(sun);
    const mats = new Map();
    function material(color) {
        if (!mats.has(color)) mats.set(color, new THREE.MeshStandardMaterial({ color, roughness: .65 }));
        return mats.get(color);
    }
    function mesh(geometry, color, x, y, z, parent = scene) {
        const o = new THREE.Mesh(geometry, material(color)); o.position.set(x, y, z);
        o.castShadow = o.receiveShadow = true; parent.add(o); return o;
    }
    const box = (w,h,d,color,x,y,z,parent) => mesh(new THREE.BoxGeometry(w,h,d),color,x,y,z,parent);
    box(50,.2,32,'#90ad77',0,-.25,0);
    box(48,.1,8,'#495764',0,-.06,0);
    for (const z of [-6.5,6.5]) {
        box(48,.25,5,'#d6d8d2',0,.08,z);
        box(48,.32,.18,'#f1ead9',0,.1,Math.sign(z)*4.05);
        for (let x=-24;x<24;x+=2) box(.025,.01,5,'#bcc1be',x,.21,z);
    }
    for(let x=-22;x<24;x+=3) box(1.5,.015,.1,'#f8db74',x,.01,0);
    for(let x=-18;x<22;x+=8) {
        box(5,5,4,x%3?'#d5b79d':'#a7c6cb',x,2.5,-11);
        for(const dx of [-1.4,0,1.4]) for(const y of [1.8,3.5]) box(.8,.9,.04,'#54768a',x+dx,y,-8.97);
        box(1,1.6,.05,'#6a574c',x,.8,-8.94);
    }
    for (const x of [-13,13]) {
        mesh(new THREE.CylinderGeometry(.07,.07,4,12),'#40515c',x,2,8);
        box(1.1,.12,.45,'#e7d7ac',x,4,7.65);
    }
    function wheels(parent, xs, z, radius) {
        for (const x of xs) for (const side of [-1,1]) {
            const w=mesh(new THREE.CylinderGeometry(radius,radius,.22,24),'#202b35',x,radius,z*side,parent); w.rotation.x=Math.PI/2;
            const hub=mesh(new THREE.CylinderGeometry(radius*.5,radius*.5,.24,16),'#b8c5cc',x,radius,z*side,parent);hub.rotation.x=Math.PI/2;
        }
    }
    function car(x,z,color,cutaway=false) {
        const g=new THREE.Group(); g.position.set(x,0,z);scene.add(g);
        box(4.6,.6,1.85,color,0,.7,0,g);
        box(1.1,.35,1.8,color,1.7,1,0,g); box(.7,.35,1.8,color,-1.9,1,0,g);
        if(!cutaway) {box(2.5,.75,1.6,'#6699ac',-.1,1.3,0,g);box(2.6,.12,1.7,color,-.1,1.75,0,g);}
        else {
            // Cutaway roof is omitted so the occupants remain visible from above.
            box(2.6,.65,.07,'#6699ac',-.1,1.4,-.85,g);
            for(const sx of [-.6,.8]) box(.5,.2,.6,'#303e4d',sx,.85,0,g);
        }
        wheels(g,[-1.5,1.5],.95,.38);
        for(const zz of [-.6,.6])box(.06,.2,.35,'#fff2b0',2.34,.9,zz,g);
        return g;
    }
    const bus=new THREE.Group();bus.position.z=2.5;scene.add(bus);
    // Open doorway at x=2 on the sidewalk side; no wall crosses the descent path.
    box(8,.28,2.4,'#ebbf3a',0,.65,0,bus);
    box(8,.15,2.5,'#356fa3',0,3.25,0,bus);
    box(8,1,.1,'#ebbf3a',0,1.25,-1.2,bus);
    box(5.3,1,.1,'#ebbf3a',-1.35,1.25,1.2,bus);
    box(1.25,1,.1,'#ebbf3a',3.35,1.25,1.2,bus);
    for(const x of [-3.2,-1.8,-.4,3.3])for(const z of [-1.22,1.22])box(1.1,1.1,.06,'#78b6ce',x,2.35,z,bus);
    box(1.5,1.1,.06,'#78b6ce',1.6,2.35,-1.22,bus);
    box(.1,2.25,2.4,'#356fa3',-4,1.9,0,bus);
    box(.1,1.25,2.4,'#78b6ce',4,2.5,0,bus);box(.1,1,2.4,'#ebbf3a',4,1.2,0,bus);
    for(const x of [-3,-1.6,0])for(const z of [-.6,.6]) {box(.65,.15,.5,'#356fa3',x,1.1,z,bus);box(.12,.6,.5,'#356fa3',x-.3,1.4,z,bus);}
    for(const [z,y] of [[.6,.62],[1.1,.42],[1.4,.24]])box(1.3,.17,.5,'#9ca8ac',2,y,z,bus);
    for(const x of [1.3,2.7]) {const d=box(.65,2.15,.06,'#6596ac',x,1.85,1.5,bus);d.rotation.y=x<2?1.2:-1.2;}
    wheels(bus,[-2.6,2.7],1.23,.48);
    for(const z of [-.85,.85])box(.07,.25,.35,'#fff1b8',4.07,1.1,z,bus);
    const isBus=mode==='moving'||mode==='after';bus.visible=isBus;
    const mainCar=car(0,mode==='descent'?0:2.5,'#bb5d4e',true);mainCar.visible=!isBus;
    if(mode==='boarding') {
        box(2.6,.8,.08,'#bb5d4e',0,1.05,.94,mainCar);
        box(2.6,.45,.06,'#8cbdce',0,1.65,.94,mainCar);
    }
    const door=new THREE.Group();door.position.set(1.3,0,mode==='boarding'?-.94:.94);mainCar.add(door);
    box(1.25,.9,.07,'#bb5d4e',-.6,1.05,0,door);box(1.2,.5,.05,'#8cbdce',-.6,1.65,0,door);
    const queued = [car(-5.7,0,'#698eac'),car(5.7,0,'#d1b763'),car(0,2.8,'#889c92')];
    queued.forEach(g=>g.visible=mode==='descent');
    // Other traffic stays in the far lane, never intersects the demonstrated paths.
    const traffic=car(-12,-2,'#f1cc64');
    function person(color,scale=1) {
        const g=new THREE.Group();scene.add(g);g.scale.setScalar(scale);
        mesh(new THREE.SphereGeometry(.19,16,12),'#b98260',0,1.55,0,g);
        mesh(new THREE.SphereGeometry(.195,16,12),'#453629',0,1.65,-.035,g);
        box(.43,.58,.28,color,0,1.09,0,g);
        const limbs=[];
        for(const s of [-1,1]) {
            limbs.push(box(.14,.65,.16,'#34445d',s*.13,.49,0,g));
            box(.16,.12,.28,'#202f3d',s*.13,.1,.05,g);
        }
        const arm=box(.12,.5,.14,color,.31,1.12,0,g);box(.12,.5,.14,color,-.31,1.12,0,g);
        return {g,limbs,arm};
    }
    const luna=person('#efb82e',.8), adult=person('#7563a2',1), other=person('#4d9479',.95);
    adult.g.position.set(.4,.22,6.4);other.g.visible=false;
    const marker=mesh(new THREE.TorusGeometry(.6,.045,8,40),'#d58322',0,.24,0);marker.rotation.x=Math.PI/2;
    let choice='',time=0,last=performance.now(),paused=false,disposed=false,frame,previousCaption='';
    const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function setView(view) {
        camera.position.set(...(view==='detail'?[8,6,12]:[15,14,19]));camera.lookAt(0,1,2);
    }
    setView('overview');
    function update() {
        const p=passengerPose(mode,choice,time);
        const oldX=luna.g.position.x,oldZ=luna.g.position.z;
        luna.g.position.set(p.x,p.y,p.z);
        if(p.walk&&Math.hypot(p.x-oldX,p.z-oldZ)>.001)luna.g.rotation.y=Math.atan2(p.x-oldX,p.z-oldZ);
        if(p.signal)luna.g.rotation.y=-Math.PI/2;
        luna.limbs.forEach((leg,i)=>leg.rotation.x=p.walk?Math.sin(time*9+i*Math.PI)*.35:0);
        luna.arm.rotation.z=p.signal?-.9:0;
        bus.position.x=p.busX;
        door.rotation.y=(mode==='boarding'?-1:1)*p.door*1.15;
        adult.g.position.set(mode==='descent'?-.7:mode==='after'&&choice==='segura'?p.x-1:.4,mode==='descent'?.65:.22,mode==='descent'?-.25:6.4);
        adult.limbs.forEach((leg,i)=>leg.rotation.x=mode==='after'&&choice==='segura'&&p.walk?Math.sin(time*9+i*Math.PI)*.25:0);
        other.g.visible=choice==='copiar';
        other.g.position.set(p.x+(mode==='boarding'?-1:1),p.y,p.z+(mode==='descent'?.3:-.35));
        other.limbs.forEach((leg,i)=>leg.rotation.x=p.walk?Math.sin(time*9+i*Math.PI)*.3:0);
        traffic.position.x=mode==='descent'?-12:12-Math.min(time,choice&&choice!=='segura'?3:10)*1.8;
        marker.visible=p.signal;marker.position.set(p.x,.25,p.z);
        if(previousCaption!==p.caption){previousCaption=p.caption;onCaption(p.caption);}
    }
    const resize=()=>{if(host.clientWidth&&host.clientHeight){renderer.setSize(host.clientWidth,host.clientHeight,false);camera.aspect=host.clientWidth/host.clientHeight;camera.updateProjectionMatrix();}};
    const observer=new ResizeObserver(resize);observer.observe(host);resize();
    function render(now){if(disposed)return;const dt=Math.min((now-last)/1000,.05);last=now;if(!paused)time=Math.min(10,time+dt);update();renderer.render(scene,camera);frame=requestAnimationFrame(render);}
    if(reduced)time=10;paused=reduced;update();frame=requestAnimationFrame(render);
    return {
        setOutcome(id){choice=id||'';time=reduced?10:0;paused=reduced;update();},
        setPaused(value){paused=value;},setView,
        dispose(){disposed=true;cancelAnimationFrame(frame);observer.disconnect();scene.traverse(o=>o.geometry?.dispose());mats.forEach(m=>m.dispose());renderer.dispose();renderer.domElement.remove();},
    };
}
