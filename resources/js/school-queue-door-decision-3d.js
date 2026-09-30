import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountSchoolQueueDoorDecision(host) {
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#c9e4f0'); scene.fog = new THREE.Fog('#c9e4f0', 48, 120);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 150); camera.position.set(25, 22, 31);
    const renderer = new THREE.WebGLRenderer({ antialias: true }); renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7)); renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap; renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.1;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block'; renderer.domElement.setAttribute('role', 'img'); renderer.domElement.setAttribute('aria-label', 'Escena tridimensional frente a una escuela con automóviles detenidos, una persona menor moviéndose en el asiento trasero y una puerta que puede abrirse hacia la vía'); host.appendChild(renderer.domElement);
    const controls = new OrbitControls(camera, renderer.domElement); controls.target.set(1, .6, 1); controls.enableDamping = true; controls.minDistance = 18; controls.maxDistance = 68; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x58734d, 2.45)); const sun = new THREE.DirectionalLight(0xffefd3, 3.2); sun.position.set(-17, 29, 20); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048); Object.assign(sun.shadow.camera, { left: -38, right: 38, top: 28, bottom: -28 }); scene.add(sun);
    const materials = []; const mat = (color, options = {}) => { const value = new THREE.MeshStandardMaterial({ color, roughness: .72, ...options }); materials.push(value); return value; };
    const mesh = (geometry, material, parent, x, y, z) => { const value = new THREE.Mesh(geometry, material); value.position.set(x, y, z); value.castShadow = value.receiveShadow = true; parent.add(value); return value; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    box(94, .2, 48, mat('#789b6c'), scene, 0, -.2, 0); box(88, .14, 13, mat('#48535a'), scene, 0, -.02, 0); for (const z of [-7.4, 7.4]) box(88, .24, 3.2, mat('#c9c8be'), scene, 0, .04, z); for (let x = -40; x < 41; x += 4.4) box(2.2, .025, .1, mat('#e7c763'), scene, x, .07, 0);

    // Centro educativo y entrada principal.
    box(28, 6.5, 8, mat('#eee4ca'), scene, 8, 3.05, 15); const roof = mesh(new THREE.CylinderGeometry(0, 19, 2.3, 4), mat('#a65d43'), scene, 8, 7.15, 15); roof.rotation.y = Math.PI / 4;
    box(3.2, 4, .14, mat('#225b83'), scene, 8, 2, 10.95); for (const x of [-2, 1, 15, 18]) box(2, 1.5, .12, mat('#69b3d2'), scene, x, 3.5, 10.94);
    box(12, .45, 1.2, mat('#174a78'), scene, 8, 6.1, 10.5);

    function wheel(parent, x, z) { const tire = mesh(new THREE.CylinderGeometry(.38, .38, .22, 22), mat('#20262b'), parent, x, .43, z); tire.rotation.x = Math.PI / 2; }
    function car(color, x, z) { const group = new THREE.Group(); scene.add(group); const body = mat(color, { roughness: .32, metalness: .45 }); box(4.5, .75, 1.95, body, group, 0, .75, 0); box(2.4, .72, 1.7, body, group, -.2, 1.45, 0); box(2.08, .45, 1.72, mat('#21475b'), group, -.2, 1.49, 0); for (const wx of [-1.32, 1.32]) for (const wz of [-.98, .98]) wheel(group, wx, wz); group.position.set(x, 0, z); return group; }
    car('#5b7893', -10, 4.1); const targetCar = car('#c4434b', 1.5, 4.1); car('#d8c868', 12, 4.1); car('#4e826b', 22, 4.1);

    // Persona menor visible por la ventana trasera y puerta del lado de la calzada.
    const child = new THREE.Group(); targetCar.add(child); child.position.set(-.8, 1.45, 1.02); mesh(new THREE.SphereGeometry(.22, 16, 12), mat('#a96f4d'), child, 0, .25, 0); mesh(new THREE.CapsuleGeometry(.16, .3, 5, 10), mat('#4b7bd1'), child, 0, -.15, 0);
    const doorPivot = new THREE.Group(); doorPivot.position.set(.55, 1.12, 1.02); targetCar.add(doorPivot); const doorMat = mat('#c4434b', { metalness: .42 }); box(1.7, 1.2, .12, doorMat, doorPivot, -.82, 0, 0); box(1.25, .44, .13, mat('#21475b'), doorPivot, -.68, .28, 0);

    function person() { const group = new THREE.Group(); scene.add(group); mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), mat('#ef9c35'), group, 0, 1.18, 0); mesh(new THREE.SphereGeometry(.22, 18, 14), mat('#a96f4d'), group, 0, 1.78, 0); const limbs = []; for (const side of [-1, 1]) { const leg = new THREE.Group(); leg.position.set(side * .14, .9, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), mat('#34475b'), leg, 0, -.34, 0); limbs.push(leg); const arm = new THREE.Group(); arm.position.set(side * .28, 1.43, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .45, 4, 10), mat('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm); } return { group, limbs }; }
    const luna = person();
    const safeCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-16, .1, 7.4), new THREE.Vector3(-7, .1, 8.25), new THREE.Vector3(2, .1, 8.35), new THREE.Vector3(12, .1, 7.8)]);
    const closeCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-16, .1, 6.25), new THREE.Vector3(-6, .1, 6.15), new THREE.Vector3(1, .1, 6.05), new THREE.Vector3(12, .1, 6.2)]);
    const safeMat = new THREE.MeshStandardMaterial({ color: '#2f9d61', emissive: '#2f9d61', emissiveIntensity: .8 }); const dangerMat = new THREE.MeshStandardMaterial({ color: '#df3e39', emissive: '#df3e39', emissiveIntensity: 1.3, side: THREE.DoubleSide }); materials.push(safeMat, dangerMat);
    const safePath = new THREE.Mesh(new THREE.TubeGeometry(safeCurve, 90, .12, 9), safeMat); safePath.visible = false; scene.add(safePath); const closePath = new THREE.Mesh(new THREE.TubeGeometry(closeCurve, 90, .12, 9), dangerMat); closePath.visible = false; scene.add(closePath);
    const doorArc = new THREE.Mesh(new THREE.RingGeometry(.9, 2.25, 50, 1, 0, Math.PI / 2), dangerMat); doorArc.rotation.x = -Math.PI / 2; doorArc.rotation.z = Math.PI / 2; doorArc.position.set(2.05, .18, 5.15); doorArc.visible = false; scene.add(doorArc);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();
    function reset() { luna.group.position.set(-16, .1, 7.4); luna.group.rotation.set(0, Math.PI / 2, 0); luna.limbs.forEach(limb => limb.rotation.x = 0); doorPivot.rotation.y = 0; child.rotation.z = 0; safePath.visible = closePath.visible = doorArc.visible = false; }
    function setOutcome(value) { outcome = value; progress = 0; reset(); safePath.visible = value === 'anticipate'; closePath.visible = value === 'ignore' || value === 'close'; doorArc.visible = value !== 'intro'; }
    function moveLuna(curve, t, running = false) { const p = curve.getPointAt(Math.min(.999, t)); const next = curve.getPointAt(Math.min(1, t + .01)); luna.group.position.copy(p); luna.group.lookAt(next.x, p.y, next.z); luna.limbs.forEach((limb, index) => { limb.rotation.x = Math.sin(t * (running ? 55 : 36)) * (running ? .5 : .32) * (index % 2 ? -1 : 1); }); }
    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);
    function render(now) {
        if (disposed) return; const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now; const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / (outcome === 'close' ? 4.5 : 7)); const ease = progress * progress * (3 - 2 * progress);
            child.rotation.z = Math.sin(now * .007) * .18;
            if (outcome === 'anticipate') { moveLuna(safeCurve, ease); doorPivot.rotation.y = Math.min(1, ease * 1.8) * 1.22; }
            else if (outcome === 'ignore' || outcome === 'close') { moveLuna(closeCurve, ease, outcome === 'close'); doorPivot.rotation.y = Math.max(0, (ease - .3) / .35) * 1.22; }
            if (doorArc.visible) { const pulse = 1 + Math.sin(now * .008) * .08; doorArc.scale.setScalar(pulse); }
            if (view === 'pedestrian') { const p = luna.group.position; camera.position.set(p.x - 1.4, 2.45, p.z + 1.1); camera.lookAt(1.5, 1, 6.1); }
            else if (view === 'door') { camera.position.set(2.1, 2, 5.4); camera.lookAt(luna.group.position.x, 1, luna.group.position.z); }
            else controls.update(); renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setOutcome('intro'); frame = requestAnimationFrame(render);
    return { setOutcome, setPaused(value) { paused = value; }, setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) { camera.position.set(25, 22, 31); controls.target.set(1, .6, 1); controls.update(); } }, dispose() { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose()); renderer.dispose(); renderer.domElement.remove(); } };
}
