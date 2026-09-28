import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

// All outcome poses use the same clock, so pause, replay and camera changes remain synchronized.
export function messagePose(outcome, time) {
    const t = Math.max(0, Math.min(time, 12));
    const lerp = (a,b,u) => a+(b-a)*Math.max(0,Math.min(1,u));
    let x=10,z=lerp(18,14,t/2),read=0,walking=t<2,heading=Math.PI;
    if(t>=2 && outcome==='protegido') {
        x=lerp(10,13,(t-2)/2);z=lerp(14,15,(t-2)/2);walking=t<4;
        heading=t<4?Math.atan2(3,1):Math.PI;
        read=t<4?0:t<9?Math.min(1,(t-4)*2):Math.max(0,1-(t-9)*2);
    } else if(t>=2 && outcome==='caminando') {
        z=lerp(14,8.1,(t-2)/8);read=Math.min(1,(t-2)*2);walking=t<10;
    } else if(t>=2 && outcome==='rapido') {
        z=t<5?lerp(14,9,(t-2)/3):lerp(9,7.6,(t-5)/1.3);
        read=t<6?Math.min(1,(t-2)*3):Math.max(0,1-(t-6)*3);walking=t<6.3;
    }
    return {x,z,read,walking,heading};
}

export function mountMessageCrossing(host, onCaption=()=>{}, configuration={}) {
    const scene=new THREE.Scene();scene.background=new THREE.Color('#dbe9ee');
    const camera=new THREE.PerspectiveCamera(40,1,.08,180);
    const renderer=new THREE.WebGLRenderer({antialias:true});renderer.setPixelRatio(Math.min(devicePixelRatio||1,2));renderer.shadowMap.enabled=true;renderer.shadowMap.type=THREE.PCFSoftShadowMap;renderer.toneMapping=THREE.ACESFilmicToneMapping;renderer.toneMappingExposure=1.15;renderer.domElement.style.cssText='display:block;width:100%;height:100%';renderer.domElement.setAttribute('role','img');renderer.domElement.setAttribute('aria-label','Luna recibe un mensaje al acercarse por la acera a una intersección urbana.');host.append(renderer.domElement);
    const controls=new OrbitControls(camera,renderer.domElement);controls.enableDamping=true;controls.minDistance=7;controls.maxDistance=75;controls.maxPolarAngle=Math.PI*.47;
    scene.add(new THREE.HemisphereLight(0xeaf4ff,0x81937a,2.4));const sun=new THREE.DirectionalLight(0xffefd9,3.3);sun.position.set(-20,40,22);sun.castShadow=true;sun.shadow.mapSize.set(2048,2048);Object.assign(sun.shadow.camera,{left:-45,right:45,top:45,bottom:-45});sun.shadow.normalBias=.03;scene.add(sun);
    const materials=new Map();function material(c){if(!materials.has(c))materials.set(c,new THREE.MeshStandardMaterial({color:c,roughness:.68}));return materials.get(c);}
    function mesh(g,c,p,x=0,y=0,z=0){const m=new THREE.Mesh(g,typeof c==='string'?material(c):c);m.position.set(x,y,z);m.castShadow=m.receiveShadow=true;p.add(m);return m;}
    const box=(p,x,y,z,w,h,d,c)=>mesh(new THREE.BoxGeometry(w,h,d),c,p,x,y,z);
    function rod(p,a,b,r,c){const v=new THREE.Vector3(...a),w=new THREE.Vector3(...b),d=w.clone().sub(v);const m=mesh(new THREE.CylinderGeometry(r,r,d.length(),12),c,p);m.position.copy(v).add(w).multiplyScalar(.5);m.quaternion.setFromUnitVectors(new THREE.Vector3(0,1,0),d.normalize());return m;}
    const glow=[];
    if(!configuration.customStreet) {
    box(scene,0,-.22,0,100,.3,100,'#94ac99');box(scene,0,0,0,100,.12,14,'#45545c');box(scene,0,.005,0,14,.12,100,'#45545c');
    for(const sx of [-1,1])for(const sz of [-1,1]){
        box(scene,sx*28.5,.15,sz*28.5,43,.3,43,'#ccd4d1');
        const p=new THREE.Group();p.position.set(sx*27,0,sz*27);scene.add(p);const h=sx===sz?10:15;
        box(p,0,h/2+.3,0,17,h,16,sx>0?'#e4e0d5':'#b8c9cc');box(p,0,h+.4,0,17.5,.3,16.5,'#748e90');
        for(let y=2.5;y<h;y+=2.8)for(let u=-6;u<=6;u+=3){box(p,u,y,8.03,1.8,1.6,.08,'#517987');box(p,u,y,-8.03,1.8,1.6,.08,'#517987');box(p,8.53,y,u,.08,1.6,1.8,'#517987');}
        for(const [x,z]of [[sx*18,sz*12],[sx*13,sz*25]]){box(scene,x,.5,z,2.4,.4,2.4,'#8b9e91');rod(scene,[x,.5,z],[x,3.7,z],.15,'#776251');const leaf=mesh(new THREE.IcosahedronGeometry(1.5,2),'#4f8165',scene,x,4.4,z);leaf.scale.y=1.2;}
    }
    // The sidewalk retreat is separated from both kerbs and the crossing approach.
    box(scene,14,.85,17.2,3,.18,.7,'#997b55');for(const x of [13,15])rod(scene,[x,.3,17.2],[x,.8,17.2],.065,'#566567');
    for(let v=-48;v<49;v+=3.5)if(Math.abs(v)>13){box(scene,v,.078,0,1.9,.02,.12,'#e2c674');box(scene,0,.08,v,.12,.02,1.9,'#e2c674');}
    for(const side of [-1,1])for(let k=-5.5;k<=5.6;k+=1.4){box(scene,side*10,.09,k,2.8,.03,.7,'#f5f3e6');box(scene,k,.095,side*10,.7,.03,2.8,'#f5f3e6');}
    for(const z of [-7.7,7.7]){box(scene,10,.32,z,2.8,.04,1,'#d5b35b');for(let x=9;x<11.1;x+=.35)for(let dz=-.3;dz<=.3;dz+=.3)mesh(new THREE.SphereGeometry(.05,6,4),'#ead083',scene,x,.36,z+dz);}
    box(scene,-13,.085,3.5,.25,.03,6.5,'#f5f3e6');box(scene,13,.085,-3.5,.25,.03,6.5,'#f5f3e6');box(scene,3.5,.085,13,6.5,.03,.25,'#f5f3e6');box(scene,-3.5,.085,-13,6.5,.03,.25,'#f5f3e6');
    function signal(x,z,rotation,green){const p=new THREE.Group();p.position.set(x,0,z);p.rotation.y=rotation;scene.add(p);rod(p,[0,0,0],[0,4.9,0],.07,'#43555b');box(p,0,4.25,0,.55,1.6,.35,'#243138');for(let i=0;i<3;i++){const active=i===(green?2:0);const c=['#ec4658','#eeb447','#29d0a0'][i];const m=new THREE.MeshStandardMaterial({color:active?c:'#28383d',emissive:c,emissiveIntensity:active?1.7:0});glow.push(m);mesh(new THREE.SphereGeometry(.16,14,10),m,p,0,4.72-i*.46,.2).scale.z=.3;}}
    signal(-13,7.8,-Math.PI/2,true);signal(13,-7.8,Math.PI/2,true);signal(7.8,13,0,false);signal(-7.8,-13,Math.PI,false);
    function pedSignal(z,rot){const p=new THREE.Group();p.position.set(11.8,0,z);p.rotation.y=rot;scene.add(p);rod(p,[0,0,0],[0,3.4,0],.055,'#4c6064');box(p,0,3.05,0,.68,.8,.3,'#233239');const m=new THREE.MeshStandardMaterial({color:'#f44f60',emissive:'#f44f60',emissiveIntensity:1.8});glow.push(m);mesh(new THREE.SphereGeometry(.075,12,8),m,p,0,3.3,.18);rod(p,[0,3.22,.18],[0,3,.18],.032,m);rod(p,[-.12,3.13,.18],[.12,3.13,.18],.026,m);rod(p,[0,3,.18],[-.09,2.86,.18],.027,m);rod(p,[0,3,.18],[.09,2.86,.18],.027,m);}
    pedSignal(8.2,Math.PI);pedSignal(-8.2,0);
    }
    function car(c){const p=new THREE.Group();scene.add(p);box(p,0,.65,0,4.2,.65,1.8,c);box(p,-.15,1.14,0,2.2,.56,1.62,'#365562');box(p,-.15,1.45,0,2.3,.1,1.7,c);box(p,0,1.14,0,.1,.6,1.65,c);for(const s of [-1,1]){box(p,2.12,.83,s*.6,.05,.12,.36,'#fff3ca');box(p,-2.12,.83,s*.6,.05,.14,.36,'#c94052');box(p,.5,1.12,s*.96,.3,.14,.2,c);for(const x of [-1.28,1.28]){mesh(new THREE.CylinderGeometry(.36,.36,.2,20),'#242e34',p,x,.38,s*.92).rotation.x=Math.PI/2;mesh(new THREE.CylinderGeometry(.2,.2,.22,16),'#afbdc2',p,x,.38,s*.92).rotation.x=Math.PI/2;}}return p;}
    const traffic=[];for(const direction of [-1,1])for(let i=0;i<3;i++)traffic.push({model:car(['#6d99ae','#ece8db','#bb6860'][i]),direction,offset:i*31+(direction>0?0:17)});
    if(!configuration.customStreet) for(const direction of [-1,1])for(let i=0;i<2;i++){const p=car(i?'#95aca3':'#536c7d');p.position.set(direction>0?-3.5:3.5,.1,direction*(-16-i*7));p.rotation.y=direction>0?-Math.PI/2:Math.PI/2;}
    const luna=new THREE.Group();scene.add(luna);box(luna,0,1.16,0,.43,.57,.28,'#de9b4e');const head=new THREE.Group();head.position.set(0,1.68,0);luna.add(head);mesh(new THREE.SphereGeometry(.2,20,16),'#b47c5c',head);mesh(new THREE.SphereGeometry(.205,16,12,0,Math.PI*2,0,Math.PI/2),'#44352e',head,0,.045,0);mesh(new THREE.ConeGeometry(.04,.13,10),'#b47c5c',head,0,-.015,.21).rotation.x=Math.PI/2;
    const legs=[];for(const s of [-1,1]){const p=new THREE.Group();p.position.set(s*.12,.89,0);luna.add(p);box(p,0,-.4,0,.16,.8,.17,'#38546c');box(p,0,-.83,.055,.18,.12,.3,'#e9e7dd');legs.push(p);}
    const arms=[];for(const s of [-1,1]){const p=new THREE.Group();p.position.set(s*.26,1.37,0);luna.add(p);rod(p,[0,0,0],[0,-.5,0],.057,'#b47c5c');arms.push(p);}
    const phone=new THREE.Group();arms[1].add(phone);phone.position.set(0,-.48,.03);box(phone,0,0,0,.18,.31,.035,'#233139');const screen=box(phone,0,0,.022,.145,.255,.008,'#80d6ed');
    const safe=new THREE.Mesh(new THREE.RingGeometry(1.15,1.23,60),new THREE.MeshBasicMaterial({color:'#3aa988',transparent:true,opacity:.7,side:THREE.DoubleSide}));safe.rotation.x=-Math.PI/2;safe.position.set(13,.32,15);scene.add(safe);
    const danger=new THREE.Mesh(new THREE.RingGeometry(.65,.74,48),new THREE.MeshBasicMaterial({color:'#de9950',transparent:true,opacity:.8,side:THREE.DoubleSide}));danger.rotation.x=-Math.PI/2;scene.add(danger);
    const extension=configuration.decorate?.({scene,box,mesh,rod,phone,screen,head,luna,arms,traffic,safe}) || {};
    if(configuration.label) renderer.domElement.setAttribute('aria-label',configuration.label);
    let outcome='intro',time=0,paused=false,view='overview',frame=0,disposed=false,last=performance.now(),lastCaption='';
    const world=new THREE.Vector3(),quat=new THREE.Quaternion(),forward=new THREE.Vector3(),target=new THREE.Vector3();
    function caption(){if(time<2)return 'Luna se acerca al cruce por la acera.';if(outcome==='intro')return 'El teléfono vibra. Elegí cuándo conviene revisarlo.';if(outcome==='protegido')return time<4?'Primero se aparta del borde, sin mirar la pantalla.':time<9?'Se detiene en un lugar protegido y revisa el mensaje.':'Guarda el teléfono y vuelve a observar el entorno.';if(outcome==='caminando')return 'Camina más despacio, pero la pantalla ocupa su mirada mientras se acerca al borde.';return time<6?'Mira el mensaje con prisa mientras llega al cruce.':'Se detiene junto al borde: todavía hay tránsito y señal peatonal roja.';}
    function drawState(){const pose=(configuration.pose || messagePose)(outcome,time);luna.position.set(pose.x,pose.y ?? .33,pose.z);luna.rotation.y=pose.heading;head.rotation.x=pose.read*.65;head.rotation.y=pose.look ?? (outcome==='protegido'&&time>=10?Math.sin((time-10)*2)*.6:0);legs.forEach((p,i)=>p.rotation.x=pose.walking?Math.sin(time*7)*.38*(i?-1:1):0);arms[0].rotation.x=pose.walking?Math.sin(time*7)*.22:0;arms[1].rotation.x=-pose.read*1.8;phone.rotation.z=time>=2&&time<3?Math.sin(time*65)*.09:0;screen.material.emissive.set('#66cce8');screen.material.emissiveIntensity=pose.read?1:.2;phone.visible=!pose.stowed;safe.visible=outcome===(configuration.safeOutcome || 'protegido');danger.visible=outcome!=='intro'&&outcome!==(configuration.safeOutcome || 'protegido')&&time>5;danger.position.set(pose.x,.34,pose.z);traffic.forEach(c=>{const x=((time*4+c.offset)%94)-47;c.model.position.set(c.direction*x,.1,c.direction*3.5);c.model.rotation.y=c.direction>0?0:Math.PI;});extension.update?.(outcome,time,pose);const text=configuration.caption?configuration.caption(outcome,time):caption();if(text!==lastCaption){lastCaption=text;onCaption(text);}if(view==='pedestrian'){head.getWorldPosition(world);head.getWorldQuaternion(quat);forward.set(0,0,1).applyQuaternion(quat);camera.position.copy(world).addScaledVector(forward,.34);target.copy(camera.position).addScaledVector(forward,10);camera.lookAt(target);}else controls.update();}
    function setView(v){view=v;controls.enabled=v!=='pedestrian';if(v==='detail'){camera.position.fromArray(configuration.detailCamera || [18,6.5,22]);controls.target.fromArray(configuration.detailTarget || [11,1,12]);}else if(v==='overview'){camera.position.fromArray(configuration.camera || [36,30,39]);controls.target.fromArray(configuration.target || [5,0,5]);}controls.update();}
    const observer=new ResizeObserver(()=>{if(!host.clientWidth||!host.clientHeight)return;renderer.setSize(host.clientWidth,host.clientHeight,false);camera.aspect=host.clientWidth/host.clientHeight;camera.updateProjectionMatrix();});observer.observe(host);
    function animate(now){if(disposed)return;const dt=Math.min((now-last)/1000,.04);last=now;const rect=host.getBoundingClientRect();if(!document.hidden&&rect.bottom>0&&rect.top<innerHeight){if(!paused)time=Math.min(outcome==='intro'?(configuration.introDuration ?? 2.5):(configuration.duration ?? 12),time+dt);drawState();renderer.render(scene,camera);}frame=requestAnimationFrame(animate);}setView('overview');drawState();frame=requestAnimationFrame(animate);
    return {setView,setOutcome(value){outcome=value;time=0;lastCaption='';drawState();},setPaused(value){paused=value;},dispose(){disposed=true;cancelAnimationFrame(frame);observer.disconnect();controls.dispose();extension.dispose?.();scene.traverse(o=>o.geometry?.dispose());materials.forEach(m=>m.dispose());glow.forEach(m=>m.dispose());safe.material.dispose();danger.material.dispose();renderer.dispose();renderer.domElement.remove();}};
}
