import * as THREE from 'three';
import { createStage, buildStreet, buildPerson, buildPhone, buildCar, walk, smooth, LANE_A } from './incident-scene-kit';

// Escena "Cable desconocido": un cable quedó sobre la calzada después de un choque.
// Resultados: 'protected' (distancia + advertir sin tocar), 'expose' (moverlo), 'record' (grabar antes de pedir ayuda).
const DURATION = 9; // segundos por resultado
const STEP_STARTS = [0, .34, .68]; // progreso en que inicia cada paso de texto
const STEP_POSES = [.01, .5, 1]; // progreso mostrado al saltar a un paso
const DANGER = new THREE.Vector3(1.2, .2, 2); const DANGER_RADIUS = 5.2; // zona que no hay que pisar
const CABLE_END = new THREE.Vector3(3, .06, 3.9);
const VOS_START = new THREE.Vector3(9, .1, 7.4), VOS_SAFE = new THREE.Vector3(11.8, .1, 7.4);
const OTHER_START = new THREE.Vector3(19, .1, 7.4);

export function mountUnknownCableDecision(host, onStep = () => {}) {
    const stage = createStage(host, { label: 'Escena tridimensional: un cable caído de un poste inclinado quedó sobre la calzada después de un choque, con una zona de peligro alrededor y un vehículo detenido a distancia.', overview: { position: [17, 17, 25], target: [3, .4, 2] } });
    const { scene, camera, reduced, mat, glow, mesh, box, cyl, ring, tube, THREE: T } = stage;
    buildStreet(stage);

    // Postes: uno inclinado por el choque y otro en pie, unidos por cables de la red.
    const woodPole = mat('#6b5238', { roughness: .9 }), steel = mat('#555d66', { metalness: .6, roughness: .4 });
    const leaning = new T.Group(); leaning.position.set(-4.4, 0, -8.2); leaning.rotation.z = .3; scene.add(leaning);
    cyl(.17, 8, woodPole, leaning, 0, 4, 0, 14); box(2.4, .14, .14, steel, leaning, 0, 7.6, 0);
    const standing = new T.Group(); standing.position.set(12, 0, -8.2); scene.add(standing);
    cyl(.17, 8, woodPole, standing, 0, 4, 0, 14); box(2.4, .14, .14, steel, standing, 0, 7.6, 0);
    const topX = -4.4 - Math.sin(.3) * 7.6, topY = Math.cos(.3) * 7.6;
    const wireMat = mat('#1d1f22', { roughness: .5 });
    for (const dz of [-.9, .9]) {
        const wire = new T.CatmullRomCurve3([new T.Vector3(12, 7.55, -8.2 + dz * .2), new T.Vector3(3.6, 6.3, -8.2 + dz * .2), new T.Vector3(topX, topY - .1, -8.2 + dz * .2)]);
        mesh(new T.TubeGeometry(wire, 24, .02, 6), wireMat, scene);
    }
    // Cable caído: baja desde el poste inclinado y termina sobre la calzada.
    const cableCurve = new T.CatmullRomCurve3([new T.Vector3(topX, topY, -8.2), new T.Vector3(-6, 3.5, -7), new T.Vector3(-4.2, .5, -4.4), new T.Vector3(-1.6, .07, -1.2), new T.Vector3(1, .06, 1.9), CABLE_END.clone()]);
    mesh(new T.TubeGeometry(cableCurve, 60, .08, 10), mat('#141516', { roughness: .45, metalness: .1 }), scene);
    const spark = mesh(new T.SphereGeometry(.1, 12, 10), glow('#cfe9ff', 1, 2.2), scene, CABLE_END.x, .18, CABLE_END.z); spark.castShadow = false;
    const sparkLight = new T.PointLight('#9fd0ff', 8, 6); sparkLight.position.set(CABLE_END.x, .6, CABLE_END.z); scene.add(sparkLight);

    // Vehículo del choque junto al poste, con luces intermitentes lentas.
    const crashed = buildCar(stage, '#b83b2f'); crashed.group.position.set(-7.4, 0, -3.3); crashed.group.rotation.y = .7;
    // Vehículo que espera detenido a distancia (carril cercano, hacia -x).
    const waiting = buildCar(stage, '#2b7195'); waiting.group.rotation.y = Math.PI; waiting.group.position.set(17, 0, LANE_A);

    // Señal de advertencia (triángulo) que se coloca a distancia.
    const sign = new T.Group(); scene.add(sign); sign.position.set(9.6, 0, 3.4); sign.rotation.y = Math.PI / 2;
    const tri = (r, color, z) => { const s = new T.Shape(); s.moveTo(0, r); s.lineTo(r * .87, -r * .5); s.lineTo(-r * .87, -r * .5); s.closePath(); const m = mesh(new T.ShapeGeometry(s), glow(color, 1, .35), sign, 0, .75, z); m.material.side = T.DoubleSide; m.castShadow = false; };
    tri(.52, '#d4281f', 0); tri(.36, '#fff7e0', .004); tri(.19, '#ffb400', .008);
    for (const x of [-.25, .25]) tube([x, .05, -.1], [x * .2, .5, 0], .015, steel, sign);
    sign.scale.setScalar(.001);

    const vos = buildPerson(stage, { shirt: '#e8782c', pants: '#2f4257', skin: '#b98260', hair: '#2a1d17', scale: .99 });
    const phone = buildPhone(stage, vos);
    const other = buildPerson(stage, { shirt: '#4f8f5c', pants: '#3b3b46', skin: '#c28d68', hair: '#1c1c1c', scale: .96 });

    // Marcadores didácticos: zona de peligro, aura de descarga, aviso de otra persona y ondas de llamada.
    const dangerMat = glow('#e8b72f', .55, 1), vosRiskMat = glow('#df3e39', .9, 1.5);
    const dangerRing = ring(DANGER_RADIUS - .32, DANGER_RADIUS, dangerMat, DANGER.x, .2, DANGER.z);
    const safeRing = ring(.95, 1.3, glow('#2fa866', .8, 1.2), VOS_SAFE.x, .2, VOS_SAFE.z); safeRing.visible = false;
    const vosRisk = ring(.95, 1.3, vosRiskMat, 0, .22, 0); vosRisk.visible = false;
    const otherRisk = ring(.95, 1.3, vosRiskMat, 0, .22, 0); otherRisk.visible = false;
    const auraMat = new T.MeshStandardMaterial({ color: '#8fc9ff', emissive: '#59a8ff', emissiveIntensity: 1.4, transparent: true, opacity: .25, side: T.DoubleSide }); stage.track(auraMat);
    const aura = mesh(new T.SphereGeometry(1, 24, 18), auraMat, scene); aura.castShadow = false; aura.visible = false;
    const waves = [0, 1, 2].map(() => { const wave = ring(.28, .33, glow('#2fa866', .8, 1.5), 0, 0, 0); wave.visible = false; return wave; });
    const clock = new T.Group(); clock.visible = false; scene.add(clock);
    const face = cyl(.62, .08, mat('#fffaf0'), clock, 0, 0, 0, 36); face.rotation.x = Math.PI / 2; face.castShadow = false;
    const needlePivot = new T.Group(); clock.add(needlePivot); box(.06, .46, .05, mat('#df3e39'), needlePivot, 0, .22, .07);

    const curves = {
        protectedVos: new T.CatmullRomCurve3([VOS_START, new T.Vector3(10.4, .1, 7.4), VOS_SAFE]),
        protectedOther: new T.CatmullRomCurve3([OTHER_START, new T.Vector3(16, .1, 7.4), new T.Vector3(13.8, .1, 7.4)]),
        retreatOther: new T.CatmullRomCurve3([new T.Vector3(13.8, .1, 7.4), new T.Vector3(16, .1, 7.4), new T.Vector3(18.2, .1, 7.4)]),
        exposeVos: new T.CatmullRomCurve3([VOS_START, new T.Vector3(6.2, .1, 5.9), new T.Vector3(4.1, .1, 4.9)]),
        recordOther: new T.CatmullRomCurve3([OTHER_START, new T.Vector3(11, .1, 7.2), new T.Vector3(6.6, .1, 5.8), new T.Vector3(4.6, .1, 4.4)]),
    };

    let outcome = 'intro', progress = 0, step = -1;
    function emitStep() { const next = outcome === 'intro' ? -1 : STEP_STARTS.reduce((acc, start, index) => progress >= start ? index : acc, 0); if (next !== step) { step = next; if (next >= 0) onStep(next); } }
    const resetActor = (actor, from, yaw) => { actor.group.position.copy(from); actor.group.rotation.set(0, yaw, 0); actor.limbs.forEach(limb => { limb.rotation.x = 0; }); actor.knees.forEach(k => { k.rotation.x = 0; }); actor.elbows.forEach(e => { e.rotation.x = -.08; }); };
    function reset() {
        resetActor(vos, VOS_START, -Math.PI / 2); resetActor(other, OTHER_START, -Math.PI / 2);
        [safeRing, vosRisk, otherRisk, aura, clock].forEach(item => { item.visible = false; }); waves.forEach(wave => { wave.visible = false; }); sign.scale.setScalar(.001); waiting.group.position.set(17, 0, LANE_A); step = -1;
    }
    function setOutcome(value) { outcome = value; progress = reduced && value !== 'intro' ? 1 : 0; reset(); }
    function setStep(index) { if (outcome === 'intro') return; progress = STEP_POSES[Math.max(0, Math.min(2, index))]; stage.paused = true; }

    stage.run(dt => {
        const simTime = stage.simTime;
        if (!stage.paused && outcome !== 'intro') progress = Math.min(1, progress + dt / DURATION);
        phone.visible = false; vos.limbs[3].rotation.x = 0; vos.limbs[1].rotation.x = 0; let waitingBrake = 2.2;
        // Luz de la chispa y luces de emergencia: pulsos lentos (menos de 3 por segundo).
        const pulse = .5 + .5 * Math.sin(simTime * 5); spark.scale.setScalar(.8 + pulse * .6); sparkLight.intensity = 3 + pulse * 6;
        crashed.brakeMat.emissiveIntensity = Math.floor(simTime * 1.2) % 2 ? 2.2 : .25;
        let dangerColor = outcome === 'intro' ? '#e8b72f' : '#df3e39'; dangerMat.color.set(dangerColor); dangerMat.emissive.set(dangerColor); dangerRing.scale.setScalar(1 + Math.sin(simTime * 3) * .015);
        if (outcome === 'protected') {
            const t = smooth(progress / .3); walk(vos, curves.protectedVos, t, false); if (t >= 1) vos.group.rotation.y = Math.PI / 2 + .2;
            const walkOther = smooth((progress - .12) / .45); if (progress < .66) walk(other, curves.protectedOther, walkOther, false); else walk(other, curves.retreatOther, smooth((progress - .66) / .3), false);
            if (progress >= .66 && progress < .96) other.group.rotation.y = Math.PI / 2; // se da la vuelta
            const warn = smooth((progress - .3) / .08); vos.limbs[1].rotation.x = -1.5 * warn; vos.elbows[0].rotation.x = -.15 * warn;
            sign.scale.setScalar(.001 + smooth((progress - .34) / .12) * .999); sign.position.y = 0;
            const call = smooth((progress - .68) / .08); vos.limbs[3].rotation.x = -2.3 * call; vos.elbows[1].rotation.x = -.5 * call; phone.visible = call > .02; phone.rotation.x = .3;
            safeRing.visible = progress > .28; safeRing.position.set(vos.group.position.x, .2, vos.group.position.z);
            if (progress > .68) waves.forEach((wave, index) => { const phase = ((simTime * .7 + index / 3) % 1); wave.visible = true; wave.position.set(vos.group.position.x, 2.55 + phase * .5, vos.group.position.z); wave.scale.setScalar(.6 + phase * 4.5); wave.material.opacity = (1 - phase) * .8; });
        } else if (outcome === 'expose') {
            const t = smooth((progress - .08) / .42); walk(vos, curves.exposeVos, t, false);
            const reach = smooth((progress - .5) / .1); vos.limbs[3].rotation.x = -1.2 * reach; vos.elbows[1].rotation.x = -.3 * reach; vos.group.lookAt(CABLE_END.x, .1, CABLE_END.z);
            vosRisk.visible = t > .2; vosRisk.position.set(vos.group.position.x, .22, vos.group.position.z); vosRisk.scale.setScalar(1 + Math.sin(simTime * 3) * .1);
            aura.visible = progress > .45; aura.position.set(vos.group.position.x, 1, vos.group.position.z); aura.scale.setScalar(1.6 + smooth((progress - .45) / .5) * 1.4 + Math.sin(simTime * 4) * .08); auraMat.opacity = .2 + .12 * Math.sin(simTime * 4);
        } else if (outcome === 'record') {
            const up = smooth(progress / .08); vos.limbs[3].rotation.x = -1.25 * up; vos.elbows[1].rotation.x = -1.1 * up; phone.visible = true; phone.rotation.x = .4; vos.group.rotation.y = -Math.PI / 2 - .3;
            clock.visible = progress > .28; clock.position.set(VOS_START.x, 3.2, VOS_START.z); clock.lookAt(camera.position); needlePivot.rotation.z = -progress * Math.PI * 6;
            const t = smooth((progress - .15) / .8); walk(other, curves.recordOther, t, false);
            const creep = smooth(progress); waiting.group.position.set(17 - creep * 9.5, 0, LANE_A); waitingBrake = progress > .85 ? 2.4 : .25;
            otherRisk.visible = t > .55; otherRisk.position.set(other.group.position.x, .22, other.group.position.z); otherRisk.scale.setScalar(1 + Math.sin(simTime * 3) * .1);
        }
        waiting.brakeMat.emissiveIntensity = waitingBrake; waiting.wheels.forEach(wheel => { wheel.rotation.z -= dt * (outcome === 'record' && progress < .97 && !stage.paused ? 2 : 0); });
        emitStep();
        if (stage.view === 'pedestrian') { const p = vos.group.position; camera.position.set(p.x + 1.6, 1.95, p.z + 1.8); camera.lookAt(2, .6, 3); }
        else if (stage.view === 'driver') { const p = waiting.group.position; camera.position.set(p.x - 1.2, 1.35, p.z); camera.lookAt(p.x - 22, .5, p.z + .6); }
    });
    setOutcome('intro');
    return { setOutcome, setStep, setPaused(value) { stage.paused = value; }, setView: stage.setView, dispose: stage.dispose };
}
