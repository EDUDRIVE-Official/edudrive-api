import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

// Kit compartido de las escenas 3D de "respuesta ante incidentes": escenario, calle, personas, vehículos y bicicleta.
export const LANE_A = 2.7, LANE_B = -2.7; // carriles: A (cercano, hacia -x) y B (lejano, hacia +x)
export const clamp01 = value => Math.min(1, Math.max(0, value));
export const smooth = value => { const t = clamp01(value); return t * t * (3 - 2 * t); };
export const easeOut = value => 1 - Math.pow(1 - clamp01(value), 3);

// Escenario base: renderer, cielo, reflejos, luces, cámara orbital y utilidades para crear mallas y materiales.
export function createStage(host, { label, overview = { position: [10, 19, 30], target: [1, .4, 4] } }) {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const disposables = [], materials = [];
    const renderer = new THREE.WebGLRenderer({ antialias: true }); renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7)); renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap; renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.05;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block'; renderer.domElement.setAttribute('role', 'img'); renderer.domElement.setAttribute('aria-label', label); host.appendChild(renderer.domElement);

    const scene = new THREE.Scene(); const pmrem = new THREE.PMREMGenerator(renderer); const envTexture = pmrem.fromScene(new RoomEnvironment(), .04).texture; scene.environment = envTexture; scene.environmentIntensity = .55; disposables.push(envTexture, pmrem);
    const canvasTexture = (width, height, draw, repeatX = 1, repeatY = 1) => { const canvas = document.createElement('canvas'); canvas.width = width; canvas.height = height; draw(canvas.getContext('2d'), width, height); const texture = new THREE.CanvasTexture(canvas); texture.colorSpace = THREE.SRGBColorSpace; texture.wrapS = texture.wrapT = THREE.RepeatWrapping; texture.repeat.set(repeatX, repeatY); texture.anisotropy = 4; disposables.push(texture); return texture; };
    const speckle = (ctx, w, h, base, tones, count, size) => { ctx.fillStyle = base; ctx.fillRect(0, 0, w, h); for (let i = 0; i < count; i++) { ctx.fillStyle = tones[i % tones.length]; ctx.fillRect(Math.random() * w, Math.random() * h, size, size); } };
    const sky = canvasTexture(2, 256, (ctx, w, h) => { const g = ctx.createLinearGradient(0, 0, 0, h); g.addColorStop(0, '#6fa8dc'); g.addColorStop(.55, '#b9d9ee'); g.addColorStop(1, '#e3eff3'); ctx.fillStyle = g; ctx.fillRect(0, 0, w, h); }); sky.wrapS = sky.wrapT = THREE.ClampToEdgeWrapping;
    scene.background = sky; scene.fog = new THREE.Fog('#d6e6ee', 60, 135);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 170);
    const controls = new OrbitControls(camera, renderer.domElement); controls.enableDamping = true; controls.minDistance = 15; controls.maxDistance = 65; controls.maxPolarAngle = Math.PI * .48;
    const resetCamera = () => { camera.position.set(...overview.position); controls.target.set(...overview.target); controls.update(); }; resetCamera();
    scene.add(new THREE.HemisphereLight(0xeaf4ff, 0x5d7a52, 1.5)); const sun = new THREE.DirectionalLight(0xfff1d6, 3.3); sun.position.set(-20, 30, 20); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048); sun.shadow.bias = -.0004; sun.shadow.normalBias = .03; Object.assign(sun.shadow.camera, { left: -42, right: 42, top: 30, bottom: -30 }); scene.add(sun);

    const track = value => { materials.push(value); return value; };
    const mat = (color, options = {}) => track(new THREE.MeshStandardMaterial({ color, roughness: .75, ...options }));
    const paint = color => track(new THREE.MeshPhysicalMaterial({ color, roughness: .3, metalness: .55, clearcoat: 1, clearcoatRoughness: .08 }));
    const glass = () => track(new THREE.MeshPhysicalMaterial({ color: '#1d2d38', roughness: .04, metalness: .35, clearcoat: 1 }));
    const glow = (color, opacity = .7, intensity = 1.2) => track(new THREE.MeshStandardMaterial({ color, emissive: color, emissiveIntensity: intensity, transparent: true, opacity, side: THREE.DoubleSide }));
    const mesh = (geometry, material, parent, x = 0, y = 0, z = 0) => { const value = new THREE.Mesh(geometry, material); value.position.set(x, y, z); value.castShadow = value.receiveShadow = true; parent.add(value); return value; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    const cyl = (r, h, material, parent, x, y, z, segments = 16) => mesh(new THREE.CylinderGeometry(r, r, h, segments), material, parent, x, y, z);
    const capsule = (r, length, material, parent, x, y, z) => mesh(new THREE.CapsuleGeometry(r, length, 6, 14), material, parent, x, y, z);
    const tube = (a, b, r, material, parent) => { const from = new THREE.Vector3(...a), to = new THREE.Vector3(...b), dir = to.clone().sub(from); const value = cyl(r, dir.length(), material, parent, 0, 0, 0, 10); value.position.copy(from).add(to).multiplyScalar(.5); value.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), dir.normalize()); return value; };
    const ring = (inner, outer, material, x, y, z) => { const value = new THREE.Mesh(new THREE.RingGeometry(inner, outer, 56), material); value.rotation.x = -Math.PI / 2; value.position.set(x, y, z); scene.add(value); return value; };

    const stage = { THREE, scene, camera, renderer, controls, reduced, disposables, canvasTexture, speckle, track, mat, paint, glass, glow, mesh, box, cyl, capsule, tube, ring, resetCamera, view: 'overview', paused: false, simTime: 0 };

    // Bucle de render: solo avanza y dibuja si la escena es visible y la pestaña está activa.
    let frame = 0, last = performance.now(), disposed = false;
    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);
    stage.run = tick => {
        const loop = now => {
            if (disposed) return; const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now; const rect = host.getBoundingClientRect();
            if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) { if (!stage.paused) stage.simTime += dt; tick(dt, now); if (stage.view === 'overview') controls.update(); renderer.render(scene, camera); }
            frame = requestAnimationFrame(loop);
        };
        frame = requestAnimationFrame(loop);
    };
    stage.setView = value => { stage.view = value; controls.enabled = value === 'overview'; if (controls.enabled) resetCamera(); };
    stage.dispose = () => { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose()); disposables.forEach(value => value.dispose?.()); renderer.dispose(); renderer.domElement.remove(); };
    return stage;
}

// Calle de dos carriles con aceras, cordón, líneas viales, casas, árboles y postes de luz.
export function buildStreet(stage) {
    const { scene, mat, paint, glass, glow, mesh, box, cyl, tube, canvasTexture, speckle } = stage;
    const grass = mat('#6f9d62', { map: canvasTexture(256, 256, (ctx, w, h) => speckle(ctx, w, h, '#6f9d62', ['#628f56', '#7aa96c', '#5b8650'], 5000, 2), 26, 14), roughness: 1 });
    const asphalt = mat('#444a50', { map: canvasTexture(256, 256, (ctx, w, h) => speckle(ctx, w, h, '#444a50', ['#3b4147', '#50565c', '#2f353a', '#5c6268'], 7000, 2), 20, 3), roughness: .92 });
    const pavement = mat('#cfccc2', { map: canvasTexture(128, 128, (ctx, w, h) => { speckle(ctx, w, h, '#cfccc2', ['#c4c1b7', '#d8d5cb'], 700, 2); ctx.strokeStyle = '#a9a69c'; ctx.lineWidth = 3; ctx.strokeRect(0, 0, w, h); }, 38, 1.6), roughness: .88 });
    box(110, .18, 60, grass, scene, 0, -.18, 0); box(100, .14, 13, asphalt, scene, 0, -.02, 0);
    for (const z of [-7.4, 7.4]) box(100, .24, 3.2, pavement, scene, 0, .04, z);
    const curb = mat('#b6b3aa'); for (const z of [-5.82, 5.82]) box(100, .22, .2, curb, scene, 0, .06, z);
    const paintWhite = mat('#eceae2', { roughness: .8 }), paintYellow = mat('#e2b93c', { roughness: .8 });
    for (const z of [-5.2, 5.2]) box(100, .02, .12, paintWhite, scene, 0, .06, z);
    for (let x = -46; x <= 46; x += 4.4) box(2.3, .02, .12, paintYellow, scene, x, .06, 0);

    function house(x, z, w, d, wall, roof, facing = 1) {
        const group = new THREE.Group(); group.position.set(x, 0, z); scene.add(group); const walls = mat(wall), roofMat = mat(roof, { roughness: .85 }), windowMat = glass(), frameMat = mat('#f1efe8');
        box(w, 4.2, d, walls, group, 0, 2.1, 0);
        const ridge = mesh(new THREE.CylinderGeometry(0, 1, 1, 4), roofMat, group, 0, 5.4, 0); ridge.scale.set(w * .78, 2.4, d * .78); ridge.rotation.y = Math.PI / 4;
        for (let i = -1; i <= 1; i += 2) { box(1.3, 1.3, .12, frameMat, group, i * w * .27, 2.5, facing * (d / 2 + .02)); box(1.1, 1.1, .14, windowMat, group, i * w * .27, 2.5, facing * (d / 2 + .04)); }
        box(1.1, 2.2, .14, mat('#7a4f33'), group, 0, 1.1, facing * (d / 2 + .04));
    }
    house(-18, -17, 11, 8, '#e5d6b8', '#9b6047', 1); house(2, -18, 9, 8, '#d9e1e8', '#5f6b7a', 1); house(21, -17, 12, 8, '#efe3cf', '#8a5a44', 1); house(-28, 17, 10, 8, '#e8d9c4', '#8c5c45', -1); house(22, 20, 11, 8, '#dfe6d7', '#67594a', -1);
    function tree(x, z, scale = 1) { const trunk = cyl(.2 * scale, 3.4 * scale, mat('#5a4434'), scene, x, 1.7 * scale, z, 10); trunk.rotation.z = .03; for (const [dx, dy, dz, r] of [[0, 4.2, 0, 1.55], [.8, 3.7, .3, 1.1], [-.7, 3.8, -.4, 1.2], [.1, 5.1, .2, 1]]) { const crown = mesh(new THREE.IcosahedronGeometry(r * scale, 2), mat('#3d7a4d', { roughness: .95 }), scene, x + dx * scale, dy * scale, z + dz * scale); crown.scale.y = 1.1; } }
    tree(-14, 11.5); tree(24, 11.8, 1.1); tree(-26, -10); tree(14, -11.5, .9); tree(-4, 12.4, .8);
    function lamp(x, z) { const metal = mat('#4c5560', { metalness: .6, roughness: .4 }); cyl(.08, 5.4, metal, scene, x, 2.7, z, 10); tube([x, 5.4, z], [x, 5.6, z - Math.sign(z) * 1.3], .06, metal, scene); box(.6, .14, .3, metal, scene, x, 5.6, z - Math.sign(z) * 1.4); const light = box(.5, .06, .24, glow('#fff2c4', 1, .8), scene, x, 5.5, z - Math.sign(z) * 1.4); light.castShadow = false; }
    for (const x of [-30, -10, 10, 30]) { lamp(x, 8.7); lamp(x + 5, -8.7); }
    return { paint };
}

// Personas: torso, cuello, cabeza con cabello y rostro, brazos y piernas articulados, calzado.
export function buildPerson(stage, { shirt, pants, skin = '#b07a55', hair = '#2a1d17', shoe = '#20262c', scale = 1 }) {
    const { THREE: T, scene, mat, mesh, cyl, capsule, box } = stage;
    const group = new T.Group(); scene.add(group); group.scale.setScalar(scale);
    const skinMat = mat(skin, { roughness: .6 }), shirtMat = mat(shirt, { roughness: .85 }), pantsMat = mat(pants, { roughness: .85 }), hairMat = mat(hair, { roughness: .9 }), shoeMat = mat(shoe, { roughness: .6 });
    const hips = mesh(new T.SphereGeometry(.2, 18, 12), pantsMat, group, 0, .96, 0); hips.scale.set(1.1, .7, .78);
    const torso = capsule(.19, .42, shirtMat, group, 0, 1.3, 0); torso.scale.set(1.15, 1, .75);
    cyl(.055, .12, skinMat, group, 0, 1.62, 0, 12);
    const head = mesh(new T.SphereGeometry(.115, 24, 18), skinMat, group, 0, 1.78, 0); head.scale.set(.95, 1.18, 1.02);
    const cap = mesh(new T.SphereGeometry(.122, 24, 14, 0, Math.PI * 2, 0, Math.PI * .56), hairMat, group, 0, 1.79, -.012); cap.scale.set(.98, 1.2, 1.06); cap.rotation.x = -.18;
    mesh(new T.SphereGeometry(.022, 10, 8), skinMat, group, 0, 1.765, .112);
    for (const side of [-1, 1]) mesh(new T.SphereGeometry(.012, 8, 6), mat('#14100e'), group, side * .043, 1.8, .105);
    const limbs = [], knees = [], elbows = [], hands = [];
    for (const side of [-1, 1]) {
        const leg = new T.Group(); leg.position.set(side * .1, .94, 0); group.add(leg); capsule(.085, .32, pantsMat, leg, 0, -.23, 0);
        const knee = new T.Group(); knee.position.set(0, -.46, 0); leg.add(knee); capsule(.068, .34, pantsMat, knee, 0, -.22, 0); box(.12, .08, .27, shoeMat, knee, 0, -.5, .06);
        limbs.push(leg); knees.push(knee);
        const arm = new T.Group(); arm.position.set(side * .27, 1.5, 0); group.add(arm); capsule(.052, .22, shirtMat, arm, 0, -.17, 0);
        const elbow = new T.Group(); elbow.position.set(0, -.33, 0); arm.add(elbow); capsule(.045, .2, skinMat, elbow, 0, -.15, 0); const hand = mesh(new T.SphereGeometry(.05, 12, 10), skinMat, elbow, 0, -.31, 0);
        limbs.push(arm); elbows.push(elbow); hands.push(hand);
    }
    return { group, limbs, knees, elbows, hands }; // limbs: [piernaIzq, brazoIzq, piernaDer, brazoDer]
}

// Teléfono que se sostiene en la mano derecha de una persona.
export function buildPhone(stage, person) {
    const { mat, glow, box } = stage; const phone = box(.075, .15, .012, mat('#12181f', { roughness: .3 }), person.hands[1], .01, -.05, .045); box(.065, .135, .014, glow('#86d1ff', 1, 1.1), phone, 0, 0, .002).castShadow = false; return phone;
}

// Caminar o correr a lo largo de una curva; t en 0..1.
export function walk(actor, curve, t, running) {
    const p = curve.getPointAt(Math.min(.999, t)), next = curve.getPointAt(Math.min(1, t + .01)); actor.group.position.copy(p); if (t < 1) actor.group.lookAt(next.x, p.y, next.z);
    const moving = t > 0 && t < 1, swing = moving ? Math.sin(t * (running ? 58 : 38)) * (running ? .62 : .36) : 0;
    [0, 2].forEach((leg, i) => { const s = swing * (i ? -1 : 1); actor.limbs[leg].rotation.x = s; actor.knees[i].rotation.x = Math.max(0, -s) * 1.5; });
    [1, 3].forEach((arm, i) => { actor.limbs[arm].rotation.x = -swing * (i ? -1 : 1) * .9; actor.elbows[i].rotation.x = -(moving ? (running ? 1 : .35) : .08); });
}

// Bicicleta con cuadro de diamante, ruedas con radios, manubrio, sillín y pedales (de pie sobre el eje x).
export function buildBicycle(stage) {
    const { THREE: T, paint, mat, mesh, cyl, box, tube } = stage;
    const group = new T.Group(); const frameMat = paint('#d6483c'), tireMat = mat('#16191c', { roughness: .9 }), metal = mat('#aab2ba', { metalness: .85, roughness: .3 });
    const bb = [0, .3, 0], seat = [-.14, .8, 0], head = [.4, .76, 0], rear = [-.56, .34, 0], front = [.56, .34, 0];
    for (const [a, b] of [[bb, seat], [bb, head], [seat, head], [seat, rear], [bb, rear]]) tube(a, b, .022, frameMat, group); tube(head, front, .02, metal, group); tube([.4, .76, 0], [.42, .92, 0], .02, metal, group);
    for (const hub of [rear, front]) { mesh(new T.TorusGeometry(.34, .03, 10, 36), tireMat, group, hub[0], hub[1], 0); for (let i = 0; i < 12; i++) { const spoke = cyl(.004, .66, metal, group, hub[0], hub[1], 0, 4); spoke.rotation.z = i * Math.PI / 6; spoke.castShadow = false; } cyl(.03, .08, metal, group, hub[0], hub[1], 0, 12).rotation.x = Math.PI / 2; }
    cyl(.012, .5, mat('#222'), group, .42, .93, 0, 8).rotation.x = Math.PI / 2; box(.26, .04, .12, mat('#1b1b1b'), group, -.16, .84, 0); cyl(.1, .02, metal, group, 0, .3, .04, 20).rotation.x = Math.PI / 2;
    box(.12, .02, .07, mat('#222'), group, .1, .22, .16); box(.12, .02, .07, mat('#222'), group, -.1, .38, -.16);
    return group;
}

// Vehículo: carrocería extruida, vidrios, faros, luces traseras, parrilla, placa y ruedas con rin. Frente en +x.
export function buildCar(stage, color) {
    const { THREE: T, scene, mat, paint, glass, glow, mesh, box, cyl, tube, track } = stage;
    const group = new T.Group(); scene.add(group); const body = paint(color), dark = mat('#14181c', { roughness: .6 });
    const lower = new T.Shape(); lower.moveTo(-2.25, .38); lower.lineTo(2.25, .38); lower.lineTo(2.3, .62); lower.quadraticCurveTo(2.3, .92, 2.02, .96); lower.lineTo(.8, 1.03); lower.lineTo(-1.6, 1.05); lower.quadraticCurveTo(-2.26, 1, -2.3, .75); lower.closePath();
    const lowerGeo = new T.ExtrudeGeometry(lower, { depth: 1.7, bevelEnabled: true, bevelSize: .07, bevelThickness: .07, bevelSegments: 4, curveSegments: 14 }); lowerGeo.translate(0, 0, -.85); mesh(lowerGeo, body, group);
    const cabin = new T.Shape(); cabin.moveTo(.95, 1.0); cabin.lineTo(.4, 1.5); cabin.lineTo(-1.15, 1.52); cabin.quadraticCurveTo(-1.5, 1.45, -1.8, 1.02); cabin.closePath();
    const cabinGeo = new T.ExtrudeGeometry(cabin, { depth: 1.5, bevelEnabled: true, bevelSize: .05, bevelThickness: .05, bevelSegments: 3 }); cabinGeo.translate(0, 0, -.75); mesh(cabinGeo, glass(), group);
    box(1.5, .07, 1.6, body, group, -.38, 1.56, 0); for (const z of [-.76, .76]) { tube([.45, 1.48, z], [.98, 1.02, z], .035, body, group); tube([-1.2, 1.5, z], [-1.84, 1.02, z], .04, body, group); }
    const headMat = glow('#fff4d0', 1, 1.6), tailMat = track(new T.MeshStandardMaterial({ color: '#8a1616', emissive: '#ff2a1f', emissiveIntensity: .25 })), brakeMat = tailMat;
    for (const z of [-.62, .62]) { box(.06, .13, .36, headMat, group, 2.35, .8, z).castShadow = false; box(.06, .14, .34, tailMat, group, -2.35, .86, z); box(.06, .1, .22, mat('#d98b2a'), group, 2.34, .62, z * 1.28); }
    box(.08, .2, 1, dark, group, 2.36, .6, 0); box(.14, .16, 1.76, mat('#2a2f35', { roughness: .5 }), group, 2.3, .45, 0); box(.14, .16, 1.76, mat('#2a2f35', { roughness: .5 }), group, -2.3, .45, 0); box(.02, .13, .36, mat('#f4f4ee'), group, 2.42, .52, 0); box(.02, .13, .36, mat('#f4f4ee'), group, -2.42, .52, 0);
    for (const z of [-.95, .95]) box(.14, .1, .1, body, group, .62, 1.12, z * 1.03);
    const wheels = []; for (const x of [-1.42, 1.42]) for (const z of [-.86, .86]) {
        const wheelGroup = new T.Group(); wheelGroup.position.set(x, .38, z); group.add(wheelGroup);
        const tire = cyl(.38, .26, mat('#15181b', { roughness: .95 }), wheelGroup, 0, 0, 0, 28), rim = cyl(.24, .27, mat('#c3cad1', { metalness: .9, roughness: .25 }), wheelGroup, 0, 0, 0, 20), hub = cyl(.07, .29, dark, wheelGroup, 0, 0, 0, 12);
        for (let i = 0; i < 5; i++) { const spoke = box(.05, .4, .02, mat('#9ea6ad', { metalness: .9, roughness: .3 }), wheelGroup, 0, 0, z > 0 ? .14 : -.14); spoke.rotation.z = i * Math.PI * 2 / 5; }
        tire.rotation.x = rim.rotation.x = hub.rotation.x = Math.PI / 2; wheels.push(wheelGroup);
    }
    return { group, wheels, brakeMat };
}

// Etiqueta flotante con texto (globo de diálogo, aviso, ícono). Siempre mira a la cámara y se ve sobre la escena.
export function buildLabel(stage, text, { bg = '#ffffff', fg = '#0f172a', width = 2.4 } = {}) {
    const { THREE: T, scene, track } = stage;
    const canvas = document.createElement('canvas'); canvas.width = 320; canvas.height = 112; const ctx = canvas.getContext('2d');
    ctx.fillStyle = bg; ctx.strokeStyle = fg; ctx.lineWidth = 6; ctx.beginPath(); ctx.roundRect(6, 6, 308, 100, 28); ctx.fill(); ctx.stroke();
    let size = 52; ctx.font = `700 ${size}px system-ui, sans-serif`; while (ctx.measureText(text).width > 270 && size > 20) { size -= 2; ctx.font = `700 ${size}px system-ui, sans-serif`; }
    ctx.fillStyle = fg; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText(text, 160, 58);
    const texture = new T.CanvasTexture(canvas); texture.colorSpace = T.SRGBColorSpace; const material = new T.SpriteMaterial({ map: texture, depthTest: false, transparent: true });
    const sprite = new T.Sprite(material); sprite.scale.set(width * 1.5, width * 1.5 * 112 / 320, 1); sprite.renderOrder = 10; sprite.visible = false; scene.add(sprite); stage.disposables.push(texture, material); return sprite;
}

// Marcador de ubicación: esfera sobre un cono invertido.
export function buildPin(stage, color) {
    const { THREE: T, scene, glow, mesh } = stage; const group = new T.Group(); scene.add(group); const material = glow(color, 1, .9);
    const head = mesh(new T.SphereGeometry(.45, 20, 16), material, group, 0, .9, 0); const tip = mesh(new T.ConeGeometry(.3, .8, 16), material, group, 0, .3, 0); tip.rotation.x = Math.PI; head.castShadow = tip.castShadow = false;
    return { group, material };
}

// Ambulancia: carrocería blanca con módulo trasero, franja roja y barra de luces. Frente en +x.
export function buildAmbulance(stage) {
    const { THREE: T, mat, glow, box } = stage; const car = buildCar(stage, '#f4f6f7'); const white = mat('#f4f6f7', { roughness: .4 }), red = mat('#d4281f', { roughness: .5 });
    box(2.9, 1.05, 1.75, white, car.group, -.75, 1.75, 0); box(2.95, .22, 1.8, red, car.group, -.75, 1.35, 0); box(.04, .5, .9, red, car.group, -2.24, 1.78, 0);
    const blue = glow('#2f7bff', 1, 1.8), redLight = glow('#ff3b30', 1, 1.8); box(.3, .14, .5, blue, car.group, .35, 2.38, -.45); box(.3, .14, .5, redLight, car.group, .35, 2.38, .45);
    return { ...car, blue, redLight };
}
