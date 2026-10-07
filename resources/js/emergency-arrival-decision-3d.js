import * as THREE from 'three';
import { createStage, buildStreet, buildPerson, buildPhone, buildAmbulance, buildLabel, walk, clamp01, smooth, LANE_A } from './incident-scene-kit';

// Escena "Llegan emergencias": un grupo bloquea el acceso mientras graba videos.
// Resultados: 'protected' (alejarse, liberar el paso y proteger la privacidad), 'expose' (acercarse a obtener información), 'record' (grabar y compartir).
const DURATION = 9; // segundos por resultado
const STEP_STARTS = [0, .34, .68]; // progreso en que inicia cada paso de texto
const STEP_POSES = [.01, .5, 1]; // progreso mostrado al saltar a un paso
const PATIENT = new THREE.Vector3(0, .3, 1.2);
const VOS_START = new THREE.Vector3(3.3, .1, 3.5), VOS_BACK = new THREE.Vector3(6.4, .1, 7);
const CLOUD = new THREE.Vector3(13, 5.8, 3.5);
const CROWD_BLOCK = [[2.9, 1.1], [3.2, 2.4], [3.5, 3.9], [1.9, -.1], [2.7, 5]]; // forman un arco entre la ambulancia y la persona
const CROWD_AWAY = [[-4, 7], [2.5, 7.9], [-1.5, 8.2], [9, 7.6], [11, 8.4]]; // se retiran a la acera
const CROWD_PUSH = [[1.5, 1.1], [1.9, 2.1], [2.2, 3.1], [1.3, .1], [1.9, 3.9]]; // se aprietan alrededor

export function mountEmergencyArrivalDecision(host, onStep = () => {}) {
    const stage = createStage(host, { label: 'Escena tridimensional: llegó una ambulancia a atender a una persona en el suelo, pero un grupo de personas con teléfonos bloquea el acceso del personal de emergencia.', overview: { position: [9, 20, 31], target: [3, 0, 2.5] } });
    const { camera, reduced, mat, glow, cyl, box, ring, scene, mesh, THREE: T } = stage;
    buildStreet(stage);

    const patient = buildPerson(stage, { shirt: '#3f6fd0', pants: '#3a4658', skin: '#d0a07a', hair: '#6b4a2b', scale: .99 });
    patient.group.position.copy(PATIENT); patient.group.rotation.set(0, .3, Math.PI / 2 - .08); patient.knees[0].rotation.x = .5; patient.knees[1].rotation.x = .15; patient.limbs[1].rotation.x = .3; patient.limbs[3].rotation.x = -.5; patient.elbows[1].rotation.x = -.6;

    const ambulance = buildAmbulance(stage); ambulance.group.rotation.y = Math.PI; ambulance.group.position.set(8.2, 0, LANE_A);
    const vos = buildPerson(stage, { shirt: '#e8782c', pants: '#2f4257', skin: '#b98260', hair: '#2a1d17', scale: .99 });
    const vosPhone = buildPhone(stage, vos);
    // Grupo: cada persona sostiene un teléfono levantado.
    const crowd = CROWD_BLOCK.map(([x, z], index) => {
        const person = buildPerson(stage, { shirt: ['#4f8f5c', '#8e5bd0', '#5a6b8c', '#c2564a', '#d4a02a'][index], pants: '#3b3b46', skin: ['#c28d68', '#a97550', '#d9a982', '#b98260', '#c9966f'][index], hair: ['#1c1c1c', '#4a2d18', '#2a2a2a', '#3a2a1a', '#1c1c1c'][index], scale: .95 + (index % 3) * .03 });
        return { person, phone: buildPhone(stage, person), curve: null, pos: new T.Vector3(x, .1, z) };
    });
    // Personal de emergencia con una camilla.
    const medics = [0, 1].map(index => buildPerson(stage, { shirt: '#ff7a00', pants: '#1f2a44', skin: ['#c9966f', '#a97550'][index], hair: ['#1c1c1c', '#3a2a1a'][index], scale: 1 }));
    const stretcher = new T.Group(); scene.add(stretcher); const white = mat('#f1f4f6', { roughness: .5 }), steel = mat('#8a949e', { metalness: .7, roughness: .35 });
    box(1.9, .09, .68, white, stretcher, 0, .86, 0); box(1.9, .05, .64, mat('#2a6fb8'), stretcher, 0, .92, 0); for (const x of [-.7, .7]) { box(.05, .7, .5, steel, stretcher, x, .5, 0); for (const z of [-.25, .25]) { const wheel = cyl(.09, .06, mat('#1c1f23'), stretcher, x, .12, z, 14); wheel.rotation.x = Math.PI / 2; } }
    // Biombos de privacidad alrededor de la persona atendida.
    const screenMat = new T.MeshStandardMaterial({ color: '#f6f8fa', roughness: .8, transparent: true, opacity: .92 }); stage.track(screenMat);
    const screens = [[0, -.6, 2.8, .08], [-1.7, .5, .08, 2.4], [1.7, .5, .08, 2.4]].map(([x, z, w, d]) => { const panel = mesh(new T.BoxGeometry(w, 1.6, d), screenMat, scene, PATIENT.x + x, .8, PATIENT.z + z); panel.scale.y = .001; panel.position.y = .02; return panel; });
    const corridor = box(5.2, .03, 1.7, glow('#2fa866', .55, .9), scene, 3.7, .2, 2.1); corridor.castShadow = false; corridor.visible = false;
    const ambulanceRing = ring(2.2, 2.45, glow('#e8b72f', .6, .8), PATIENT.x, .2, PATIENT.z); const riskRing = ring(1.5, 1.9, glow('#df3e39', .9, 1.5), PATIENT.x, .22, PATIENT.z); riskRing.visible = false;

    // Etiquetas.
    const cloud = buildLabel(stage, 'Redes (público)', { bg: '#ede9fe', fg: '#4c1d95', width: 3.6 }); cloud.position.copy(CLOUD);
    const blockedLabel = buildLabel(stage, 'Acceso bloqueado ✗', { bg: '#fee2e2', fg: '#991b1b', width: 4 }); const freeLabel = buildLabel(stage, 'Paso libre ✓', { bg: '#dcfce7', fg: '#166534', width: 3 });
    const privacyLabel = buildLabel(stage, 'Privacidad protegida ✓', { bg: '#dcfce7', fg: '#166534', width: 4.6 }); const stopLabel = buildLabel(stage, 'Sin grabar', { bg: '#dbeafe', fg: '#1e3a8a', width: 2.8 });
    const delay = buildLabel(stage, 'La atención se retrasa ✗', { bg: '#fee2e2', fg: '#991b1b', width: 4.8 }); const crowdLabel = buildLabel(stage, 'Grupo grabando', { bg: '#fff3cd', fg: '#7a5a00', width: 3.4 });
    const rec = buildLabel(stage, '● REC', { bg: '#fee2e2', fg: '#b91c1c', width: 2 }); const shared = buildLabel(stage, 'Compartido', { bg: '#ede9fe', fg: '#4c1d95', width: 2.8 });
    const clock = new T.Group(); clock.visible = false; scene.add(clock);
    const face = cyl(.62, .08, mat('#fffaf0'), clock, 0, 0, 0, 36); face.rotation.x = Math.PI / 2; face.castShadow = false;
    const needlePivot = new T.Group(); clock.add(needlePivot); box(.06, .46, .05, mat('#df3e39'), needlePivot, 0, .22, .07);

    const facePatient = (actor, pos) => { actor.group.rotation.set(0, Math.atan2(PATIENT.x - pos.x, PATIENT.z - pos.z), 0); };
    const straight = (a, b) => new T.CatmullRomCurve3([a, a.clone().lerp(b, .5), b]);
    const lerp3 = (from, to, t) => new T.Vector3(from[0], .1, from[1]).lerp(new T.Vector3(to[0], .1, to[1]), t);
    const setPhone = (person, phone, k) => { person.limbs[3].rotation.x = -1.35 * k; person.elbows[1].rotation.x = -1.1 * k; phone.visible = k > .02; phone.rotation.x = .35; };
    const aboveVos = new T.Vector3(), medicStart = [new T.Vector3(5.6, .1, 3.7), new T.Vector3(5.6, .1, 1.7)];
    const stretcherPath = (s) => new T.Vector3(5.2 + (1.7 - 5.2) * s, 0, 2.7 + (1.5 - 2.7) * s);

    const hideAll = () => { [cloud, blockedLabel, freeLabel, privacyLabel, stopLabel, delay, rec, shared, corridor, riskRing, clock].forEach(item => { item.visible = false; }); crowdLabel.visible = true; };
    let outcome = 'intro', progress = 0, step = -1;
    function emitStep() { const next = outcome === 'intro' ? -1 : STEP_STARTS.reduce((acc, start, index) => progress >= start ? index : acc, 0); if (next !== step) { step = next; if (next >= 0) onStep(next); } }
    function setOutcome(value) { outcome = value; progress = reduced && value !== 'intro' ? 1 : 0; step = -1; hideAll(); }
    function setStep(index) { if (outcome === 'intro') return; progress = STEP_POSES[Math.max(0, Math.min(2, index))]; stage.paused = true; }
    const fly = (label, from, to, t0, t1, lift = .8) => { const t = (progress - t0) / (t1 - t0); label.visible = t > 0 && t < 1.12; if (!label.visible) return; label.position.lerpVectors(from, to, smooth(t)); label.position.y += Math.sin(Math.PI * clamp01(t)) * lift; };

    stage.run(dt => {
        const simTime = stage.simTime;
        if (!stage.paused && outcome !== 'intro') progress = Math.min(1, progress + dt / DURATION);
        const beat = Math.floor(simTime * 1.6) % 2; ambulance.blue.emissiveIntensity = beat ? 2.2 : .3; ambulance.redLight.emissiveIntensity = beat ? .3 : 2.2;
        ambulance.brakeMat.emissiveIntensity = 2.2;
        vos.limbs.forEach(limb => { limb.rotation.x = 0; }); vos.knees.forEach(k => { k.rotation.x = 0; }); vos.elbows.forEach(e => { e.rotation.x = -.08; }); vosPhone.visible = false; vos.group.position.copy(VOS_START); facePatient(vos, VOS_START);
        screens.forEach(panel => { panel.scale.y = .001; panel.position.y = .02; });
        // Posiciones del grupo según el resultado.
        let crowdFrom = CROWD_BLOCK, crowdTo = CROWD_BLOCK, crowdT = 0, phones = 1;
        medics.forEach((m, i) => { m.group.position.copy(medicStart[i]); m.group.rotation.set(0, -Math.PI / 2, 0); m.limbs.forEach(l => { l.rotation.x = 0; }); m.knees.forEach(k => { k.rotation.x = 0; }); m.elbows.forEach(e => { e.rotation.x = -.08; }); });
        stretcher.position.copy(stretcherPath(0)); stretcher.rotation.y = 0;
        if (outcome === 'protected') {
            const back = smooth(progress / .26); walk(vos, straight(VOS_START, VOS_BACK), back, false); if (back >= 1) vos.group.rotation.y = Math.PI * .75;
            const gesture = smooth((progress - .06) / .1); vos.limbs[1].rotation.x = -1.45 * gesture; vos.elbows[0].rotation.x = -.15 * gesture; stopLabel.visible = progress > .08 && progress < .5; stopLabel.position.set(vos.group.position.x, 3.2, vos.group.position.z);
            phones = 1 - smooth((progress - .14) / .1); crowdFrom = CROWD_BLOCK; crowdTo = CROWD_AWAY; crowdT = smooth((progress - .3) / .3);
            corridor.visible = progress > .56; freeLabel.visible = progress > .56 && progress < .8; freeLabel.position.set(3.7, 2.8, 2.1);
            const go = smooth((progress - .6) / .32); stretcher.position.copy(stretcherPath(go)); medics.forEach((m, i) => { const p = stretcher.position; m.group.position.set(p.x + (i ? 1.25 : -1.25), .1, p.z); m.group.rotation.y = -Math.PI / 2; m.limbs[1].rotation.x = m.limbs[3].rotation.x = -.7; const sw = Math.sin(go * 40) * .35 * (go > 0 && go < 1 ? 1 : 0); m.limbs[0].rotation.x = sw; m.limbs[2].rotation.x = -sw; });
            screens.forEach(panel => { const k = smooth((progress - .78) / .14); panel.scale.y = Math.max(.001, k); panel.position.y = .02 + .8 * k; });
            privacyLabel.visible = progress > .86; privacyLabel.position.set(PATIENT.x, 4, PATIENT.z); crowdLabel.visible = progress < .3; ambulanceRing.visible = progress < .6;
        } else if (outcome === 'expose') {
            const near = smooth((progress - .08) / .3); const target = new T.Vector3(1.1, .1, 2.1); walk(vos, straight(VOS_START, target), near, false); if (near >= 1) facePatient(vos, vos.group.position);
            crowdFrom = CROWD_BLOCK; crowdTo = CROWD_PUSH; crowdT = smooth((progress - .34) / .3); phones = 1;
            blockedLabel.visible = progress > .5; blockedLabel.position.set(1.4, 4.6, 2.1); delay.visible = progress > .74; delay.position.set(6.2, 3.4, 2.7);
            riskRing.visible = progress > .3; riskRing.scale.setScalar(1 + Math.sin(simTime * 3) * .07); crowdLabel.visible = false;
            medics.forEach(m => { m.limbs[1].rotation.x = m.limbs[3].rotation.x = -.5; }); stretcher.position.copy(stretcherPath(0));
        } else if (outcome === 'record') {
            setPhone(vos, vosPhone, smooth(progress / .1)); vos.group.position.copy(VOS_START);
            rec.visible = progress > .12; rec.position.set(vos.group.position.x, 3.2, vos.group.position.z); cloud.visible = progress > .5; aboveVos.set(vos.group.position.x, 3.2, vos.group.position.z); fly(shared, aboveVos, CLOUD, .66, .86, 1);
            clock.visible = progress > .3; clock.position.set(vos.group.position.x, 4.4, vos.group.position.z); clock.lookAt(camera.position); needlePivot.rotation.z = -progress * Math.PI * 6;
            blockedLabel.visible = progress > .45; blockedLabel.position.set(6.2, 3.6, 2.7); crowdLabel.visible = progress < .3; phones = 1;
            medics.forEach(m => { m.limbs[1].rotation.x = m.limbs[3].rotation.x = -.5; });
        }
        crowd.forEach((item, i) => {
            const p = lerp3(crowdFrom[i], crowdTo[i], crowdT); const moving = crowdT > 0 && crowdT < 1; item.person.group.position.copy(p);
            if (crowdT > 0) { const dir = new T.Vector3(crowdTo[i][0] - crowdFrom[i][0], 0, crowdTo[i][1] - crowdFrom[i][1]); if (dir.lengthSq() > .01 && crowdT < 1) item.person.group.rotation.set(0, Math.atan2(dir.x, dir.z), 0); else if (outcome === 'expose') facePatient(item.person, p); }
            else facePatient(item.person, p);
            const swing = moving ? Math.sin(crowdT * 50 + i) * .4 : 0; item.person.limbs[0].rotation.x = swing; item.person.limbs[2].rotation.x = -swing; item.person.knees[0].rotation.x = Math.max(0, -swing) * 1.4; item.person.knees[1].rotation.x = Math.max(0, swing) * 1.4;
            item.person.limbs[1].rotation.x = 0; setPhone(item.person, item.phone, phones);
        });
        crowdLabel.position.set(3, 3.6, 2.6);
        ambulance.wheels.forEach(wheel => { wheel.rotation.z -= 0; });
        emitStep();
        if (stage.view === 'pedestrian') { const p = vos.group.position; camera.position.set(p.x + 1.4, 1.9, p.z + 1.8); camera.lookAt(PATIENT.x + .5, .6, PATIENT.z); }
        else if (stage.view === 'driver') { const p = ambulance.group.position; camera.position.set(p.x - 1.3, 1.9, p.z); camera.lookAt(PATIENT.x, .8, PATIENT.z); }
    });
    setOutcome('intro');
    return { setOutcome, setStep, setPaused(value) { stage.paused = value; }, setView: stage.setView, dispose: stage.dispose };
}
