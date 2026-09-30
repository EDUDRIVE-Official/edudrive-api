import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountRiskRadar(host) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#b9dbea');
    scene.fog = new THREE.Fog('#b9dbea', 45, 110);
    const camera = new THREE.PerspectiveCamera(44, 1, .1, 150);
    camera.position.set(25, 19, 30);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    host.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(0, .5, 0); controls.enableDamping = true; controls.minDistance = 15; controls.maxDistance = 65; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0xd8eeff, 0x315038, 2.15));
    const sun = new THREE.DirectionalLight(0xffffff, 2.25); sun.position.set(-18, 30, 17); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048); Object.assign(sun.shadow.camera, { left: -40, right: 40, top: 28, bottom: -28 }); scene.add(sun);

    const materials = [];
    const mat = (color, options = {}) => { const material = new THREE.MeshStandardMaterial({ color, roughness: .72, ...options }); materials.push(material); return material; };
    const mesh = (geometry, material, parent, x, y, z) => { const object = new THREE.Mesh(geometry, material); object.position.set(x, y, z); object.castShadow = object.receiveShadow = true; parent.add(object); return object; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    const road = mat('#4b5963'); const sidewalk = mat('#c2c0bc'); const grass = mat('#75a76c'); const stripe = mat('#f0cf47');
    box(84, .2, 44, grass, scene, 0, -.22, 0); box(80, .14, 13, road, scene, 0, -.03, 0);
    for (const z of [-7.7, 7.7]) box(80, .25, 3.6, sidewalk, scene, 0, .04, z);
    for (let x = -36; x <= 36; x += 4.6) box(2.3, .025, .1, stripe, scene, x, .08, 0);

    for (const [x, z] of [[-25, -14], [23, -14], [-24, 14], [24, 14]]) {
        box(12, 4.5, 7, mat('#d8d7c8'), scene, x, 2.15, z);
        const roof = mesh(new THREE.CylinderGeometry(0, 8, 1.8, 4), mat('#a9674b'), scene, x, 5.15, z); roof.rotation.y = Math.PI / 4;
    }

    function person(parent, color = '#e98c22') {
        const group = new THREE.Group(); parent.add(group);
        mesh(new THREE.CapsuleGeometry(.23, .5, 6, 12), mat(color), group, 0, 1.2, 0);
        mesh(new THREE.SphereGeometry(.22, 18, 14), mat('#a96f4d'), group, 0, 1.82, 0);
        const limbs = [];
        for (const side of [-1, 1]) {
            const leg = new THREE.Group(); leg.position.set(side * .14, .93, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), mat('#243047'), leg, 0, -.34, 0); limbs.push(leg);
            const arm = new THREE.Group(); arm.position.set(side * .28, 1.47, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .44, 4, 10), mat('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm);
        }
        return { group, limbs };
    }
    function wheel(parent, x, z) { const value = mesh(new THREE.CylinderGeometry(.38, .38, .23, 20), mat('#14191d'), parent, x, .42, z); value.rotation.x = Math.PI / 2; }
    function car(parent, color = '#247da6') {
        const group = new THREE.Group(); parent.add(group); const body = mat(color, { roughness: .3, metalness: .45 });
        box(4.5, .75, 1.95, body, group, 0, .75, 0); box(2.35, .74, 1.7, body, group, -.25, 1.46, 0); box(2.05, .45, 1.72, mat('#1a3444'), group, -.25, 1.5, 0);
        for (const x of [-1.34, 1.34]) for (const z of [-.98, .98]) wheel(group, x, z);
        return group;
    }
    function motorcycle(parent) {
        const group = new THREE.Group(); parent.add(group);
        for (const x of [-.85, .85]) { const tire = mesh(new THREE.TorusGeometry(.38, .1, 10, 20), mat('#171b20'), group, x, .48, 0); tire.rotation.y = Math.PI / 2; }
        box(1.45, .28, .48, mat('#cf3346', { metalness: .35 }), group, 0, .75, 0);
        mesh(new THREE.CylinderGeometry(.09, .09, 1.05, 10), mat('#333a40'), group, .55, 1.15, 0).rotation.z = -.35;
        const rider = person(group, '#2f6da2'); rider.group.scale.setScalar(.68); rider.group.position.set(-.05, .62, 0);
        return group;
    }
    function bus(parent) {
        const group = new THREE.Group(); parent.add(group); const body = mat('#e7b62d', { roughness: .38 });
        box(7.6, 2.65, 2.55, body, group, 0, 1.55, 0); box(7.2, .9, 2.58, mat('#f2efe2'), group, 0, 2.56, 0);
        for (const x of [-2.3, -.75, .8, 2.35]) box(1.05, .72, .05, mat('#4ca9cf', { emissive: '#17384b', emissiveIntensity: .35 }), group, x, 2.55, -1.3);
        for (const x of [-2.5, 2.5]) for (const z of [-1.3, 1.3]) wheel(group, x, z);
        return group;
    }
    function bicycle(parent) {
        const group = new THREE.Group(); parent.add(group); const tireMat = mat('#252a30');
        for (const x of [-.75, .75]) { const tire = mesh(new THREE.TorusGeometry(.4, .06, 10, 22), tireMat, group, x, .47, 0); tire.rotation.y = Math.PI / 2; }
        const frame = mat('#e78530', { metalness: .3 });
        const barA = mesh(new THREE.CylinderGeometry(.045, .045, 1.08, 8), frame, group, 0, .67, 0); barA.rotation.z = Math.PI / 2;
        const barB = mesh(new THREE.CylinderGeometry(.045, .045, .95, 8), frame, group, -.2, .72, 0); barB.rotation.z = -.55;
        return group;
    }

    const blind = new THREE.Group(); scene.add(blind);
    const blindWalker = person(blind); blindWalker.group.position.set(-13, .1, 7.5);
    const blindBus = bus(blind); blindBus.position.set(1, 0, 2.6); blindBus.rotation.y = Math.PI;
    const hiddenMotorcycle = motorcycle(blind); hiddenMotorcycle.position.set(5.1, 0, 2.6); hiddenMotorcycle.rotation.y = Math.PI;

    const rain = new THREE.Group(); scene.add(rain);
    const rainWalker = person(rain, '#e2b631'); rainWalker.group.position.set(-12, .1, 7.5);
    const umbrella = mesh(new THREE.SphereGeometry(1.25, 18, 10, 0, Math.PI * 2, 0, Math.PI / 2), mat('#284f83'), rain, -12, 2.65, 7.5); umbrella.scale.y = .45;
    const rainCar = car(rain, '#697681'); rainCar.position.set(12, 0, 2.7); rainCar.rotation.y = Math.PI;
    const puddleMaterial = mat('#329dc7', { transparent: true, opacity: .65, metalness: .25 });
    const puddle = mesh(new THREE.CircleGeometry(3.3, 40), puddleMaterial, rain, 1, .13, 5.35); puddle.rotation.x = -Math.PI / 2;
    const rainPositions = new Float32Array(240 * 3);
    for (let index = 0; index < rainPositions.length; index += 3) { rainPositions[index] = Math.random() * 70 - 35; rainPositions[index + 1] = Math.random() * 17 + 1; rainPositions[index + 2] = Math.random() * 30 - 15; }
    const rainGeometry = new THREE.BufferGeometry(); rainGeometry.setAttribute('position', new THREE.BufferAttribute(rainPositions, 3));
    const rainMaterial = new THREE.PointsMaterial({ color: '#d7efff', size: .12, transparent: true, opacity: .8 }); materials.push(rainMaterial);
    const drops = new THREE.Points(rainGeometry, rainMaterial); rain.add(drops);

    const doorScene = new THREE.Group(); scene.add(doorScene);
    const parkedCar = car(doorScene, '#a83d43'); parkedCar.position.set(5, 0, 4.2); parkedCar.rotation.y = Math.PI;
    const cyclist = bicycle(doorScene); cyclist.position.set(-14, 0, 4.9);
    const doorPivot = new THREE.Group(); doorPivot.position.set(5.85, 1.05, 5.2); doorScene.add(doorPivot);
    const door = box(1.7, 1.25, .12, mat('#a83d43', { metalness: .4 }), doorPivot, -.82, 0, 0);
    box(1.25, .48, .13, mat('#1a3444'), doorPivot, -.67, .28, 0);

    const warningMaterial = new THREE.MeshStandardMaterial({ color: '#ef4444', emissive: '#ef4444', emissiveIntensity: 1.6, side: THREE.DoubleSide }); materials.push(warningMaterial);
    const warning = new THREE.Mesh(new THREE.RingGeometry(1.15, 1.52, 40), warningMaterial); warning.rotation.x = -Math.PI / 2; warning.visible = false; scene.add(warning);

    let mode = 'oculto', target = 0, progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();
    function resetObjects() {
        hiddenMotorcycle.position.set(5.1, 0, 2.6); hiddenMotorcycle.visible = false;
        puddle.scale.setScalar(.12); puddleMaterial.opacity = .25;
        cyclist.position.set(-14, 0, 4.9); doorPivot.rotation.y = 0;
        warning.visible = false;
    }
    function applyVisibility() { blind.visible = mode === 'oculto'; rain.visible = mode === 'lluvia'; doorScene.visible = mode === 'puerta'; }
    function setScene(value, revealed = false) { mode = value; target = revealed ? 1 : 0; progress = 0; resetObjects(); applyVisibility(); scene.background.set(value === 'lluvia' ? '#718493' : '#b9dbea'); scene.fog.color.copy(scene.background); scene.fog.near = value === 'lluvia' ? 22 : 45; }
    function reveal(value) { target = value ? 1 : 0; if (value) progress = 0; else resetObjects(); }

    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);
    function render(now) {
        if (disposed) return;
        const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now;
        const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused) progress += (target - progress) * Math.min(1, dt * 2.7);
            const ease = progress * progress * (3 - 2 * progress);
            warning.visible = target > 0;
            if (mode === 'oculto') {
                hiddenMotorcycle.visible = progress > .03; hiddenMotorcycle.position.x = 5.1 - ease * 14; warning.position.set(-2.4, .18, 2.6);
            } else if (mode === 'lluvia') {
                puddle.scale.setScalar(.12 + ease * .88); puddleMaterial.opacity = .25 + ease * .48; warning.position.set(1, .18, 5.35);
                if (!paused) { const positions = drops.geometry.attributes.position.array; for (let i = 1; i < positions.length; i += 3) { positions[i] -= dt * 17; if (positions[i] < .3) positions[i] = 18; } drops.geometry.attributes.position.needsUpdate = true; }
            } else {
                cyclist.position.x = -14 + ease * 15.5; doorPivot.rotation.y = -ease * 1.32; warning.position.set(4.2, .18, 5.1);
            }
            if (warning.visible) { const pulse = 1 + Math.sin(now * .009) * .16; warning.scale.setScalar(pulse); }
            if (view === 'walker') {
                const actor = mode === 'oculto' ? blindWalker.group : mode === 'lluvia' ? rainWalker.group : cyclist;
                camera.position.set(actor.position.x + 1.1, 2.45, actor.position.z + 2.2); camera.lookAt(mode === 'puerta' ? 5 : 1, 1, mode === 'oculto' ? 2.6 : 4.5);
            } else if (view === 'hazard') {
                const focus = mode === 'oculto' ? hiddenMotorcycle.position : mode === 'lluvia' ? puddle.position : doorPivot.position;
                camera.position.set(focus.x + 4, 3.2, focus.z + 5); camera.lookAt(focus.x, .8, focus.z);
            } else controls.update();
            renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setScene('oculto', false); frame = requestAnimationFrame(render);
    return {
        setScene, reveal, setPaused(value) { paused = value; },
        setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) { camera.position.set(25, 19, 30); controls.target.set(0, .5, 0); controls.update(); } },
        dispose() { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(material => material.dispose()); renderer.dispose(); renderer.domElement.remove(); },
    };
}
