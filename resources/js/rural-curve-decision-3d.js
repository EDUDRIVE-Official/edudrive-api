import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountRuralCurveDecision(host) {
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#c9e1ee'); scene.fog = new THREE.Fog('#c9e1ee', 50, 125);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 160); camera.position.set(27, 27, 34);
    const renderer = new THREE.WebGLRenderer({ antialias: true }); renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7)); renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap; renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.08;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block'; renderer.domElement.setAttribute('role', 'img'); renderer.domElement.setAttribute('aria-label', 'Camino rural tridimensional sin acera con una curva cerrada, vegetación que bloquea la vista, Luna fuera de la calzada y un vehículo oculto que se aproxima'); host.appendChild(renderer.domElement);
    const controls = new OrbitControls(camera, renderer.domElement); controls.target.set(0, .5, 0); controls.enableDamping = true; controls.minDistance = 18; controls.maxDistance = 72; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x42603b, 2.35)); const sun = new THREE.DirectionalLight(0xffefcf, 3.1); sun.position.set(-20, 32, 18); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048); Object.assign(sun.shadow.camera, { left: -45, right: 45, top: 32, bottom: -32 }); scene.add(sun);
    const materials = []; const mat = (color, options = {}) => { const value = new THREE.MeshStandardMaterial({ color, roughness: .75, ...options }); materials.push(value); return value; };
    const mesh = (geometry, material, parent, x, y, z) => { const value = new THREE.Mesh(geometry, material); value.position.set(x, y, z); value.castShadow = value.receiveShadow = true; parent.add(value); return value; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    box(100, .25, 62, mat('#6f9b5c'), scene, 0, -.24, 0);

    const roadCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-40, 0, -7), new THREE.Vector3(-23, 0, -6.5), new THREE.Vector3(-10, 0, -4), new THREE.Vector3(1, 0, 2.5), new THREE.Vector3(13, 0, 8), new THREE.Vector3(40, 0, 8.5)], false, 'catmullrom', .25);
    const roadMat = mat('#4a5559', { roughness: .58 }); const edgeMat = mat('#d7d1b5'); const centerMat = mat('#e6c64f');
    const segments = 48;
    for (let index = 0; index < segments; index++) {
        const a = roadCurve.getPoint(index / segments); const b = roadCurve.getPoint((index + 1) / segments); const dx = b.x - a.x; const dz = b.z - a.z; const length = Math.hypot(dx, dz) + .25; const yaw = -Math.atan2(dz, dx);
        const roadPart = box(length, .14, 9, roadMat, scene, (a.x + b.x) / 2, -.02, (a.z + b.z) / 2); roadPart.rotation.y = yaw;
        for (const side of [-1, 1]) { const edge = box(length, .025, .12, edgeMat, scene, (a.x + b.x) / 2, .075, (a.z + b.z) / 2); edge.rotation.y = yaw; const normalX = -dz / Math.max(.01, length); const normalZ = dx / Math.max(.01, length); edge.position.x += normalX * 4.25 * side; edge.position.z += normalZ * 4.25 * side; }
        if (index % 3 === 0) { const mark = box(length * .72, .03, .12, centerMat, scene, (a.x + b.x) / 2, .08, (a.z + b.z) / 2); mark.rotation.y = yaw; }
    }

    function tree(x, z, scale = 1) { mesh(new THREE.CylinderGeometry(.16 * scale, .25 * scale, 3.7 * scale, 10), mat('#594737'), scene, x, 1.8 * scale, z); const crown = mesh(new THREE.SphereGeometry(1.5 * scale, 14, 11), mat(scale > 1.1 ? '#2f653e' : '#39784a'), scene, x, 4.2 * scale, z); crown.scale.y = 1.2; }
    for (const [x, z, scale] of [[-30, -14, 1.2], [-23, -13, 1], [-15, -12, .9], [-3, 9, 1.3], [1, 11, 1.45], [5, 12, 1.2], [9, 14, 1.35], [14, 16, 1.1], [20, 16, 1.2], [29, 15, 1]]) tree(x, z, scale);
    for (const [x, z] of [[-8, 8], [-5, 10], [3, 9], [7, 11], [12, 13]]) { const bush = mesh(new THREE.SphereGeometry(1.3, 14, 10), mat('#477d3e'), scene, x, 1, z); bush.scale.set(1.4, .85, 1); }

    function person() { const group = new THREE.Group(); scene.add(group); mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), mat('#ef9135'), group, 0, 1.18, 0); mesh(new THREE.SphereGeometry(.22, 18, 14), mat('#a96f4d'), group, 0, 1.78, 0); const limbs = []; for (const side of [-1, 1]) { const leg = new THREE.Group(); leg.position.set(side * .14, .9, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), mat('#34475b'), leg, 0, -.34, 0); limbs.push(leg); const arm = new THREE.Group(); arm.position.set(side * .28, 1.43, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .45, 4, 10), mat('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm); } return { group, limbs }; }
    const luna = person();
    const car = new THREE.Group(); scene.add(car); const body = mat('#be3d49', { roughness: .3, metalness: .45 }); box(4.5, .75, 1.95, body, car, 0, .75, 0); box(2.35, .74, 1.7, body, car, -.15, 1.46, 0); box(2.02, .45, 1.72, mat('#19374a'), car, -.15, 1.5, 0); const wheels = []; for (const x of [-1.34, 1.34]) for (const z of [-.98, .98]) { const tire = mesh(new THREE.CylinderGeometry(.38, .38, .22, 20), mat('#151a1e'), car, x, .42, z); tire.rotation.x = Math.PI / 2; wheels.push(tire); } for (const z of [-.62, .62]) mesh(new THREE.SphereGeometry(.17, 14, 9), mat('#fff4bd', { emissive: '#fff0a3', emissiveIntensity: 4 }), car, 2.22, .82, z);

    const safeCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-18, .1, -12), new THREE.Vector3(-13, .1, -11.4), new THREE.Vector3(-8, .1, -9.2)]);
    const peekCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-18, .1, -12), new THREE.Vector3(-13, .1, -8.5), new THREE.Vector3(-8, .1, -4.2)]);
    const safeMat = new THREE.MeshStandardMaterial({ color: '#35a868', emissive: '#35a868', emissiveIntensity: 1 }); const riskMat = new THREE.MeshStandardMaterial({ color: '#e0443e', emissive: '#e0443e', emissiveIntensity: 1.4, side: THREE.DoubleSide }); materials.push(safeMat, riskMat);
    const safePath = new THREE.Mesh(new THREE.TubeGeometry(safeCurve, 60, .12, 9), safeMat); safePath.visible = false; scene.add(safePath);
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.15, 1.55, 40), riskMat); risk.rotation.x = -Math.PI / 2; risk.position.set(-8, .18, -4.2); risk.visible = false; scene.add(risk);
    const occlusionMat = new THREE.MeshBasicMaterial({ color: '#e5ba3f', transparent: true, opacity: .22, side: THREE.DoubleSide, depthWrite: false }); materials.push(occlusionMat);
    const occlusion = new THREE.Mesh(new THREE.CircleGeometry(8, 40, 0, Math.PI * .52), occlusionMat); occlusion.rotation.x = -Math.PI / 2; occlusion.rotation.z = -.35; occlusion.position.set(-8, .16, -4.2); scene.add(occlusion);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();
    function placeCar(t) { const clamped = Math.max(0, Math.min(.999, t)); const p = roadCurve.getPointAt(clamped); const next = roadCurve.getPointAt(Math.max(0, clamped - .012)); const dx = next.x - p.x; const dz = next.z - p.z; car.position.set(p.x, .08, p.z); car.rotation.y = -Math.atan2(dz, dx); wheels.forEach(wheel => { wheel.rotation.z -= .16; }); }
    function moveLuna(curve, t) { const p = curve.getPointAt(Math.min(.999, t)); const next = curve.getPointAt(Math.min(1, t + .01)); luna.group.position.copy(p); luna.group.lookAt(next.x, p.y, next.z); luna.limbs.forEach((limb, index) => { limb.rotation.x = Math.sin(t * 38) * .34 * (index % 2 ? -1 : 1); }); }
    function reset() { luna.group.position.set(-18, .1, -12); luna.group.rotation.set(0, Math.PI / 2, 0); luna.limbs.forEach(limb => limb.rotation.x = 0); placeCar(.94); safePath.visible = risk.visible = false; occlusion.visible = true; }
    function setOutcome(value) { outcome = value; progress = 0; reset(); safePath.visible = value === 'protected'; risk.visible = value === 'peek' || value === 'silence'; }
    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);
    function render(now) {
        if (disposed) return; const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now; const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / 7); const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'protected') { moveLuna(safeCurve, ease); placeCar(.94 - ease * .7); occlusion.visible = ease < .72; }
            else if (outcome === 'peek') { moveLuna(peekCurve, ease); placeCar(.94 - ease * .82); }
            else if (outcome === 'silence') { moveLuna(peekCurve, Math.min(1, ease * 1.35)); placeCar(.94 - ease * .88); }
            else placeCar(.94 - ((now * .00005) % 1) * .18);
            if (risk.visible) { const pulse = 1 + Math.sin(now * .008) * .15; risk.scale.setScalar(pulse); }
            if (view === 'pedestrian') { const p = luna.group.position; camera.position.set(p.x + .8, 2.45, p.z - 1.8); camera.lookAt(1, .8, 5); }
            else if (view === 'driver') { const p = car.position; const direction = new THREE.Vector3(1, 0, 0).applyEuler(car.rotation); camera.position.set(p.x, 1.75, p.z).addScaledVector(direction, 1.1); camera.lookAt(p.x + direction.x * 13, .75, p.z + direction.z * 13); }
            else controls.update(); renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setOutcome('intro'); frame = requestAnimationFrame(render);
    return { setOutcome, setPaused(value) { paused = value; }, setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) { camera.position.set(27, 27, 34); controls.target.set(0, .5, 0); controls.update(); } }, dispose() { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose()); renderer.dispose(); renderer.domElement.remove(); } };
}
