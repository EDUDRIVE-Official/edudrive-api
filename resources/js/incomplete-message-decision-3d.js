import * as THREE from 'three';
import { createStage, buildStreet, buildPerson, buildPhone, buildCar, buildAmbulance, buildLabel, buildPin, walk, clamp01, smooth, easeOut, LANE_A } from './incident-scene-kit';

// Escena "Mensaje incompleto": alguien dice «hubo un accidente» y corta; no se sabe dónde ni qué pasó.
// Resultados: 'protected' (quedarse disponible y responder lo necesario), 'expose' (reenviar a un grupo y esperar), 'record' (grabar y compartir).
const DURATION = 9; // segundos por resultado
const STEP_STARTS = [0, .34, .68]; // progreso en que inicia cada paso de texto
const STEP_POSES = [.01, .5, 1]; // progreso mostrado al saltar a un paso
const VOS = new THREE.Vector3(6, .1, 7.4);
const CRASH = new THREE.Vector3(-15, 0, -2.7);
const CENTRAL = new THREE.Vector3(10, 5.8, -3); // «Servicio de ayuda»
const CLOUD = new THREE.Vector3(13, 5.8, 3.5); // «Redes»

export function mountIncompleteMessageDecision(host, onStep = () => {}) {
    const stage = createStage(host, { label: 'Escena tridimensional: alguien avisó de un accidente sin decir dónde y cortó. Se ve un choque lejano sin ubicación confirmada, una persona con el teléfono y el servicio de ayuda.', overview: { position: [6, 25, 38], target: [-2, 0, 1] } });
    const { camera, reduced, mat, glow, cyl, box, ring, scene, THREE: T } = stage;
    buildStreet(stage);

    // Lugar del choque (sin ubicación confirmada al inicio).
    const crashed = buildCar(stage, '#2b7195'); crashed.group.position.copy(CRASH); crashed.group.rotation.y = .55;
    const crashedB = buildCar(stage, '#b83b2f'); crashedB.group.position.set(CRASH.x - 5.2, 0, CRASH.z + 3.2); crashedB.group.rotation.y = -.35;
    const pin = buildPin(stage, '#9aa3ab'); pin.group.position.set(CRASH.x - 1.5, 4.6, CRASH.z);
    const pinRing = ring(2.3, 2.6, glow('#9aa3ab', .5, .5), CRASH.x - 1.8, .2, CRASH.z + .4);
    const unknown = buildLabel(stage, '¿Dónde?', { bg: '#fff3cd', fg: '#7a5a00', width: 2.2 }); unknown.position.set(CRASH.x - 1.5, 7, CRASH.z);
    const located = buildLabel(stage, 'Ubicación ✓', { bg: '#dcfce7', fg: '#166534', width: 2.6 }); located.position.set(CRASH.x - 1.5, 7, CRASH.z);

    const caller = buildPerson(stage, { shirt: '#8e5bd0', pants: '#40394f', skin: '#c9966f', hair: '#3a2a1a', scale: .97 }); caller.group.position.set(-9.5, .1, 6.8); caller.group.rotation.y = -Math.PI / 2 - .5; caller.limbs[3].rotation.x = -.35; caller.elbows[1].rotation.x = -.4;
    const vos = buildPerson(stage, { shirt: '#e8782c', pants: '#2f4257', skin: '#b98260', hair: '#2a1d17', scale: .99 }); vos.group.position.copy(VOS); vos.group.rotation.y = -Math.PI / 2;
    const phone = buildPhone(stage, vos);
    const group = [[2.6, 8.9], [4.3, 9.3], [8.6, 9.0]].map(([x, z], index) => { const person = buildPerson(stage, { shirt: ['#4f8f5c', '#d4a02a', '#4a78c2'][index], pants: '#3b3b46', skin: ['#c28d68', '#a97550', '#d9a982'][index], hair: ['#1c1c1c', '#4a2d18', '#2a2a2a'][index], scale: .95 + index * .02 }); person.group.position.set(x, .1, z); person.group.rotation.y = -Math.PI / 2 + (index - 1) * .5; const question = buildLabel(stage, '?', { bg: '#fff3cd', fg: '#7a5a00', width: 1.3 }); return { person, question, x, z }; });

    const ambulance = buildAmbulance(stage); ambulance.group.rotation.y = Math.PI; ambulance.group.position.set(52, 0, LANE_A);
    const central = buildLabel(stage, 'Servicio de ayuda', { bg: '#dbeafe', fg: '#1e3a8a', width: 3.6 }); central.position.copy(CENTRAL); central.visible = true;
    const cloud = buildLabel(stage, 'Redes / grupo', { bg: '#ede9fe', fg: '#4c1d95', width: 3.4 }); cloud.position.copy(CLOUD);
    const asks = [buildLabel(stage, '¿Dónde ocurrió?', { bg: '#dbeafe', fg: '#1e3a8a', width: 3.4 }), buildLabel(stage, '¿Hay heridos?', { bg: '#dbeafe', fg: '#1e3a8a', width: 3 })];
    const replies = [buildLabel(stage, 'Ubicación ✓', { bg: '#dcfce7', fg: '#166534', width: 2.8 }), buildLabel(stage, 'Datos ✓', { bg: '#dcfce7', fg: '#166534', width: 2.2 })];
    const sends = [0, 1, 2].map(() => buildLabel(stage, 'Mensaje', { bg: '#ffffff', fg: '#334155', width: 2 }));
    const rec = buildLabel(stage, '● REC', { bg: '#fee2e2', fg: '#b91c1c', width: 2 }); const shared = buildLabel(stage, 'Compartido', { bg: '#ede9fe', fg: '#4c1d95', width: 2.8 });
    const clock = new T.Group(); clock.visible = false; scene.add(clock);
    const face = cyl(.62, .08, mat('#fffaf0'), clock, 0, 0, 0, 36); face.rotation.x = Math.PI / 2; face.castShadow = false;
    const needlePivot = new T.Group(); clock.add(needlePivot); box(.06, .46, .05, mat('#df3e39'), needlePivot, 0, .22, .07);
    const aboveVos = new T.Vector3(VOS.x, 3.3, VOS.z);

    const hideAll = () => { [...asks, ...replies, ...sends, rec, shared, located, cloud].forEach(item => { item.visible = false; }); group.forEach(item => { item.question.visible = false; item.person.group.visible = false; }); clock.visible = false; unknown.visible = true; };
    let outcome = 'intro', progress = 0, step = -1;
    function emitStep() { const next = outcome === 'intro' ? -1 : STEP_STARTS.reduce((acc, start, index) => progress >= start ? index : acc, 0); if (next !== step) { step = next; if (next >= 0) onStep(next); } }
    function setOutcome(value) { outcome = value; progress = reduced && value !== 'intro' ? 1 : 0; step = -1; hideAll(); }
    function setStep(index) { if (outcome === 'intro') return; progress = STEP_POSES[Math.max(0, Math.min(2, index))]; stage.paused = true; }
    // Mueve una etiqueta entre dos puntos durante una ventana de progreso.
    const fly = (label, from, to, t0, t1, lift = .8) => { const t = (progress - t0) / (t1 - t0); label.visible = t > 0 && t < 1.12; if (!label.visible) return; const k = smooth(t); label.position.lerpVectors(from, to, k); label.position.y += Math.sin(Math.PI * clamp01(t)) * lift; };

    stage.run(dt => {
        const simTime = stage.simTime;
        if (!stage.paused && outcome !== 'intro') progress = Math.min(1, progress + dt / DURATION);
        crashed.brakeMat.emissiveIntensity = Math.floor(simTime * 1.2) % 2 ? 2.2 : .25; crashedB.brakeMat.emissiveIntensity = Math.floor(simTime * 1.2 + .5) % 2 ? 2.2 : .25;
        phone.visible = false; vos.limbs[3].rotation.x = 0; vos.elbows[1].rotation.x = -.08; vos.group.rotation.y = -Math.PI / 2; unknown.visible = outcome !== 'protected' || progress < .7; located.visible = outcome === 'protected' && progress >= .7;
        pin.group.position.y = 4.6 + Math.sin(simTime * 2.2) * .12; pin.group.rotation.y += .01;
        const ambulanceActive = outcome === 'protected' && progress > .55; const run = easeOut((progress - .55) / .42);
        ambulance.group.position.set(ambulanceActive ? 52 - run * 62 : 52, 0, LANE_A);
        const beat = Math.floor(simTime * 1.6) % 2; ambulance.blue.emissiveIntensity = ambulanceActive ? (beat ? 2.2 : .3) : .3; ambulance.redLight.emissiveIntensity = ambulanceActive ? (beat ? .3 : 2.2) : .3;
        pin.material.color.set(outcome === 'protected' && progress >= .7 ? '#2fa866' : '#9aa3ab'); pin.material.emissive.set(outcome === 'protected' && progress >= .7 ? '#2fa866' : '#9aa3ab');
        if (outcome === 'protected') {
            const call = smooth(progress / .1); vos.limbs[3].rotation.x = -2.5 * call; vos.elbows[1].rotation.x = -.3 * call; phone.visible = call > .02; phone.rotation.x = .2;
            fly(asks[0], CENTRAL, aboveVos, .34, .46); fly(replies[0], aboveVos, CENTRAL, .44, .56); fly(asks[1], CENTRAL, aboveVos, .52, .62); fly(replies[1], aboveVos, CENTRAL, .6, .7);
        } else if (outcome === 'expose') {
            const typing = smooth(progress / .08); vos.limbs[3].rotation.x = -1.1 * typing; vos.elbows[1].rotation.x = -1.2 * typing; phone.visible = true; phone.rotation.x = .5;
            cloud.visible = progress > .2; group.forEach((item, index) => { item.person.group.visible = progress > .2; item.question.visible = progress > .52; item.question.position.set(item.x, 2.7, item.z); });
            sends.forEach((label, index) => fly(label, aboveVos, new T.Vector3(group[index].x, 2.7, group[index].z), .34 + index * .04, .54 + index * .04, 1));
            clock.visible = progress > .5; clock.position.set(VOS.x, 3.4, VOS.z); clock.lookAt(camera.position); needlePivot.rotation.z = -progress * Math.PI * 6;
        } else if (outcome === 'record') {
            const up = smooth(progress / .08); vos.limbs[3].rotation.x = -1.3 * up; vos.elbows[1].rotation.x = -1.0 * up; phone.visible = true; phone.rotation.x = .4; vos.group.rotation.y = -Math.PI / 2 - .5;
            rec.visible = progress > .1; rec.position.set(VOS.x, 3.1, VOS.z); cloud.visible = progress > .5; fly(shared, aboveVos, CLOUD, .66, .86, 1);
            clock.visible = progress > .3; clock.position.set(VOS.x, 4.1, VOS.z); clock.lookAt(camera.position); needlePivot.rotation.z = -progress * Math.PI * 6;
        }
        ambulance.wheels.forEach(wheel => { wheel.rotation.z -= (ambulanceActive && progress < .97 && !stage.paused ? 1 - run * .85 : 0) * dt * 10; });
        emitStep();
        if (stage.view === 'pedestrian') { camera.position.set(VOS.x + 1.6, 1.95, VOS.z + 1.8); camera.lookAt(CRASH.x, 1, CRASH.z); }
        else if (stage.view === 'driver') { const p = ambulance.group.position; camera.position.set(Math.min(p.x - 1.2, 30), 1.7, p.z); camera.lookAt(CRASH.x, 1, CRASH.z); }
    });
    setOutcome('intro');
    return { setOutcome, setStep, setPaused(value) { stage.paused = value; }, setView: stage.setView, dispose: stage.dispose };
}
