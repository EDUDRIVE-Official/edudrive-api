import * as THREE from 'three';
import { mountMessageCrossing } from './message-crossing-decision-3d.js';
const mix=(a,b,t)=>a+(b-a)*Math.max(0,Math.min(1,t));

export function busExitPose(mode,t) {
    t=Math.max(0,Math.min(24,t));
    const p={x:27,z:5.5,y:1.05,heading:0,read:0,look:0,walking:true,stowed:false};
    // The doorway is at x=27. The route crosses only the open door, not the bus wall.
    if(t<4) {
        p.z=t<1.5?mix(5.5,6.3,t/1.5):t<2.7?mix(6.3,6.8,(t-1.5)/1.2):mix(6.8,8.8,(t-2.7)/1.3);
        p.y=t<1.5?1.05:t<2.1?mix(1.05,.72,(t-1.5)/.6):t<2.7?mix(.72,.4,(t-2.1)/.6):mix(.4,.33,(t-2.7)/.4);
        return p;
    }
    p.z=8.8;p.y=.33;p.walking=false;
    if(mode==='intro') return p;
    if(mode==='pausar') {
        p.stowed=t>=6;p.read=t>=4&&t<5?Math.sin((t-4)*Math.PI):0;
        p.z=t<8?mix(8.8,10,(t-6)/2):t<17?10:mix(10,15,(t-17)/3);
        p.x=t<8?27:mix(27,13,(t-8)/9);
        p.walking=t>=6&&t<20;p.heading=t<8||t>=17?0:-Math.PI/2;
        if(t>=20){p.heading=Math.PI;p.look=t<21?mix(0,.8,t-20):t<22.5?mix(.8,-.8,(t-21)/1.5):mix(-.8,.8,(t-22.5)/1.5);}
    } else {
        const rearTime=mode==='correr'?9:13;
        p.x=mix(27,18,(t-4)/(rearTime-4));p.heading=t<rearTime?-Math.PI/2:Math.PI;
        p.z=t<rearTime?8.8:mix(8.8,mode==='correr'?1.2:6.8,(t-rearTime)/3);
        p.y=p.z>=7.2?.33:mix(.33,.08,(7.2-p.z)/.4);
        p.walking=t<rearTime+3;p.look=mode==='seguir'?.15*Math.sin(t*4):0;
    }
    return p;
}
export function busCaption(mode,t) {
    if(t<1.5)return 'Luna se dirige a la puerta abierta del autobús con los audífonos puestos.';
    if(t<4)return 'Baja los escalones y llega a la acera. El autobús permanece detenido.';
    if(mode==='intro')return 'Sigue en una llamada y piensa cruzar por detrás. Elegí su primera acción.';
    if(mode==='pausar')return t<6?'Se detiene: pausa la llamada, se quita y guarda los audífonos.':t<20?'Con el teléfono guardado, camina por la acera hacia un punto con visibilidad.':'Se detiene lejos del borde y mira a ambos lados. No cruza: la señal peatonal sigue roja.';
    if(mode==='seguir')return t<13?'Sigue hablando mientras rodea el autobús por la acera. Una mirada rápida no recupera la atención.':'Se asoma por detrás: el autobús oculta el tránsito. La escena se detiene para señalar el riesgo.';
    return t<9?'Se apresura hacia la parte trasera del autobús. La urgencia reduce su observación.':'Sale por detrás a la calzada con visibilidad limitada. La simulación se detiene antes de una colisión.';
}

export function mountHeadphonesBus(host,onCaption) {
    return mountMessageCrossing(host,onCaption,{
        pose:busExitPose,caption:busCaption,safeOutcome:'pausar',introDuration:4.2,duration:24,
        camera:[40,22,32],target:[21,1,7],detailCamera:[32,7,16],detailTarget:[26,1.8,6.5],
        label:'Luna baja de un autobús amarillo y azul por la puerta hacia la acera, con audífonos y una llamada activa.',
        decorate({scene,box,mesh,rod,head,arms,traffic,safe}) {
            const bus=new THREE.Group();bus.position.set(24,0,5.5);scene.add(bus);
            const yellow='#efc548',blue='#447bae',glass='#75bed6';
            box(bus,-1.35,.8,0,7.3,.4,2.5,yellow);box(bus,4.35,.8,0,1.3,.4,2.5,yellow);
            box(bus,3,.8,-.4,1.4,.4,1.7,yellow);box(bus,0,3.6,0,10.1,.2,2.6,blue);
            // Curb-side body is split around a real 1.4 m door opening.
            box(bus,-1.35,1.5,1.23,7.3,1,.08,yellow);box(bus,4.35,1.5,1.23,1.3,1,.08,yellow);
            box(bus,0,1.5,-1.23,10,1,.09,yellow);
            for(const z of [-1.25,1.25])for(const x of [-4,-2.4,-.8,.8,4.3]){box(bus,x,2.7,z,1.35,1.35,.055,glass);box(bus,x-.72,2.7,z,.09,1.5,.09,blue);}
            box(bus,3,2.7,-1.25,1.5,1.35,.055,glass);
            box(bus,5,1.4,0,.12,1,2.5,yellow);box(bus,5,2.75,0,.1,1.5,2.4,glass);box(bus,-5,2.25,0,.12,2.5,2.5,blue);
            box(bus,3,.9,.4,1.35,.3,1.2,'#68757d');box(bus,3,.57,.95,1.35,.3,.5,'#9eaaaf');box(bus,3,.28,1.3,1.35,.25,.32,'#bdc6c6');
            for(const x of [2.25,3.75])rod(bus,[x,1,1.15],[x,3.3,1.15],.035,'#e6e1ac');
            const doors=[];for(const s of [-1,1]) {const panel=new THREE.Group();panel.position.set(3+s*.7,0,1.28);bus.add(panel);box(panel,-s*.34,2.25,0,.65,2.55,.06,glass);box(panel,-s*.34,1,0,.65,.12,.08,blue);doors.push({panel,s});}
            for(const x of [-3.3,4.3])for(const z of [-1.27,1.27]) {mesh(new THREE.CylinderGeometry(.55,.55,.26,24),'#243039',bus,x,.56,z).rotation.x=Math.PI/2;mesh(new THREE.CylinderGeometry(.28,.28,.28,20),'#bdc6cb',bus,x,.56,z).rotation.x=Math.PI/2;}
            for(const z of [-.9,.9]){box(bus,5.08,1.3,z,.05,.22,.36,'#fff2bf');box(bus,-5.08,1.4,z,.05,.28,.25,'#ee5960');rod(bus,[4.7,3.15,z],[5.4,3.15,z*1.65],.045,'#3e505b');box(bus,5.4,2.98,z*1.65,.12,.4,.23,'#3e505b');}
            // Visible interior, driver and seats; no opaque solid box through the doorway.
            for(const x of [-3.5,-1.8,0])for(const z of [-.65,.65]){box(bus,x,1.42,z,.7,.15,.55,blue);box(bus,x-.3,1.8,z,.14,.8,.55,blue);}
            box(bus,4,1.55,-.65,.6,.2,.6,'#304c63');mesh(new THREE.SphereGeometry(.19,12,10),'#a87960',bus,4,2.45,-.65);box(bus,4,2.02,-.65,.4,.5,.3,'#688998');
            const headset=new THREE.Group();head.add(headset);
            for(const s of [-1,1])box(headset,s*.22,0,0,.09,.22,.16,'#27384d');
            const band=mesh(new THREE.TorusGeometry(.225,.025,8,24,Math.PI),'#27384d',headset);band.position.y=.03;
            // Stop pole beyond the unloading corridor.
            rod(scene,[30,.3,8.4],[30,3.7,8.4],.055,'#50636b');box(scene,30,3.5,8.4,.9,.85,.1,blue);box(scene,30,3.52,8.48,.58,.3,.02,'#f4edd6');
            safe.position.set(13,.32,15);
            return {update(mode,t,pose) {
                const closed=Math.max(0,Math.min(1,(t-4.5)/1.5));
                doors.forEach(({panel,s})=>panel.rotation.y=s*(1-closed)*Math.PI*.47);
                headset.visible=mode!=='pausar'||t<6;
                headset.position.y=mode==='pausar'&&t>=5?Math.min(.35,(t-5)*.35):0;
                if(mode==='pausar'&&t>=5&&t<6)arms[0].rotation.x=-2.5;
                // The bus holds its bay; cars behind it wait instead of passing through it.
                traffic.filter(c=>c.direction>0).forEach((c,i)=>{c.model.visible=i===0;c.model.position.set(12.5,.1,5.3);});
                if(mode!=='pausar' && mode!=='intro' && !pose.walking) traffic.forEach(c=>{if(c.direction<0)c.model.position.x=-((( (mode==='correr'?12:16)*4+c.offset)%94)-47);});
            }};
        },
    });
}
