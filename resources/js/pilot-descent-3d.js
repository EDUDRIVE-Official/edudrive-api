import * as THREE from 'three';
import { pilotDescentState } from './pilot-descent-path';

export function mountPilotDescent(host, done) {
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#cfe8f2');
    const camera = new THREE.PerspectiveCamera(42, 1, .1, 100);
    camera.position.set(8, 7, 11); camera.lookAt(.4, .8, 1.7);
    const renderer = new THREE.WebGLRenderer({antialias:true});
    renderer.setPixelRatio(Math.min(devicePixelRatio || 1, 2)); renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.shadowMap.enabled = true; renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role','img'); renderer.domElement.setAttribute('aria-label','Un microbús está detenido. La puerta abierta queda frente a un espaldón de grava sin acera. Luna y una persona adulta todavía están dentro.'); host.appendChild(renderer.domElement);
    scene.add(new THREE.HemisphereLight(0xffffff,0x627a52,2.5)); const sun = new THREE.DirectionalLight(0xfff2dc,3); sun.position.set(5,15,8); sun.castShadow=true; scene.add(sun);
    const materials=[]; const mat=(color,extra={})=>{const m=new THREE.MeshStandardMaterial({color,roughness:.68,...extra});materials.push(m);return m;};
    function mesh(g,m,x,y,z,p=scene){const o=new THREE.Mesh(g,m);o.position.set(x,y,z);o.castShadow=o.receiveShadow=true;p.add(o);return o;}
    const box=(w,h,d,m,x,y,z,p)=>mesh(new THREE.BoxGeometry(w,h,d),m,x,y,z,p);
    box(40,.1,23,mat('#88a96f'),0,-.35,0); box(38,.12,6,mat('#4b565d'),0,-.12,0);
    box(38,.14,2.2,mat('#9b8a6c'),0,-.03,4.1); box(38,.18,3.6,mat('#d7d4c6'),0,0,-4.8);
    for(let x=-17;x<18;x+=3) box(1.5,.02,.09,mat('#e9bd44'),x,.01,0);
    for(const x of [-10,0,10]) box(6,4,4,mat(x?'#d6b69e':'#b4cbd0'),x,2,-10);
    // Microbús longitudinal, detenido en su carril. El costado cercano queda abierto para explicar el descenso.
    const bus=new THREE.Group(); bus.position.set(0,0,1.8); scene.add(bus);
    // Vista de maqueta abierta por el costado cercano: piso, pared opuesta, extremos y techo.
    box(7,.65,1.85,mat('#f1eee3'),0,.48,0,bus);
    box(6.5,.18,1.8,mat('#2e6377'),-.15,2.56,0,bus);
    box(6.5,1.75,.12,mat('#2e6377'),-.15,1.62,-.86,bus);
    box(.18,1.75,1.8,mat('#f1eee3'),-3.42,1.62,0,bus);
    box(.18,1.75,1.8,mat('#f1eee3'),3.42,1.62,0,bus);
    box(.05,.75,1.55,mat('#294651'),3.28,2.05,0,bus);
    for(const x of [-2.3,-1.05,.2,1.45]) box(.9,.58,.025,mat('#98d0df'),x,2.08,-.93,bus);
    // Abertura real de la puerta en el costado de descenso, con escalón y dos hojas abiertas.
    box(1.15,1.85,.05,mat('#26343a'),2.05,1.15,.94,bus); box(1.1,.14,.65,mat('#a8a8a0'),2.05,.24,1.18,bus);
    const doorMat=mat('#e7e4da'); const d1=box(.5,1.75,.05,doorMat,1.45,1.15,1.28,bus);d1.rotation.y=-.75;
    const d2=box(.5,1.75,.05,doorMat,2.65,1.15,1.28,bus);d2.rotation.y=.75;
    const tire=mat('#22292e'); for(const x of [-2.25,2.25]) for(const z of [-.96,.96]){const w=mesh(new THREE.CylinderGeometry(.43,.43,.2,24),tire,x,.38,z,bus);w.rotation.x=Math.PI/2;}
    // Luces intermitentes de detención visibles, sin sugerir que el espaldón sea una zona segura.
    for(const x of [-3.52,3.52]) for(const z of [-.58,.58]) box(.04,.18,.28,mat('#f1a326',{emissive:'#f1a326',emissiveIntensity:.45}),x,1.08,z,bus);
    function person(x,y,z,scale,color){const g=new THREE.Group();g.position.set(x,y,z);g.scale.setScalar(scale);scene.add(g);mesh(new THREE.SphereGeometry(.18,16,12),mat('#bd8966'),0,1.35,0,g);box(.38,.5,.25,mat(color),0,.91,0,g);const legs=[];for(const s of [-1,1]){legs.push(box(.13,.55,.15,mat('#35445a'),s*.12,.37,0,g));box(.1,.43,.12,mat(color),s*.25,.87,0,g);}return {g,legs};}
    const luna=person(.7,.35,2,.85,'#f2ad24'); const adult=person(-.5,.35,1.35,1.08,'#70598d');
    function sprite(text){const c=document.createElement('canvas');c.width=512;c.height=96;const x=c.getContext('2d');x.fillStyle='#17364c';x.fillRect(0,0,512,96);x.fillStyle='white';x.font='bold 28px sans-serif';x.textAlign='center';x.fillText(text,256,61);const texture=new THREE.CanvasTexture(c);const material=new THREE.SpriteMaterial({map:texture});const s=new THREE.Sprite(material);s.scale.set(5.4,1,1);scene.add(s);return {s,texture,material};}
    const shoulder=sprite('Espaldón de grava · no hay acera');shoulder.s.position.set(7,2.1,4.1);
    const message=sprite('No hay un lugar protegido para bajar');message.s.position.set(.3,3.5,1.2);message.s.visible=false;
    let running=false,paused=false,disposed=false,time=0,choice='',frame,last=performance.now();
    function update(){const s=pilotDescentState(time,choice);luna.g.position.set(s.lunaX,s.lunaY,s.lunaZ);message.s.visible=s.message;for(const p of [luna])p.legs.forEach((leg,i)=>leg.rotation.x=s.walking?Math.sin(time*8+i*Math.PI)*.24:0);}
    const observer=new ResizeObserver(()=>{if(!host.clientWidth||!host.clientHeight)return;renderer.setSize(host.clientWidth,host.clientHeight,false);camera.aspect=host.clientWidth/host.clientHeight;camera.updateProjectionMatrix();});observer.observe(host);
    function render(now){if(disposed)return;const dt=Math.min((now-last)/1000,.05);last=now;if(running&&!paused){time=Math.min(time+dt,4);update();if(time===4){running=false;done();}}renderer.render(scene,camera);frame=requestAnimationFrame(render);}
    update();frame=requestAnimationFrame(render);
    return {play(v){choice=v;time=0;running=true;paused=false;update();},pause(v){paused=v;},finish(v){choice=v;time=4;running=false;update();},reset(){choice='';time=0;running=false;paused=false;update();},dispose(){disposed=true;cancelAnimationFrame(frame);observer.disconnect();scene.traverse(o=>o.geometry?.dispose());materials.forEach(m=>m.dispose());for(const l of [shoulder,message]){l.texture.dispose();l.material.dispose();}renderer.dispose();renderer.domElement.remove();}};
}
