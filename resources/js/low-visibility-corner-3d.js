import * as THREE from 'three';
import { pedestrianDecisionPoints } from './pedestrian-decision-paths';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountLowVisibilityCorner(host, { stopAtDecisionPoint = false } = {}) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#c8e3ef');
    scene.fog = new THREE.Fog('#c8e3ef', 45, 115);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 140);
    camera.position.set(25, 25, 30);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.75));
    renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.12;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Esquina tridimensional con un camión estacionado que impide a Luna ver un automóvil que se aproxima y otro paso permitido con mejor visibilidad');
    host.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(2, 0, 0); controls.enableDamping = true; controls.minDistance = 18; controls.maxDistance = 68; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x58724d, 2.5));
    const sun = new THREE.DirectionalLight(0xffefd3, 3.25);
    sun.position.set(-16, 29, 20); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048);
    Object.assign(sun.shadow.camera, { left: -35, right: 35, top: 28, bottom: -28 }); sun.shadow.bias = -.0004; scene.add(sun);

    const materials = new Map();
    const material = (color, metalness = 0) => {
        const key = color + metalness;
        if (!materials.has(key)) materials.set(key, new THREE.MeshStandardMaterial({ color, roughness: metalness ? .34 : .82, metalness }));
        return materials.get(key);
    };
    const mesh = (geometry, color, parent, x, y, z, metalness = 0) => {
        const object = new THREE.Mesh(geometry, material(color, metalness)); object.position.set(x, y, z);
        object.castShadow = object.receiveShadow = true; parent.add(object); return object;
    };
    const box = (w, h, d, color, parent, x, y, z, metalness = 0) => mesh(new THREE.BoxGeometry(w, h, d), color, parent, x, y, z, metalness);

    // Cruce en T con aceras y dos pasos permitidos.
    box(78, .2, 58, '#76986b', scene, 0, -.2, 0);
    box(74, .12, 10, '#474f55', scene, 0, -.02, 0);
    box(10, .12, 31, '#474f55', scene, 0, -.01, 19);
    for (const z of [-6.5, 6.5]) box(74, .24, 3.2, '#c9c8be', scene, 0, .04, z);
    for (const x of [-6.5, 6.5]) box(3.2, .24, 29, '#c9c8be', scene, x, .04, 20);
    for (let x = -32; x < 33; x += 4) if (Math.abs(x) > 6) box(2, .025, .1, '#e6c661', scene, x, .07, 0);
    for (let z = 10; z < 31; z += 4) box(.1, .025, 2, '#e6c661', scene, 0, .07, z);
    const blockedCrossingX = 1.7;
    const visibleCrossingX = 17;
    for (const x of [blockedCrossingX, visibleCrossingX]) {
        for (let z = -4.5; z <= 4.5; z += 1.05) box(3.3, .035, .58, '#f4f2e8', scene, x, .09, z);
        for (const z of [-5.1, 5.1]) box(3.6, .1, 1.6, '#dfddd1', scene, x, .08, z);
    }

    // Entorno urbano para que la esquina y la distancia sean legibles.
    for (const [x, z, color] of [[-17, -14, '#e4d2b3'], [17, -14, '#d6e2dc'], [-17, 18, '#e9dfca'], [18, 19, '#d8cdbb']]) {
        box(10, 4.5, 7, color, scene, x, 2.15, z);
        const roof = mesh(new THREE.CylinderGeometry(0, 7.3, 1.8, 4), '#935f49', scene, x, 5.15, z); roof.rotation.y = Math.PI / 4;
    }
    for (const [x, z] of [[-10, -10], [11, -11], [-11, 12], [11, 13], [25, -10]]) {
        mesh(new THREE.CylinderGeometry(.15, .24, 3.6, 10), '#6d5941', scene, x, 1.75, z);
        const crown = mesh(new THREE.SphereGeometry(1.5, 14, 11), '#3e8050', scene, x, 4.2, z); crown.scale.y = 1.25;
    }

    function wheel(parent, x, z, radius = .42) {
        const tire = mesh(new THREE.CylinderGeometry(radius, radius, .24, 22), '#20262b', parent, x, radius + .08, z); tire.rotation.x = Math.PI / 2;
        const hub = mesh(new THREE.CylinderGeometry(radius * .48, radius * .48, .26, 16), '#a8b1b5', parent, x, radius + .08, z, .7); hub.rotation.x = Math.PI / 2;
    }
    const truck = new THREE.Group(); scene.add(truck);
    box(6.8, 2.9, 2.4, '#e0a72e', truck, -1.1, 1.85, 0, .25);
    box(2.4, 1.8, 2.35, '#26728e', truck, 3.3, 1.15, 0, .4);
    box(1.45, .78, 2.39, '#294b59', truck, 3.55, 1.55, 0, .3);
    for (const x of [-2.8, 1.2, 3.7]) for (const z of [-1.24, 1.24]) wheel(truck, x, z);
    truck.position.set(1, 0, -3.65);

    const car = new THREE.Group(); scene.add(car);
    box(4.4, .72, 1.9, '#bc4039', car, 0, .76, 0, .45);
    box(2.35, .7, 1.68, '#bc4039', car, -.25, 1.45, 0, .45);
    box(2.05, .45, 1.72, '#294957', car, -.25, 1.48, 0, .35);
    for (const x of [-1.3, 1.3]) for (const z of [-.98, .98]) wheel(car, x, z, .37);

    const luna = new THREE.Group(); scene.add(luna);
    mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), '#ef9c35', luna, 0, 1.18, 0);
    mesh(new THREE.SphereGeometry(.22, 18, 14), '#a96f4d', luna, 0, 1.78, 0);
    mesh(new THREE.SphereGeometry(.23, 14, 10, 0, Math.PI * 2, 0, Math.PI / 2), '#392d27', luna, 0, 1.82, 0);
    const limbs = [];
    for (const side of [-1, 1]) {
        const leg = new THREE.Group(); leg.position.set(side * .14, .9, 0); luna.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), '#34475b', leg, 0, -.34, 0); limbs.push(leg);
        const arm = new THREE.Group(); arm.position.set(side * .28, 1.43, 0); luna.add(arm); mesh(new THREE.CapsuleGeometry(.065, .45, 4, 10), '#a96f4d', arm, 0, -.27, 0); limbs.push(arm);
    }

    const makePath = (points, color) => {
        const mat = new THREE.MeshStandardMaterial({ color, emissive: color, emissiveIntensity: .38, transparent: true, opacity: .88 });
        const curve = new THREE.CatmullRomCurve3(points, false, 'catmullrom', .08);
        const object = new THREE.Mesh(new THREE.TubeGeometry(curve, 110, .12, 10), mat); object.position.y = .16; object.visible = false; scene.add(object);
        return { curve, object, mat };
    };
    const blockedPath = makePath([new THREE.Vector3(1.7, 0, 7), new THREE.Vector3(1.7, 0, 4), new THREE.Vector3(1.7, 0, -7)], '#d93c35');
    const visiblePath = makePath(pedestrianDecisionPoints('visibility', stopAtDecisionPoint).map(p => new THREE.Vector3(...p)), '#18865c');
    const roadPeekPath = makePath([new THREE.Vector3(1.7, 0, 7), new THREE.Vector3(1.7, 0, 4.1), new THREE.Vector3(4.6, 0, 2.5)], '#e08d24');
    const sightMaterial = new THREE.MeshBasicMaterial({ color: '#e99328', transparent: true, opacity: .27, side: THREE.DoubleSide, depthWrite: false });
    const sight = new THREE.Mesh(new THREE.BufferGeometry().setFromPoints([
        new THREE.Vector3(1.7, .24, 5), new THREE.Vector3(-19, .24, 1.7), new THREE.Vector3(12, .24, 1.7),
    ]), sightMaterial); sight.geometry.setIndex([0, 1, 2]); sight.geometry.computeVertexNormals(); sight.visible = false; scene.add(sight);
    const riskMaterial = new THREE.MeshStandardMaterial({ color: '#d93732', emissive: '#d93732', emissiveIntensity: 1.1, side: THREE.DoubleSide });
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.05, 1.4, 40), riskMaterial); risk.rotation.x = -Math.PI / 2; risk.position.set(3.2, .18, 1.8); risk.visible = false; scene.add(risk);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview';
    let disposed = false, frame = 0, last = performance.now();
    const reset = () => {
        luna.position.set(1.7, .1, 7); luna.rotation.set(0, Math.PI, 0); limbs.forEach(limb => limb.rotation.x = 0);
        car.position.set(-32, 0, 2.1); car.rotation.set(0, 0, 0);
        blockedPath.object.visible = false; visiblePath.object.visible = false; roadPeekPath.object.visible = false; sight.visible = false; risk.visible = false;
    };
    const setOutcome = value => {
        outcome = value; progress = 0; reset();
        blockedPath.object.visible = value === 'blocked'; visiblePath.object.visible = value === 'visible'; roadPeekPath.object.visible = value === 'peek';
        sight.visible = value === 'blocked' || value === 'peek'; risk.visible = value === 'blocked' || value === 'peek';
    };
    const observer = new ResizeObserver(() => {
        if (!host.clientWidth || !host.clientHeight) return;
        renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix();
    }); observer.observe(host);
    function movePerson(curve, t) {
        const point = curve.getPointAt(Math.min(.999, t)); const ahead = curve.getPointAt(Math.min(1, t + .01));
        luna.position.copy(point); luna.lookAt(ahead.x, point.y, ahead.z);
        limbs.forEach((limb, index) => limb.rotation.x = Math.sin(t * 36) * .34 * (index % 2 ? -1 : 1));
    }
    function render(now) {
        if (disposed) return;
        const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now;
        const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / (outcome === 'visible' ? 10 : 5));
            const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'visible') { movePerson(visiblePath.curve, ease); car.position.x = -32 + Math.min(ease, .58) * 42; }
            else if (outcome === 'blocked') { movePerson(blockedPath.curve, ease); car.position.x = -32 + ease * 50; }
            else if (outcome === 'peek') { movePerson(roadPeekPath.curve, ease); car.position.x = -32 + ease * 50; }
            else car.position.x = -32 + ((now * .0017) % 1) * 65;
            if (risk.visible) { const pulse = 1 + Math.sin(now * .007) * .14; risk.scale.setScalar(pulse); }
            if (view === 'pedestrian') { camera.position.set(-.7, 2.6, 9.2); camera.lookAt(4, .9, 0); }
            else if (view === 'driver') { camera.position.set(car.position.x + 3.2, 1.8, 2.1); camera.lookAt(car.position.x + 11, .8, 1); }
            else controls.update();
            renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setOutcome('intro'); frame = requestAnimationFrame(render);
    return {
        setOutcome,
        setPaused(value) { paused = value; },
        setView(value) {
            view = value; controls.enabled = value === 'overview';
            if (controls.enabled) { camera.position.set(25, 25, 30); controls.target.set(2, 0, 0); controls.update(); }
        },
        dispose() {
            disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose();
            scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose());
            blockedPath.mat.dispose(); visiblePath.mat.dispose(); roadPeekPath.mat.dispose(); sightMaterial.dispose(); riskMaterial.dispose();
            renderer.dispose(); renderer.domElement.remove();
        },
    };
}
