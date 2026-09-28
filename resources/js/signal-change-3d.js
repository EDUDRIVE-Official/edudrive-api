import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountSignalChange(host) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#c8e4f1');
    scene.fog = new THREE.Fog('#c8e4f1', 42, 105);

    const camera = new THREE.PerspectiveCamera(43, 1, .1, 130);
    camera.position.set(23, 23, 27);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.75));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.12;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Escena tridimensional de Luna esperando con señal peatonal verde mientras una motocicleta se aproxima a la esquina y gira frente al cruce');
    host.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(0, 0, 0); controls.enableDamping = true;
    controls.minDistance = 17; controls.maxDistance = 62; controls.maxPolarAngle = Math.PI * .48;
    controls.update();

    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x536b49, 2.5));
    const sun = new THREE.DirectionalLight(0xffefd2, 3.3);
    sun.position.set(-16, 27, 19); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048);
    Object.assign(sun.shadow.camera, { left: -30, right: 30, top: 28, bottom: -28 });
    sun.shadow.bias = -.0004; scene.add(sun);

    const materials = new Map();
    const material = (color, metalness = 0) => {
        const key = color + metalness;
        if (!materials.has(key)) materials.set(key, new THREE.MeshStandardMaterial({ color, roughness: metalness ? .32 : .82, metalness }));
        return materials.get(key);
    };
    const mesh = (geometry, color, parent, x, y, z, metalness = 0) => {
        const object = new THREE.Mesh(geometry, material(color, metalness));
        object.position.set(x, y, z); object.castShadow = object.receiveShadow = true; parent.add(object); return object;
    };
    const box = (w, h, d, color, parent, x, y, z, metalness = 0) =>
        mesh(new THREE.BoxGeometry(w, h, d), color, parent, x, y, z, metalness);

    // Intersección urbana, aceras, pasos peatonales y marcas viales.
    box(70, .2, 58, '#729669', scene, 0, -.2, 0);
    box(70, .12, 9, '#454d53', scene, 0, -.02, 0);
    box(9, .13, 58, '#454d53', scene, 0, -.01, 0);
    for (const z of [-6, 6]) box(70, .24, 3, '#c9c8bf', scene, 0, .04, z);
    for (const x of [-6, 6]) box(3, .24, 58, '#c9c8bf', scene, x, .04, 0);
    for (let x = -30; x < 31; x += 4) if (Math.abs(x) > 6) box(2, .025, .1, '#e4c45e', scene, x, .07, 0);
    for (let z = -25; z < 26; z += 4) if (Math.abs(z) > 6) box(.1, .025, 2, '#e4c45e', scene, 0, .07, z);
    for (let z = -4; z <= 4; z += 1) box(3.4, .035, .56, '#f5f3e9', scene, -2, .09, z);

    // Edificios bajos y vegetación para dar escala y profundidad.
    const corners = [[-15, -15], [15, -15], [-16, 16], [16, 16]];
    corners.forEach(([x, z], index) => {
        box(10, 4 + index % 2, 7, ['#e7d5b4', '#d6e1db', '#e9dfca', '#d8cfbd'][index], scene, x, 2, z);
        const roof = mesh(new THREE.CylinderGeometry(0, 7.3, 1.8, 4), '#915d49', scene, x, 5, z);
        roof.rotation.y = Math.PI / 4;
        for (const offset of [-3, 0, 3]) box(1.35, 1.35, .08, '#385d70', scene, x + offset, 2.3, z + (z < 0 ? 3.53 : -3.53));
    });
    for (const [x, z] of [[-9, -9], [9, -10], [-10, 11], [10, 11]]) {
        mesh(new THREE.CylinderGeometry(.14, .23, 3.5, 10), '#6f5b43', scene, x, 1.7, z);
        const crown = mesh(new THREE.SphereGeometry(1.45, 14, 11), '#3d7e50', scene, x, 4, z);
        crown.scale.set(1, 1.25, 1);
    }

    // Semáforo peatonal favorable, visible junto al punto de espera.
    const signal = new THREE.Group(); scene.add(signal); signal.position.set(-4.5, 0, 5);
    mesh(new THREE.CylinderGeometry(.1, .13, 4.2, 12), '#454d55', signal, 0, 2.1, 0, .7);
    box(1.05, 1.45, .55, '#26323b', signal, 0, 4.15, 0);
    const greenMaterial = new THREE.MeshStandardMaterial({ color: '#39e66f', emissive: '#1ed75b', emissiveIntensity: 2.2, roughness: .4 });
    const green = new THREE.Mesh(new THREE.CircleGeometry(.28, 24), greenMaterial);
    green.position.set(0, 4.15, .286); signal.add(green);
    box(.36, .54, .08, '#dbffe3', signal, 0, 4.13, .3);
    for (const side of [-1, 1]) {
        const leg = box(.1, .38, .08, '#39a95d', signal, side * .1, 3.79, .31); leg.rotation.z = side * .18;
        const arm = box(.1, .35, .08, '#39a95d', signal, side * .19, 4.18, .31); arm.rotation.z = side * .85;
    }

    const luna = new THREE.Group(); scene.add(luna);
    mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), '#ef9c35', luna, 0, 1.18, 0);
    mesh(new THREE.SphereGeometry(.22, 18, 14), '#a96f4d', luna, 0, 1.78, 0);
    mesh(new THREE.SphereGeometry(.23, 14, 10, 0, Math.PI * 2, 0, Math.PI / 2), '#3a2d27', luna, 0, 1.82, 0);
    const limbs = [];
    for (const side of [-1, 1]) {
        const leg = new THREE.Group(); leg.position.set(side * .14, .9, 0); luna.add(leg);
        mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), '#34475b', leg, 0, -.34, 0); limbs.push(leg);
        const arm = new THREE.Group(); arm.position.set(side * .28, 1.43, 0); luna.add(arm);
        mesh(new THREE.CapsuleGeometry(.065, .45, 4, 10), '#a96f4d', arm, 0, -.27, 0); limbs.push(arm);
    }
    const phone = box(.22, .36, .06, '#182837', luna, .25, 1.22, -.24, .5); phone.visible = false;

    const motorcycle = new THREE.Group(); scene.add(motorcycle);
    for (const x of [-.85, .85]) {
        const tire = mesh(new THREE.TorusGeometry(.4, .1, 10, 28), '#20262b', motorcycle, x, .45, 0);
        mesh(new THREE.CylinderGeometry(.12, .12, .18, 16), '#aeb8bd', motorcycle, x, .45, 0, .8).rotation.x = Math.PI / 2;
        tire.rotation.y = 0;
    }
    box(1.5, .22, .38, '#d9323f', motorcycle, 0, .75, 0, .45);
    const tank = mesh(new THREE.SphereGeometry(.48, 16, 12), '#df3545', motorcycle, .15, 1.02, 0, .45); tank.scale.set(1.25, .7, .75);
    box(.8, .13, .38, '#222a31', motorcycle, -.55, 1.08, 0);
    mesh(new THREE.CylinderGeometry(.055, .055, 1.1, 10), '#adb6bb', motorcycle, .7, 1.25, 0, .8).rotation.z = -.65;
    const rider = mesh(new THREE.CapsuleGeometry(.2, .5, 6, 12), '#1d5682', motorcycle, -.1, 1.55, 0); rider.rotation.z = -.3;
    mesh(new THREE.SphereGeometry(.23, 16, 12), '#f0c13e', motorcycle, .12, 2.05, 0, .3);

    const routeMaterial = new THREE.MeshStandardMaterial({ color: '#f0a528', emissive: '#e89516', emissiveIntensity: .65, transparent: true, opacity: .82 });
    const routeCurve = new THREE.CatmullRomCurve3([
        new THREE.Vector3(22, .15, 2), new THREE.Vector3(8, .15, 2),
        new THREE.Vector3(3.5, .15, 2.2), new THREE.Vector3(1.8, .15, 4.4),
        new THREE.Vector3(1.8, .15, 15),
    ], false, 'catmullrom', .12);
    const route = new THREE.Mesh(new THREE.TubeGeometry(routeCurve, 100, .1, 10), routeMaterial);
    route.position.y = .08; scene.add(route);
    const riskMaterial = new THREE.MeshStandardMaterial({ color: '#d93732', emissive: '#d93732', emissiveIntensity: 1.1, side: THREE.DoubleSide });
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.05, 1.38, 40), riskMaterial);
    risk.rotation.x = -Math.PI / 2; risk.position.set(-1.4, .17, 1.7); risk.visible = false; scene.add(risk);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview';
    let disposed = false, frame = 0, last = performance.now();
    const reset = () => {
        luna.position.set(-2, .1, 6.2); luna.rotation.set(0, Math.PI, 0); phone.visible = false;
        motorcycle.position.copy(routeCurve.getPointAt(0)); motorcycle.rotation.set(0, 0, 0);
        risk.visible = false;
    };
    const setOutcome = value => { outcome = value; progress = 0; reset(); phone.visible = value === 'phone'; risk.visible = value === 'cross' || value === 'phone'; };

    const observer = new ResizeObserver(() => {
        if (!host.clientWidth || !host.clientHeight) return;
        renderer.setSize(host.clientWidth, host.clientHeight, false);
        camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix();
    });
    observer.observe(host);

    function placeMotorcycle(t) {
        const point = routeCurve.getPointAt(Math.min(.999, t));
        const ahead = routeCurve.getPointAt(Math.min(1, t + .008));
        motorcycle.position.copy(point);
        const direction = ahead.clone().sub(point);
        motorcycle.rotation.y = -Math.atan2(direction.z, direction.x);
        motorcycle.rotation.z = t > .42 && t < .72 ? -.1 : 0;
    }

    function render(now) {
        if (disposed) return;
        const elapsed = (now - last) / 1000;
        const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now;
        const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused) progress += dt / (outcome === 'intro' ? 8 : 5.5);
            if (outcome === 'intro' && progress >= 1) { progress = 0; reset(); }
            else progress = Math.min(1, progress);
            const ease = progress * progress * (3 - 2 * progress);
            const motorT = outcome === 'confirm' ? Math.min(.47, ease * .47) : ease;
            placeMotorcycle(motorT);
            if (outcome === 'cross') {
                const walk = Math.min(1, ease * 1.35);
                luna.position.z = 6.2 - walk * 8.2;
                limbs.forEach((limb, index) => limb.rotation.x = Math.sin(walk * 32) * .32 * (index % 2 ? -1 : 1));
            } else if (outcome === 'phone') {
                luna.rotation.x = -.12; luna.rotation.y = Math.PI + .25;
            }
            if (risk.visible) {
                const pulse = 1 + Math.sin(now * .007) * .13; risk.scale.setScalar(pulse);
            }
            greenMaterial.emissiveIntensity = 1.8 + Math.sin(now * .004) * .35;
            if (view === 'pedestrian') {
                camera.position.set(-5.8, 2.7, 8.8); camera.lookAt(1.5, 1, 1.5);
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
            if (controls.enabled) { camera.position.set(23, 23, 27); controls.target.set(0, 0, 0); controls.update(); }
        },
        dispose() {
            disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose();
            scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose());
            greenMaterial.dispose(); routeMaterial.dispose(); riskMaterial.dispose();
            renderer.dispose(); renderer.domElement.remove();
        },
    };
}
