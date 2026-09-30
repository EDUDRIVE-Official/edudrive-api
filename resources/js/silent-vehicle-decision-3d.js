import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountSilentVehicleDecision(host) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#101a2a');
    scene.fog = new THREE.Fog('#101a2a', 38, 100);

    const camera = new THREE.PerspectiveCamera(43, 1, .1, 150);
    camera.position.set(26, 20, 31);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = .95;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Escena tridimensional nocturna con Luna esperando ante un paso peatonal, señal favorable y un vehículo eléctrico silencioso que se aproxima');
    host.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(3, .4, 0);
    controls.enableDamping = true;
    controls.minDistance = 17;
    controls.maxDistance = 64;
    controls.maxPolarAngle = Math.PI * .48;
    controls.update();

    scene.add(new THREE.HemisphereLight(0x607da5, 0x111b18, 1.25));
    const moonlight = new THREE.DirectionalLight(0x9ab8eb, 1.75);
    moonlight.position.set(-18, 28, 14);
    moonlight.castShadow = true;
    moonlight.shadow.mapSize.set(2048, 2048);
    Object.assign(moonlight.shadow.camera, { left: -40, right: 40, top: 28, bottom: -28 });
    scene.add(moonlight);

    const materials = [];
    const mat = (color, options = {}) => {
        const material = new THREE.MeshStandardMaterial({ color, roughness: .72, ...options });
        materials.push(material);
        return material;
    };
    const mesh = (geometry, material, parent, x, y, z) => {
        const object = new THREE.Mesh(geometry, material);
        object.position.set(x, y, z);
        object.castShadow = object.receiveShadow = true;
        parent.add(object);
        return object;
    };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);

    const grass = mat('#233c31');
    const road = mat('#252d36', { roughness: .48 });
    const sidewalk = mat('#858a8d');
    const white = mat('#dedfd9');
    box(84, .18, 46, grass, scene, 0, -.2, 0);
    box(80, .14, 13, road, scene, 0, -.02, 0);
    for (const z of [-7.5, 7.5]) box(80, .24, 3.3, sidewalk, scene, 0, .04, z);
    for (let x = -36; x <= 36; x += 4.5) box(2.25, .025, .11, mat('#c7a93d'), scene, x, .07, 0);
    for (const z of [-3.25, 3.25]) for (let x = -36; x <= 36; x += 5.5) box(2.7, .025, .09, white, scene, x, .075, z);

    const crossingX = 5;
    for (let z = -5.8; z <= 5.8; z += 1.12) box(3.6, .035, .62, white, scene, crossingX, .1, z);
    for (const z of [-6.15, 6.15]) box(4.4, .13, 1.6, sidewalk, scene, crossingX, .08, z);

    // Entorno nocturno con iluminación concentrada en el cruce.
    const litWindow = mat('#ffd77b', { emissive: '#e7a53c', emissiveIntensity: 1.65 });
    for (const [x, z, color] of [[-23, -15, '#36414c'], [19, -15, '#35434d'], [-20, 15, '#3c434a'], [24, 15, '#30464a']]) {
        box(12, 4.8, 7, mat(color), scene, x, 2.3, z);
        const roof = mesh(new THREE.CylinderGeometry(0, 8, 1.8, 4), mat('#202932'), scene, x, 5.35, z);
        roof.rotation.y = Math.PI / 4;
        for (const dx of [-2.2, 2.2]) box(1.3, 1.15, .08, litWindow, scene, x + dx, 2.5, z > 0 ? z - 3.54 : z + 3.54);
    }

    const poleMat = mat('#2a3035', { metalness: .7 });
    const lampMat = mat('#fff2b0', { emissive: '#ffdc78', emissiveIntensity: 4 });
    function streetLamp(x, z) {
        mesh(new THREE.CylinderGeometry(.08, .12, 5, 12), poleMat, scene, x, 2.5, z);
        mesh(new THREE.SphereGeometry(.27, 16, 12), lampMat, scene, x, 4.96, z);
        const light = new THREE.SpotLight(0xffdf91, 75, 18, Math.PI / 4.2, .5, 1.45);
        light.position.set(x, 4.8, z);
        light.target.position.set(x, 0, 0);
        light.castShadow = true;
        scene.add(light, light.target);
    }
    streetLamp(1.8, 7.1);
    streetLamp(8.2, -7.1);

    function pedestrianSignal() {
        const group = new THREE.Group();
        scene.add(group);
        mesh(new THREE.CylinderGeometry(.1, .14, 3.6, 12), poleMat, group, 0, 1.8, 0);
        box(.9, 1.25, .38, mat('#171d22'), group, 0, 3.55, 0);
        const glow = mat('#6eff87', { emissive: '#23f15a', emissiveIntensity: 4 });
        mesh(new THREE.SphereGeometry(.22, 16, 12), glow, group, 0, 3.7, -.22);
        const body = mesh(new THREE.CapsuleGeometry(.07, .25, 4, 8), glow, group, 0, 3.35, -.23);
        body.rotation.z = -.3;
        group.position.set(8, 0, 6.2);
        return group;
    }
    pedestrianSignal();

    function person() {
        const group = new THREE.Group();
        scene.add(group);
        mesh(new THREE.CapsuleGeometry(.23, .5, 6, 12), mat('#df8b22'), group, 0, 1.2, 0);
        mesh(new THREE.SphereGeometry(.22, 18, 14), mat('#a96f4d'), group, 0, 1.82, 0);
        mesh(new THREE.SphereGeometry(.23, 14, 10, 0, Math.PI * 2, 0, Math.PI / 2), mat('#302828'), group, 0, 1.87, 0);
        const limbs = [];
        for (const side of [-1, 1]) {
            const leg = new THREE.Group(); leg.position.set(side * .14, .93, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), mat('#263448'), leg, 0, -.34, 0); limbs.push(leg);
            const arm = new THREE.Group(); arm.position.set(side * .28, 1.47, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .44, 4, 10), mat('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm);
        }
        return { group, limbs };
    }
    const luna = person();

    function electricCar() {
        const group = new THREE.Group();
        scene.add(group);
        const body = mat('#167a9f', { roughness: .25, metalness: .55 });
        box(4.5, .75, 1.95, body, group, 0, .75, 0);
        box(2.35, .76, 1.7, body, group, -.25, 1.47, 0);
        box(2.03, .45, 1.72, mat('#132b40', { roughness: .16, metalness: .3 }), group, -.25, 1.52, 0);
        for (const x of [-1.34, 1.34]) for (const z of [-.98, .98]) { const tire = mesh(new THREE.CylinderGeometry(.38, .38, .22, 20), mat('#11171c'), group, x, .42, z); tire.rotation.x = Math.PI / 2; }
        const headlight = mat('#fff5c4', { emissive: '#fff0a0', emissiveIntensity: 5 });
        for (const z of [-.62, .62]) mesh(new THREE.SphereGeometry(.17, 14, 9), headlight, group, 2.22, .82, z);
        const electric = mat('#78f5e3', { emissive: '#29dcc2', emissiveIntensity: 3 });
        const badge = mesh(new THREE.TorusGeometry(.24, .055, 10, 18), electric, group, 0, .82, -1.01); badge.rotation.x = Math.PI / 2;
        return group;
    }
    const vehicle = electricCar();
    vehicle.rotation.y = Math.PI;

    const beamMaterial = new THREE.MeshBasicMaterial({ color: '#fff0aa', transparent: true, opacity: .17, side: THREE.DoubleSide, depthWrite: false });
    materials.push(beamMaterial);
    const beamGeometry = new THREE.BufferGeometry().setFromPoints([new THREE.Vector3(0, .1, 0), new THREE.Vector3(17, .1, -4), new THREE.Vector3(17, .1, 4)]);
    beamGeometry.setIndex([0, 1, 2]); beamGeometry.computeVertexNormals();
    const beam = new THREE.Mesh(beamGeometry, beamMaterial); scene.add(beam);

    const stopMat = new THREE.MeshStandardMaterial({ color: '#35b96b', emissive: '#35b96b', emissiveIntensity: 1.4, side: THREE.DoubleSide });
    const riskMat = new THREE.MeshStandardMaterial({ color: '#ef4444', emissive: '#ef4444', emissiveIntensity: 1.8, side: THREE.DoubleSide });
    materials.push(stopMat, riskMat);
    const stopMarker = new THREE.Mesh(new THREE.RingGeometry(1.05, 1.42, 40), stopMat); stopMarker.rotation.x = -Math.PI / 2; stopMarker.position.set(9.2, .18, 3.25); stopMarker.visible = false; scene.add(stopMarker);
    const riskMarker = new THREE.Mesh(new THREE.RingGeometry(1.15, 1.55, 40), riskMat); riskMarker.rotation.x = -Math.PI / 2; riskMarker.position.set(5, .18, 2.2); riskMarker.visible = false; scene.add(riskMarker);

    const crossingPath = new THREE.LineCurve3(new THREE.Vector3(5, .15, 7.5), new THREE.Vector3(5, .15, -7.5));
    let outcome = 'intro', progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();

    function reset() {
        luna.group.position.set(5, .1, 7.4);
        luna.group.rotation.set(0, Math.PI, 0);
        luna.limbs.forEach(limb => limb.rotation.x = 0);
        vehicle.position.set(31, 0, 3.25);
        vehicle.rotation.set(0, Math.PI, 0);
        beam.position.copy(vehicle.position);
        beam.rotation.y = Math.PI;
        stopMarker.visible = riskMarker.visible = false;
    }
    function setOutcome(value) {
        outcome = value; progress = 0; reset();
        stopMarker.visible = value === 'confirm';
        riskMarker.visible = value === 'signal' || value === 'silence';
    }
    function moveLuna(t, running = false) {
        const p = crossingPath.getPointAt(Math.min(1, t));
        luna.group.position.copy(p);
        luna.group.rotation.y = Math.PI;
        luna.limbs.forEach((limb, index) => { limb.rotation.x = Math.sin(t * (running ? 54 : 38)) * (running ? .52 : .34) * (index % 2 ? -1 : 1); });
    }

    const observer = new ResizeObserver(() => {
        if (!host.clientWidth || !host.clientHeight) return;
        renderer.setSize(host.clientWidth, host.clientHeight, false);
        camera.aspect = host.clientWidth / host.clientHeight;
        camera.updateProjectionMatrix();
    });
    observer.observe(host);

    function render(now) {
        if (disposed) return;
        const elapsed = (now - last) / 1000;
        const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0;
        last = now;
        const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / (outcome === 'confirm' ? 9 : 6));
            const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'confirm') {
                const approach = Math.min(1, ease / .46);
                vehicle.position.x = 31 - approach * 21.8;
                if (ease > .55) moveLuna((ease - .55) / .45);
            } else if (outcome === 'signal' || outcome === 'silence') {
                vehicle.position.x = 31 - ease * 40;
                moveLuna(Math.min(1, ease * (outcome === 'silence' ? 1.35 : 1.08)), outcome === 'silence');
                const pulse = 1 + Math.sin(now * .009) * .17;
                riskMarker.scale.setScalar(pulse);
            } else {
                vehicle.position.x = 31 - ((now * .00008) % 1) * 13;
            }
            beam.position.copy(vehicle.position);
            if (stopMarker.visible) { const pulse = 1 + Math.sin(now * .006) * .1; stopMarker.scale.setScalar(pulse); }

            if (view === 'pedestrian') {
                const p = luna.group.position;
                camera.position.set(p.x + 1.25, 2.5, p.z + 2.15);
                camera.lookAt(vehicle.position.x, 1, vehicle.position.z);
            } else if (view === 'driver') {
                camera.position.set(vehicle.position.x - 1.15, 1.72, vehicle.position.z);
                camera.lookAt(vehicle.position.x - 13, .85, vehicle.position.z);
            } else controls.update();
            renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }

    setOutcome('intro');
    frame = requestAnimationFrame(render);
    return {
        setOutcome,
        setPaused(value) { paused = value; },
        setView(value) {
            view = value;
            controls.enabled = value === 'overview';
            if (controls.enabled) { camera.position.set(26, 20, 31); controls.target.set(3, .4, 0); controls.update(); }
        },
        dispose() {
            disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose();
            scene.traverse(object => object.geometry?.dispose()); materials.forEach(material => material.dispose()); renderer.dispose(); renderer.domElement.remove();
        },
    };
}
