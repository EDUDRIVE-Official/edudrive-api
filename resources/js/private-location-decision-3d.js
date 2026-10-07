import * as THREE from 'three';
import { createStage, buildStreet, buildPerson, buildPhone, buildAmbulance, buildLabel, walk, clamp01, smooth, easeOut, LANE_A } from './incident-scene-kit';

// Escena "Ubicación privada": una niña necesita pedir ayuda desde un lugar público (parada de bus frente a la escuela).
// Resultados: 'protected' (referencia necesaria al servicio, con apoyo adulto), 'expose' (publicar ubicación y fotos), 'record' (grabar y compartir).
const DURATION = 9; // segundos por resultado
const STEP_STARTS = [0, .34, .68]; // progreso en que inicia cada paso de texto
const STEP_POSES = [.01, .5, 1]; // progreso mostrado al saltar a un paso
const GIRL = new THREE.Vector3(5.2, .1, 8.2);
const ADULT_START = new THREE.Vector3(-5, .1, 7.4), ADULT_STAND = new THREE.Vector3(3.5, .1, 8.1);
const CENTRAL = new THREE.Vector3(11, 5.8, -3); // «Servicio de ayuda»
const CLOUD = new THREE.Vector3(14, 5.8, 3.5); // «Redes (público)»

export function mountPrivateLocationDecision(host, onStep = () => {}) {
    const stage = createStage(host, { label: 'Escena tridimensional: una niña con un teléfono en una parada de bus frente a una escuela necesita pedir ayuda. Hay un servicio de ayuda, un adulto de confianza y redes sociales públicas.', overview: { position: [9, 22, 34], target: [4, 0, 3] } });
    const { camera, reduced, mat, glow, cyl, box, ring, scene, mesh, THREE: T } = stage;
    buildStreet(stage);

    // Parada de bus con techo, banca y poste con señal; la escuela queda al frente.
    const metal = mat('#4c5560', { metalness: .6, roughness: .4 }), roofMat = new T.MeshStandardMaterial({ color: '#cfe8f5', transparent: true, opacity: .55, roughness: .2 }); stage.track(roofMat);
    for (const [x, z] of [[2.2, 7.1], [8.2, 7.1], [2.2, 9], [8.2, 9]]) cyl(.07, 2.6, metal, scene, x, 1.3, z, 10);
    box(6.4, .1, 2.2, roofMat, scene, 5.2, 2.65, 8.05); box(.1, 2.2, 2.0, roofMat, scene, 8.2, 1.4, 8.05);
    const wood = mat('#9a6b43'); box(2.4, .08, .55, wood, scene, 6.2, .55, 8.7); box(2.4, .5, .08, wood, scene, 6.2, .85, 8.95); for (const x of [5.2, 7.2]) box(.1, .5, .5, metal, scene, x, .28, 8.7);
    cyl(.05, 3, metal, scene, 9.4, 1.5, 7.1, 10); box(.7, .7, .06, mat('#1d5fb8'), scene, 9.4, 3.1, 7.1);
    const stopLabel = buildLabel(stage, 'Parada de bus', { bg: '#dbeafe', fg: '#1e3a8a', width: 3 }); stopLabel.position.set(10.6, 4.3, 7.1); stopLabel.visible = true;
    const school = buildLabel(stage, 'Escuela', { bg: '#fef3c7', fg: '#92400e', width: 2.8 }); school.position.set(2, 8.6, -14); school.visible = true;

    const girl = buildPerson(stage, { shirt: '#e86a9a', pants: '#3a4a7a', skin: '#c9966f', hair: '#2a1a12', scale: .74 }); girl.group.position.copy(GIRL); girl.group.rotation.y = Math.PI;
    const phone = buildPhone(stage, girl);
    const need = buildLabel(stage, 'Necesita ayuda', { bg: '#fff3cd', fg: '#7a5a00', width: 3.2 }); need.position.set(GIRL.x, 3.6, GIRL.z); need.visible = true;
    const adult = buildPerson(stage, { shirt: '#2f8f8a', pants: '#3b3b46', skin: '#a97550', hair: '#1c1c1c', scale: 1.02 }); adult.group.position.copy(ADULT_START); adult.group.visible = false;
    const adultLabel = buildLabel(stage, 'Adulto de confianza', { bg: '#ccfbf1', fg: '#115e59', width: 3.8 });
    const strangers = [[11.5, 9.6], [13.5, 8.2], [-0.5, 9.5]].map(([x, z], index) => { const person = buildPerson(stage, { shirt: ['#5a6b8c', '#8c5a5a', '#6b8c5a'][index], pants: '#33363d', skin: ['#d9a982', '#b98260', '#c28d68'][index], hair: ['#2a2a2a', '#4a2d18', '#1c1c1c'][index], scale: .98 }); person.group.position.set(x, .1, z); person.group.rotation.y = Math.PI + (index - 1) * .3; person.group.visible = false; const question = buildLabel(stage, 'Ver', { bg: '#ede9fe', fg: '#4c1d95', width: 1.4 }); return { person, question, x, z }; });

    const ambulance = buildAmbulance(stage); ambulance.group.rotation.y = Math.PI; ambulance.group.position.set(52, 0, LANE_A);
    const central = buildLabel(stage, 'Servicio de ayuda', { bg: '#dbeafe', fg: '#1e3a8a', width: 3.6 }); central.position.copy(CENTRAL); central.visible = true;
    const cloud = buildLabel(stage, 'Redes (público)', { bg: '#ede9fe', fg: '#4c1d95', width: 3.6 }); cloud.position.copy(CLOUD);
    const asks = [buildLabel(stage, '¿Dónde estás?', { bg: '#dbeafe', fg: '#1e3a8a', width: 3.2 }), buildLabel(stage, '¿Estás acompañada?', { bg: '#dbeafe', fg: '#1e3a8a', width: 3.8 })];
    const replies = [buildLabel(stage, 'Frente a la escuela', { bg: '#dcfce7', fg: '#166534', width: 3.8 }), buildLabel(stage, 'Con un adulto ✓', { bg: '#dcfce7', fg: '#166534', width: 3.4 })];
    const onlyNeeded = buildLabel(stage, 'Solo la referencia necesaria', { bg: '#dcfce7', fg: '#166534', width: 5 });
    const posts = [buildLabel(stage, 'Mi ubicación', { bg: '#fee2e2', fg: '#991b1b', width: 3.2 }), buildLabel(stage, 'Mis fotos', { bg: '#fee2e2', fg: '#991b1b', width: 2.6 })];
    const exposed = buildLabel(stage, 'Privacidad ✗', { bg: '#fee2e2', fg: '#991b1b', width: 3 });
    const rec = buildLabel(stage, '● REC', { bg: '#fee2e2', fg: '#b91c1c', width: 2 }); const shared = buildLabel(stage, 'Compartido', { bg: '#ede9fe', fg: '#4c1d95', width: 2.8 });
    const safeRing = ring(1.2, 1.55, glow('#2fa866', .75), GIRL.x, .2, GIRL.z); safeRing.visible = false;
    const riskRing = ring(1.2, 1.55, glow('#df3e39', .9, 1.5), GIRL.x, .2, GIRL.z); riskRing.visible = false;
    const clock = new T.Group(); clock.visible = false; scene.add(clock);
    const face = cyl(.62, .08, mat('#fffaf0'), clock, 0, 0, 0, 36); face.rotation.x = Math.PI / 2; face.castShadow = false;
    const needlePivot = new T.Group(); clock.add(needlePivot); box(.06, .46, .05, mat('#df3e39'), needlePivot, 0, .22, .07);
    const aboveGirl = new T.Vector3(GIRL.x, 3.2, GIRL.z), adultCurve = new T.CatmullRomCurve3([ADULT_START, new T.Vector3(-1, .1, 7.5), ADULT_STAND]);

    const hideAll = () => { [...asks, ...replies, ...posts, onlyNeeded, exposed, rec, shared, cloud, adultLabel, safeRing, riskRing, clock].forEach(item => { item.visible = false; }); strangers.forEach(item => { item.question.visible = false; item.person.group.visible = false; }); adult.group.visible = false; need.visible = true; };
    let outcome = 'intro', progress = 0, step = -1;
    function emitStep() { const next = outcome === 'intro' ? -1 : STEP_STARTS.reduce((acc, start, index) => progress >= start ? index : acc, 0); if (next !== step) { step = next; if (next >= 0) onStep(next); } }
    function setOutcome(value) { outcome = value; progress = reduced && value !== 'intro' ? 1 : 0; step = -1; hideAll(); }
    function setStep(index) { if (outcome === 'intro') return; progress = STEP_POSES[Math.max(0, Math.min(2, index))]; stage.paused = true; }
    const fly = (label, from, to, t0, t1, lift = .8) => { const t = (progress - t0) / (t1 - t0); label.visible = t > 0 && t < 1.12; if (!label.visible) return; label.position.lerpVectors(from, to, smooth(t)); label.position.y += Math.sin(Math.PI * clamp01(t)) * lift; };

    stage.run(dt => {
        const simTime = stage.simTime;
        if (!stage.paused && outcome !== 'intro') progress = Math.min(1, progress + dt / DURATION);
        phone.visible = false; girl.limbs[3].rotation.x = 0; girl.elbows[1].rotation.x = -.08; girl.group.rotation.y = Math.PI; need.position.y = 3.6 + Math.sin(simTime * 2) * .08;
        const ambulanceActive = outcome === 'protected' && progress > .62; const run = easeOut((progress - .62) / .35);
        ambulance.group.position.set(ambulanceActive ? 52 - run * 44.5 : 52, 0, LANE_A);
        const beat = Math.floor(simTime * 1.6) % 2; ambulance.blue.emissiveIntensity = ambulanceActive ? (beat ? 2.2 : .3) : .3; ambulance.redLight.emissiveIntensity = ambulanceActive ? (beat ? .3 : 2.2) : .3;
        if (outcome === 'protected') {
            const call = smooth(progress / .08); girl.limbs[3].rotation.x = -2.5 * call; girl.elbows[1].rotation.x = -.3 * call; phone.visible = call > .02; phone.rotation.x = .2;
            const arrive = smooth(progress / .3); adult.group.visible = true; walk(adult, adultCurve, arrive, false); if (arrive >= 1) adult.group.rotation.y = Math.PI + .4;
            adultLabel.visible = arrive > .5; adultLabel.position.set(adult.group.position.x, 3.2, adult.group.position.z);
            fly(asks[0], CENTRAL, aboveGirl, .34, .46); fly(replies[0], aboveGirl, CENTRAL, .44, .56); fly(asks[1], CENTRAL, aboveGirl, .52, .62); fly(replies[1], aboveGirl, CENTRAL, .6, .7);
            onlyNeeded.visible = progress > .48 && progress < .7; onlyNeeded.position.set(GIRL.x, 5.3, GIRL.z);
            need.visible = progress < .62; safeRing.visible = progress > .62; safeRing.scale.setScalar(1 + Math.sin(simTime * 4) * .06);
        } else if (outcome === 'expose') {
            const typing = smooth(progress / .08); girl.limbs[3].rotation.x = -1.1 * typing; girl.elbows[1].rotation.x = -1.2 * typing; phone.visible = true; phone.rotation.x = .5;
            need.visible = progress < .34; cloud.visible = progress > .2; posts.forEach((label, index) => fly(label, aboveGirl, CLOUD, .34 + index * .06, .56 + index * .06, 1));
            strangers.forEach(item => { item.person.group.visible = progress > .52; item.question.visible = progress > .56; item.question.position.set(item.x, 3, item.z); });
            exposed.visible = progress > .7; exposed.position.set(GIRL.x, 5.1, GIRL.z); riskRing.visible = progress > .6; riskRing.scale.setScalar(1 + Math.sin(simTime * 3) * .08);
        } else if (outcome === 'record') {
            const up = smooth(progress / .08); girl.limbs[3].rotation.x = -1.3 * up; girl.elbows[1].rotation.x = -1.0 * up; phone.visible = true; phone.rotation.x = .4; girl.group.rotation.y = Math.PI + .5;
            need.visible = progress < .34; rec.visible = progress > .1; rec.position.set(GIRL.x, 3.2, GIRL.z); cloud.visible = progress > .5; fly(shared, aboveGirl, CLOUD, .66, .86, 1);
            clock.visible = progress > .3; clock.position.set(GIRL.x, 4.3, GIRL.z); clock.lookAt(camera.position); needlePivot.rotation.z = -progress * Math.PI * 6;
        }
        ambulance.wheels.forEach(wheel => { wheel.rotation.z -= (ambulanceActive && progress < .97 && !stage.paused ? 1 - run * .85 : 0) * dt * 10; });
        emitStep();
        if (stage.view === 'pedestrian') { camera.position.set(GIRL.x + 1.1, 1.5, GIRL.z + 1.3); camera.lookAt(5.5, 1.2, 2); }
        else if (stage.view === 'driver') { const p = ambulance.group.position; camera.position.set(Math.min(p.x - 1.2, 30), 1.7, p.z); camera.lookAt(GIRL.x, 1.2, GIRL.z); }
    });
    setOutcome('intro');
    return { setOutcome, setStep, setPaused(value) { stage.paused = value; }, setView: stage.setView, dispose: stage.dispose };
}
