import * as THREE from 'three';
import { pedestrianDecisionPoints } from './pedestrian-decision-paths';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountParkedCarsShortcut(host, { stopAtDecisionPoint = false } = {}) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#c9e4f0');
    scene.fog = new THREE.Fog('#c9e4f0', 45, 115);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 140);
    camera.position.set(24, 24, 31);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.75));
    renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.12;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Escena tridimensional de Luna frente a dos automóviles estacionados que ocultan la visibilidad y un paso peatonal cercano');
    host.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(2, 0, 0); controls.enableDamping = true; controls.minDistance = 18; controls.maxDistance = 68; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x58734d, 2.5));
    const sun = new THREE.DirectionalLight(0xffefd3, 3.25);
    sun.position.set(-17, 29, 20); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048);
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

    box(80, .2, 42, '#789b6c', scene, 0, -.2, 0);
    box(72, .12, 10, '#474f55', scene, 0, -.02, 0);
    for (const z of [-6.5, 6.5]) box(72, .24, 3.2, '#c9c8be', scene, 0, .04, z);
    for (let x = -31; x < 32; x += 4) box(2, .025, .1, '#e7c763', scene, x, .07, 0);
    const crossingX = 16;
    for (let z = -4.5; z <= 4.5; z += 1.05) box(3.4, .035, .58, '#f4f2e8', scene, crossingX, .09, z);
    for (const z of [-5.1, 5.1]) box(3.6, .1, 1.6, '#dfddd1', scene, crossingX, .08, z);

    // Parque al otro lado: árboles, banca, sendero y juego infantil.
    box(25, .025, 7, '#d7c8a7', scene, 9, -.07, -12);
    for (const [x, z] of [[-10, -11], [1, -13], [13, -12], [24, -11]]) {
        mesh(new THREE.CylinderGeometry(.16, .25, 3.8, 10), '#6e5941', scene, x, 1.8, z);
        const crown = mesh(new THREE.SphereGeometry(1.55, 14, 11), '#3e8050', scene, x, 4.3, z); crown.scale.y = 1.25;
    }
    box(3.2, .18, .85, '#845b36', scene, 7, .72, -9.3);
    for (const x of [5.8, 8.2]) box(.16, .75, .6, '#4b5054', scene, x, .35, -9.3, .6);
    const slide = box(3.5, .16, 1, '#e9a824', scene, -3, 1.25, -10.5); slide.rotation.z = -.42;
    box(.15, 2.7, .15, '#236e91', scene, -4.4, 1.3, -10.5, .5);

    function car(color, x, z, scale = 1) {
        const group = new THREE.Group(); scene.add(group);
        box(4.4, .72, 1.9, color, group, 0, .76, 0, .45);
        box(2.35, .7, 1.68, color, group, -.25, 1.45, 0, .45);
        box(2.05, .45, 1.72, '#294957', group, -.25, 1.48, 0, .35);
        box(.13, .58, 1.75, color, group, -.3, 1.47, 0);
        for (const wx of [-1.3, 1.3]) for (const wz of [-.98, .98]) {
            const wheel = mesh(new THREE.CylinderGeometry(.37, .37, .2, 22), '#20262b', group, wx, .43, wz); wheel.rotation.x = Math.PI / 2;
            const hub = mesh(new THREE.CylinderGeometry(.18, .18, .22, 16), '#a8b0b4', group, wx, .43, wz, .7); hub.rotation.x = Math.PI / 2;
        }
        group.position.set(x, 0, z); group.scale.setScalar(scale); return group;
    }
    const parkedLeft = car('#316f91', -3.5, -3.7);
    const parkedRight = car('#d9d4c7', 4, -3.7);
    const movingCar = car('#bf3c36', -31, 2.3);

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
        const object = new THREE.Mesh(new THREE.TubeGeometry(curve, 100, .12, 10), mat); object.position.y = .16; object.visible = false; scene.add(object);
        return { curve, object, mat };
    };
    const shortcut = makePath([new THREE.Vector3(.2, 0, 6.5), new THREE.Vector3(.2, 0, 4.6), new THREE.Vector3(.2, 0, -1.9), new THREE.Vector3(.2, 0, -6.5)], '#d93c35');
    const safe = makePath(pedestrianDecisionPoints('parked', stopAtDecisionPoint).map(p => new THREE.Vector3(...p)), '#18865c');
    const sightMaterial = new THREE.MeshBasicMaterial({ color: '#ed9b2c', transparent: true, opacity: .24, side: THREE.DoubleSide, depthWrite: false });
    const sight = new THREE.Mesh(new THREE.BufferGeometry().setFromPoints([
        new THREE.Vector3(.2, .25, 4.8), new THREE.Vector3(-17, .25, 2.2), new THREE.Vector3(11, .25, 2.2),
    ]), sightMaterial);
    sight.geometry.setIndex([0, 1, 2]); sight.geometry.computeVertexNormals(); sight.visible = false; scene.add(sight);
    const riskMaterial = new THREE.MeshStandardMaterial({ color: '#d93732', emissive: '#d93732', emissiveIntensity: 1.1, side: THREE.DoubleSide });
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.05, 1.4, 40), riskMaterial); risk.rotation.x = -Math.PI / 2; risk.position.set(.2, .18, 1.7); risk.visible = false; scene.add(risk);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview';
    let disposed = false, frame = 0, last = performance.now();
    const reset = () => {
        luna.position.set(.2, .1, 6.5); luna.rotation.set(0, Math.PI, 0);
        movingCar.position.set(-31, 0, 2.3); movingCar.rotation.set(0, 0, 0);
        limbs.forEach(limb => limb.rotation.x = 0); risk.visible = false; sight.visible = false;
        shortcut.object.visible = false; safe.object.visible = false;
    };
    const setOutcome = value => {
        outcome = value; progress = 0; reset();
        shortcut.object.visible = value === 'shortcut' || value === 'run';
        safe.object.visible = value === 'safe'; sight.visible = value === 'shortcut' || value === 'run';
        risk.visible = value === 'shortcut' || value === 'run';
    };
    const observer = new ResizeObserver(() => {
        if (!host.clientWidth || !host.clientHeight) return;
        renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix();
    }); observer.observe(host);

    function movePerson(curve, t) {
        const point = curve.getPointAt(Math.min(.999, t)); const ahead = curve.getPointAt(Math.min(1, t + .01));
        luna.position.copy(point); luna.lookAt(ahead.x, point.y, ahead.z);
        limbs.forEach((limb, index) => limb.rotation.x = Math.sin(t * (outcome === 'run' ? 55 : 35)) * .34 * (index % 2 ? -1 : 1));
    }
    function render(now) {
        if (disposed) return;
        const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now;
        const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / (outcome === 'safe' ? 10 : outcome === 'run' ? 3.2 : 5));
            const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'safe') {
                movePerson(safe.curve, ease);
                movingCar.position.x = -31 + Math.min(ease, .62) * 34;
            } else if (outcome === 'shortcut' || outcome === 'run') {
                movePerson(shortcut.curve, ease);
                movingCar.position.x = -31 + ease * 48;
            } else {
                movingCar.position.x = -31 + ((now * .0018) % 1) * 62;
            }
            if (risk.visible) { const pulse = 1 + Math.sin(now * .007) * .14; risk.scale.setScalar(pulse); }
            if (view === 'pedestrian') { camera.position.set(-1.7, 2.6, 8.8); camera.lookAt(.5, .9, -2); }
            else if (view === 'driver') { camera.position.set(movingCar.position.x + 3.2, 1.8, 2.25); camera.lookAt(movingCar.position.x + 10, .8, 1); }
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
            if (controls.enabled) { camera.position.set(24, 24, 31); controls.target.set(2, 0, 0); controls.update(); }
        },
        dispose() {
            disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose();
            scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose());
            shortcut.mat.dispose(); safe.mat.dispose(); sightMaterial.dispose(); riskMaterial.dispose();
            renderer.dispose(); renderer.domElement.remove();
        },
    };
}
