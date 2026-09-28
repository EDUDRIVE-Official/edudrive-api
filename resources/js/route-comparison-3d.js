import * as THREE from 'three';
import { pedestrianDecisionPoints } from './pedestrian-decision-paths';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

export function mountRouteComparison(host, { stopAtDecisionPoint = false } = {}) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#c9e3ef');
    scene.fog = new THREE.Fog('#c9e3ef', 45, 100);
    const camera = new THREE.PerspectiveCamera(42, 1, .1, 130);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.75));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.12;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Mapa tridimensional entre una escuela y una casa con una ruta corta obstruida y otra protegida con paso peatonal');
    host.appendChild(renderer.domElement);
    camera.position.set(23, 29, 30);
    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(0, 0, 0); controls.enableDamping = true;
    controls.minDistance = 24; controls.maxDistance = 72; controls.maxPolarAngle = Math.PI * .47;
    controls.update();

    scene.add(new THREE.HemisphereLight(0xeaf8ff, 0x55704d, 2.5));
    const sun = new THREE.DirectionalLight(0xffefd5, 3.2);
    sun.position.set(-15, 28, 20); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048);
    Object.assign(sun.shadow.camera, { left: -30, right: 30, top: 25, bottom: -25 });
    sun.shadow.bias = -.0004; scene.add(sun);
    const materials = new Map();
    const material = (color, metalness = 0) => {
        const key = `${color}-${metalness}`;
        if (!materials.has(key)) materials.set(key, new THREE.MeshStandardMaterial({ color, roughness: metalness ? .35 : .82, metalness }));
        return materials.get(key);
    };
    const mesh = (geometry, color, parent, x, y, z, metalness = 0) => {
        const item = new THREE.Mesh(geometry, material(color, metalness));
        item.position.set(x, y, z); item.castShadow = item.receiveShadow = true; parent.add(item); return item;
    };
    const box = (w, h, d, color, parent, x, y, z, metalness = 0) => mesh(new THREE.BoxGeometry(w, h, d), color, parent, x, y, z, metalness);
    box(90, .2, 60, '#79a06d', scene, 0, -.2, 0);
    box(60, .12, 8, '#4b5258', scene, 0, -.02, 0);
    for (const z of [-5.5, 5.5]) box(60, .22, 3, '#c5c4ba', scene, 0, .02, z);
    for (let x = -27; x < 28; x += 4) box(2, .02, .1, '#e8c763', scene, x, .05, 0);
    // Paso protegido y rampas a ras de la calzada.
    for (let z = -3.6; z <= 3.6; z += 1) box(3.1, .025, .55, '#f3f1e6', scene, -10, .07, z);
    for (const z of [-4.25, 4.25]) box(3.3, .06, 1.2, '#dfded3', scene, -10, .06, z);
    // Escuela y casa como referencias reconocibles.
    box(9, 4.8, 5, '#e8d5ad', scene, -16, 2.4, 10);
    const schoolRoof = mesh(new THREE.CylinderGeometry(0, 6.2, 2, 4), '#a45e47', scene, -16, 5.8, 10); schoolRoof.rotation.y = Math.PI / 4;
    box(1.5, 2.8, .1, '#5f5147', scene, -16, 1.4, 7.45);
    for (const x of [-19, -13]) box(1.6, 1.5, .1, '#315a70', scene, x, 2.7, 7.45);
    box(4.5, .5, .4, '#f5eee0', scene, -16, 4.2, 7.25);
    box(7, 4.2, 5, '#dce6dc', scene, 16, 2.1, -10);
    const homeRoof = mesh(new THREE.CylinderGeometry(0, 5.2, 1.8, 4), '#b96548', scene, 16, 5, -10); homeRoof.rotation.y = Math.PI / 4;
    box(1.2, 2.4, .1, '#755c45', scene, 16, 1.2, -7.45);
    for (const x of [13.8, 18.2]) box(1.15, 1.35, .1, '#376174', scene, x, 2.3, -7.45);
    // Obras que interrumpen la acera corta y reducen la visibilidad.
    const barriers = new THREE.Group(); scene.add(barriers);
    for (const x of [-2.5, 0, 2.5]) {
        box(2.1, .7, .25, '#f1b72e', barriers, x, .72, 5.4);
        box(.36, .72, .28, '#27303b', barriers, x - .65, .72, 5.4);
        box(.36, .72, .28, '#27303b', barriers, x + .65, .72, 5.4);
        box(.12, .8, .12, '#8b6a4b', barriers, x - .75, .2, 5.4);
        box(.12, .8, .12, '#8b6a4b', barriers, x + .75, .2, 5.4);
    }
    box(6.4, .16, 2.5, '#9b7a5a', barriers, 0, .12, 5.5);
    const coneGeometry = new THREE.ConeGeometry(.3, .8, 16);
    for (const x of [-3.5, 3.5]) mesh(coneGeometry, '#ee742d', barriers, x, .42, 4.3);
    // Vehículos muestran por qué abandonar la acera reduce el margen.
    const car = new THREE.Group(); scene.add(car);
    box(4.2, .7, 1.8, '#2c819c', car, 0, .75, 0, .5); box(2.3, .65, 1.6, '#2c819c', car, -.2, 1.4, 0, .5);
    box(2.05, .42, 1.64, '#294d60', car, -.2, 1.43, 0, .45);
    for (const x of [-1.25, 1.25]) for (const z of [-.91, .91]) { const wheel = mesh(new THREE.CylinderGeometry(.36, .36, .2, 20), '#222930', car, x, .42, z); wheel.rotation.x = Math.PI / 2; }
    car.position.set(-22, 0, 1.9);
    // Líneas elevadas y fáciles de seguir.
    const shortPoints = [new THREE.Vector3(-14, .28, 5.3), new THREE.Vector3(-4, .28, 5.3), new THREE.Vector3(-3, .28, 3.5), new THREE.Vector3(3, .28, 3.5), new THREE.Vector3(5, .28, -5.3), new THREE.Vector3(14, .28, -5.3)];
    const safePoints = pedestrianDecisionPoints('route', stopAtDecisionPoint).map(p => new THREE.Vector3(...p));
    const routeMaterial = color => new THREE.MeshStandardMaterial({ color, emissive: color, emissiveIntensity: .18, roughness: .55 });
    const makeRoute = (points, color) => {
        const curve = new THREE.CatmullRomCurve3(points, false, 'catmullrom', .15);
        const object = new THREE.Mesh(new THREE.TubeGeometry(curve, 100, .16, 10, false), routeMaterial(color));
        object.castShadow = true; scene.add(object); return { curve, object };
    };
    const shortRoute = makeRoute(shortPoints, '#cf3030');
    const safeRoute = makeRoute(safePoints, '#16875c');
    shortRoute.object.visible = safeRoute.object.visible = false;
    // Viajera en silla de ruedas para comprobar continuidad y rampas.
    const traveler = new THREE.Group(); scene.add(traveler); traveler.visible = false;
    box(.58, .1, .55, '#334758', traveler, 0, .59, 0); box(.58, .57, .1, '#334758', traveler, 0, .88, -.27);
    for (const side of [-1, 1]) { const wheel = mesh(new THREE.TorusGeometry(.42, .045, 10, 28), '#27323a', traveler, side * .38, .43, -.1); wheel.rotation.y = Math.PI / 2; }
    mesh(new THREE.CapsuleGeometry(.2, .24, 6, 14), '#6b64a3', traveler, 0, 1, 0); mesh(new THREE.SphereGeometry(.18, 16, 14), '#ad795a', traveler, 0, 1.43, 0);
    let route = 'none', progress = 0, paused = false, view = 'overview', frame = 0, disposed = false, last = performance.now();
    function selectRoute(next) {
        route = next; progress = 0; traveler.visible = next !== 'none';
        shortRoute.object.visible = next === 'short'; safeRoute.object.visible = next === 'safe';
        car.visible = next === 'short'; car.position.x = -22;
    }
    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); });
    observer.observe(host);
    function render(now) {
        if (disposed) return;
        const elapsed = (now - last) / 1000;
        const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0;
        last = now;
        if (host.getBoundingClientRect().bottom > 0 && host.getBoundingClientRect().top < innerHeight && !document.hidden) {
            if (!paused && route !== 'none') progress = Math.min(1, progress + dt / (route === 'safe' ? 10 : 7));
            const active = route === 'short' ? shortRoute.curve : safeRoute.curve;
            if (route !== 'none') {
                const point = active.getPointAt(progress); const ahead = active.getPointAt(Math.min(1, progress + .01));
                traveler.position.copy(point); traveler.lookAt(ahead.x, point.y, ahead.z);
                if (route === 'short') car.position.x = -22 + progress * 42;
            }
            if (view === 'traveler' && route !== 'none') { const p = traveler.position; camera.position.set(p.x - 3.5, 2.5, p.z + 5); camera.lookAt(p.x + 4, .7, p.z); }
            else controls.update();
            renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    selectRoute('none'); frame = requestAnimationFrame(render);
    return {
        setRoute: selectRoute,
        setPaused(value) { paused = value; },
        setView(value) {
            view = value; controls.enabled = value === 'overview';
            if (controls.enabled) { camera.position.set(23, 29, 30); controls.target.set(0, 0, 0); controls.update(); }
        },
        dispose() {
            disposed = true; cancelAnimationFrame(frame); observer.disconnect();
            scene.traverse(item => item.geometry?.dispose());
            materials.forEach(value => value.dispose());
            shortRoute.object.material.dispose(); safeRoute.object.material.dispose();
            controls.dispose(); renderer.dispose(); renderer.domElement.remove();
        },
    };
}
