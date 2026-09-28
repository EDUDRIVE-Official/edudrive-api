import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountChangingConditions(host) {
    const scene = new THREE.Scene();
    const clearSky = new THREE.Color('#c9e6f3');
    scene.background = clearSky.clone();
    scene.fog = new THREE.Fog(clearSky, 45, 115);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 140);
    camera.position.set(22, 22, 29);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.75));
    renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.12;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'El mismo cruce peatonal tridimensional comparado con clima claro, lluvia y noche');
    host.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(0, 0, 0); controls.enableDamping = true; controls.minDistance = 18; controls.maxDistance = 65; controls.maxPolarAngle = Math.PI * .48; controls.update();
    const hemisphere = new THREE.HemisphereLight(0xeaf8ff, 0x58724d, 2.5); scene.add(hemisphere);
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
    const roadMaterial = new THREE.MeshStandardMaterial({ color: '#474f55', roughness: .8, metalness: 0 });
    const road = new THREE.Mesh(new THREE.BoxGeometry(72, .12, 11), roadMaterial); road.position.y = -.02; road.receiveShadow = true; scene.add(road);
    for (const z of [-7, 7]) box(72, .24, 3.2, '#c9c8be', scene, 0, .04, z);
    for (let x = -32; x < 33; x += 4) box(2, .025, .1, '#e6c661', scene, x, .07, 0);
    for (let z = -5; z <= 5; z += 1.05) box(3.4, .035, .58, '#f4f2e8', scene, 0, .09, z);
    for (const z of [-5.65, 5.65]) box(3.6, .1, 1.6, '#dfddd1', scene, 0, .08, z);
    for (const [x, z, color] of [[-18, -13, '#e4d2b3'], [17, -14, '#d6e2dc'], [-18, 14, '#e9dfca'], [18, 14, '#d8cdbb']]) {
        box(10, 4.4, 7, color, scene, x, 2.1, z);
        const roof = mesh(new THREE.CylinderGeometry(0, 7.3, 1.8, 4), '#935f49', scene, x, 5.05, z); roof.rotation.y = Math.PI / 4;
    }
    for (const [x, z] of [[-10, -11], [9, -11], [-10, 11], [10, 11]]) {
        mesh(new THREE.CylinderGeometry(.15, .24, 3.6, 10), '#6d5941', scene, x, 1.75, z);
        const crown = mesh(new THREE.SphereGeometry(1.5, 14, 11), '#3e8050', scene, x, 4.2, z); crown.scale.y = 1.25;
    }
    const lamps = [];
    const lampGlowMaterial = new THREE.MeshStandardMaterial({ color: '#fff0a8', emissive: '#ffd766', emissiveIntensity: 0, roughness: .35 });
    for (const x of [-13, 13]) for (const z of [-6.2, 6.2]) {
        mesh(new THREE.CylinderGeometry(.08, .1, 4.8, 10), '#48515a', scene, x, 2.4, z, .7);
        const glow = new THREE.Mesh(new THREE.SphereGeometry(.24, 14, 10), lampGlowMaterial); glow.position.set(x, 4.75, z); scene.add(glow);
        const light = new THREE.PointLight(0xffdd83, 0, 15, 2); light.position.set(x, 4.55, z); scene.add(light); lamps.push(light);
    }

    function wheel(parent, x, z) {
        const tire = mesh(new THREE.CylinderGeometry(.37, .37, .22, 22), '#20262b', parent, x, .44, z); tire.rotation.x = Math.PI / 2;
        const hub = mesh(new THREE.CylinderGeometry(.18, .18, .24, 16), '#a8b1b5', parent, x, .44, z, .7); hub.rotation.x = Math.PI / 2;
    }
    function car(color, direction) {
        const group = new THREE.Group(); scene.add(group);
        box(4.4, .72, 1.9, color, group, 0, .76, 0, .45); box(2.35, .7, 1.68, color, group, -.25, 1.45, 0, .45);
        box(2.05, .45, 1.72, '#294957', group, -.25, 1.48, 0, .35); for (const x of [-1.3, 1.3]) for (const z of [-.98, .98]) wheel(group, x, z);
        group.rotation.y = direction < 0 ? Math.PI : 0;
        const headlightMaterial = new THREE.MeshStandardMaterial({ color: '#fff4c0', emissive: '#ffe477', emissiveIntensity: 0, roughness: .3 });
        for (const z of [-.62, .62]) { const lamp = new THREE.Mesh(new THREE.BoxGeometry(.07, .22, .4), headlightMaterial); lamp.position.set(2.22, .88, z); group.add(lamp); }
        const beams = [];
        for (const z of [-.6, .6]) {
            const beamMaterial = new THREE.MeshBasicMaterial({ color: '#ffe9a0', transparent: true, opacity: 0, depthWrite: false, side: THREE.DoubleSide });
            const beam = new THREE.Mesh(new THREE.ConeGeometry(1.8, 9, 20, 1, true), beamMaterial); beam.rotation.z = -Math.PI / 2; beam.position.set(6.4, .75, z); group.add(beam); beams.push(beamMaterial);
        }
        return { group, headlightMaterial, beams };
    }
    const firstCar = car('#287c9a', 1); const secondCar = car('#d8d3c7', -1);
    const person = new THREE.Group(); scene.add(person);
    mesh(new THREE.CapsuleGeometry(.23, .48, 6, 12), '#ef9c35', person, 0, 1.18, 0);
    mesh(new THREE.SphereGeometry(.22, 18, 14), '#a96f4d', person, 0, 1.78, 0);
    mesh(new THREE.SphereGeometry(.23, 14, 10, 0, Math.PI * 2, 0, Math.PI / 2), '#392d27', person, 0, 1.82, 0);
    const reflectiveMaterial = new THREE.MeshStandardMaterial({ color: '#e9f7db', emissive: '#d7ff9b', emissiveIntensity: 0, roughness: .4 });
    const reflectiveBand = new THREE.Mesh(new THREE.BoxGeometry(.5, .12, .5), reflectiveMaterial); reflectiveBand.position.set(0, 1.28, 0); person.add(reflectiveBand);
    person.position.set(-2, .1, 7); person.rotation.y = Math.PI;

    const rainCount = 850; const rainPositions = new Float32Array(rainCount * 3);
    for (let i = 0; i < rainCount; i++) { rainPositions[i * 3] = (Math.random() - .5) * 68; rainPositions[i * 3 + 1] = Math.random() * 24; rainPositions[i * 3 + 2] = (Math.random() - .5) * 34; }
    const rainGeometry = new THREE.BufferGeometry(); rainGeometry.setAttribute('position', new THREE.BufferAttribute(rainPositions, 3));
    const rainMaterial = new THREE.PointsMaterial({ color: '#d8efff', size: .1, transparent: true, opacity: .72 });
    const rain = new THREE.Points(rainGeometry, rainMaterial); rain.visible = false; scene.add(rain);

    let condition = 'clear', paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();
    const setCondition = value => {
        condition = value;
        const rainMode = value === 'rain'; const night = value === 'night';
        const sky = new THREE.Color(night ? '#071526' : rainMode ? '#718494' : '#c9e6f3');
        scene.background.copy(sky); scene.fog.color.copy(sky); scene.fog.near = night ? 22 : rainMode ? 18 : 45; scene.fog.far = night ? 62 : rainMode ? 55 : 115;
        hemisphere.intensity = night ? .38 : rainMode ? 1.25 : 2.5; sun.intensity = night ? .1 : rainMode ? .75 : 3.25;
        renderer.toneMappingExposure = night ? .82 : rainMode ? .95 : 1.12;
        roadMaterial.color.set(night ? '#202a35' : rainMode ? '#303c44' : '#474f55'); roadMaterial.roughness = rainMode ? .18 : .8; roadMaterial.metalness = rainMode ? .18 : 0;
        rain.visible = rainMode; lampGlowMaterial.emissiveIntensity = night ? 2.4 : rainMode ? .8 : 0;
        lamps.forEach(light => { light.intensity = night ? 18 : rainMode ? 5 : 0; });
        [firstCar, secondCar].forEach(vehicle => {
            vehicle.headlightMaterial.emissiveIntensity = night ? 3 : rainMode ? 1.2 : 0;
            vehicle.beams.forEach(beam => { beam.opacity = night ? .16 : rainMode ? .06 : 0; });
        });
        reflectiveMaterial.emissiveIntensity = night ? 1.8 : rainMode ? .45 : 0;
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
                const speed = condition === 'clear' ? .006 : condition === 'rain' ? .0034 : .0042;
                firstCar.group.position.x = -32 + ((now * speed * .07) % 1) * 64; firstCar.group.position.z = 2.35;
                secondCar.group.position.x = 32 - ((now * speed * .06 + .35) % 1) * 64; secondCar.group.position.z = -2.35;
                if (rain.visible) {
                    const positions = rain.geometry.attributes.position.array;
                    for (let i = 0; i < rainCount; i++) { positions[i * 3 + 1] -= dt * 20; positions[i * 3] -= dt * 2.5; if (positions[i * 3 + 1] < 0) positions[i * 3 + 1] = 24; }
                    rain.geometry.attributes.position.needsUpdate = true;
                }
            }
            if (view === 'pedestrian') { camera.position.set(-4.3, 2.7, 9.2); camera.lookAt(2, .9, -2); } else controls.update();
            renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setCondition('clear'); frame = requestAnimationFrame(render);
    return {
        setCondition,
        setPaused(value) { paused = value; },
        setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) { camera.position.set(22, 22, 29); controls.target.set(0, 0, 0); controls.update(); } },
        dispose() {
            disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose();
            scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose());
            roadMaterial.dispose(); lampGlowMaterial.dispose(); reflectiveMaterial.dispose(); rainMaterial.dispose();
            [firstCar, secondCar].forEach(vehicle => { vehicle.headlightMaterial.dispose(); vehicle.beams.forEach(beam => beam.dispose()); });
            renderer.dispose(); renderer.domElement.remove();
        },
    };
}
