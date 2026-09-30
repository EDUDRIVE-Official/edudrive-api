import * as THREE from 'three';
import { addRoadActors } from './road-actors-3d';
import { addCrossingMovements } from './crossing-movements-3d';

// One renderer per explicitly opened lesson; all resources are released on close.
export function mountCrossing(host, mode = 'crossing') {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#c6deed');
    scene.fog = new THREE.Fog('#c6deed', 35, 90);
    const camera = new THREE.PerspectiveCamera(43, 1, 0.1, 120);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.75));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('aria-label', mode === 'actors' ? 'Autobús que bloquea la vista entre un peatón y un ciclista, con una persona en silla de ruedas en la otra acera' : mode === 'bus-stop' ? 'Luna espera en la acera junto a un autobús que oculta a un ciclista' : mode === 'crossing-movements' ? 'Luna espera mientras un automóvil sale de un garaje y una bicicleta se aproxima por el borde de la vía' : 'Escena tridimensional de un cruce peatonal con tránsito en dos sentidos');
    renderer.domElement.setAttribute('role', 'img');
    host.appendChild(renderer.domElement);
    scene.add(new THREE.HemisphereLight(0xe5f4ff, 0x62714e, 2.4));
    const sun = new THREE.DirectionalLight(0xffedcf, 3.2);
    sun.position.set(-12, 24, 16);
    sun.castShadow = true;
    sun.shadow.mapSize.set(2048, 2048);
    Object.assign(sun.shadow.camera, { left: -25, right: 25, top: 25, bottom: -25 });
    sun.shadow.bias = -0.0004;
    scene.add(sun);
    const materials = new Map();
    function material(color, metalness = 0) {
        const key = `${color}-${metalness}`;
        if (!materials.has(key)) materials.set(key, new THREE.MeshStandardMaterial({ color, metalness, roughness: metalness ? 0.3 : 0.85 }));
        return materials.get(key);
    }
    function mesh(geometry, color, parent, x, y, z, metalness = 0) {
        const object = new THREE.Mesh(geometry, material(color, metalness));
        object.position.set(x, y, z);
        object.castShadow = object.receiveShadow = true;
        parent.add(object);
        return object;
    }
    const box = (w, h, d, color, parent, x, y, z, metalness = 0) => mesh(new THREE.BoxGeometry(w, h, d), color, parent, x, y, z, metalness);
    box(110, 0.2, 90, '#76936a', scene, 0, -0.2, 0);
    box(95, 0.12, 8, '#444c52', scene, 0, -0.02, 0);
    for (const side of [-1, 1]) {
        box(90, 0.22, 3, '#b9b9b0', scene, 0, 0.03, side * 5.5);
        // Ramp is flush with the crossing; raised curb continues on each side.
        for (const x of [-24, 24]) box(44, 0.28, 0.22, '#deddd0', scene, x, 0.1, side * 4.1);
        for (let x = -40; x < 42; x += 2) box(0.015, 0.01, 2.9, '#989e98', scene, x, 0.147, side * 5.5);
    }
    for (let x = -44; x < 44; x += 4) {
        if (Math.abs(x) > 3) box(2, 0.015, 0.1, '#e7c362', scene, x, 0.05, 0);
    }
    for (let z = -3.5; z <= 3.5; z += 1) box(3, 0.02, 0.52, '#f3f2e8', scene, 0, 0.065, z);
    box(0.22, 0.02, 3.7, '#eeeadd', scene, -3.4, 0.065, 2);
    box(0.22, 0.02, 3.7, '#eeeadd', scene, 3.4, 0.065, -2);
    // Modest neighborhood buildings, pitched roofs, windows and tropical trees.
    for (let i = 0; i < 8; i++) {
        const x = -26 + i * 8, z = -12 - (i % 2);
        box(6, 3.8, 5, ['#ead8b9', '#d8e5df', '#eee8cf'][i % 3], scene, x, 1.9, z);
        const roof = mesh(new THREE.CylinderGeometry(0, 4.8, 1.6, 4), '#98624d', scene, x, 4.5, z);
        roof.rotation.y = Math.PI / 4;
        for (const offset of [-1.8, 1.8]) {
            box(1.15, 1.3, 0.05, '#456372', scene, x + offset, 2.1, z + 2.53);
            box(0.07, 1.3, 0.09, '#eee8d6', scene, x + offset, 2.1, z + 2.57);
        }
        box(0.95, 2.4, 0.08, '#797460', scene, x, 1.2, z + 2.55);
        mesh(new THREE.CylinderGeometry(0.15, 0.23, 4, 10), '#706049', scene, x + 3.5, 2, -8);
        const crown = mesh(new THREE.SphereGeometry(1.5, 14, 12), '#3e7152', scene, x + 3.5, 4.6, -8);
        crown.scale.set(1, 1.3, 1);
    }
    function car(color, x, z, direction) {
        const group = new THREE.Group();
        scene.add(group);
        box(4.3, 0.65, 1.85, color, group, 0, 0.7, 0, 0.5);
        box(2.35, 0.65, 1.65, color, group, -0.2, 1.32, 0, 0.5);
        box(2.1, 0.43, 1.68, '#294554', group, -0.2, 1.36, 0, 0.45);
        box(0.14, 0.58, 1.71, color, group, -0.3, 1.35, 0);
        box(2.38, 0.1, 1.7, color, group, -0.2, 1.7, 0, 0.5);
        for (const wx of [-1.3, 1.3]) for (const wz of [-0.94, 0.94]) {
            const wheel = mesh(new THREE.CylinderGeometry(0.36, 0.36, 0.22, 24), '#20252a', group, wx, 0.42, wz);
            wheel.rotation.x = Math.PI / 2;
            const hub = mesh(new THREE.CylinderGeometry(0.2, 0.2, 0.24, 16), '#9ca6aa', group, wx, 0.42, wz, 0.8);
            hub.rotation.x = Math.PI / 2;
        }
        for (const z of [-0.6, 0.6]) box(0.05, 0.18, 0.4, '#fff4c9', group, 2.17, 0.85, z);
        group.position.set(x, 0, z);
        group.rotation.y = direction < 0 ? Math.PI : 0;
        return group;
    }
    const cars = [car('#237c9d', -17, 2, 1), car('#e1dfd3', 19, -2, -1)];
    const person = new THREE.Group();
    scene.add(person);
    mesh(new THREE.CapsuleGeometry(0.22, 0.42, 6, 12), '#eea93c', person, 0, 1.13, 0);
    mesh(new THREE.SphereGeometry(0.21, 20, 16), '#ae7651', person, 0, 1.7, 0);
    mesh(new THREE.SphereGeometry(0.215, 16, 12, 0, Math.PI * 2, 0, Math.PI / 2), '#362d28', person, 0, 1.74, 0);
    const limbs = [];
    for (const side of [-1, 1]) {
        const leg = new THREE.Group(); leg.position.set(side * 0.13, 0.85, 0); person.add(leg);
        mesh(new THREE.CapsuleGeometry(0.095, 0.5, 4, 10), '#344959', leg, 0, -0.33, 0);
        box(0.2, 0.12, 0.32, '#f0eadb', leg, 0, -0.69, -0.06);
        const arm = new THREE.Group(); arm.position.set(side * 0.29, 1.38, 0); person.add(arm);
        mesh(new THREE.CapsuleGeometry(0.065, 0.48, 4, 10), '#ae7651', arm, 0, -0.27, 0);
        limbs.push(leg, arm);
    }
    box(0.35, 0.42, 0.17, '#35696e', person, 0, 1.2, 0.27);
    const actors = ['actors', 'bus-stop'].includes(mode)
        ? addRoadActors({ scene, box, mesh, person, cars, variant: mode })
        : mode === 'crossing-movements' ? addCrossingMovements({ scene, box, mesh, person, cars }) : null;
    let step = 0, progress = 1, view = 'overview', frame = 0, disposed = false, paused = false;
    let starts = [], targets = [], last = performance.now();
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    function state(index) {
        step = index;
        actors?.state(index);
        starts = [person.position.clone(), ...cars.map(c => c.position.clone())];
        targets = [new THREE.Vector3(index === 0 ? 2.2 : 0, 0.16, index === 4 ? -5.2 : 5.3),
            new THREE.Vector3(index < 3 ? -17 + index * 4 : -5.9, 0, 2),
            new THREE.Vector3(index < 3 ? 19 - index * 4.5 : 5.9, 0, -2)];
        progress = reduced.matches ? 1 : 0;
    }
    if (!actors) person.position.set(2.2, 0.16, 5.3);
    state(0);
    function resize() {
        if (!host.clientWidth || !host.clientHeight) return;
        renderer.setSize(host.clientWidth, host.clientHeight, false);
        camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix();
    }
    const observer = new ResizeObserver(resize); observer.observe(host); resize();
    function render(now) {
        if (disposed) return;
        const dt = Math.min((now - last) / 1000, 0.05); last = now;
        if (host.getBoundingClientRect().bottom > 0 && host.getBoundingClientRect().top < innerHeight && !document.hidden) {
            if (!paused) progress = Math.min(1, progress + dt / (actors ? 6 : step === 4 ? 8 : 1.8));
            const ease = progress * progress * (3 - 2 * progress);
            if (actors) {
                actors.update(ease, view, camera);
            } else {
            const walk = step === 4 ? Math.max(0, (progress - 0.25) / 0.75) : ease;
            person.position.lerpVectors(starts[0], targets[0], walk);
            cars.forEach((item, i) => {
                const brake = step === 4 ? Math.min(1, progress * 4) : progress;
                item.position.lerpVectors(starts[i + 1], targets[i + 1], brake * brake * (3 - 2 * brake));
            });
            limbs.forEach((limb, i) => limb.rotation.x = walk > 0 && walk < 1 && (step === 0 || step === 4) ? Math.sin(walk * 38) * 0.35 * (i % 2 ? -1 : 1) : 0);
            person.rotation.y = step === 2 ? Math.sin(ease * Math.PI * 2) * 0.65 : 0;
            if (view === 'pedestrian') { camera.position.set(1.5, 2.1, 7.8); camera.lookAt(0, 0.8, -2); }
            else { camera.position.set(13, 13, 19); camera.lookAt(0, 0, 0); }
            }
            renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    frame = requestAnimationFrame(render);
    return {
        setStep: state,
        setPaused(value) { paused = value; },
        setView(value) { view = value; },
        dispose() {
            disposed = true; cancelAnimationFrame(frame); observer.disconnect();
            actors?.dispose();
            scene.traverse(object => object.geometry?.dispose());
            materials.forEach(value => value.dispose()); renderer.dispose(); renderer.domElement.remove();
        },
    };
}
