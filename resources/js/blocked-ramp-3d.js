import * as THREE from 'three';
import { pedestrianDecisionPoints } from './pedestrian-decision-paths';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountBlockedRamp(host, { stopAtDecisionPoint = false } = {}) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#c9e3ef');
    scene.fog = new THREE.Fog('#c9e3ef', 45, 105);

    const camera = new THREE.PerspectiveCamera(43, 1, .1, 130);
    camera.position.set(22, 24, 29);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.75));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.1;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Escena tridimensional de una rampa bloqueada por una macetera, una persona en silla de ruedas, un acompañante y vehículos en movimiento');
    host.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(1, 0, 0); controls.enableDamping = true;
    controls.minDistance = 18; controls.maxDistance = 65; controls.maxPolarAngle = Math.PI * .48;
    controls.update();

    scene.add(new THREE.HemisphereLight(0xe9f7ff, 0x58734d, 2.4));
    const sun = new THREE.DirectionalLight(0xffedd1, 3.2);
    sun.position.set(-15, 28, 20); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048);
    Object.assign(sun.shadow.camera, { left: -32, right: 32, top: 25, bottom: -25 });
    sun.shadow.bias = -.0004; scene.add(sun);

    const materials = new Map();
    const material = (color, metalness = 0) => {
        const key = color + metalness;
        if (!materials.has(key)) materials.set(key, new THREE.MeshStandardMaterial({ color, roughness: metalness ? .35 : .82, metalness }));
        return materials.get(key);
    };
    const mesh = (geometry, color, parent, x, y, z, metalness = 0) => {
        const object = new THREE.Mesh(geometry, material(color, metalness));
        object.position.set(x, y, z); object.castShadow = object.receiveShadow = true; parent.add(object); return object;
    };
    const box = (w, h, d, color, parent, x, y, z, metalness = 0) =>
        mesh(new THREE.BoxGeometry(w, h, d), color, parent, x, y, z, metalness);

    box(72, .2, 54, '#7fa66f', scene, 0, -.2, 0);
    box(62, .12, 9, '#4d545a', scene, 0, -.02, 0);
    for (const z of [-6, 6]) box(62, .25, 3, '#cbc9bf', scene, 0, .03, z);
    for (let x = -28; x < 29; x += 4) box(2, .025, .1, '#e6c765', scene, x, .06, 0);

    const addCrossing = x => {
        for (let z = -4; z <= 4; z += 1) box(3, .03, .56, '#f5f3e8', scene, x, .08, z);
        for (const z of [-4.65, 4.65]) {
            const ramp = box(3.2, .1, 1.5, '#deddd3', scene, x, .08, z);
            ramp.rotation.x = z > 0 ? -.05 : .05;
        }
    };
    addCrossing(0); addCrossing(13);

    // Macetera situada sobre la rampa más cercana.
    const planter = new THREE.Group(); scene.add(planter);
    mesh(new THREE.CylinderGeometry(.95, .75, 1, 20), '#a95e3c', planter, 0, .58, 4.85);
    for (let i = 0; i < 7; i++) {
        const leaf = mesh(new THREE.SphereGeometry(.43, 14, 10), i % 2 ? '#2d7c43' : '#3f9853', planter,
            Math.cos(i) * .52, 1.35 + (i % 3) * .2, 4.85 + Math.sin(i) * .38);
        leaf.scale.y = 1.35;
    }

    // Dos pasos: uno bloqueado y otro accesible.
    const safeLineMaterial = new THREE.MeshStandardMaterial({ color: '#16875c', emissive: '#16875c', emissiveIntensity: .2 });
    const dangerLineMaterial = new THREE.MeshStandardMaterial({ color: '#d34234', emissive: '#d34234', emissiveIntensity: .2 });
    const pathMesh = (points, mat) => {
        const curve = new THREE.CatmullRomCurve3(points, false, 'catmullrom', .12);
        const object = new THREE.Mesh(new THREE.TubeGeometry(curve, 90, .15, 10), mat);
        object.castShadow = true; scene.add(object); object.visible = false;
        return { curve, object };
    };
    const safePath = pathMesh(pedestrianDecisionPoints('ramp', stopAtDecisionPoint).map(p => new THREE.Vector3(...p)), safeLineMaterial);
    const roadPath = pathMesh([
        new THREE.Vector3(-6, .3, 6), new THREE.Vector3(-2, .3, 6),
        new THREE.Vector3(-2, .3, 2), new THREE.Vector3(6, .3, 2),
    ], dangerLineMaterial);

    const wheelchair = new THREE.Group(); scene.add(wheelchair);
    box(.62, .1, .62, '#34495c', wheelchair, 0, .62, 0);
    box(.62, .65, .12, '#34495c', wheelchair, 0, .96, -.3);
    for (const side of [-1, 1]) {
        const wheel = mesh(new THREE.TorusGeometry(.45, .055, 10, 28), '#202830', wheelchair, side * .42, .46, -.08);
        wheel.rotation.y = Math.PI / 2;
    }
    mesh(new THREE.CapsuleGeometry(.21, .26, 6, 14), '#665d9e', wheelchair, 0, 1.08, 0);
    mesh(new THREE.SphereGeometry(.2, 16, 14), '#a97555', wheelchair, 0, 1.55, 0);

    const helper = new THREE.Group(); scene.add(helper);
    mesh(new THREE.CapsuleGeometry(.24, .65, 7, 14), '#287b83', helper, 0, 1.05, 0);
    mesh(new THREE.SphereGeometry(.22, 16, 14), '#80583e', helper, 0, 1.73, 0);
    for (const x of [-.15, .15]) {
        const leg = box(.13, .7, .13, '#273a55', helper, x, .4, 0); leg.rotation.z = x > 0 ? -.08 : .08;
    }

    const car = new THREE.Group(); scene.add(car);
    box(4.2, .75, 1.8, '#287f9b', car, 0, .75, 0, .45);
    box(2.3, .65, 1.6, '#287f9b', car, -.25, 1.42, 0, .45);
    box(2.05, .42, 1.64, '#284b5c', car, -.25, 1.45, 0, .4);
    for (const x of [-1.25, 1.25]) for (const z of [-.92, .92]) {
        const wheel = mesh(new THREE.CylinderGeometry(.36, .36, .2, 20), '#21272c', car, x, .42, z);
        wheel.rotation.x = Math.PI / 2;
    }

    const risk = mesh(new THREE.RingGeometry(1.2, 1.5, 36), '#d34234', scene, 0, .15, 4.85);
    risk.rotation.x = -Math.PI / 2; risk.visible = false;

    let outcome = 'intro', progress = 0, paused = false, view = 'overview';
    let disposed = false, frame = 0, last = performance.now();
    const resetActors = () => {
        wheelchair.position.set(-6, 0, 6); wheelchair.rotation.y = Math.PI / 2;
        helper.position.set(-8, 0, 7.4); helper.rotation.y = Math.PI / 2;
        car.position.set(-23, 0, 2);
    };
    const setOutcome = value => {
        outcome = value; progress = 0; resetActors();
        safePath.object.visible = value === 'ask';
        roadPath.object.visible = value === 'road';
        risk.visible = value === 'push' || value === 'road';
        car.visible = value === 'road';
    };

    const observer = new ResizeObserver(() => {
        if (!host.clientWidth || !host.clientHeight) return;
        renderer.setSize(host.clientWidth, host.clientHeight, false);
        camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix();
    });
    observer.observe(host);

    function render(now) {
        if (disposed) return;
        const elapsed = (now - last) / 1000;
        const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0;
        last = now;
        const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / (outcome === 'ask' ? 10 : 6));
            if (outcome === 'ask') {
                const point = safePath.curve.getPointAt(progress);
                const ahead = safePath.curve.getPointAt(Math.min(1, progress + .01));
                wheelchair.position.copy(point); wheelchair.lookAt(ahead.x, point.y, ahead.z);
                const follow = safePath.curve.getPointAt(Math.max(0, progress - .025));
                helper.position.set(follow.x - .7, follow.y, follow.z + .8); helper.lookAt(point.x, follow.y, point.z);
            } else if (outcome === 'road') {
                const point = roadPath.curve.getPointAt(progress);
                const ahead = roadPath.curve.getPointAt(Math.min(1, progress + .01));
                wheelchair.position.copy(point); wheelchair.lookAt(ahead.x, point.y, ahead.z);
                car.position.x = -23 + progress * 40;
            } else if (outcome === 'push') {
                const t = Math.min(1, progress * 1.7);
                wheelchair.position.set(-6 + t * 4.7, 0, 6 - t * .7);
                helper.position.set(wheelchair.position.x - .8, 0, wheelchair.position.z + .65);
                helper.lookAt(wheelchair.position.x, 0, wheelchair.position.z);
            }
            if (risk.visible) {
                const pulse = 1 + Math.sin(now * .006) * .12; risk.scale.setScalar(pulse);
            }
            if (view === 'traveler' && outcome !== 'intro') {
                const p = wheelchair.position;
                camera.position.set(p.x - 3.7, 2.7, p.z + 5.2); camera.lookAt(p.x + 3, .75, p.z);
            } else controls.update();
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
            if (controls.enabled) {
                camera.position.set(22, 24, 29); controls.target.set(1, 0, 0); controls.update();
            }
        },
        dispose() {
            disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose();
            scene.traverse(object => object.geometry?.dispose());
            materials.forEach(value => value.dispose());
            safeLineMaterial.dispose(); dangerLineMaterial.dispose();
            renderer.dispose(); renderer.domElement.remove();
        },
    };
}
