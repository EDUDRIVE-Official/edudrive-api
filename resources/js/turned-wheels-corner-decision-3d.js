import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountTurnedWheelsCornerDecision(host) {
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#c9e4f0'); scene.fog = new THREE.Fog('#c9e4f0', 48, 120);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 150); camera.position.set(25, 23, 31);
    const renderer = new THREE.WebGLRenderer({ antialias: true }); renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7)); renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap; renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.1;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block'; renderer.domElement.setAttribute('role', 'img'); renderer.domElement.setAttribute('aria-label', 'Intersección tridimensional con Luna en la esquina y un automóvil detenido cuyas ruedas delanteras apuntan hacia el cruce'); host.appendChild(renderer.domElement);
    const controls = new OrbitControls(camera, renderer.domElement); controls.target.set(1, .5, 1); controls.enableDamping = true; controls.minDistance = 18; controls.maxDistance = 68; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x58734d, 2.45)); const sun = new THREE.DirectionalLight(0xffefd3, 3.2); sun.position.set(-17, 29, 20); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048); Object.assign(sun.shadow.camera, { left: -38, right: 38, top: 28, bottom: -28 }); scene.add(sun);
    const materials = []; const mat = (color, options = {}) => { const value = new THREE.MeshStandardMaterial({ color, roughness: .72, ...options }); materials.push(value); return value; };
    const mesh = (geometry, material, parent, x, y, z) => { const value = new THREE.Mesh(geometry, material); value.position.set(x, y, z); value.castShadow = value.receiveShadow = true; parent.add(value); return value; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    const grass = mat('#789b6c'); const road = mat('#48535a'); const pavement = mat('#c9c8be'); const stripe = mat('#e7c763');
    box(86, .2, 50, grass, scene, 0, -.2, 0); box(82, .14, 13, road, scene, 0, -.02, 0); box(13, .15, 48, road, scene, 3, -.01, 0);
    for (const z of [-7.4, 7.4]) box(82, .24, 3.2, pavement, scene, 0, .04, z); for (const x of [-4.4, 10.4]) box(3.2, .24, 48, pavement, scene, x, .04, 0);
    for (let x = -38; x < 39; x += 4.4) box(2.2, .025, .1, stripe, scene, x, .07, 0); for (let z = -20; z < 21; z += 4.4) box(.1, .025, 2.2, stripe, scene, 3, .075, z);
    for (let z = -5.8; z <= 5.8; z += 1.08) box(3.6, .035, .58, mat('#f3f1e7'), scene, 9.2, .09, z);
    for (const [x, z, color] of [[-20, -15, '#d8ccb0'], [20, -15, '#c9d8d2'], [-20, 15, '#e0d3bc'], [20, 15, '#d6cabb']]) { box(11, 4.5, 7, mat(color), scene, x, 2.1, z); const roof = mesh(new THREE.CylinderGeometry(0, 7.8, 1.8, 4), mat('#855a49'), scene, x, 5.1, z); roof.rotation.y = Math.PI / 4; }

    function person() { const group = new THREE.Group(); scene.add(group); mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), mat('#ef9c35'), group, 0, 1.18, 0); mesh(new THREE.SphereGeometry(.22, 18, 14), mat('#a96f4d'), group, 0, 1.78, 0); const limbs = []; for (const side of [-1, 1]) { const leg = new THREE.Group(); leg.position.set(side * .14, .9, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), mat('#34475b'), leg, 0, -.34, 0); limbs.push(leg); const arm = new THREE.Group(); arm.position.set(side * .28, 1.43, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .45, 4, 10), mat('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm); } return { group, limbs }; }
    const luna = person();
    const car = new THREE.Group(); scene.add(car); const body = mat('#2e7398', { roughness: .3, metalness: .48 }); box(4.5, .75, 1.95, body, car, 0, .75, 0); box(2.4, .72, 1.7, body, car, -.2, 1.45, 0); box(2.08, .44, 1.71, mat('#21475b'), car, -.2, 1.49, 0);
    const frontWheelPivots = []; const wheels = [];
    for (const x of [-1.32, 1.32]) for (const z of [-.98, .98]) {
        const pivot = new THREE.Group(); pivot.position.set(x, .43, z); car.add(pivot);
        const tireGeometry = new THREE.CylinderGeometry(.38, .38, .22, 22); tireGeometry.rotateX(Math.PI / 2);
        const tire = mesh(tireGeometry, mat('#20262b'), pivot, 0, 0, 0); wheels.push(tire);
        const hubGeometry = new THREE.CylinderGeometry(.17, .17, .24, 16); hubGeometry.rotateX(Math.PI / 2);
        mesh(hubGeometry, mat('#aeb6ba', { metalness: .65 }), tire, 0, 0, 0);
        if (x > 0) { pivot.rotation.y = -.48; frontWheelPivots.push(pivot); }
    }
    for (const z of [-.62, .62]) mesh(new THREE.SphereGeometry(.17, 14, 9), mat('#fff4bd', { emissive: '#fff0a3', emissiveIntensity: 3 }), car, 2.22, .82, z);

    const turnCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-8, .1, 2.7), new THREE.Vector3(-2, .1, 2.7), new THREE.Vector3(2.8, .1, 5), new THREE.Vector3(3, .1, 15)], false, 'catmullrom', .1);
    const crossingCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(9.2, .1, 7.4), new THREE.Vector3(9.2, .1, 0), new THREE.Vector3(9.2, .1, -7.4)]);
    const trajectoryMat = new THREE.MeshStandardMaterial({ color: '#e4c340', emissive: '#e4c340', emissiveIntensity: .85 }); const riskMat = new THREE.MeshStandardMaterial({ color: '#df3e39', emissive: '#df3e39', emissiveIntensity: 1.35, side: THREE.DoubleSide }); materials.push(trajectoryMat, riskMat);
    const trajectory = new THREE.Mesh(new THREE.TubeGeometry(turnCurve, 90, .12, 9), trajectoryMat); scene.add(trajectory);
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.25, 1.65, 40), riskMat); risk.rotation.x = -Math.PI / 2; risk.position.set(3, .18, 6.2); risk.visible = false; scene.add(risk);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();
    function placeCar(t) {
        const clamped = Math.min(.999, t); const p = turnCurve.getPointAt(clamped); const next = turnCurve.getPointAt(Math.min(1, clamped + .01)); const dx = next.x - p.x; const dz = next.z - p.z;
        car.position.copy(p); car.rotation.y = -Math.atan2(dz, dx);
        const straighten = Math.max(0, Math.min(1, (clamped - .48) / .38));
        frontWheelPivots.forEach(pivot => { pivot.rotation.y = -.48 * (1 - straighten); });
        wheels.forEach(wheel => { wheel.rotation.z = -clamped * 42; });
    }
    function moveLuna(t, running = false) { const p = crossingCurve.getPointAt(Math.min(.999, t)); const next = crossingCurve.getPointAt(Math.min(1, t + .01)); luna.group.position.copy(p); luna.group.lookAt(next.x, p.y, next.z); luna.limbs.forEach((limb, index) => { limb.rotation.x = Math.sin(t * (running ? 54 : 36)) * (running ? .5 : .32) * (index % 2 ? -1 : 1); }); }
    function reset() { luna.group.position.set(9.2, .1, 7.4); luna.group.rotation.set(0, Math.PI, 0); luna.limbs.forEach(limb => limb.rotation.x = 0); placeCar(0); risk.visible = false; }
    function setOutcome(value) { outcome = value; progress = 0; reset(); risk.visible = value === 'stationary' || value === 'trust'; }
    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);
    function render(now) {
        if (disposed) return; const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now; const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / 7); const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'confirm') { placeCar(Math.min(1, ease * 1.5)); if (ease > .62) moveLuna((ease - .62) / .38); }
            else if (outcome === 'stationary' || outcome === 'trust') { placeCar(ease); moveLuna(Math.min(1, ease * (outcome === 'trust' ? 1.25 : 1)), outcome === 'trust'); }
            if (risk.visible) { const pulse = 1 + Math.sin(now * .008) * .15; risk.scale.setScalar(pulse); }
            if (view === 'pedestrian') { const p = luna.group.position; camera.position.set(p.x + 1.5, 2.45, p.z + 1.6); camera.lookAt(car.position.x, .9, car.position.z); }
            else if (view === 'wheels') { const front = new THREE.Vector3(1.32, .43, -1.25); car.localToWorld(front); camera.position.set(front.x + 2.3, 1.35, front.z - 2.6); camera.lookAt(front.x, .45, front.z); }
            else controls.update(); renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setOutcome('intro'); frame = requestAnimationFrame(render);
    return { setOutcome, setPaused(value) { paused = value; }, setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) { camera.position.set(25, 23, 31); controls.target.set(1, .5, 1); controls.update(); } }, dispose() { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose()); renderer.dispose(); renderer.domElement.remove(); } };
}
