import * as THREE from 'three';
import {mountMessageCrossing} from './message-crossing-decision-3d.js';
const mix=(a,b,t)=>a+(b-a)*Math.max(0,Math.min(1,t));
export function noisePose(mode,t) {
    const p={x:mix(-12,-8,t/3),z:9.5,y:.33,heading:Math.PI/2,read:0,stowed:true,walking:t<3,look:0};
    if(t<3||mode==='intro')return p;
    if(mode==='margen-visual'){
        p.x=mix(-8,-17,(t-3)/6);p.z=mix(9.5,11,(t-3)/3);p.heading=-Math.PI/2;p.walking=t<9;
        if(t>=9){p.heading=Math.PI;p.look=t<10?mix(0,.8,t-9):t<11.5?mix(.8,-.8,(t-10)/1.5):mix(-.8,.8,(t-11.5)/1.5);}
    }else if(mode==='oido'){
        p.x=mix(-8,-3,(t-3)/4);p.z=mix(9.5,7.65,(t-3)/4);p.walking=t<7;p.heading=Math.atan2(5,-1.85);
    }else if(mode==='rapido'){
        p.z=mix(9.5,1.2,(t-3)/3);p.heading=Math.PI;p.walking=t<6;p.y=p.z>=7.2?.33:mix(.33,.08,(7.2-p.z)/.4);
    }
    return p;
}
export function noiseCaption(mode,t){
    if(t<3)return 'Luna camina por la acera. El trabajo de construcción dificulta distinguir los sonidos del tránsito.';
    if(mode==='intro')return 'Una calle, dos sentidos. El ruido no permite saber si se acerca un vehículo: elegí una respuesta.';
    if(mode==='margen-visual')return t<9?'Se aleja de la obra por la acera y aumenta su distancia al borde.':'Se detiene en un punto despejado y mira izquierda–derecha–izquierda. No adivina por el sonido.';
    if(mode==='oido')return 'Acercarse al borde no elimina el ruido: reduce el margen frente al tránsito.';
    return t<6?'Se apresura a entrar en la calle sin comprobar ambos sentidos.':'La escena se detiene al mostrar el riesgo: cruzar con prisa no recupera la información que falta.';
}
export function mountConstructionNoise(host,caption){
    return mountMessageCrossing(host,caption,{
        customStreet:true,pose:noisePose,caption:noiseCaption,safeOutcome:'margen-visual',introDuration:3.2,duration:14,
        camera:[-29,24,35],target:[-2,1,7],detailCamera:[-19,6,18],detailTarget:[-8,1,9],
        label:'Calle recta con un carril por sentido, edificios y una obra vallada junto a la acera.',
        decorate({scene,box,rod,mesh,safe,traffic}){
            box(scene,0,-.2,0,100,.3,65,'#8fa697');box(scene,0,0,0,100,.12,14,'#46535a');
            for(const z of [-9.5,9.5]){box(scene,0,.15,z,100,.3,5,'#cdd3d0');box(scene,0,.2,Math.sign(z)*7.12,100,.4,.24,'#e4e3d9');}
            for(let x=-48;x<=48;x+=4)box(scene,x,.08,0,2.3,.02,.13,'#e9c65a');
            for(const z of [-6.65,6.65])box(scene,0,.08,z,100,.02,.12,'#e9e9df');
            for(const [x,z,h]of [[-27,19,9],[25,20,13],[-24,-20,12],[0,-20,8],[25,-20,15]]){
                box(scene,x,h/2+.3,z,12,h,12,'#d5d9d1');box(scene,x,h+.4,z,12.4,.25,12.4,'#70888d');
                for(let y=2;y<h;y+=2.5)for(let dx=-4;dx<=4;dx+=2.5)box(scene,x+dx,y,z+(z>0?-6.03:6.03),1.5,1.45,.08,'#5c8594');
            }
            // The worksite is behind the sidewalk, leaving a continuous pedestrian corridor.
            box(scene,3,.32,19,22,.15,13,'#ad9576');
            for(const x of [-7,0,7,13])for(const z of [15,23])box(scene,x,4,z,.45,8,.45,'#8e9896');
            for(const y of [4,8]){box(scene,3,y,19,21,.3,9,'#a7afab');for(const x of [-7,0,7,13])rod(scene,[x,y,14.5],[x,y+1.2,14.5],.045,'#dcae47');rod(scene,[-7,y+1.1,14.5],[13,y+1.1,14.5],.04,'#dcae47');}
            for(let x=-9;x<=15;x+=3){rod(scene,[x,.3,12.3],[x,2.4,12.3],.045,'#78878b');box(scene,x+1.45,1.35,12.3,2.85,1.8,.07,'#abb7b1');box(scene,x+1.45,1.25,12.37,2.7,.25,.04,'#e6ad41');}
            for(const x of [-9,16]){box(scene,x,.36,11.9,.65,.12,.65,'#475258');mesh(new THREE.ConeGeometry(.24,.75,16),'#e59143',scene,x,.78,11.9);}
            // Compact excavator with articulated boom, bucket and rubber tracks.
            const machine=new THREE.Group();machine.position.set(4,.4,16);scene.add(machine);
            for(const z of [-.95,.95])box(machine,0,.35,z,3,.55,.5,'#34434b');
            box(machine,0,.9,0,2.7,.6,1.8,'#e9ae38');box(machine,-.55,1.8,0,1.2,1.3,1.5,'#526f7a');box(machine,-.55,2.5,0,1.35,.13,1.65,'#e9ae38');
            const boom=new THREE.Group();boom.position.set(.7,1.3,0);machine.add(boom);rod(boom,[0,0,0],[1.4,2,0],.16,'#e5b044');rod(boom,[1.4,2,0],[3,.3,0],.14,'#e5b044');box(boom,3,.12,0,.75,.45,.85,'#54636b');
            for(let i=0;i<6;i++)box(scene,-5+(i%3)*.9,.55+Math.floor(i/3)*.3,18,.8,.25,1.6,'#beab91');
            // Visual sound pulses are explanatory cues, not an audio requirement.
            const pulses=[];for(let i=0;i<3;i++){const m=mesh(new THREE.TorusGeometry(1,.025,6,48),'#d9a548',scene,4,2,13);pulses.push(m);}
            safe.position.set(-17,.32,11);
            return {update(mode,t){
                const clock=mode==='rapido'?Math.min(t,6):t;
                boom.rotation.z=Math.sin(clock*1.5)*.12;
                pulses.forEach((p,i)=>{const u=((clock*.65+i/3)%1);p.scale.setScalar(.5+u*2);p.visible=u<.9;});
                if(mode==='rapido')traffic.forEach(c=>{c.model.position.x=c.direction*(((clock*4+c.offset)%94)-47);});
            }};
        },
    });
}
