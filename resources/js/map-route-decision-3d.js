import * as THREE from 'three';
import { mountMessageCrossing } from './message-crossing-decision-3d.js';

const mix=(a,b,t)=>a+(b-a)*Math.max(0,Math.min(1,t));
export function mapRoutePose(outcome,time) {
    const t=Math.max(0,Math.min(time,12));
    const p={x:10,z:mix(18,14,t/2),heading:Math.PI,read:0,walking:t<2,look:0,stowed:false};
    if(t<2 || outcome==='intro') return p;
    if(outcome==='detenerse') {
        p.x=mix(10,13,(t-2)/2);p.z=mix(14,15,(t-2)/2);
        p.walking=t<4;p.heading=t<4?Math.atan2(3,1):Math.PI;
        p.read=t<4?0:t<7?Math.min(1,(t-4)*2):Math.max(0,1-(t-7)*2);
        p.stowed=t>=7.5;
        // Deliberate left–right–left check after putting the device away.
        if(t>=8) p.look=t<9?mix(0,.85,t-8):t<10.5?mix(.85,-.85,(t-9)/1.5):mix(-.85,.85,(t-10.5)/1.5);
    } else if(outcome==='seguir-pantalla') {
        p.z=mix(14,9,(t-2)/3);p.x=t<5?10:mix(10,16,(t-5)/5);
        p.heading=mix(Math.PI,Math.PI/2,(t-4.7)/.6);p.read=Math.min(1,(t-2)*2);p.walking=t<10;
    } else if(outcome==='copiar') {
        p.z=mix(14,10,(t-2)/3);p.x=t<5?10:mix(10,16,(t-5)/5);
        p.heading=mix(Math.PI,Math.PI/2,(t-4.7)/.6);p.walking=t<10;p.stowed=true;
    }
    return p;
}

export function mapRouteCaption(outcome,t) {
    if(t<2) return 'Luna llega a una intersección desconocida. El mapa indica girar a la derecha.';
    if(outcome==='intro') return 'El mapa orienta, pero no confirma que sea seguro avanzar. Elegí una decisión.';
    if(outcome==='detenerse') return t<4?'Se aparta hacia el interior de la acera sin consultar la pantalla.':t<7?'Ya detenida y lejos del borde, revisa el nuevo recorrido.':t<8?'Guarda el teléfono antes de tomar otra decisión.':'Mira a la izquierda, a la derecha y otra vez a la izquierda. Reevalúa sin iniciar el cruce.';
    if(outcome==='seguir-pantalla') return 'Gira siguiendo la pantalla: continúa por la acera, pero deja de observar vehículos, señales y accesos.';
    return 'Sigue a otra persona sin comprobar la ruta. Esa persona continúa por la acera: su destino no confirma el de Luna.';
}

export function mountMapRoute(host,onCaption) {
    return mountMessageCrossing(host,onCaption,{
        pose:mapRoutePose,caption:mapRouteCaption,safeOutcome:'detenerse',
        label:'Luna recibe una indicación de giro del mapa en una intersección desconocida.',
        decorate({scene,box,mesh,rod,phone}) {
            // A miniature street map and right-turn route on the physical phone screen.
            for(const x of [-.045,.04]) box(phone,x,0,.03,.012,.22,.006,'#eaf4ed');
            for(const y of [-.07,.04]) box(phone,0,y,.032,.13,.01,.006,'#eaf4ed');
            box(phone,0,-.04,.04,.014,.12,.008,'#1566da');
            box(phone,.035,.02,.04,.07,.014,.008,'#1566da');
            rod(phone,[.055,.04,.04],[.072,.02,.04],.007,'#1566da');
            rod(phone,[.055,0,.04],[.072,.02,.04],.007,'#1566da');
            const other=new THREE.Group();scene.add(other);
            box(other,0,1.13,0,.43,.58,.3,'#63769b');
            mesh(new THREE.SphereGeometry(.19,16,12),'#b58264',other,0,1.66,0);
            const feet=[];
            for(const s of [-1,1]) { const leg=new THREE.Group();leg.position.set(s*.12,.88,0);other.add(leg);box(leg,0,-.4,0,.16,.8,.17,'#334a52');box(leg,0,-.82,.05,.18,.12,.29,'#f2eada');feet.push(leg);rod(other,[s*.27,1.35,0],[s*.27,.87,0],.06,'#b58264'); }
            return {update(outcome,t) {
                other.visible=outcome==='copiar';
                const q=mapRoutePose('copiar',Math.min(12,t+1.1));
                other.position.set(q.x+1.1,.33,q.z-.65);other.rotation.y=q.heading;
                feet.forEach((f,i)=>f.rotation.x=q.walking?Math.sin(t*7)*.36*(i?1:-1):0);
            }};
        },
    });
}
