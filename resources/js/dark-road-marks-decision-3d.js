import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountDarkRoadMarksDecision(host) {
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#c8e4ef'); scene.fog = new THREE.Fog('#c8e4ef', 48, 120);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 150); camera.position.set(26, 21, 31);
    const renderer = new THREE.WebGLRenderer({ antialias: true }); renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7)); renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap; renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.08;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block'; renderer.domElement.setAttribute('role', 'img'); renderer.domElement.setAttribute('aria-label', 'Escena tridimensional de una motocicleta que se aproxima a una mancha brillante capaz de reducir el agarre y alterar su trayectoria'); host.appendChild(renderer.domElement);
    const controls = new OrbitControls(camera, renderer.domElement); controls.target.set(1, .5, 1); controls.enableDamping = true; controls.minDistance = 17; controls.maxDistance = 65; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x58734d, 2.35)); const sun = new THREE.DirectionalLight(0xffefcf, 3.15); sun.position.set(-18, 29, 18); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048); Object.assign(sun.shadow.camera, { left: -38, right: 38, top: 27, bottom: -27 }); scene.add(sun);

    const materials = []; const mat = (color, options = {}) => { const value = new THREE.MeshStandardMaterial({ color, roughness: .72, ...options }); materials.push(value); return value; };
    const mesh = (geometry, material, parent, x, y, z) => { const value = new THREE.Mesh(geometry, material); value.position.set(x, y, z); value.castShadow = value.receiveShadow = true; parent.add(value); return value; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    box(96, .18, 50, mat('#78a66c'), scene, 0, -.18, 0); box(92, .14, 13, mat('#48545c', { roughness: .52 }), scene, 0, -.02, 0); for (const z of [-7.4, 7.4]) box(92, .24, 3.2, mat('#c9c8c0'), scene, 0, .04, z);
    for (let x = -42; x <= 42; x += 4.4) box(2.25, .025, .1, mat('#e2c158'), scene, x, .07, 0);
    for (const [x, z, color] of [[-24, -14, '#d8ccb0'], [21, -14, '#c9d8d2'], [-22, 14, '#e0d3bc'], [24, 14, '#d6cabb']]) { box(11, 4.5, 7, mat(color), scene, x, 2.1, z); const roof = mesh(new THREE.CylinderGeometry(0, 7.8, 1.8, 4), mat('#855a49'), scene, x, 5.1, z); roof.rotation.y = Math.PI / 4; }

    function person() { const group = new THREE.Group(); scene.add(group); mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), mat('#ef9135'), group, 0, 1.18, 0); mesh(new THREE.SphereGeometry(.22, 18, 14), mat('#a96f4d'), group, 0, 1.78, 0); const limbs = []; for (const side of [-1, 1]) { const leg = new THREE.Group(); leg.position.set(side * .14, .9, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), mat('#34475b'), leg, 0, -.34, 0); limbs.push(leg); const arm = new THREE.Group(); arm.position.set(side * .28, 1.43, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .45, 4, 10), mat('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm); } return { group, limbs }; }
    const luna = person();
    function motorcycle() {
        const group = new THREE.Group(); scene.add(group); const tires = [];
        for (const x of [-.9, .9]) { const tire = mesh(new THREE.TorusGeometry(.4, .11, 10, 22), mat('#171b20'), group, x, .48, 0); tire.rotation.y = Math.PI / 2; tires.push(tire); }
        box(1.55, .3, .55, mat('#d7374b', { metalness: .35 }), group, 0, .78, 0); const fork = mesh(new THREE.CylinderGeometry(.07, .07, 1.1, 10), mat('#343b41'), group, .62, 1.13, 0); fork.rotation.z = -.38;
        mesh(new THREE.CapsuleGeometry(.2, .46, 5, 10), mat('#276aa1'), group, -.08, 1.48, 0).rotation.z = -.32; mesh(new THREE.SphereGeometry(.24, 16, 12), mat('#27313b', { metalness: .25 }), group, .1, 2.02, 0);
        return { group, tires };
    }
    const motorcycleActor = motorcycle();

    const slickMaterial = mat('#111820', { roughness: .05, metalness: .82, transparent: true, opacity: .88 });
    const slick = mesh(new THREE.CircleGeometry(3.3, 48), slickMaterial, scene, 2, .1, 3.25); slick.rotation.x = -Math.PI / 2; slick.scale.set(1.35, .62, 1);
    const sheenMaterial = new THREE.MeshBasicMaterial({ color: '#8bd8ef', transparent: true, opacity: .28, side: THREE.DoubleSide }); materials.push(sheenMaterial);
    const sheen = new THREE.Mesh(new THREE.RingGeometry(1.3, 2.7, 48, 1, .2, 2.2), sheenMaterial); sheen.rotation.x = -Math.PI / 2; sheen.position.set(2, .12, 3.25); sheen.scale.set(1.35, .62, 1); scene.add(sheen);

    const normalCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(30, .12, 3.25), new THREE.Vector3(13, .12, 3.25), new THREE.Vector3(2, .12, 3.25), new THREE.Vector3(-18, .12, 3.25)]);
    const skidCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(30, .12, 3.25), new THREE.Vector3(13, .12, 3.25), new THREE.Vector3(2, .12, 3.25), new THREE.Vector3(-5, .12, .9), new THREE.Vector3(-18, .12, 1.25)]);
    const walkCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-7, .1, 7.4), new THREE.Vector3(-2, .1, 7.4), new THREE.Vector3(2, .1, 3.25)]);
    const plannedMat = new THREE.MeshStandardMaterial({ color: '#e6c542', emissive: '#e6c542', emissiveIntensity: .9 }); const dangerMat = new THREE.MeshStandardMaterial({ color: '#e0413c', emissive: '#e0413c', emissiveIntensity: 1.25 }); materials.push(plannedMat, dangerMat);
    const plannedPath = new THREE.Mesh(new THREE.TubeGeometry(normalCurve, 100, .1, 9), plannedMat); plannedPath.visible = false; scene.add(plannedPath);
    const skidPath = new THREE.Mesh(new THREE.TubeGeometry(skidCurve, 100, .13, 9), dangerMat); skidPath.visible = false; scene.add(skidPath);
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.2, 1.58, 40), dangerMat); risk.rotation.x = -Math.PI / 2; risk.position.set(2, .18, 3.25); risk.visible = false; scene.add(risk);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();
    function reset() { luna.group.position.set(-7, .1, 7.4); luna.group.rotation.set(0, Math.PI / 2, 0); luna.limbs.forEach(limb => limb.rotation.x = 0); motorcycleActor.group.position.set(30, .12, 3.25); motorcycleActor.group.rotation.set(0, Math.PI, 0); plannedPath.visible = skidPath.visible = risk.visible = false; }
    function setOutcome(value) { outcome = value; progress = 0; reset(); plannedPath.visible = value === 'relate'; skidPath.visible = value === 'relate' || value === 'isolate'; risk.visible = value === 'test'; }
    function moveMotorcycle(curve, t) { const p = curve.getPointAt(Math.min(.999, t)); const next = curve.getPointAt(Math.min(1, t + .01)); motorcycleActor.group.position.copy(p); motorcycleActor.group.lookAt(next.x, p.y, next.z); motorcycleActor.group.rotateY(Math.PI / 2); motorcycleActor.group.rotation.z = t > .45 ? Math.sin((t - .45) * Math.PI) * .22 : 0; motorcycleActor.tires.forEach(tire => { tire.rotation.z -= .2; }); }
    function moveLuna(t) { const p = walkCurve.getPointAt(Math.min(.999, t)); const next = walkCurve.getPointAt(Math.min(1, t + .01)); luna.group.position.copy(p); luna.group.lookAt(next.x, p.y, next.z); luna.limbs.forEach((limb, index) => { limb.rotation.x = Math.sin(t * 42) * .34 * (index % 2 ? -1 : 1); }); }
    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);
    function render(now) {
        if (disposed) return; const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now; const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / 7); const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'relate' || outcome === 'isolate') moveMotorcycle(skidCurve, ease); else if (outcome === 'test') { moveMotorcycle(normalCurve, ease); moveLuna(Math.min(1, ease * 1.18)); } else moveMotorcycle(normalCurve, (now * .00006) % .42);
            sheen.rotation.z = Math.sin(now * .001) * .08; if (risk.visible) { const pulse = 1 + Math.sin(now * .008) * .15; risk.scale.setScalar(pulse); }
            if (view === 'pedestrian') { const p = luna.group.position; camera.position.set(p.x + 1, 2.45, p.z + 2); camera.lookAt(2, .6, 3.25); }
            else if (view === 'rider') { const p = motorcycleActor.group.position; camera.position.set(p.x - 1.1, 2.1, p.z); camera.lookAt(p.x - 12, .7, p.z); }
            else controls.update(); renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setOutcome('intro'); frame = requestAnimationFrame(render);
    return { setOutcome, setPaused(value) { paused = value; }, setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) { camera.position.set(26, 21, 31); controls.target.set(1, .5, 1); controls.update(); } }, dispose() { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose()); renderer.dispose(); renderer.domElement.remove(); } };
}
