import * as THREE from 'three';
import { pilotVanState } from './pilot-van-path';

export function mountPilotVan(host, done) {
    const scene=new THREE.Scene(); scene.background=new THREE.Color('#cee7ef');
    const camera=new THREE.PerspectiveCamera(43,1,.1,100);
    camera.position.set(10,10,15); camera.lookAt(2,0,1);
    const renderer=new THREE.WebGLRenderer({antialias:true}); renderer.setPixelRatio(Math.min(devicePixelRatio||1,2));
    renderer.outputColorSpace=THREE.SRGBColorSpace; renderer.shadowMap.enabled=true;
    renderer.domElement.style.cssText='width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role','img'); renderer.domElement.setAttribute('aria-label','Una van estacionada junto a la acera tapa parte de la vía. Luna y un adulto están detrás del borde. A la derecha hay acera libre.'); host.appendChild(renderer.domElement);
    scene.add(new THREE.HemisphereLight(0xffffff,0x657f56,2.5)); const sun=new THREE.DirectionalLight(0xfff1d5,3); sun.position.set(4,18,6); sun.castShadow=true; scene.add(sun);
    const mats=[];
    function mat(color,extra={}){const m=new THREE.MeshStandardMaterial({color,roughness:.65,...extra}); mats.push(m);return m;}
    function mesh(geometry,material,x,y,z,parent=scene){const o=new THREE.Mesh(geometry,material);o.position.set(x,y,z);o.castShadow=o.receiveShadow=true;parent.add(o);return o;}
    const box=(w,h,d,m,x,y,z,p)=>mesh(new THREE.BoxGeometry(w,h,d),m,x,y,z,p);
    const road=mat('#45545e'),walk=mat('#d5d3c8'),paint=mat('#f5f1dc'),glass=mat('#294754'),tire=mat('#232a31');
    box(40,.1,24,mat('#8aac79'),0,-.3,0);box(38,.1,6,road,0,-.1,0);
    for(const z of [-5,5])box(38,.2,4,walk,0,0,z);
    for(let x=-17;x<18;x+=3)box(1.5,.015,.09,mat('#e6bd46'),x,.01,0);
    for(const x of [-10,1,11]){box(6,4,4,mat(x===1?'#b7ccd2':'#dbbba0'),x,2,-10);box(4,1.5,.04,glass,x,2,-7.97);}
    // Parked van is wholly in the near lane, longitudinally aligned, front +x.
    const van=new THREE.Group();van.position.set(-1,0,2);scene.add(van);
    box(4.7,1.35,1.7,paint,0,1.05,0,van);box(4.3,.85,1.66,paint,-.1,2.02,0,van);
    box(.04,.65,1.4,glass,2.07,2.05,0,van);
    for(const z of [-.84,.84])for(const x of [-1.35,-.2,1.1])box(.85,.52,.025,glass,x,2.08,z,van);
    const seam=mat('#a4aaa6');box(.02,1.5,.015,seam,.25,1.48,.86,van);box(.28,.045,.025,seam,.48,1.42,.87,van);
    for(const x of [-1.5,1.45])for(const z of [-.86,.86]){const w=mesh(new THREE.CylinderGeometry(.37,.37,.18,24),tire,x,.38,z,van);w.rotation.x=Math.PI/2;}
    for(const z of [-.58,.58])box(.03,.18,.28,mat('#fff2c4',{emissive:'#ffe7a0',emissiveIntensity:.4}),2.36,1.06,z,van);
    function person(x,z,scale,color){const g=new THREE.Group();g.position.set(x,.14,z);g.scale.setScalar(scale);scene.add(g);mesh(new THREE.SphereGeometry(.18,16,12),mat('#bf8d69'),0,1.35,0,g);box(.38,.5,.25,mat(color),0,.91,0,g);const legs=[];for(const s of [-1,1]){legs.push(box(.13,.55,.15,mat('#304057'),s*.12,.37,0,g));box(.1,.43,.12,mat(color),s*.25,.87,0,g);}return {g,legs};}
    const luna=person(2.6,4.5,.85,'#f2ad24'),adult=person(3.2,5.3,1.1,'#70598d');
    // Neutral location markers, not a suggestion that either is a legal crossing.
    function label(text,x,z){const canvas=document.createElement('canvas');canvas.width=256;canvas.height=64;const c=canvas.getContext('2d');c.fillStyle='#17364c';c.fillRect(0,0,256,64);c.fillStyle='white';c.font='bold 25px sans-serif';c.textAlign='center';c.fillText(text,128,42);const texture=new THREE.CanvasTexture(canvas);const m=new THREE.SpriteMaterial({map:texture});const s=new THREE.Sprite(m);s.position.set(x,2.8,z);s.scale.set(3.7,.92,1);scene.add(s);return {texture,m};}
    const labels=[label('Luna',2.6,4.5),label('Acera de enfrente',3,-5)];
    const lunaLabel=scene.children.filter(o=>o.isSprite)[0];
    luna.g.attach(lunaLabel);
    const point=mesh(new THREE.RingGeometry(.38,.5,32),mat('#317ba7',{side:THREE.DoubleSide}),7,.13,4.5);point.rotation.x=-Math.PI/2;
    let running=false,paused=false,disposed=false,time=0,choice='',frame,last=performance.now();
    function update(){const s=pilotVanState(time,choice);luna.g.position.set(s.x,.14,s.z);adult.g.position.set(s.adultX,.14,s.adultZ);for(const p of [luna,adult])p.legs.forEach((leg,i)=>leg.rotation.x=s.walking?Math.sin(time*8+i*Math.PI)*.25:0);}
    const observer=new ResizeObserver(()=>{if(!host.clientWidth||!host.clientHeight)return;renderer.setSize(host.clientWidth,host.clientHeight,false);camera.aspect=host.clientWidth/host.clientHeight;camera.updateProjectionMatrix();});observer.observe(host);
    function render(now){if(disposed)return;const dt=Math.min((now-last)/1000,.05);last=now;if(running&&!paused){time=Math.min(time+dt,4);update();if(time===4){running=false;done();}}renderer.render(scene,camera);frame=requestAnimationFrame(render);}
    update();frame=requestAnimationFrame(render);
    return {play(v){choice=v;time=0;running=true;paused=false;update();},pause(v){paused=v;},finish(v){choice=v;time=4;running=false;update();},reset(){choice='';time=0;running=false;paused=false;update();},dispose(){disposed=true;cancelAnimationFrame(frame);observer.disconnect();scene.traverse(o=>o.geometry?.dispose());mats.forEach(m=>m.dispose());labels.forEach(l=>{l.texture.dispose();l.m.dispose();});renderer.dispose();renderer.domElement.remove();}};
}
