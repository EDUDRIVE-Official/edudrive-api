import * as THREE from 'three';
import { createStage, buildStreet, buildPerson, buildPhone, buildCar, buildAmbulance, buildLabel, walk, clamp01, smooth, easeOut, LANE_A, LANE_B } from './incident-scene-kit';

// Escena "Persona en el suelo": alguien quiere levantar de inmediato a la persona afectada.
// Resultados: 'protected' (dar espacio, alertar y seguir instrucciones), 'expose' (moverla para que siga el tránsito), 'record' (grabar y compartir).
const DURATION = 9; // segundos por resultado
const STEP_STARTS = [0, .34, .68]; // progreso en que inicia cada paso de texto
const STEP_POSES = [.01, .5, 1]; // progreso mostrado al saltar a un paso
const PATIENT = new THREE.Vector3(0, .3, 1.2); const PATIENT_SIDEWALK = new THREE.Vector3(-2.4, .3, 6.7);
const VOS_START = new THREE.Vector3(-3, .1, 5.7), VOS_BACK = new THREE.Vector3(3.2, .1, 7);
const CENTRAL = new THREE.Vector3(10, 5.8, -3); const CLOUD = new THREE.Vector3(13, 5.8, 3.5);

export function mountPersonOnGroundDecision(host, onStep = () => {}) {
    const stage = createStage(host, { label: 'Escena tridimensional: una persona yace en la calzada y varias personas la observan. Hay un vehículo detenido, el servicio de ayuda y una ambulancia en camino.', overview: { position: [8, 21, 33], target: [1, 0, 2.5] } });
    const { camera, reduced, mat, glow, cyl, box, ring, scene, mesh, THREE: T } = stage;
    buildStreet(stage);

    // Persona afectada tendida en la calzada.
    const patient = buildPerson(stage, { shirt: '#3f6fd0', pants: '#3a4658', skin: '#d0a07a', hair: '#6b4a2b', scale: .99 });
    const layPatient = () => { patient.group.position.copy(PATIENT); patient.group.rotation.set(0, .3, Math.PI / 2 - .08); patient.limbs.forEach(limb => { limb.rotation.x = 0; }); patient.knees[0].rotation.x = .5; patient.knees[1].rotation.x = .15; patient.limbs[1].rotation.x = .3; patient.limbs[3].rotation.x = -.5; patient.elbows[1].rotation.x = -.6; };
    layPatient();

    const vos = buildPerson(stage, { shirt: '#e8782c', pants: '#2f4257', skin: '#b98260', hair: '#2a1d17', scale: .99 });
    const phone = buildPhone(stage, vos);
    const onlookers = [[-6, 8], [-1.5, 8.6], [-8.5, 7.2]].map(([x, z], index) => { const person = buildPerson(stage, { shirt: ['#4f8f5c', '#8e5bd0', '#5a6b8c'][index], pants: '#3b3b46', skin: ['#c28d68', '#a97550', '#d9a982'][index], hair: ['#1c1c1c', '#4a2d18', '#2a2a2a'][index], scale: .96 + index * .02 }); person.group.position.set(x, .1, z); person.group.rotation.y = Math.PI + .2 * (index - 1); return person; });
    const medics = [0, 1].map(index => { const person = buildPerson(stage, { shirt: '#ff7a00', pants: '#1f2a44', skin: ['#c9966f', '#a97550'][index], hair: ['#1c1c1c', '#3a2a1a'][index], scale: 1 }); person.group.visible = false; return person; });

    // Tránsito detenido a ambos lados y ambulancia en camino.
    const waiting = buildCar(stage, '#b83b2f'); waiting.group.position.set(-9, 0, LANE_B);
    const blocked = buildCar(stage, '#2b7195'); blocked.group.rotation.y = Math.PI; blocked.group.position.set(10, 0, LANE_A);
    const ambulance = buildAmbulance(stage); ambulance.group.rotation.y = Math.PI; ambulance.group.position.set(60, 0, LANE_A);

    // Marcadores y etiquetas.
    const central = buildLabel(stage, 'Servicio de ayuda', { bg: '#dbeafe', fg: '#1e3a8a', width: 3.6 }); central.position.copy(CENTRAL); central.visible = true;
    const cloud = buildLabel(stage, 'Redes (público)', { bg: '#ede9fe', fg: '#4c1d95', width: 3.6 }); cloud.position.copy(CLOUD);
    const need = buildLabel(stage, 'Necesita atención', { bg: '#fff3cd', fg: '#7a5a00', width: 3.4 }); need.position.set(PATIENT.x, 3.4, PATIENT.z); need.visible = true;
    const alerted = buildLabel(stage, 'Alerta enviada ✓', { bg: '#dcfce7', fg: '#166534', width: 3.4 });
    const tells = [buildLabel(stage, 'No la muevas', { bg: '#dbeafe', fg: '#1e3a8a', width: 3 }), buildLabel(stage, 'Ya van en camino', { bg: '#dbeafe', fg: '#1e3a8a', width: 3.4 })];
    const space = buildLabel(stage, 'Dar espacio', { bg: '#dcfce7', fg: '#166534', width: 2.8 }); const pros = buildLabel(stage, 'Personal profesional ✓', { bg: '#dcfce7', fg: '#166534', width: 4.4 });
    const worse = buildLabel(stage, 'Puede empeorar la lesión', { bg: '#fee2e2', fg: '#991b1b', width: 4.8 }); const noHelp = buildLabel(stage, 'Sin ayuda profesional ✗', { bg: '#fee2e2', fg: '#991b1b', width: 4.4 });
    const rec = buildLabel(stage, '● REC', { bg: '#fee2e2', fg: '#b91c1c', width: 2 }); const shared = buildLabel(stage, 'Compartido', { bg: '#ede9fe', fg: '#4c1d95', width: 2.8 });
    const spaceRing = ring(3.3, 3.6, glow('#2fa866', .7, 1), PATIENT.x, .2, PATIENT.z); spaceRing.visible = false;
    const riskRing = ring(1.5, 1.9, glow('#df3e39', .9, 1.5), PATIENT.x, .2, PATIENT.z); riskRing.visible = false;
    const clock = new T.Group(); clock.visible = false; scene.add(clock);
    const face = cyl(.62, .08, mat('#fffaf0'), clock, 0, 0, 0, 36); face.rotation.x = Math.PI / 2; face.castShadow = false;
    const needlePivot = new T.Group(); clock.add(needlePivot); box(.06, .46, .05, mat('#df3e39'), needlePivot, 0, .22, .07);
    const aboveVos = new T.Vector3(), curves = {
        approach: new T.CatmullRomCurve3([VOS_START, new T.Vector3(-1.4, .1, 3.8), new T.Vector3(.3, .1, 2.1)]),
        back: new T.CatmullRomCurve3([new T.Vector3(-1.2, .1, 4.3), new T.Vector3(1.2, .1, 5.8), VOS_BACK]),
        medicA: new T.CatmullRomCurve3([new T.Vector3(3.6, .1, 3.8), new T.Vector3(2.4, .1, 3.0), new T.Vector3(1.4, .1, 2.2)]),
        medicB: new T.CatmullRomCurve3([new T.Vector3(3.8, .1, 1.4), new T.Vector3(2.6, .1, .6), new T.Vector3(1.4, .1, .1)]),
    };
    const crouch = (actor, k) => { actor.limbs[0].rotation.x = actor.limbs[2].rotation.x = -1.35 * k; actor.knees[0].rotation.x = actor.knees[1].rotation.x = 1.5 * k; actor.group.position.y = .1 - .5 * k; actor.limbs[1].rotation.x = actor.limbs[3].rotation.x = -.9 * k; };

    const hideAll = () => { [alerted, ...tells, space, pros, worse, noHelp, rec, shared, cloud, spaceRing, riskRing, clock].forEach(item => { item.visible = false; }); medics.forEach(m => { m.group.visible = false; }); need.visible = true; layPatient(); };
    let outcome = 'intro', progress = 0, step = -1;
    function emitStep() { const next = outcome === 'intro' ? -1 : STEP_STARTS.reduce((acc, start, index) => progress >= start ? index : acc, 0); if (next !== step) { step = next; if (next >= 0) onStep(next); } }
    function setOutcome(value) { outcome = value; progress = reduced && value !== 'intro' ? 1 : 0; step = -1; hideAll(); }
    function setStep(index) { if (outcome === 'intro') return; progress = STEP_POSES[Math.max(0, Math.min(2, index))]; stage.paused = true; }
    const fly = (label, from, to, t0, t1, lift = .8) => { const t = (progress - t0) / (t1 - t0); label.visible = t > 0 && t < 1.12; if (!label.visible) return; label.position.lerpVectors(from, to, smooth(t)); label.position.y += Math.sin(Math.PI * clamp01(t)) * lift; };

    stage.run(dt => {
        const simTime = stage.simTime;
        if (!stage.paused && outcome !== 'intro') progress = Math.min(1, progress + dt / DURATION);
        vos.limbs.forEach(limb => { limb.rotation.x = 0; }); vos.knees.forEach(k => { k.rotation.x = 0; }); vos.elbows.forEach(e => { e.rotation.x = -.08; }); phone.visible = false;
        waiting.brakeMat.emissiveIntensity = 2.2; blocked.brakeMat.emissiveIntensity = 2.2; waiting.group.position.set(-9, 0, LANE_B); blocked.group.position.set(10, 0, LANE_A);
        const ambulanceActive = outcome === 'protected' && progress > .5; const run = easeOut((progress - .5) / .3);
        ambulance.group.position.set(ambulanceActive ? 60 - run * 53.5 : 60, 0, LANE_A); const beat = Math.floor(simTime * 1.6) % 2;
        ambulance.blue.emissiveIntensity = ambulanceActive ? (beat ? 2.2 : .3) : .3; ambulance.redLight.emissiveIntensity = ambulanceActive ? (beat ? .3 : 2.2) : .3;
        if (outcome === 'intro') { vos.group.position.copy(VOS_START); vos.group.rotation.y = Math.PI; }
        else if (outcome === 'protected') {
            const t = smooth(progress / .3); walk(vos, curves.back, t, false); if (t >= 1) vos.group.rotation.y = Math.PI - .5;
            const warn = smooth((progress - .12) / .08); vos.limbs[1].rotation.x = -1.4 * warn; vos.elbows[0].rotation.x = -.2 * warn;
            const call = smooth((progress - .26) / .08); vos.limbs[3].rotation.x = -2.5 * call; vos.elbows[1].rotation.x = -.3 * call; phone.visible = call > .02; phone.rotation.x = .2; if (call > .5) vos.limbs[1].rotation.x = 0;
            aboveVos.set(vos.group.position.x, 3.2, vos.group.position.z); vos.group.position.y = .1;
            spaceRing.visible = progress > .05; spaceRing.scale.setScalar(1 + Math.sin(simTime * 3) * .015); space.visible = progress > .05 && progress < .4; space.position.set(PATIENT.x + 3.2, 3, PATIENT.z + 1.8);
            fly(alerted, aboveVos, CENTRAL, .3, .42); fly(tells[0], CENTRAL, aboveVos, .42, .54); fly(tells[1], CENTRAL, aboveVos, .52, .64);
            medics.forEach((m, i) => { m.group.visible = progress > .74; const t = smooth((progress - .74) / .16); walk(m, i ? curves.medicB : curves.medicA, t, false); if (t >= 1) { m.group.rotation.y = i ? Math.PI * .5 : Math.PI * .75; crouch(m, smooth((progress - .9) / .08)); } });
            pros.visible = progress > .86; pros.position.set(PATIENT.x + 1, 3.4, PATIENT.z + .6); need.visible = progress < .5;
        } else if (outcome === 'expose') {
            const approach = smooth((progress - .06) / .22), drag = smooth((progress - .3) / .32);
            if (progress < .3) { walk(vos, curves.approach, approach, false); } else { const p = new T.Vector3().lerpVectors(PATIENT, PATIENT_SIDEWALK, drag); patient.group.position.copy(p); vos.group.position.set(p.x + .3, .1, p.z + .9); vos.group.rotation.y = Math.PI; vos.limbs[1].rotation.x = vos.limbs[3].rotation.x = -1.1; vos.elbows[0].rotation.x = vos.elbows[1].rotation.x = -.3; const step = Math.sin(drag * 34) * .4 * (drag > 0 && drag < 1 ? 1 : 0); vos.limbs[0].rotation.x = step; vos.limbs[2].rotation.x = -step; }
            riskRing.visible = progress > .3; riskRing.position.set(patient.group.position.x, .2, patient.group.position.z); riskRing.scale.setScalar(1 + Math.sin(simTime * 3) * .08);
            worse.visible = progress > .4 && progress < .72; worse.position.set(patient.group.position.x, 3.6, patient.group.position.z); noHelp.visible = progress > .72; noHelp.position.set(patient.group.position.x, 3.6, patient.group.position.z); need.visible = false;
            const go = clamp01((progress - .62) / .38); waiting.group.position.set(-9 + go * 42, 0, LANE_B); waiting.brakeMat.emissiveIntensity = go > 0 ? .25 : 2.2;
        } else if (outcome === 'record') {
            const t = smooth(progress / .12); vos.group.position.lerpVectors(VOS_START, new T.Vector3(-.4, .1, 4.4), t); vos.group.rotation.y = Math.PI;
            const up = smooth(progress / .1); vos.limbs[3].rotation.x = -1.3 * up; vos.elbows[1].rotation.x = -1.0 * up; phone.visible = true; phone.rotation.x = .4;
            rec.visible = progress > .12; rec.position.set(vos.group.position.x, 3.2, vos.group.position.z); cloud.visible = progress > .5; aboveVos.set(vos.group.position.x, 3.2, vos.group.position.z); fly(shared, aboveVos, CLOUD, .66, .86, 1);
            clock.visible = progress > .3; clock.position.set(vos.group.position.x, 4.3, vos.group.position.z); clock.lookAt(camera.position); needlePivot.rotation.z = -progress * Math.PI * 6; need.visible = progress < .34;
        }
        ambulance.wheels.forEach(wheel => { wheel.rotation.z -= (ambulanceActive && progress < .8 && !stage.paused ? 1 - run * .85 : 0) * dt * 10; });
        emitStep();
        if (stage.view === 'pedestrian') { const p = vos.group.position; camera.position.set(p.x + 1.3, 1.9, p.z + 1.6); camera.lookAt(patient.group.position.x, .5, patient.group.position.z); }
        else if (stage.view === 'driver') { const p = waiting.group.position; camera.position.set(p.x + 1.2, 1.35, p.z); camera.lookAt(patient.group.position.x, .5, patient.group.position.z); }
    });
    setOutcome('intro');
    return { setOutcome, setStep, setPaused(value) { stage.paused = value; }, setView: stage.setView, dispose: stage.dispose };
}
