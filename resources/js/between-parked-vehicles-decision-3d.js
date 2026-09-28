import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountBetweenParkedVehiclesDecision(host) {
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#c9e4f0'); scene.fog = new THREE.Fog('#c9e4f0', 48, 120);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 150); camera.position.set(25, 23, 31);
    const renderer = new THREE.WebGLRenderer({ antialias: true }); renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7)); renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap; renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.1;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block'; renderer.domElement.setAttribute('role', 'img'); renderer.domElement.setAttribute('aria-label', 'Escena tridimensional donde dos vehículos altos estacionados bloquean la visión de Luna y ocultan un automóvil en movimiento'); host.appendChild(renderer.domElement);
    const controls = new OrbitControls(camera, renderer.domElement); controls.target.set(2, .5, 0); controls.enableDamping = true; controls.minDistance = 18; controls.maxDistance = 68; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x58734d, 2.45)); const sun = new THREE.DirectionalLight(0xffefd3, 3.2); sun.position.set(-17, 29, 20); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048); Object.assign(sun.shadow.camera, { left: -38, right: 38, top: 28, bottom: -28 }); scene.add(sun);
    const materials = []; const mat = (color, options = {}) => { const value = new THREE.MeshStandardMaterial({ color, roughness: .75, ...options }); materials.push(value); return value; };
    const mesh = (geometry, material, parent, x, y, z) => { const value = new THREE.Mesh(geometry, material); value.position.set(x, y, z); value.castShadow = value.receiveShadow = true; parent.add(value); return value; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    box(92, .2, 46, mat('#789b6c'), scene, 0, -.2, 0); box(86, .14, 13, mat('#475158'), scene, 0, -.02, 0); for (const z of [-7.4, 7.4]) box(86, .24, 3.2, mat('#c9c8be'), scene, 0, .04, z); for (let x = -39; x < 40; x += 4.4) box(2.2, .025, .1, mat('#e7c763'), scene, x, .07, 0);
    const crossingX = 18; for (let z = -5.8; z <= 5.8; z += 1.08) box(3.6, .035, .58, mat('#f4f2e8'), scene, crossingX, .09, z);

    function wheel(parent, x, z, radius = .4) { const tire = mesh(new THREE.CylinderGeometry(radius, radius, .23, 22), mat('#20262b'), parent, x, radius + .06, z); tire.rotation.x = Math.PI / 2; return tire; }
    function van(color, x) {
        const group = new THREE.Group(); scene.add(group); const body = mat(color, { roughness: .36, metalness: .35 });
        box(6.2, 2.35, 2.45, body, group, 0, 1.35, 0); box(1.6, .95, 2.28, mat('#294957'), group, 1.75, 2.1, 0); box(.13, .95, 2.34, body, group, .85, 2.1, 0);
        for (const wx of [-1.85, 1.85]) for (const wz of [-1.25, 1.25]) wheel(group, wx, wz, .44);
        group.position.set(x, 0, 3.9); return group;
    }
    van('#316f91', -3.5); van('#d9d4c7', 5.2);
    function car() { const group = new THREE.Group(); scene.add(group); const body = mat('#bd3d43', { roughness: .3, metalness: .5 }); box(4.5, .75, 1.92, body, group, 0, .75, 0); box(2.4, .72, 1.67, body, group, -.2, 1.45, 0); box(2.08, .44, 1.71, mat('#21475b'), group, -.2, 1.49, 0); const wheels = []; for (const x of [-1.32, 1.32]) for (const z of [-.97, .97]) wheels.push(wheel(group, x, z, .38)); return { group, wheels }; }
    const movingCar = car();
    function person() { const group = new THREE.Group(); scene.add(group); mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), mat('#ef9c35'), group, 0, 1.18, 0); mesh(new THREE.SphereGeometry(.22, 18, 14), mat('#a96f4d'), group, 0, 1.78, 0); const limbs = []; for (const side of [-1, 1]) { const leg = new THREE.Group(); leg.position.set(side * .14, .9, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), mat('#34475b'), leg, 0, -.34, 0); limbs.push(leg); const arm = new THREE.Group(); arm.position.set(side * .28, 1.43, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .45, 4, 10), mat('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm); } return { group, limbs }; }
    const luna = person();

    const gapCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(.7, .1, 7.4), new THREE.Vector3(.7, .1, 5.2), new THREE.Vector3(.7, .1, 1.8)]);
    const safeCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(.7, .1, 7.4), new THREE.Vector3(-1, .1, 9.1), new THREE.Vector3(10, .1, 9.1), new THREE.Vector3(18, .1, 7.4)]);
    const makePath = (curve, color) => { const material = new THREE.MeshStandardMaterial({ color, emissive: color, emissiveIntensity: .75 }); materials.push(material); const object = new THREE.Mesh(new THREE.TubeGeometry(curve, 90, .12, 9), material); object.visible = false; scene.add(object); return object; };
    const gapPath = makePath(gapCurve, '#df3e39'); const safePath = makePath(safeCurve, '#2f9d61');
    const sightMaterial = new THREE.MeshBasicMaterial({ color: '#ed9b2c', transparent: true, opacity: .25, side: THREE.DoubleSide, depthWrite: false }); materials.push(sightMaterial);
    const sight = new THREE.Mesh(new THREE.BufferGeometry().setFromPoints([new THREE.Vector3(.7, .2, 6.4), new THREE.Vector3(-18, .2, 2.4), new THREE.Vector3(19, .2, 2.4)]), sightMaterial); sight.geometry.setIndex([0, 1, 2]); sight.geometry.computeVertexNormals(); scene.add(sight);
    const riskMaterial = new THREE.MeshStandardMaterial({ color: '#df3e39', emissive: '#df3e39', emissiveIntensity: 1.4, side: THREE.DoubleSide }); materials.push(riskMaterial);
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.15, 1.55, 40), riskMaterial); risk.rotation.x = -Math.PI / 2; risk.position.set(.7, .18, 1.8); risk.visible = false; scene.add(risk);
    const soundMaterial = new THREE.MeshBasicMaterial({ color: '#6ba9d0', transparent: true, opacity: .45, side: THREE.DoubleSide }); materials.push(soundMaterial); const soundRings = [];
    for (let index = 0; index < 3; index++) { const ring = new THREE.Mesh(new THREE.RingGeometry(.8 + index * .7, .9 + index * .7, 36), soundMaterial); ring.rotation.x = -Math.PI / 2; ring.visible = false; scene.add(ring); soundRings.push(ring); }

    let outcome = 'intro', progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();
    function reset() { luna.group.position.set(.7, .1, 7.4); luna.group.rotation.set(0, Math.PI, 0); luna.limbs.forEach(limb => limb.rotation.x = 0); movingCar.group.position.set(-34, 0, 2.4); movingCar.group.rotation.set(0, 0, 0); gapPath.visible = safePath.visible = risk.visible = false; sight.visible = true; soundRings.forEach(ring => { ring.visible = true; ring.position.set(-20, .15, 2.4); }); }
    function setOutcome(value) { outcome = value; progress = 0; reset(); gapPath.visible = value === 'advance' || value === 'sound'; safePath.visible = value === 'safe'; risk.visible = value === 'advance' || value === 'sound'; }
    function moveLuna(curve, t, running = false) { const p = curve.getPointAt(Math.min(.999, t)); const next = curve.getPointAt(Math.min(1, t + .01)); luna.group.position.copy(p); luna.group.lookAt(next.x, p.y, next.z); luna.limbs.forEach((limb, index) => { limb.rotation.x = Math.sin(t * (running ? 54 : 36)) * (running ? .53 : .34) * (index % 2 ? -1 : 1); }); }
    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);
    function render(now) {
        if (disposed) return; const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now; const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / (outcome === 'safe' ? 9 : 5.5)); const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'safe') { moveLuna(safeCurve, ease); movingCar.group.position.x = -34 + ease * 48; sight.visible = ease < .72; }
            else if (outcome === 'advance' || outcome === 'sound') { moveLuna(gapCurve, Math.min(1, ease * (outcome === 'sound' ? 1.3 : 1)), outcome === 'sound'); movingCar.group.position.x = -34 + ease * 53; }
            else movingCar.group.position.x = -34 + ((now * .00008) % 1) * 18;
            movingCar.wheels.forEach(wheelObject => { wheelObject.rotation.z -= dt * 6; }); soundRings.forEach((ring, index) => { ring.position.x = movingCar.group.position.x; ring.scale.setScalar(1 + ((now * .001 + index * .28) % 1) * .55); });
            if (risk.visible) { const pulse = 1 + Math.sin(now * .008) * .15; risk.scale.setScalar(pulse); }
            if (view === 'pedestrian') { const p = luna.group.position; camera.position.set(p.x - 1.2, 2.45, p.z + 1.8); camera.lookAt(0, .8, 1.5); }
            else if (view === 'driver') { const p = movingCar.group.position; camera.position.set(p.x + 1.2, 1.75, p.z); camera.lookAt(p.x + 13, .75, p.z); }
            else controls.update(); renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setOutcome('intro'); frame = requestAnimationFrame(render);
    return { setOutcome, setPaused(value) { paused = value; }, setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) { camera.position.set(25, 23, 31); controls.target.set(2, .5, 0); controls.update(); } }, dispose() { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose()); renderer.dispose(); renderer.domElement.remove(); } };
}
