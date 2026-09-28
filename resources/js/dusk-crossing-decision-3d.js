import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountDuskCrossingDecision(host) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#172b48');
    scene.fog = new THREE.Fog('#172b48', 34, 90);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 140);
    camera.position.set(25, 20, 31);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7));
    renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = .92;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Escena tridimensional al anochecer con una esquina oscura, un cruce iluminado, Luna y un automóvil cuyos faros muestran los límites de visibilidad');
    host.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(1, .5, 0); controls.enableDamping = true; controls.minDistance = 16; controls.maxDistance = 62; controls.maxPolarAngle = Math.PI * .48; controls.update();
    scene.add(new THREE.HemisphereLight(0x7897c2, 0x172319, 1.25));
    const dusk = new THREE.DirectionalLight(0x91a8d0, 1.65); dusk.position.set(-17, 24, 15); dusk.castShadow = true;
    dusk.shadow.mapSize.set(2048, 2048); Object.assign(dusk.shadow.camera, { left: -36, right: 36, top: 25, bottom: -25 }); scene.add(dusk);

    const materials = [];
    const mat = (color, options = {}) => { const value = new THREE.MeshStandardMaterial({ color, roughness: .75, ...options }); materials.push(value); return value; };
    const mesh = (geometry, material, parent, x, y, z) => { const object = new THREE.Mesh(geometry, material); object.position.set(x, y, z); object.castShadow = object.receiveShadow = true; parent.add(object); return object; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    const grass = mat('#294535'); const road = mat('#27313b', { roughness: .5 }); const pavement = mat('#7d8589'); const stripe = mat('#ddd9c5');
    box(76, .2, 43, grass, scene, 0, -.2, 0); box(72, .14, 12, road, scene, 0, -.02, 0);
    for (const z of [-7.2, 7.2]) box(72, .24, 3.2, pavement, scene, 0, .04, z);
    for (let x = -32; x <= 32; x += 4.2) box(2.1, .025, .1, mat('#bd9f45'), scene, x, .07, 0);
    const crossingX = 10;
    for (let z = -5.5; z <= 5.5; z += 1.08) box(3.6, .035, .58, stripe, scene, crossingX, .09, z);
    for (const z of [-5.9, 5.9]) box(4.2, .12, 1.55, pavement, scene, crossingX, .08, z);

    // Barrio oscuro y cruce iluminado a pocos pasos.
    const windowMat = mat('#f2c56c', { emissive: '#e89a37', emissiveIntensity: 1.5 });
    for (const [x, z, color] of [[-20, -14, '#394753'], [18, -14, '#3e4854'], [-20, 14, '#42484e'], [22, 14, '#364c4c']]) {
        box(11, 4.5, 7, mat(color), scene, x, 2.15, z);
        const roof = mesh(new THREE.CylinderGeometry(0, 7.7, 1.8, 4), mat('#222a32'), scene, x, 5.15, z); roof.rotation.y = Math.PI / 4;
        for (const dx of [-2.1, 2.1]) box(1.25, 1.1, .08, windowMat, scene, x + dx, 2.4, z > 0 ? z - 3.54 : z + 3.54);
    }
    for (const [x, z] of [[-10, -11], [1, -11], [-10, 11], [1, 11], [28, -10]]) {
        mesh(new THREE.CylinderGeometry(.14, .22, 3.6, 10), mat('#39342e'), scene, x, 1.75, z);
        const crown = mesh(new THREE.SphereGeometry(1.45, 14, 11), mat('#284735'), scene, x, 4.1, z); crown.scale.y = 1.2;
    }

    const lampDark = mat('#26323a', { metalness: .7 });
    const lampGlow = mat('#fff1b0', { emissive: '#ffd76e', emissiveIntensity: 4 });
    function streetLamp(x, z) {
        mesh(new THREE.CylinderGeometry(.08, .12, 4.6, 12), lampDark, scene, x, 2.3, z);
        mesh(new THREE.SphereGeometry(.25, 16, 12), lampGlow, scene, x, 4.58, z);
        const light = new THREE.SpotLight(0xffdc87, 65, 17, Math.PI / 4.4, .48, 1.5); light.position.set(x, 4.5, z); light.target.position.set(x, 0, 0); light.castShadow = true; scene.add(light, light.target);
    }
    streetLamp(7.5, 7); streetLamp(12.5, -7);

    function person() {
        const group = new THREE.Group(); scene.add(group);
        // Ropa oscura a propósito: cambia de visibilidad cuando entra en la luz.
        const torsoMat = mat('#253343');
        mesh(new THREE.CapsuleGeometry(.23, .5, 6, 12), torsoMat, group, 0, 1.2, 0);
        mesh(new THREE.SphereGeometry(.22, 18, 14), mat('#a96f4d'), group, 0, 1.82, 0);
        mesh(new THREE.SphereGeometry(.23, 14, 10, 0, Math.PI * 2, 0, Math.PI / 2), mat('#2b2525'), group, 0, 1.86, 0);
        const limbs = [];
        for (const side of [-1, 1]) {
            const leg = new THREE.Group(); leg.position.set(side * .14, .93, 0); group.add(leg); mesh(new THREE.CapsuleGeometry(.09, .52, 4, 10), mat('#202c38'), leg, 0, -.34, 0); limbs.push(leg);
            const arm = new THREE.Group(); arm.position.set(side * .28, 1.47, 0); group.add(arm); mesh(new THREE.CapsuleGeometry(.065, .44, 4, 10), mat('#a96f4d'), arm, 0, -.27, 0); limbs.push(arm);
        }
        const reflector = box(.54, .08, .4, mat('#eac93f', { emissive: '#eac93f', emissiveIntensity: 2 }), group, 0, 1.22, 0); reflector.visible = false;
        const phone = box(.28, .48, .05, mat('#b9e9ff', { emissive: '#58cfff', emissiveIntensity: 5 }), group, .42, 1.46, -.12); phone.visible = false;
        return { group, limbs, torsoMat, reflector, phone };
    }
    const luna = person();

    function car() {
        const group = new THREE.Group(); scene.add(group);
        const body = mat('#7c2635', { roughness: .3, metalness: .5 });
        box(4.4, .74, 1.9, body, group, 0, .74, 0); box(2.3, .72, 1.65, body, group, -.25, 1.43, 0);
        box(2, .44, 1.68, mat('#173345', { roughness: .18, metalness: .3 }), group, -.25, 1.48, 0);
        for (const x of [-1.3, 1.3]) for (const z of [-.96, .96]) { const tire = mesh(new THREE.CylinderGeometry(.37, .37, .21, 20), mat('#151a1e'), group, x, .43, z); tire.rotation.x = Math.PI / 2; }
        const headMat = mat('#fff4bd', { emissive: '#fff0a3', emissiveIntensity: 5 });
        for (const z of [-.6, .6]) mesh(new THREE.SphereGeometry(.17, 14, 9), headMat, group, -2.18, .8, z);
        return group;
    }
    const vehicle = car(); vehicle.rotation.y = Math.PI;
    const beamMat = new THREE.MeshBasicMaterial({ color: '#ffedac', transparent: true, opacity: .18, side: THREE.DoubleSide, depthWrite: false }); materials.push(beamMat);
    const beam = new THREE.Mesh(new THREE.BufferGeometry().setFromPoints([new THREE.Vector3(0, .1, 0), new THREE.Vector3(-18, .1, -4), new THREE.Vector3(-18, .1, 4)]), beamMat); beam.geometry.setIndex([0, 1, 2]); beam.geometry.computeVertexNormals(); scene.add(beam);

    const pathMat = new THREE.MeshStandardMaterial({ color: '#35a868', emissive: '#35a868', emissiveIntensity: 1 }); materials.push(pathMat);
    const safeCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-9, .18, 7), new THREE.Vector3(2, .18, 7), new THREE.Vector3(10, .18, 7), new THREE.Vector3(10, .18, 0), new THREE.Vector3(10, .18, -7)], false, 'catmullrom', .12);
    const directCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(-9, .18, 7), new THREE.Vector3(-9, .18, 0), new THREE.Vector3(-9, .18, -7)]);
    const route = new THREE.Mesh(new THREE.TubeGeometry(safeCurve, 100, .12, 10), pathMat); route.visible = false; scene.add(route);
    const riskMat = new THREE.MeshStandardMaterial({ color: '#df3e39', emissive: '#df3e39', emissiveIntensity: 1.5, side: THREE.DoubleSide }); materials.push(riskMat);
    const risk = new THREE.Mesh(new THREE.RingGeometry(1.1, 1.48, 40), riskMat); risk.rotation.x = -Math.PI / 2; risk.position.set(-9, .18, -1.5); risk.visible = false; scene.add(risk);

    let outcome = 'intro', progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now();
    const reset = () => {
        luna.group.position.set(-9, .1, 7); luna.group.rotation.set(0, Math.PI, 0); luna.phone.visible = luna.reflector.visible = false; luna.torsoMat.emissive.set('#000000');
        luna.limbs.forEach(limb => limb.rotation.x = 0); vehicle.position.set(28, 0, -1.5); beam.position.copy(vehicle.position); beam.rotation.y = Math.PI;
        route.visible = risk.visible = false;
    };
    const setOutcome = value => { outcome = value; progress = 0; reset(); route.visible = value === 'visible'; risk.visible = value === 'right' || value === 'phone'; luna.phone.visible = value === 'phone'; };
    const moveOn = (curve, t, running = false) => {
        const p = curve.getPointAt(Math.min(.999, t)); const next = curve.getPointAt(Math.min(1, t + .01)); luna.group.position.copy(p); luna.group.lookAt(next.x, p.y, next.z);
        luna.limbs.forEach((limb, i) => limb.rotation.x = Math.sin(t * (running ? 52 : 38)) * (running ? .55 : .34) * (i % 2 ? -1 : 1));
    };
    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);

    function render(now) {
        if (disposed) return;
        const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now;
        const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused && outcome !== 'intro') progress = Math.min(1, progress + dt / (outcome === 'visible' ? 10 : 5.5));
            const ease = progress * progress * (3 - 2 * progress);
            if (outcome === 'visible') {
                moveOn(safeCurve, ease); vehicle.position.x = 28 - Math.min(1, ease * 1.35) * 34;
                if (ease > .35) { luna.reflector.visible = true; luna.torsoMat.emissive.set('#162638'); luna.torsoMat.emissiveIntensity = .65; }
            } else if (outcome === 'right' || outcome === 'phone') {
                moveOn(directCurve, ease, outcome === 'right'); vehicle.position.x = 28 - ease * 52;
            } else vehicle.position.x = 28 - ((now * .00125) % 1) * 52;
            beam.position.copy(vehicle.position);
            if (risk.visible) { const pulse = 1 + Math.sin(now * .008) * .15; risk.scale.setScalar(pulse); }
            if (view === 'pedestrian') { camera.position.set(-11.2, 2.55, 9.2); camera.lookAt(-3, .8, 0); }
            else if (view === 'driver') { camera.position.set(5.2, 1.75, -1.5); camera.lookAt(-9, .8, 4); }
            else controls.update();
            renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setOutcome('intro'); frame = requestAnimationFrame(render);
    return {
        setOutcome, setPaused(value) { paused = value; },
        setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) { camera.position.set(25, 20, 31); controls.target.set(1, .5, 0); controls.update(); } },
        dispose() { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose()); renderer.dispose(); renderer.domElement.remove(); },
    };
}
