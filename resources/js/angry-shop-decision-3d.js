import * as THREE from 'three';
import {mountMessageCrossing} from './message-crossing-decision-3d.js';
const mix=(a,b,t)=>a+(b-a)*Math.max(0,Math.min(1,t));

export function angryShopPose(mode,time){
    const t=Math.max(0,Math.min(18,time));
    const p={x:-12,z:17,y:.33,heading:Math.PI,read:0,stowed:true,walking:false,look:0,tension:1};
    if(t<1)return p;
    if(t<4){p.z=mix(17,9.5,(t-1)/3);p.walking=true;return p;}
    p.z=9.5;p.heading=Math.PI/2;
    if(mode==='intro')return p;
    if(mode==='pausa'){
        p.x=mix(-12,-5,(t-4)/3);p.z=mix(9.5,11.5,(t-4)/3);p.heading=t<7?Math.atan2(7,2):Math.PI;
        p.walking=t<7;p.tension=t<7?1:Math.max(0,1-(t-7)/4);
        if(t>=12)p.look=t<13?mix(0,.8,t-12):t<14.5?mix(.8,-.8,(t-13)/1.5):mix(-.8,.8,(t-14.5)/1.5);
    }else{
        const fast=mode==='rapido',end=fast?12:16;
        p.x=mix(-12,fast?19:10,(t-4)/(end-4));
        p.z=fast?mix(9.5,7.9,(t-4)/(end-4)):9.5;p.walking=t<end;
    }
    return p;
}
export function angryShopCaption(mode,t){
    if(t<1)return 'Luna abre la puerta de la tienda después de una discusión.';
    if(t<4)return 'Sale a la acera con el cuerpo tenso y camina rápido, con la mirada baja.';
    if(mode==='intro')return 'Casi no recuerda el último tramo. ¿Qué le está indicando esa falta de atención?';
    if(mode==='pausa')return t<7?'Se aparta hacia el interior de la acera, lejos del tránsito.':t<12?'Se detiene, respira y relaja los hombros para recuperar la atención.':'Levanta la mirada y comprueba el entorno antes de retomar el recorrido.';
    if(mode==='normal')return 'Continúa por una ruta conocida, pero sigue tensa y sin observar el entorno. Conocer el camino no recupera la atención.';
    return 'Acelera y se aproxima al borde de la acera. La prisa le deja menos tiempo para reaccionar; la demostración termina sin entrar en la calle.';
}
export function shopTrafficPose(index,time){
    const lane=index<3?3.5:-3.5;
    return {x:((time*(index<3?4:5)+index*27)%110)-55,z:lane,heading:0};
}

export function mountAngryShop(host,caption){
    return mountMessageCrossing(host,caption,{
        customStreet:true,pose:angryShopPose,caption:angryShopCaption,safeOutcome:'pausa',introDuration:4.2,duration:18,
        camera:[-28,23,-30],target:[-2,1,9],detailCamera:[-17,5.5,5],detailTarget:[-10,1.5,12],
        label:'Luna sale enojada de una tienda junto a una calle de sentido único con dos carriles.',
        decorate({scene,box,mesh,rod,head,luna,arms,traffic,safe}){
            box(scene,0,-.2,0,120,.3,65,'#a5b2a2');box(scene,0,0,0,120,.12,14,'#48565e');
            for(const z of [-11,11]){box(scene,0,.15,z,120,.3,8,'#d2d5ce');box(scene,0,.18,Math.sign(z)*7.1,120,.36,.2,'#ece9df');}
            // White lane divider: both lanes carry traffic in the same direction.
            for(let x=-56;x<=56;x+=4)box(scene,x,.08,0,2.3,.02,.12,'#eeeade');
            for(const z of [-6.65,6.65])box(scene,0,.08,z,120,.02,.12,'#eeeade');
            for(const z of [-3.5,3.5])for(const x of [-26,2,29]){
                box(scene,x,.09,z,3,.025,.2,'#f3f0e4');
                for(const s of [-1,1]){const tip=box(scene,x+1,.09,z+s*.36,1.1,.025,.2,'#f3f0e4');tip.rotation.y=s*Math.PI/4;}
            }
            // Shop front has a real opening (x=-13.2..-10.8), not a solid wall.
            box(scene,-12,.16,19,19,.32,8,'#d2d5ce');box(scene,-12,7,19,20,.3,9,'#d1aa7e');
            box(scene,-21.8,3.5,19,.3,7,8,'#d8cab6');box(scene,-2.2,3.5,19,.3,7,8,'#d8cab6');box(scene,-12,3.5,23,20,7,.25,'#d8cab6');
            for(const x of [-17.5,-6.5]){box(scene,x,1.7,15,8.4,3.1,.12,'#52747d');box(scene,x,.52,15,8.4,.4,.18,'#d8cab6');}
            box(scene,-12,4.8,15,20,2.1,.25,'#d8cab6');box(scene,-12,3.7,14.2,20,.22,2.4,'#486e72');
            // Lettered storefront, rendered as geometry-independent text texture.
            const signCanvas=document.createElement('canvas');signCanvas.width=1024;signCanvas.height=192;
            const ctx=signCanvas.getContext('2d');ctx.fillStyle='#264b53';ctx.fillRect(0,0,1024,192);ctx.fillStyle='#fff4da';ctx.font='bold 92px sans-serif';ctx.textAlign='center';ctx.fillText('TIENDA',512,128);
            const texture=new THREE.CanvasTexture(signCanvas);texture.colorSpace=THREE.SRGBColorSpace;
            const signMaterial=new THREE.MeshBasicMaterial({map:texture});
            const sign=mesh(new THREE.PlaneGeometry(8,1.5),signMaterial,scene,-12,5,14.8);sign.rotation.y=Math.PI;
            const door=new THREE.Group();door.position.set(-13.2,0,15);scene.add(door);
            box(door,1.2,1.8,0,2.35,3.4,.09,'#76989e');box(door,2.2,1.6,-.1,.06,.45,.06,'#e5d6b0');
            for(const x of [-18,-6]){box(scene,x,.9,20,2.8,1.5,1.2,'#bd9671');box(scene,x,1.8,20,2.3,.3,.8,'#e0b864');}
            for(const [x,z,h]of [[20,23,10],[-29,-23,11],[0,-23,15],[29,-23,9]]){
                box(scene,x,h/2+.3,z,15,h,13,'#d6d9d0');box(scene,x,h+.45,z,15.4,.25,13.4,'#7b9399');
                for(let y=2;y<h;y+=2.5)for(let dx=-5;dx<=5;dx+=3)box(scene,x+dx,y,z+(z>0?-6.55:6.55),1.7,1.4,.06,'#638692');
            }
            box(scene,-5,.86,13.5,3,.18,.65,'#ae875f');for(const x of [-6,-4])rod(scene,[x,.3,13.5],[x,.8,13.5],.065,'#526166');
            for(const x of [-27,30]){rod(scene,[x,.3,12.5],[x,5,12.5],.065,'#61717a');box(scene,x,5,12.1,1,.12,.7,'#e5dfc3');}
            // Tense eyebrows and clenched hands relax in the safe outcome.
            const brows=[];for(const s of [-1,1]){const b=box(head,s*.075,.07,.19,.1,.022,.02,'#48352f');b.rotation.z=s*.35;brows.push(b);mesh(new THREE.SphereGeometry(.018,8,6),'#293841',head,s*.075,.02,.188);mesh(new THREE.SphereGeometry(.072,10,8),'#b47c5c',arms[s>0?1:0],0,-.51,0);}
            safe.position.set(-5,.32,11.5);
            return {update(mode,t,p){
                door.rotation.y=-Math.PI*.48*Math.min(1,t/.9)*(t<4?1:Math.max(0,1-(t-4)/2));
                head.rotation.x=.22*p.tension;arms.forEach((arm,i)=>{arm.rotation.z=(i?1:-1)*.12*p.tension;arm.rotation.x=p.walking?Math.sin(t*(mode==='rapido'?12:9))*.45:0;});
                brows.forEach((b,i)=>b.rotation.z=(i?1:-1)*.35*p.tension);
                if(mode==='pausa'&&!p.walking&&t>=7&&t<12)luna.position.y+=Math.sin((t-7)*2)*.015;
                traffic.forEach((c,i)=>{const q=shopTrafficPose(i,t);c.model.position.set(q.x,.1,q.z);c.model.rotation.y=q.heading;});
            },dispose(){texture.dispose();signMaterial.dispose();}};
        },
    });
}
