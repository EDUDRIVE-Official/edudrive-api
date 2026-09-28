import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountDownpourExitDecision(host) {
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#718493'); scene.fog = new THREE.Fog('#718493', 22, 82);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 140); camera.position.set(25, 22, 31);
    const renderer = new THREE.WebGLRenderer({ antialias: true }); renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7)); renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap; renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = .92;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block'; renderer.domElement.setAttribute('role', 'img'); renderer.domElement.setAttribute('aria-label', 'Escena tridimensional de un aguacero frente a un centro educativo, con agua acumulada, personas corriendo hacia un autobús y Luna esperando bajo techo'); host.appendChild(renderer.domElement);
    const controls = new OrbitControls(camera, renderer.domElement); controls.target.set(0, .5, 1); controls.enableDamping = true; controls.minDistance = 18; controls.maxDistance = 68; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xa9c9da, 0x34483e, 1.65)); const daylight = new THREE.DirectionalLight(0xcbd9e1, 1.8); daylight.position.set(-17, 28, 18); daylight.castShadow = true; daylight.shadow.mapSize.set(2048, 2048); Object.assign(daylight.shadow.camera, { left: -38, right: 38, top: 28, bottom: -28 }); scene.add(daylight);
    const materials = []; const mat = (color, options = {}) => { const value = new THREE.MeshStandardMaterial({ color, roughness: .7, ...options }); materials.push(value); return value; };
    const mesh = (geometry, material, parent, x, y, z) => { const value = new THREE.Mesh(geometry, material); value.position.set(x, y, z); value.castShadow = value.receiveShadow = true; parent.add(value); return value; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    box(92, .2, 48, mat('#547657'), scene, 0, -.2, 0); box(88, .14, 13, mat('#3f4d57', { roughness: .4 }), scene, 0, -.02, 0); for (const z of [-7.4, 7.4]) box(88, .24, 3.2, mat('#8e9393'), scene, 0, .04, z); for (let x = -40; x < 41; x += 4.4) box(2.2, .025, .1, mat('#c4a941'), scene, x, .07, 0);

    // Centro educativo con marquesina protectora.
    box(25, 6.5, 8, mat('#d5d8d0'), scene, -19, 3.05, 15); const roof = mesh(new THREE.CylinderGeometry(0, 17, 2.2, 4), mat('#755a50'), scene, -19, 7.1, 15); roof.rotation.y = Math.PI / 4;
    box(11, .3, 5, mat('#164d75', { metalness: .35 }), scene, -10, 4.4, 9.8); for (const x of [-14.7, -5.3]) box(.18, 4.3, .18, mat('#39464d', { metalness: .6 }), scene, x, 2.15, 8.3);

    function wheel(parent, x, z) { const tire = mesh(new THREE.CylinderGeometry(.44, .44, .24, 22), mat('#1e2428'), parent, x, .48, z); tire.rotation.x = Math.PI / 2; }
    const bus = new THREE.Group(); scene.add(bus); const busBody = mat('#e1b22b', { roughness: .4 }); box(8.2, 2.8, 2.65, busBody, bus, 0, 1.65, 0); box(7.8, .95, 2.68, mat('#f0ede0'), bus, 0, 2.75, 0); for (const x of [-2.6, -.9, .8, 2.5]) box(1.15, .76, .06, mat('#4c9fc1'), bus, x, 2.72, -1.36); for (const x of [-2.65, 2.65]) for (const z of [-1.36, 1.36]) wheel(bus, x, z); bus.position.set(21, 0, 3.1); bus.rotation.y = Math.PI;

    function person(shirt, scale = 1) { const group = new THREE.Group(); scene.add(group); group.scale.setScalar(scale); mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), mat(shirt), group, 0, 1.18, 0); mesh(new THREE.SphereGeometry(.22, 18, 14), mat('#a96f4d'), group, 0, 1.78, 0); const limbs = []; for (const side of [-1, 1]) { const leg = new THREE.Group(); leg.position.set(side * .14, .9, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), mat('#34475b'), leg, 0, -.34, 0); limbs.push(leg); const arm = new THREE.Group(); arm.position.set(side * .28, 1.43, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .45, 4, 10), mat('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm); } return { group, limbs }; }
    const luna = person('#ef9c35', .95); const companion = person('#39785d', 1.08); const runners = [person('#a84c57', .9), person('#3f70a1', .9), person('#d2a630', .82)];
    const umbrella = mesh(new THREE.SphereGeometry(1.25, 18, 10, 0, Math.PI * 2, 0, Math.PI / 2), mat('#28588a'), scene, 0, 0, 0); umbrella.scale.y = .42;

    const puddleMat = mat('#2b8eb7', { transparent: true, opacity: .62, metalness: .35, roughness: .15 });
    for (const [x, z, sx, sz] of [[-1, 5.9, 1.5, .6], [9, 2.6, 1.9, .7], [15, 6.4, 1.25, .55]]) { const puddle = mesh(new THREE.CircleGeometry(2.5, 40), puddleMat, scene, x, .13, z); puddle.rotation.x = -Math.PI / 2; puddle.scale.set(sx, sz, 1); }

    const safeCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-10, .1, 8.3), new THREE.Vector3(-4, .1, 8.5), new THREE.Vector3(5, .1, 8.2), new THREE.Vector3(15, .1, 7.5)]);
    const directCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-10, .1, 8.3), new THREE.Vector3(-1, .1, 5.9), new THREE.Vector3(9, .1, 3.2), new THREE.Vector3(18, .1, 4)]);
    const safeMat = new THREE.MeshStandardMaterial({ color: '#32a565', emissive: '#32a565', emissiveIntensity: .9 }); const dangerMat = new THREE.MeshStandardMaterial({ color: '#df3e39', emissive: '#df3e39', emissiveIntensity: 1.35, side: THREE.DoubleSide }); materials.push(safeMat, dangerMat);
    const safePath = new THREE.Mesh(new THREE.TubeGeometry(safeCurve, 100, .12, 9), safeMat); safePath.visible = false; scene.add(safePath); const risk = new THREE.Mesh(new THREE.RingGeometry(1.25, 1.65, 40), dangerMat); risk.rotation.x = -Math.PI / 2; risk.position.set(-1, .18, 5.9); risk.visible = false; scene.add(risk);

    const rainPositions = new Float32Array(360 * 3); for (let i = 0; i < rainPositions.length; i += 3) { rainPositions[i] = Math.random() * 78 - 39; rainPositions[i + 1] = Math.random() * 20 + 1; rainPositions[i + 2] = Math.random() * 36 - 18; }
    const rainGeometry = new THREE.BufferGeometry(); rainGeometry.setAttribute('position', new THREE.BufferAttribute(rainPositions, 3)); const rainMaterial = new THREE.PointsMaterial({ color: '#d8f1ff', size: .13, transparent: true, opacity: .82 }); materials.push(rainMaterial); const drops = new THREE.Points(rainGeometry, rainMaterial); scene.add(drops);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();
    function moveActor(actor, curve, t, running = false, offset = 0) { const adjusted = Math.max(0, Math.min(.999, t - offset)); const p = curve.getPointAt(adjusted); const next = curve.getPointAt(Math.min(1, adjusted + .01)); actor.group.position.copy(p); actor.group.lookAt(next.x, p.y, next.z); actor.limbs.forEach((limb, index) => { limb.rotation.x = Math.sin(adjusted * (running ? 58 : 36)) * (running ? .58 : .32) * (index % 2 ? -1 : 1); }); }
    function reset() { luna.group.position.set(-10, .1, 8.3); luna.group.rotation.set(0, Math.PI / 2, 0); companion.group.position.set(-11.2, .1, 8.4); companion.group.visible = false; umbrella.position.set(-10.6, 2.75, 8.3); umbrella.visible = false; [...luna.limbs, ...companion.limbs].forEach(limb => limb.rotation.x = 0); runners.forEach((actor, index) => { actor.group.position.set(-2 + index * 3, .1, 7.2 + index * .4); }); safePath.visible = risk.visible = false; }
    function setOutcome(value) { outcome = value; progress = 0; reset(); safePath.visible = value === 'reevaluate'; risk.visible = value === 'routine' || value === 'run'; companion.group.visible = value === 'reevaluate'; umbrella.visible = value === 'reevaluate'; }
    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);
    function render(now) {
        if (disposed) return; const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now; const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / (outcome === 'run' ? 4.2 : 8)); const ease = progress * progress * (3 - 2 * progress);
            runners.forEach((actor, index) => moveActor(actor, directCurve, ((now * .00022 + index * .28) % 1), true));
            if (outcome === 'reevaluate') { if (ease > .45) { moveActor(luna, safeCurve, (ease - .45) / .55); moveActor(companion, safeCurve, Math.max(0, (ease - .49) / .51)); umbrella.position.copy(luna.group.position).add(new THREE.Vector3(0, 2.65, 0)); } }
            else if (outcome === 'routine' || outcome === 'run') { moveActor(luna, directCurve, ease, outcome === 'run'); if (outcome === 'run' && ease > .55) luna.group.rotation.z = Math.sin((ease - .55) * Math.PI * 2) * .22; }
            if (!paused) { const positions = drops.geometry.attributes.position.array; for (let i = 1; i < positions.length; i += 3) { positions[i] -= dt * 19; if (positions[i] < .2) positions[i] = 21; } drops.geometry.attributes.position.needsUpdate = true; }
            if (risk.visible) { const pulse = 1 + Math.sin(now * .009) * .15; risk.scale.setScalar(pulse); }
            if (view === 'pedestrian') { const p = luna.group.position; camera.position.set(p.x - 1.2, 2.45, p.z + 1.6); camera.lookAt(12, .8, 4); }
            else if (view === 'bus') { camera.position.set(16.8, 2.1, 3.1); camera.lookAt(luna.group.position.x, 1, luna.group.position.z); }
            else controls.update(); renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setOutcome('intro'); frame = requestAnimationFrame(render);
    return { setOutcome, setPaused(value) { paused = value; }, setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) { camera.position.set(25, 22, 31); controls.target.set(0, .5, 1); controls.update(); } }, dispose() { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose()); renderer.dispose(); renderer.domElement.remove(); } };
}
