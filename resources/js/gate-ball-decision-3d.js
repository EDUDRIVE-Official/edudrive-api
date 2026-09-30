import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountGateBallDecision(host) {
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#c8e4ef'); scene.fog = new THREE.Fog('#c8e4ef', 48, 120);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 150); camera.position.set(25, 21, 31);
    const renderer = new THREE.WebGLRenderer({ antialias: true }); renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7)); renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap; renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.08;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block'; renderer.domElement.setAttribute('role', 'img'); renderer.domElement.setAttribute('aria-label', 'Escena tridimensional con una pelota en la acera frente a un portón abierto que oculta a un niño próximo a salir'); host.appendChild(renderer.domElement);
    const controls = new OrbitControls(camera, renderer.domElement); controls.target.set(2, .7, 4); controls.enableDamping = true; controls.minDistance = 17; controls.maxDistance = 65; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x58734d, 2.4)); const sun = new THREE.DirectionalLight(0xffefcf, 3.1); sun.position.set(-18, 29, 18); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048); Object.assign(sun.shadow.camera, { left: -38, right: 38, top: 27, bottom: -27 }); scene.add(sun);

    const materials = []; const mat = (color, options = {}) => { const value = new THREE.MeshStandardMaterial({ color, roughness: .75, ...options }); materials.push(value); return value; };
    const mesh = (geometry, material, parent, x, y, z) => { const value = new THREE.Mesh(geometry, material); value.position.set(x, y, z); value.castShadow = value.receiveShadow = true; parent.add(value); return value; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    box(96, .18, 50, mat('#77a76a'), scene, 0, -.18, 0); box(90, .14, 13, mat('#46525a'), scene, 0, -.02, 0);
    for (const z of [-7.4, 7.4]) box(90, .24, 3.2, mat('#c9c8c0'), scene, 0, .04, z);
    for (let x = -42; x <= 42; x += 4.4) box(2.25, .025, .1, mat('#e2c158'), scene, x, .07, 0);

    // Fachada, muro y portón abierto. El muro oculta inicialmente a quien está detrás.
    const wall = mat('#e0d2b7'); const gateMat = mat('#365467', { metalness: .4 });
    box(18, 5, 7, wall, scene, 17, 2.35, 14.2);
    const roof = mesh(new THREE.CylinderGeometry(0, 12.8, 2.1, 4), mat('#9b6047'), scene, 17, 5.9, 14.2); roof.rotation.y = Math.PI / 4;
    box(22, 2.7, .55, wall, scene, -11, 1.3, 11.2); box(12, 2.7, .55, wall, scene, 16, 1.3, 11.2);
    const leftGate = new THREE.Group(); leftGate.position.set(0, 0, 10.9); scene.add(leftGate); box(.22, 2.5, 4.7, gateMat, leftGate, 0, 1.25, 2.15); leftGate.rotation.y = .92;
    const rightGate = new THREE.Group(); rightGate.position.set(5.2, 0, 10.9); scene.add(rightGate); box(.22, 2.5, 4.7, gateMat, rightGate, 0, 1.25, 2.15); rightGate.rotation.y = -.92;
    for (const x of [-2.7, 8]) box(.55, 3.2, .75, mat('#d1c09f'), scene, x, 1.55, 11.1);

    function tree(x, z) { mesh(new THREE.CylinderGeometry(.16, .23, 3.5, 10), mat('#5a4434'), scene, x, 1.7, z); const crown = mesh(new THREE.SphereGeometry(1.45, 14, 11), mat('#39774d'), scene, x, 4, z); crown.scale.y = 1.18; }
    tree(-15, 15); tree(28, 10.5); tree(-25, -12);

    function person(shirt, pants, scale = 1) {
        const group = new THREE.Group(); scene.add(group); group.scale.setScalar(scale);
        mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), mat(shirt), group, 0, 1.18, 0); mesh(new THREE.SphereGeometry(.22, 18, 14), mat('#a96f4d'), group, 0, 1.78, 0);
        const limbs = []; for (const side of [-1, 1]) { const leg = new THREE.Group(); leg.position.set(side * .14, .9, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), mat(pants), leg, 0, -.34, 0); limbs.push(leg); const arm = new THREE.Group(); arm.position.set(side * .28, 1.43, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .45, 4, 10), mat('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm); }
        return { group, limbs };
    }
    const luna = person('#ef9135', '#34475b', .95); const child = person('#4f7ed5', '#394759', .72);
    const ball = mesh(new THREE.SphereGeometry(.42, 24, 16), mat('#e33d3c', { roughness: .4 }), scene, 2.3, .48, 8.2);
    const seam = mesh(new THREE.TorusGeometry(.42, .028, 8, 28), mat('#fff0d6'), ball, 0, 0, 0); seam.rotation.x = Math.PI / 2;

    const car = new THREE.Group(); scene.add(car); const body = mat('#2b7195', { roughness: .3, metalness: .48 });
    box(4.5, .75, 1.92, body, car, 0, .75, 0); box(2.4, .72, 1.67, body, car, -.25, 1.45, 0); box(2.08, .44, 1.71, mat('#21475b'), car, -.25, 1.49, 0);
    const wheels = []; for (const x of [-1.32, 1.32]) for (const z of [-.97, .97]) { const wheel = mesh(new THREE.CylinderGeometry(.38, .38, .22, 22), mat('#1c2226'), car, x, .43, z); wheel.rotation.x = Math.PI / 2; wheels.push(wheel); }

    const clueMat = new THREE.MeshStandardMaterial({ color: '#e8b72f', emissive: '#e8b72f', emissiveIntensity: 1, transparent: true, opacity: .6, side: THREE.DoubleSide });
    const riskMat = new THREE.MeshStandardMaterial({ color: '#df3e39', emissive: '#df3e39', emissiveIntensity: 1.5, side: THREE.DoubleSide }); materials.push(clueMat, riskMat);
    const clue = new THREE.Mesh(new THREE.RingGeometry(1.15, 1.52, 40), clueMat); clue.rotation.x = -Math.PI / 2; clue.position.set(2.3, .16, 8.2); scene.add(clue);
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.3, 1.7, 40), riskMat); risk.rotation.x = -Math.PI / 2; risk.position.set(2.3, .18, 4.5); risk.visible = false; scene.add(risk);

    const childCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(2.3, .1, 13.2), new THREE.Vector3(2.3, .1, 10.7), new THREE.Vector3(2.3, .1, 8.7)]);
    const lunaRoadCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-9, .1, 7.4), new THREE.Vector3(-3, .1, 7.4), new THREE.Vector3(2.3, .1, 4.5)]);
    let outcome = 'intro', progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();
    function reset() { luna.group.position.set(-9, .1, 7.4); luna.group.rotation.set(0, Math.PI / 2, 0); child.group.position.set(2.3, .1, 13.2); child.group.rotation.set(0, Math.PI, 0); [...luna.limbs, ...child.limbs].forEach(limb => limb.rotation.x = 0); ball.position.set(2.3, .48, 8.2); ball.rotation.set(0, 0, 0); car.position.set(-31, 0, 2.7); risk.visible = false; clue.visible = true; }
    function setOutcome(value) { outcome = value; progress = 0; reset(); risk.visible = value === 'ignore' || value === 'move'; }
    function moveActor(actor, curve, t, running = false) { const p = curve.getPointAt(Math.min(.999, t)); const next = curve.getPointAt(Math.min(1, t + .01)); actor.group.position.copy(p); actor.group.lookAt(next.x, p.y, next.z); actor.limbs.forEach((limb, index) => { limb.rotation.x = Math.sin(t * (running ? 58 : 38)) * (running ? .58 : .34) * (index % 2 ? -1 : 1); }); }
    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);
    function render(now) {
        if (disposed) return; const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now; const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / 6); const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'anticipate') { moveActor(child, childCurve, ease, true); ball.position.z = 8.2 - Math.min(1, ease * 1.5) * 1.3; ball.rotation.x = ease * 6; car.position.x = -31 + ease * 24; }
            else if (outcome === 'ignore') { moveActor(child, childCurve, Math.min(1, ease * 1.45), true); ball.position.z = 8.2 - ease * 4.2; ball.rotation.x = ease * 15; car.position.x = -31 + ease * 45; }
            else if (outcome === 'move') { moveActor(luna, lunaRoadCurve, ease, true); moveActor(child, childCurve, Math.max(0, (ease - .18) / .82), true); car.position.x = -31 + ease * 48; }
            else car.position.x = -31 + ((now * .00007) % 1) * 12;
            wheels.forEach(wheel => { wheel.rotation.z -= dt * 5; });
            const pulse = 1 + Math.sin(now * .008) * .13; (risk.visible ? risk : clue).scale.setScalar(pulse);
            if (view === 'pedestrian') { const p = luna.group.position; camera.position.set(p.x + .2, 2.4, p.z + 1.9); camera.lookAt(2.3, 1, 10.5); }
            else if (view === 'gate') { camera.position.set(2.3, 2.2, 12.8); camera.lookAt(2.3, .8, 6); }
            else controls.update(); renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setOutcome('intro'); frame = requestAnimationFrame(render);
    return { setOutcome, setPaused(value) { paused = value; }, setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) { camera.position.set(25, 21, 31); controls.target.set(2, .7, 4); controls.update(); } }, dispose() { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose()); renderer.dispose(); renderer.domElement.remove(); } };
}
