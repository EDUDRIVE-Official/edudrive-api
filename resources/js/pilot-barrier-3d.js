import * as THREE from 'three';
import { pilotBarrierState } from './pilot-barrier-path';

export function mountPilotBarrier(host,done){
    const scene=new THREE.Scene();scene.background=new THREE.Color('#cae5f0');
    const camera=new THREE.PerspectiveCamera(42,1,.1,100);camera.position.set(11,10,16);camera.lookAt(1,0,1.5);
    const renderer=new THREE.WebGLRenderer({antialias:true});renderer.setPixelRatio(Math.min(devicePixelRatio||1,2));renderer.outputColorSpace=THREE.SRGBColorSpace;renderer.shadowMap.enabled=true;renderer.domElement.style.cssText='width:100%;height:100%;display:block';renderer.domElement.setAttribute('role','img');renderer.domElement.setAttribute('aria-label','Una obra vallada bloquea todo el ancho de la acera. Luna y una persona adulta permanecen antes de la barrera. La calzada tiene un carril por sentido.');host.appendChild(renderer.domElement);
    scene.add(new THREE.HemisphereLight(0xffffff,0x607a52,2.5));const sun=new THREE.DirectionalLight(0xfff1d8,3);sun.position.set(4,16,7);sun.castShadow=true;scene.add(sun);
    const materials=[];const mat=(color,extra={})=>{const m=new THREE.MeshStandardMaterial({color,roughness:.68,...extra});materials.push(m);return m;};
    function mesh(g,m,x,y,z,p=scene){const o=new THREE.Mesh(g,m);o.position.set(x,y,z);o.castShadow=o.receiveShadow=true;p.add(o);return o;}const box=(w,h,d,m,x,y,z,p)=>mesh(new THREE.BoxGeometry(w,h,d),m,x,y,z,p);
    box(40,.1,24,mat('#86a970'),0,-.35,0);box(38,.12,6,mat('#48565e'),0,-.12,0);box(38,.2,3.5,mat('#d7d4c8'),0,0,4.75);box(38,.2,3.5,mat('#d7d4c8'),0,0,-4.75);
    for(let x=-17;x<18;x+=3)box(1.5,.02,.09,mat('#e6bd46'),x,.01,0);
    for(const x of [-10,0,10])box(6,4,4,mat(x?'#d9b89d':'#b7cdd3'),x,2,-10);
    // Vallado ocupa de borde a borde de la acera: no deja un paso lateral representado como seguro.
    const orange=mat('#ee7d22'),white=mat('#f4f0df'),dark=mat('#313a40');
    for(const z of [3.2,4.2,5.2,6.2]){box(2.2,.75,.08,orange,2,1,z);box(2.2,.12,.09,white,2,1,z+.01);box(.12,1.8,.12,dark,1,.85,z);box(.12,1.8,.12,dark,3,.85,z);}
    // Materiales y excavadora al otro lado refuerzan que se trata de una obra real.
    box(3,.5,2.2,mat('#8b7057'),5,.25,4.7);box(1.8,.9,1.2,mat('#e3a928'),5.5,.75,4.7);const arm=box(2.7,.25,.28,mat('#e3a928'),4.2,1.55,4.7);arm.rotation.z=.55;
    for(const x of [4.9,6.1]){const w=mesh(new THREE.CylinderGeometry(.34,.34,.22,20),dark,x,.3,4.05);w.rotation.x=Math.PI/2;}
    for(const x of [0,3.8]){const cone=mesh(new THREE.ConeGeometry(.28,.75,18),orange,x,.38,2.7);box(.7,.08,.7,dark,x,.04,2.7);}
    function person(x,z,scale,color){const g=new THREE.Group();g.position.set(x,.14,z);g.scale.setScalar(scale);scene.add(g);mesh(new THREE.SphereGeometry(.18,16,12),mat('#bd8966'),0,1.35,0,g);box(.38,.5,.25,mat(color),0,.91,0,g);const legs=[];for(const s of [-1,1]){legs.push(box(.13,.55,.15,mat('#35445a'),s*.12,.37,0,g));box(.1,.43,.12,mat(color),s*.25,.87,0,g);}return{g,legs};}
    const luna=person(0,4.45,.85,'#f2ad24'),adult=person(-1,5.15,1.08,'#70598d');
    function sprite(text){const c=document.createElement('canvas');c.width=512;c.height=96;const x=c.getContext('2d');x.fillStyle='#17364c';x.fillRect(0,0,512,96);x.fillStyle='white';x.font='bold 28px sans-serif';x.textAlign='center';x.fillText(text,256,61);const texture=new THREE.CanvasTexture(c);const material=new THREE.SpriteMaterial({map:texture});const s=new THREE.Sprite(material);s.scale.set(5.2,1,1);scene.add(s);return{s,texture,material};}
    const warning=sprite('Acera cerrada por obra');warning.s.position.set(2,3,4.7);const message=sprite('Paremos y busquemos apoyo');message.s.position.set(-2.1,3.1,5);message.s.visible=false;
    let running=false,paused=false,disposed=false,time=0,choice='',frame,last=performance.now();
    function update(){const s=pilotBarrierState(time,choice);luna.g.position.set(s.lunaX,.14,s.lunaZ);adult.g.position.set(s.adultX,.14,s.adultZ);message.s.visible=s.message;for(const p of [luna,adult])p.legs.forEach((leg,i)=>leg.rotation.x=s.walking?Math.sin(time*8+i*Math.PI)*.24:0);}
    const observer=new ResizeObserver(()=>{if(!host.clientWidth||!host.clientHeight)return;renderer.setSize(host.clientWidth,host.clientHeight,false);camera.aspect=host.clientWidth/host.clientHeight;camera.updateProjectionMatrix();});observer.observe(host);
    function render(now){if(disposed)return;const dt=Math.min((now-last)/1000,.05);last=now;if(running&&!paused){time=Math.min(time+dt,4);update();if(time===4){running=false;done();}}renderer.render(scene,camera);frame=requestAnimationFrame(render);}update();frame=requestAnimationFrame(render);
    return{play(v){choice=v;time=0;running=true;paused=false;update();},pause(v){paused=v;},finish(v){choice=v;time=4;running=false;update();},reset(){choice='';time=0;running=false;paused=false;update();},dispose(){disposed=true;cancelAnimationFrame(frame);observer.disconnect();scene.traverse(o=>o.geometry?.dispose());materials.forEach(m=>m.dispose());for(const l of[warning,message]){l.texture.dispose();l.material.dispose();}renderer.dispose();renderer.domElement.remove();}};
}
