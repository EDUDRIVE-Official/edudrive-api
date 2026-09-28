import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountRainyCrossingDecision(host) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#687b88');
    scene.fog = new THREE.Fog('#687b88', 28, 78);

    const camera = new THREE.PerspectiveCamera(44, 1, .1, 120);
    camera.position.set(23, 18, 28);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = .92;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Cruce peatonal tridimensional bajo lluvia intensa; Luna sostiene una sombrilla que limita su visión y debe volver a comprobar antes de cruzar');
    host.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(0, .6, 0);
    controls.enableDamping = true;
    controls.minDistance = 15;
    controls.maxDistance = 58;
    controls.maxPolarAngle = Math.PI * .48;
    controls.update();

    scene.add(new THREE.HemisphereLight(0xcce2ec, 0x293b36, 1.7));
    const skyLight = new THREE.DirectionalLight(0xd9e9f2, 2.2);
    skyLight.position.set(-14, 25, 16);
    skyLight.castShadow = true;
    skyLight.shadow.mapSize.set(2048, 2048);
    Object.assign(skyLight.shadow.camera, { left: -32, right: 32, top: 24, bottom: -24 });
    scene.add(skyLight);

    const disposableMaterials = [];
    const standard = (color, options = {}) => {
        const value = new THREE.MeshStandardMaterial({ color, roughness: .72, ...options });
        disposableMaterials.push(value);
        return value;
    };
    const mesh = (geometry, material, parent, x, y, z) => {
        const object = new THREE.Mesh(geometry, material);
        object.position.set(x, y, z);
        object.castShadow = object.receiveShadow = true;
        parent.add(object);
        return object;
    };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);

    const grass = standard('#466a4d');
    const wetRoad = standard('#29353d', { roughness: .2, metalness: .34 });
    const sidewalk = standard('#9ba4a4', { roughness: .36, metalness: .08 });
    box(72, .18, 40, grass, scene, 0, -.18, 0);
    box(70, .14, 12, wetRoad, scene, 0, -.02, 0);
    for (const z of [-7.25, 7.25]) box(70, .24, 3, sidewalk, scene, 0, .04, z);
    for (let x = -31; x <= 31; x += 4.2) box(2.1, .025, .1, standard('#d8b844'), scene, x, .07, 0);
    for (let z = -5.5; z <= 5.5; z += 1.12) box(3.5, .035, .62, standard('#e9ece8'), scene, 0, .09, z);
    for (const z of [-5.9, 5.9]) box(4, .12, 1.55, standard('#b6bdbc'), scene, 0, .08, z);

    // Charcos y reflejos sobre el pavimento mojado.
    const puddleMaterial = standard('#8db6c4', { roughness: .08, metalness: .5, transparent: true, opacity: .5 });
    for (const [x, z, sx, sz] of [[-14, 2.7, 3, .6], [11, -3.7, 2.3, .5], [18, 3.8, 1.8, .42], [-5, -2, 1.4, .35]]) {
        const puddle = mesh(new THREE.CircleGeometry(1, 28), puddleMaterial, scene, x, .075, z);
        puddle.rotation.x = -Math.PI / 2;
        puddle.scale.set(sx, sz, 1);
    }

    // Casas, árboles y faroles: un barrio reconocible incluso con poca visibilidad.
    for (const [x, z, color] of [[-19, -14, '#9ba6a1'], [18, -14, '#929b9e'], [-20, 14, '#a49b91'], [20, 14, '#899a94']]) {
        box(10, 4.2, 6.5, standard(color), scene, x, 2, z);
        const roof = mesh(new THREE.CylinderGeometry(0, 7.1, 1.8, 4), standard('#5f5d5b'), scene, x, 4.9, z);
        roof.rotation.y = Math.PI / 4;
    }
    for (const [x, z] of [[-10, -11], [9, -11], [-11, 11], [11, 11], [28, -10]]) {
        mesh(new THREE.CylinderGeometry(.13, .2, 3.5, 10), standard('#4c463d'), scene, x, 1.7, z);
        const crown = mesh(new THREE.SphereGeometry(1.35, 14, 10), standard('#41664b'), scene, x, 4, z);
        crown.scale.y = 1.2;
    }
    const lampMaterial = standard('#222e35', { metalness: .65 });
    const glowMaterial = standard('#fff0b5', { emissive: '#ffd45a', emissiveIntensity: 2.8 });
    for (const [x, z] of [[-12, -7], [12, 7]]) {
        mesh(new THREE.CylinderGeometry(.08, .11, 4.2, 12), lampMaterial, scene, x, 2.1, z);
        mesh(new THREE.SphereGeometry(.22, 14, 10), glowMaterial, scene, x, 4.2, z);
    }

    function person(shirt, withUmbrella = false) {
        const group = new THREE.Group();
        scene.add(group);
        mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), standard(shirt), group, 0, 1.2, 0);
        mesh(new THREE.SphereGeometry(.22, 18, 14), standard('#a96f4d'), group, 0, 1.8, 0);
        mesh(new THREE.SphereGeometry(.23, 14, 10, 0, Math.PI * 2, 0, Math.PI / 2), standard('#322c2b'), group, 0, 1.84, 0);
        const limbs = [];
        for (const side of [-1, 1]) {
            const leg = new THREE.Group(); leg.position.set(side * .14, .92, 0); group.add(leg);
            mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), standard('#34475b'), leg, 0, -.34, 0); limbs.push(leg);
            const arm = new THREE.Group(); arm.position.set(side * .28, 1.45, 0); group.add(arm);
            mesh(new THREE.CapsuleGeometry(.065, .44, 4, 10), standard('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm);
        }
        let umbrella = null;
        if (withUmbrella) {
            umbrella = new THREE.Group(); umbrella.position.set(.18, 1.6, 0); group.add(umbrella);
            mesh(new THREE.CylinderGeometry(.025, .025, 2.15, 10), standard('#c3cbd0', { metalness: .7 }), umbrella, 0, .48, 0);
            const canopy = mesh(new THREE.SphereGeometry(1.05, 24, 10, 0, Math.PI * 2, 0, Math.PI / 2), standard('#e8b329', { roughness: .45 }), umbrella, 0, 1.48, 0);
            canopy.scale.set(1, .45, 1);
        }
        return { group, limbs, umbrella };
    }
    const luna = person('#e88736', true);
    const leader = person('#397890');

    function car(color) {
        const group = new THREE.Group(); scene.add(group);
        box(4.2, .72, 1.85, standard(color, { roughness: .3, metalness: .5 }), group, 0, .72, 0);
        box(2.25, .7, 1.62, standard(color, { roughness: .3, metalness: .5 }), group, -.25, 1.4, 0);
        box(1.9, .42, 1.66, standard('#243f4d', { roughness: .15, metalness: .25 }), group, -.25, 1.45, 0);
        for (const x of [-1.25, 1.25]) for (const z of [-.92, .92]) {
            const tire = mesh(new THREE.CylinderGeometry(.36, .36, .2, 20), standard('#202629'), group, x, .42, z); tire.rotation.x = Math.PI / 2;
        }
        for (const z of [-.58, .58]) mesh(new THREE.SphereGeometry(.16, 12, 8), glowMaterial, group, -2.08, .78, z);
        return group;
    }
    const approachingCar = car('#285f88');
    approachingCar.rotation.y = Math.PI;

    const visibilityMaterial = new THREE.MeshBasicMaterial({ color: '#e99b2b', transparent: true, opacity: .3, side: THREE.DoubleSide, depthWrite: false });
    disposableMaterials.push(visibilityMaterial);
    const visibilityWedge = new THREE.Mesh(new THREE.BufferGeometry().setFromPoints([
        new THREE.Vector3(0, .2, 5.6), new THREE.Vector3(-18, .2, -1.6), new THREE.Vector3(8, .2, -1.6),
    ]), visibilityMaterial);
    visibilityWedge.geometry.setIndex([0, 1, 2]); visibilityWedge.geometry.computeVertexNormals(); scene.add(visibilityWedge);

    const routeMaterial = new THREE.MeshStandardMaterial({ color: '#278653', emissive: '#278653', emissiveIntensity: .7 });
    disposableMaterials.push(routeMaterial);
    const crossingCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(0, .18, 7), new THREE.Vector3(0, .18, 0), new THREE.Vector3(0, .18, -7)]);
    const safeRoute = new THREE.Mesh(new THREE.TubeGeometry(crossingCurve, 72, .11, 9), routeMaterial); safeRoute.visible = false; scene.add(safeRoute);
    const riskMaterial = new THREE.MeshStandardMaterial({ color: '#d83b35', emissive: '#d83b35', emissiveIntensity: 1.4, side: THREE.DoubleSide });
    disposableMaterials.push(riskMaterial);
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.05, 1.42, 40), riskMaterial); risk.rotation.x = -Math.PI / 2; risk.position.set(0, .2, -1.7); risk.visible = false; scene.add(risk);

    // Lluvia local: partículas procedurales, sin descargar recursos externos.
    const rainCount = 1350;
    const rainPositions = new Float32Array(rainCount * 3);
    const rainSpeeds = new Float32Array(rainCount);
    for (let i = 0; i < rainCount; i++) {
        rainPositions[i * 3] = (Math.random() - .5) * 68;
        rainPositions[i * 3 + 1] = Math.random() * 22;
        rainPositions[i * 3 + 2] = (Math.random() - .5) * 38;
        rainSpeeds[i] = 15 + Math.random() * 12;
    }
    const rainGeometry = new THREE.BufferGeometry(); rainGeometry.setAttribute('position', new THREE.BufferAttribute(rainPositions, 3));
    const rainMaterial = new THREE.PointsMaterial({ color: '#c6e9ff', size: .09, transparent: true, opacity: .72, depthWrite: false });
    const rain = new THREE.Points(rainGeometry, rainMaterial); scene.add(rain);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview';
    let disposed = false, frame = 0, last = performance.now();
    const reset = () => {
        luna.group.position.set(0, .1, 7); luna.group.rotation.set(0, Math.PI, 0); luna.umbrella.rotation.set(0, 0, -.45);
        leader.group.position.set(-1.8, .1, 7); leader.group.rotation.set(0, Math.PI, 0); leader.group.visible = false;
        approachingCar.position.set(25, 0, -1.7);
        [...luna.limbs, ...leader.limbs].forEach(limb => limb.rotation.x = 0);
        safeRoute.visible = risk.visible = false; visibilityWedge.visible = true;
    };
    const setOutcome = value => {
        outcome = value; progress = 0; reset();
        safeRoute.visible = value === 'adjust'; risk.visible = value === 'rush' || value === 'follow'; leader.group.visible = value === 'follow';
    };
    const movePerson = (actor, t, delay = 0, running = false) => {
        const adjusted = Math.max(0, Math.min(1, (t - delay) / (1 - delay)));
        const point = crossingCurve.getPointAt(Math.min(.999, adjusted)); const ahead = crossingCurve.getPointAt(Math.min(1, adjusted + .01));
        actor.group.position.copy(point); actor.group.lookAt(ahead.x, point.y, ahead.z);
        actor.limbs.forEach((limb, index) => limb.rotation.x = Math.sin(adjusted * (running ? 54 : 34)) * (running ? .6 : .34) * (index % 2 ? -1 : 1));
    };

    const observer = new ResizeObserver(() => {
        if (!host.clientWidth || !host.clientHeight) return;
        renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix();
    }); observer.observe(host);

    function render(now) {
        if (disposed) return;
        const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now;
        const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused) {
                const positions = rainGeometry.attributes.position.array;
                for (let i = 0; i < rainCount; i++) {
                    positions[i * 3 + 1] -= rainSpeeds[i] * dt;
                    positions[i * 3] -= 2.5 * dt;
                    if (positions[i * 3 + 1] < 0) { positions[i * 3 + 1] = 22; positions[i * 3] = (Math.random() - .5) * 68; }
                }
                rainGeometry.attributes.position.needsUpdate = true;
                if (outcome !== 'intro') progress = Math.min(1, progress + dt / (outcome === 'adjust' ? 9 : 5));
            }
            const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'adjust') {
                luna.umbrella.rotation.z = -.45 + Math.min(1, ease * 4) * .7;
                visibilityWedge.material.opacity = .3 * (1 - Math.min(1, ease * 4));
                approachingCar.position.x = 25 - Math.min(1, ease * 1.65) * 51;
                if (ease > .64) movePerson(luna, (ease - .64) / .36);
            } else if (outcome === 'rush') {
                movePerson(luna, ease, 0, true); approachingCar.position.x = 25 - ease * 51;
            } else if (outcome === 'follow') {
                movePerson(leader, ease); movePerson(luna, ease, .17); approachingCar.position.x = 25 - ease * 51;
            } else approachingCar.position.x = 25 - ((now * .0014) % 1) * 51;
            if (risk.visible) { const pulse = 1 + Math.sin(now * .008) * .15; risk.scale.setScalar(pulse); }
            if (view === 'pedestrian') { camera.position.set(-2.8, 2.5, 9.4); camera.lookAt(0, .8, -3); }
            else if (view === 'driver') { camera.position.set(5, 1.75, -1.7); camera.lookAt(-3, .8, 1.8); }
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
            if (controls.enabled) { camera.position.set(23, 18, 28); controls.target.set(0, .6, 0); controls.update(); }
        },
        dispose() {
            disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose();
            scene.traverse(object => object.geometry?.dispose()); disposableMaterials.forEach(value => value.dispose());
            rainMaterial.dispose(); renderer.dispose(); renderer.domElement.remove();
        },
    };
}
