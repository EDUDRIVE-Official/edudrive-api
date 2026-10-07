import * as THREE from 'three';
import { createStage, buildStreet, buildPerson, buildPhone, buildBicycle, buildCar, walk, clamp01, smooth, easeOut, LANE_A, LANE_B } from './incident-scene-kit';

// Escena "Bicicleta caída": una persona cayó junto a la calzada y el tránsito sigue pasando.
// Resultados: 'protected' (lugar protegido + pedir ayuda), 'expose' (correr a la calzada), 'record' (grabar antes de pedir ayuda).
const DURATION = 9; // segundos por resultado
const STEP_STARTS = [0, .36, .7]; // progreso en que inicia cada paso de texto
const STEP_POSES = [.01, .5, 1]; // progreso mostrado al saltar a un paso
const FALLEN = new THREE.Vector3(-8, 0, 5.1);
const VOS_START = new THREE.Vector3(1, .1, 7.4);
const SAFE_SPOT = new THREE.Vector3(12, .1, 8.1);

export function mountFallenBicycleDecision(host, onStep = () => {}) {
    const stage = createStage(host, { label: 'Escena tridimensional: una persona cayó de su bicicleta junto a la calzada mientras pasan vehículos. Hay un lugar protegido en la acera.' });
    const { scene, camera, reduced, mat, paint, glow, mesh, box, cyl, ring } = stage;
    buildStreet(stage);

    // Lugar protegido: acera lejos de la calzada, tras una barrera baja con una banca.
    const post = mat('#2f7d4f', { roughness: .55 }); for (let x = 8.6; x <= 15.4; x += 1.7) { cyl(.12, 1, post, scene, x, .62, 6.75, 14); mesh(new THREE.SphereGeometry(.13, 12, 8), post, scene, x, 1.14, 6.75); }
    box(7, .08, .1, post, scene, 12, .98, 6.75);
    const wood = mat('#9a6b43'); box(1.9, .08, .5, wood, scene, 14.6, .55, 8.1); box(1.9, .5, .08, wood, scene, 14.6, .85, 8.38); for (const x of [13.8, 15.4]) box(.1, .5, .46, mat('#3c4650', { metalness: .5 }), scene, x, .28, 8.1);
    const safeRing = ring(1.2, 1.65, glow('#2fa866', .75), SAFE_SPOT.x, .18, SAFE_SPOT.z);
    const accessStrip = box(13, .03, .7, glow('#2fa866', .6, .8), scene, -10.5, .19, 7.4); accessStrip.castShadow = false;

    const vos = buildPerson(stage, { shirt: '#e8782c', pants: '#2f4257', skin: '#b98260', hair: '#2a1d17', scale: .99 });
    const phone = buildPhone(stage, vos);

    // Persona caída con casco y bicicleta tendida.
    const fallen = buildPerson(stage, { shirt: '#3f6fd0', pants: '#3a4658', skin: '#d0a07a', hair: '#6b4a2b', scale: .99 });
    fallen.group.position.set(FALLEN.x, .3, FALLEN.z); fallen.group.rotation.set(0, .35, Math.PI / 2 - .1); fallen.knees[0].rotation.x = .9; fallen.knees[1].rotation.x = .25; fallen.limbs[0].rotation.x = -.6; fallen.limbs[2].rotation.x = -.15; fallen.limbs[1].rotation.x = .5; fallen.limbs[3].rotation.x = -.7; fallen.elbows[1].rotation.x = -.9;
    const helmet = mesh(new THREE.SphereGeometry(.14, 20, 12, 0, Math.PI * 2, 0, Math.PI * .55), paint('#f2c230'), fallen.group, 0, 1.8, 0); helmet.scale.set(1, 1.15, 1.1); helmet.rotation.x = -.15;
    const bike = buildBicycle(stage); scene.add(bike); bike.rotation.order = 'YXZ'; bike.position.set(FALLEN.x - 1.8, .33, FALLEN.z - .45); bike.rotation.set(Math.PI / 2 - .14, .5, 0);

    const carA = buildCar(stage, '#2b7195'); carA.group.rotation.y = Math.PI; // carril cercano, hacia -x
    const carB = buildCar(stage, '#b83b2f'); // carril lejano, hacia +x

    // Marcadores didácticos.
    const clueMat = glow('#e8b72f', .65), riskMat = glow('#df3e39', .9, 1.5);
    const clue = ring(1.3, 1.7, clueMat, FALLEN.x, .18, FALLEN.z);
    const risk = ring(.95, 1.3, riskMat, 0, .2, 0); risk.visible = false;
    const waves = [0, 1, 2].map(() => { const wave = ring(.28, .33, glow('#2fa866', .8, 1.5), 0, 0, 0); wave.visible = false; return wave; });
    const clock = new THREE.Group(); clock.visible = false; scene.add(clock); clock.position.set(FALLEN.x, 3.1, FALLEN.z);
    const face = cyl(.62, .08, mat('#fffaf0'), clock, 0, 0, 0, 36); face.rotation.x = Math.PI / 2; face.castShadow = false;
    const needlePivot = new THREE.Group(); clock.add(needlePivot); box(.06, .46, .05, mat('#df3e39'), needlePivot, 0, .22, .07);

    const curves = {
        protected: new THREE.CatmullRomCurve3([VOS_START, new THREE.Vector3(6, .1, 7.9), SAFE_SPOT]),
        expose: new THREE.CatmullRomCurve3([VOS_START, new THREE.Vector3(-1.4, .1, 5.7), new THREE.Vector3(-6, .1, 3.5)]),
    };

    let outcome = 'intro', progress = 0, step = -1;
    function emitStep() { const next = outcome === 'intro' ? -1 : STEP_STARTS.reduce((acc, start, index) => progress >= start ? index : acc, 0); if (next !== step) { step = next; if (next >= 0) onStep(next); } }
    function reset() {
        vos.group.position.copy(VOS_START); vos.group.rotation.set(0, -Math.PI / 2, 0); vos.limbs.forEach(limb => { limb.rotation.x = 0; }); vos.knees.forEach(k => { k.rotation.x = 0; }); vos.elbows.forEach(e => { e.rotation.x = -.08; });
        risk.visible = false; clock.visible = false; waves.forEach(wave => { wave.visible = false; }); carA.brakeMat.emissiveIntensity = .25; step = -1;
    }
    function setOutcome(value) { outcome = value; progress = reduced && value !== 'intro' ? 1 : 0; reset(); }
    function setStep(index) { if (outcome === 'intro') return; progress = STEP_POSES[Math.max(0, Math.min(2, index))]; stage.paused = true; }

    stage.run(dt => {
        const simTime = stage.simTime;
        if (!stage.paused && outcome !== 'intro') progress = Math.min(1, progress + dt / DURATION);
        // Tránsito: en bucle, salvo el vehículo cercano cuando alguien entra a la calzada.
        if (reduced) { carA.group.position.set(14, 0, LANE_A); carB.group.position.set(-14, 0, LANE_B); }
        else { carA.group.position.set(40 - ((simTime * 7 + 20) % 80), 0, LANE_A); carB.group.position.set(-40 + ((simTime * 8) % 80), 0, LANE_B); }
        phone.visible = false; vos.limbs[3].rotation.x = 0; let brake = false, spin = 6;
        if (outcome === 'protected') {
            const t = clamp01(progress / .36); walk(vos, curves.protected, smooth(t), false);
            if (t >= 1) { vos.group.rotation.y = -Math.PI / 2 - .35; const up = smooth((progress - .36) / .1); vos.limbs[3].rotation.x = -2.35 * up; vos.elbows[1].rotation.x = -.5 * up; phone.visible = true; phone.rotation.x = .3; }
            safeRing.scale.setScalar(1 + (t >= 1 ? Math.sin(simTime * 5) * .08 : 0));
            if (progress > .4) waves.forEach((wave, index) => { const phase = ((simTime * .7 + index / 3) % 1); wave.visible = true; wave.position.set(vos.group.position.x - .2, 2.55 + phase * .5, vos.group.position.z); wave.scale.setScalar(.6 + phase * 4.5); wave.material.opacity = (1 - phase) * .8; });
        } else if (outcome === 'expose') {
            const t = smooth((progress - .1) / .38); walk(vos, curves.expose, t, true);
            const arrive = easeOut(progress / .72); carA.group.position.set(36 - arrive * 34, 0, LANE_A); brake = arrive > .35 && arrive < 1 && progress < .92; spin = 12 * (1 - arrive);
            risk.visible = t > .15; risk.position.set(vos.group.position.x, .2, vos.group.position.z); risk.scale.setScalar(1 + Math.sin(simTime * 8) * .12);
        } else if (outcome === 'record') {
            const up = smooth(progress / .08); vos.limbs[3].rotation.x = -1.25 * up; vos.elbows[1].rotation.x = -1.1 * up; phone.visible = true; phone.rotation.x = .4;
            clock.visible = progress > .28; clock.lookAt(camera.position); needlePivot.rotation.z = -progress * Math.PI * 6;
        }
        carA.brakeMat.emissiveIntensity = brake ? 2.4 : .25;
        carA.wheels.forEach(wheel => { wheel.rotation.z -= dt * spin; }); carB.wheels.forEach(wheel => { wheel.rotation.z -= dt * 6; });
        accessStrip.visible = outcome === 'protected' && progress > .7; safeRing.visible = outcome === 'protected' || outcome === 'intro';
        clue.visible = outcome !== 'protected' || progress < .7; clue.scale.setScalar(1 + Math.sin(simTime * 4) * .08); const clueColor = outcome === 'record' ? '#df3e39' : '#e8b72f'; clueMat.color.set(clueColor); clueMat.emissive.set(clueColor);
        emitStep();
        if (stage.view === 'pedestrian') { const p = vos.group.position; camera.position.set(p.x + 1.5, 1.95, p.z + 1.7); camera.lookAt(FALLEN.x, .9, FALLEN.z); }
        else if (stage.view === 'driver') { const p = carA.group.position; camera.position.set(p.x - 1.2, 1.35, p.z); camera.lookAt(p.x - 22, 1, p.z + .5); }
    });
    setOutcome('intro');
    return { setOutcome, setStep, setPaused(value) { stage.paused = value; }, setView: stage.setView, dispose: stage.dispose };
}
