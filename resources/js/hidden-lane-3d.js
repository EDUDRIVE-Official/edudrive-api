import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountHiddenLane(host) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#c8e3ef');
    scene.fog = new THREE.Fog('#c8e3ef', 45, 115);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 140);
    camera.position.set(24, 23, 29);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.75));
    renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.12;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Paso peatonal tridimensional con un automóvil detenido en el carril cercano y una motocicleta que aparece por el carril oculto');
    host.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(0, 0, 0); controls.enableDamping = true; controls.minDistance = 18; controls.maxDistance = 65; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x58724d, 2.5));
    const sun = new THREE.DirectionalLight(0xffefd3, 3.25);
    sun.position.set(-16, 29, 20); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048);
    Object.assign(sun.shadow.camera, { left: -34, right: 34, top: 26, bottom: -26 }); sun.shadow.bias = -.0004; scene.add(sun);

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

    box(76, .2, 43, '#789a6c', scene, 0, -.2, 0);
    box(72, .12, 11, '#474f55', scene, 0, -.02, 0);
    for (const z of [-7, 7]) box(72, .24, 3.2, '#c9c8be', scene, 0, .04, z);
    for (let x = -32; x < 33; x += 4) box(2, .025, .1, '#e6c661', scene, x, .07, 0);
    for (let z = -5; z <= 5; z += 1.05) box(3.3, .035, .58, '#f4f2e8', scene, 0, .09, z);
    for (const z of [-5.65, 5.65]) box(3.6, .1, 1.6, '#dfddd1', scene, 0, .08, z);
    box(.18, .035, 4.2, '#f4f2e8', scene, -4.6, .09, 2.45);

    // Barrio y elementos reconocibles a ambos lados de la vía.
    for (const [x, z, color] of [[-18, -13, '#e4d2b3'], [17, -14, '#d6e2dc'], [-18, 14, '#e9dfca'], [18, 14, '#d8cdbb']]) {
        box(10, 4.4, 7, color, scene, x, 2.1, z);
        const roof = mesh(new THREE.CylinderGeometry(0, 7.3, 1.8, 4), '#935f49', scene, x, 5.05, z); roof.rotation.y = Math.PI / 4;
    }
    for (const [x, z] of [[-10, -11], [9, -11], [-10, 11], [10, 11], [27, -10]]) {
        mesh(new THREE.CylinderGeometry(.15, .24, 3.6, 10), '#6d5941', scene, x, 1.75, z);
        const crown = mesh(new THREE.SphereGeometry(1.5, 14, 11), '#3e8050', scene, x, 4.2, z); crown.scale.y = 1.25;
    }

    function wheel(parent, x, z, radius = .37) {
        const tire = mesh(new THREE.CylinderGeometry(radius, radius, .22, 22), '#20262b', parent, x, radius + .07, z); tire.rotation.x = Math.PI / 2;
        const hub = mesh(new THREE.CylinderGeometry(radius * .48, radius * .48, .24, 16), '#a8b1b5', parent, x, radius + .07, z, .7); hub.rotation.x = Math.PI / 2;
    }
    const stoppedCar = new THREE.Group(); scene.add(stoppedCar);
    box(4.4, .72, 1.9, '#267795', stoppedCar, 0, .76, 0, .45);
    box(2.35, .7, 1.68, '#267795', stoppedCar, -.25, 1.45, 0, .45);
    box(2.05, .45, 1.72, '#294957', stoppedCar, -.25, 1.48, 0, .35);
    for (const x of [-1.3, 1.3]) for (const z of [-.98, .98]) wheel(stoppedCar, x, z);
    stoppedCar.position.set(-7, 0, 2.45);
    for (const z of [-.62, .62]) box(.05, .22, .4, '#e73a32', stoppedCar, 2.2, .85, z);

    const motorcycle = new THREE.Group(); scene.add(motorcycle);
    for (const x of [-.85, .85]) {
        const tire = mesh(new THREE.TorusGeometry(.4, .1, 10, 28), '#20262b', motorcycle, x, .45, 0);
        mesh(new THREE.CylinderGeometry(.12, .12, .18, 16), '#aeb8bd', motorcycle, x, .45, 0, .8).rotation.x = Math.PI / 2;
        tire.rotation.y = 0;
    }
    box(1.5, .22, .38, '#d9323f', motorcycle, 0, .75, 0, .45);
    const tank = mesh(new THREE.SphereGeometry(.48, 16, 12), '#df3545', motorcycle, .15, 1.02, 0, .45); tank.scale.set(1.25, .7, .75);
    box(.8, .13, .38, '#222a31', motorcycle, -.55, 1.08, 0);
    const rider = mesh(new THREE.CapsuleGeometry(.2, .5, 6, 12), '#1d5682', motorcycle, -.1, 1.55, 0); rider.rotation.z = -.3;
    mesh(new THREE.SphereGeometry(.23, 16, 12), '#f0c13e', motorcycle, .12, 2.05, 0, .3);
    motorcycle.rotation.y = Math.PI;

    function person(shirt) {
        const group = new THREE.Group(); scene.add(group);
        mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), shirt, group, 0, 1.18, 0);
        mesh(new THREE.SphereGeometry(.22, 18, 14), '#a96f4d', group, 0, 1.78, 0);
        mesh(new THREE.SphereGeometry(.23, 14, 10, 0, Math.PI * 2, 0, Math.PI / 2), '#392d27', group, 0, 1.82, 0);
        const limbs = [];
        for (const side of [-1, 1]) {
            const leg = new THREE.Group(); leg.position.set(side * .14, .9, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), '#34475b', leg, 0, -.34, 0); limbs.push(leg);
            const arm = new THREE.Group(); arm.position.set(side * .28, 1.43, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .45, 4, 10), '#a96f4d', arm, 0, -.27, 0); limbs.push(arm);
        }
        return { group, limbs };
    }
    const luna = person('#ef9c35');
    const leader = person('#4d78b7');

    const pathMaterial = new THREE.MeshStandardMaterial({ color: '#d93c35', emissive: '#d93c35', emissiveIntensity: .42, transparent: true, opacity: .88 });
    const crossingCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(0, 0, 7), new THREE.Vector3(0, 0, 2), new THREE.Vector3(0, 0, -7)], false, 'catmullrom', .08);
    const path = new THREE.Mesh(new THREE.TubeGeometry(crossingCurve, 90, .12, 10), pathMaterial); path.position.y = .16; path.visible = false; scene.add(path);
    const sightMaterial = new THREE.MeshBasicMaterial({ color: '#ed9828', transparent: true, opacity: .27, side: THREE.DoubleSide, depthWrite: false });
    const sight = new THREE.Mesh(new THREE.BufferGeometry().setFromPoints([
        new THREE.Vector3(0, .25, 5.4), new THREE.Vector3(-18, .25, -2.2), new THREE.Vector3(11, .25, -2.2),
    ]), sightMaterial); sight.geometry.setIndex([0, 1, 2]); sight.geometry.computeVertexNormals(); sight.visible = false; scene.add(sight);
    const riskMaterial = new THREE.MeshStandardMaterial({ color: '#d93732', emissive: '#d93732', emissiveIntensity: 1.1, side: THREE.DoubleSide });
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.05, 1.42, 40), riskMaterial); risk.rotation.x = -Math.PI / 2; risk.position.set(0, .18, -2.25); risk.visible = false; scene.add(risk);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview';
    let disposed = false, frame = 0, last = performance.now();
    const reset = () => {
        luna.group.position.set(0, .1, 7); luna.group.rotation.set(0, Math.PI, 0);
        leader.group.position.set(-2.1, .1, 7); leader.group.rotation.set(0, Math.PI, 0); leader.group.visible = false;
        [...luna.limbs, ...leader.limbs].forEach(limb => limb.rotation.x = 0);
        motorcycle.position.set(25, 0, -2.25); motorcycle.rotation.set(0, Math.PI, 0);
        path.visible = false; sight.visible = false; risk.visible = false;
    };
    const setOutcome = value => {
        outcome = value; progress = 0; reset();
        path.visible = value !== 'intro'; sight.visible = value !== 'intro';
        risk.visible = value === 'cross' || value === 'follow'; leader.group.visible = value === 'follow';
    };
    const observer = new ResizeObserver(() => {
        if (!host.clientWidth || !host.clientHeight) return;
        renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix();
    }); observer.observe(host);
    function movePerson(actor, t, delay = 0) {
        const adjusted = Math.max(0, Math.min(1, (t - delay) / (1 - delay)));
        const point = crossingCurve.getPointAt(Math.min(.999, adjusted)); const ahead = crossingCurve.getPointAt(Math.min(1, adjusted + .01));
        actor.group.position.copy(point); actor.group.lookAt(ahead.x, point.y, ahead.z);
        actor.limbs.forEach((limb, index) => limb.rotation.x = Math.sin(adjusted * 36) * .34 * (index % 2 ? -1 : 1));
    }
    function render(now) {
        if (disposed) return;
        const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now;
        const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / (outcome === 'check' ? 9 : 5));
            const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'check') {
                motorcycle.position.x = 25 - Math.min(1, ease * 1.7) * 51;
                if (ease > .62) movePerson(luna, (ease - .62) / .38);
            } else if (outcome === 'cross') {
                movePerson(luna, ease); motorcycle.position.x = 25 - ease * 51;
            } else if (outcome === 'follow') {
                movePerson(leader, ease); movePerson(luna, ease, .18); motorcycle.position.x = 25 - ease * 51;
            } else motorcycle.position.x = 25 - ((now * .0017) % 1) * 51;
            if (risk.visible) { const pulse = 1 + Math.sin(now * .007) * .14; risk.scale.setScalar(pulse); }
            if (view === 'pedestrian') { camera.position.set(-2.4, 2.6, 9.3); camera.lookAt(0, .8, -3); }
            else if (view === 'driver') { camera.position.set(-4.6, 1.8, 2.45); camera.lookAt(4, .8, -1); }
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
            if (controls.enabled) { camera.position.set(24, 23, 29); controls.target.set(0, 0, 0); controls.update(); }
        },
        dispose() {
            disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose();
            scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose());
            pathMaterial.dispose(); sightMaterial.dispose(); riskMaterial.dispose(); renderer.dispose(); renderer.domElement.remove();
        },
    };
}
