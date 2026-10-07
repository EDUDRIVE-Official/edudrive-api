import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

// Escena "Bicicleta caída": una persona cayó junto a la calzada y el tránsito sigue pasando.
// Resultados: 'protected' (lugar protegido + pedir ayuda), 'expose' (correr a la calzada), 'record' (grabar antes de pedir ayuda).
const DURATION = 9; // segundos por resultado
const STEP_STARTS = [0, .36, .7]; // progreso en que inicia cada paso de texto
const STEP_POSES = [.01, .5, 1]; // progreso mostrado al saltar a un paso
const FALLEN = new THREE.Vector3(-8, 0, 5.1);
const VOS_START = new THREE.Vector3(1, .1, 7.4);
const SAFE_SPOT = new THREE.Vector3(12, .1, 8.1);
const LANE_A = 2.7, LANE_B = -2.7;
const clamp01 = value => Math.min(1, Math.max(0, value));
const smooth = value => { const t = clamp01(value); return t * t * (3 - 2 * t); };
const easeOut = value => 1 - Math.pow(1 - clamp01(value), 3);

export function mountFallenBicycleDecision(host, onStep = () => {}) {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const disposables = []; // texturas y otros recursos a liberar
    const renderer = new THREE.WebGLRenderer({ antialias: true }); renderer.setPixelRatio(Math.min(devicePixelRatio, 1.7)); renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap; renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.05;
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block'; renderer.domElement.setAttribute('role', 'img'); renderer.domElement.setAttribute('aria-label', 'Escena tridimensional: una persona cayó de su bicicleta junto a la calzada mientras pasan vehículos. Hay un lugar protegido en la acera.'); host.appendChild(renderer.domElement);

    // Reflejos suaves (vidrio, pintura y metal) y cielo degradado.
    const scene = new THREE.Scene(); const pmrem = new THREE.PMREMGenerator(renderer); const envTexture = pmrem.fromScene(new RoomEnvironment(), .04).texture; scene.environment = envTexture; scene.environmentIntensity = .55; disposables.push(envTexture, pmrem);
    const canvasTexture = (width, height, draw, repeatX = 1, repeatY = 1) => { const canvas = document.createElement('canvas'); canvas.width = width; canvas.height = height; draw(canvas.getContext('2d'), width, height); const texture = new THREE.CanvasTexture(canvas); texture.colorSpace = THREE.SRGBColorSpace; texture.wrapS = texture.wrapT = THREE.RepeatWrapping; texture.repeat.set(repeatX, repeatY); texture.anisotropy = 4; disposables.push(texture); return texture; };
    const speckle = (ctx, w, h, base, tones, count, size) => { ctx.fillStyle = base; ctx.fillRect(0, 0, w, h); for (let i = 0; i < count; i++) { ctx.fillStyle = tones[i % tones.length]; ctx.fillRect(Math.random() * w, Math.random() * h, size, size); } };
    const sky = canvasTexture(2, 256, (ctx, w, h) => { const g = ctx.createLinearGradient(0, 0, 0, h); g.addColorStop(0, '#6fa8dc'); g.addColorStop(.55, '#b9d9ee'); g.addColorStop(1, '#e3eff3'); ctx.fillStyle = g; ctx.fillRect(0, 0, w, h); }); sky.wrapS = sky.wrapT = THREE.ClampToEdgeWrapping; sky.repeat.set(1, 1);
    scene.background = sky; scene.fog = new THREE.Fog('#d6e6ee', 60, 135);
    const camera = new THREE.PerspectiveCamera(43, 1, .1, 170);
    const OVERVIEW = { position: new THREE.Vector3(10, 19, 30), target: new THREE.Vector3(1, .4, 4) };
    const controls = new OrbitControls(camera, renderer.domElement); controls.enableDamping = true; controls.minDistance = 15; controls.maxDistance = 65; controls.maxPolarAngle = Math.PI * .48;
    const resetCamera = () => { camera.position.copy(OVERVIEW.position); controls.target.copy(OVERVIEW.target); controls.update(); }; resetCamera();
    scene.add(new THREE.HemisphereLight(0xeaf4ff, 0x5d7a52, 1.5)); const sun = new THREE.DirectionalLight(0xfff1d6, 3.3); sun.position.set(-20, 30, 20); sun.castShadow = true; sun.shadow.mapSize.set(2048, 2048); sun.shadow.bias = -.0004; sun.shadow.normalBias = .03; Object.assign(sun.shadow.camera, { left: -42, right: 42, top: 30, bottom: -30 }); scene.add(sun);

    const materials = []; const track = value => { materials.push(value); return value; };
    const mat = (color, options = {}) => track(new THREE.MeshStandardMaterial({ color, roughness: .75, ...options }));
    const paint = color => track(new THREE.MeshPhysicalMaterial({ color, roughness: .3, metalness: .55, clearcoat: 1, clearcoatRoughness: .08 }));
    const glass = () => track(new THREE.MeshPhysicalMaterial({ color: '#1d2d38', roughness: .04, metalness: .35, clearcoat: 1 }));
    const glow = (color, opacity = .7, intensity = 1.2) => track(new THREE.MeshStandardMaterial({ color, emissive: color, emissiveIntensity: intensity, transparent: true, opacity, side: THREE.DoubleSide }));
    const mesh = (geometry, material, parent, x = 0, y = 0, z = 0) => { const value = new THREE.Mesh(geometry, material); value.position.set(x, y, z); value.castShadow = value.receiveShadow = true; parent.add(value); return value; };
    const box = (w, h, d, material, parent, x, y, z) => mesh(new THREE.BoxGeometry(w, h, d), material, parent, x, y, z);
    const cyl = (r, h, material, parent, x, y, z, segments = 16) => mesh(new THREE.CylinderGeometry(r, r, h, segments), material, parent, x, y, z);
    const capsule = (r, length, material, parent, x, y, z) => mesh(new THREE.CapsuleGeometry(r, length, 6, 14), material, parent, x, y, z);
    const tube = (a, b, r, material, parent) => { const from = new THREE.Vector3(...a), to = new THREE.Vector3(...b), dir = to.clone().sub(from); const value = cyl(r, dir.length(), material, parent, 0, 0, 0, 10); value.position.copy(from).add(to).multiplyScalar(.5); value.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), dir.normalize()); return value; };

    // Terreno: pasto, calzada de asfalto, aceras con juntas, cordón y marcas viales.
    const grass = mat('#6f9d62', { map: canvasTexture(256, 256, (ctx, w, h) => speckle(ctx, w, h, '#6f9d62', ['#628f56', '#7aa96c', '#5b8650'], 5000, 2), 26, 14), roughness: 1 });
    const asphalt = mat('#444a50', { map: canvasTexture(256, 256, (ctx, w, h) => speckle(ctx, w, h, '#444a50', ['#3b4147', '#50565c', '#2f353a', '#5c6268'], 7000, 2), 20, 3), roughness: .92 });
    const pavement = mat('#cfccc2', { map: canvasTexture(128, 128, (ctx, w, h) => { speckle(ctx, w, h, '#cfccc2', ['#c4c1b7', '#d8d5cb'], 700, 2); ctx.strokeStyle = '#a9a69c'; ctx.lineWidth = 3; ctx.strokeRect(0, 0, w, h); }, 38, 1.6), roughness: .88 });
    box(110, .18, 60, grass, scene, 0, -.18, 0); box(100, .14, 13, asphalt, scene, 0, -.02, 0);
    for (const z of [-7.4, 7.4]) box(100, .24, 3.2, pavement, scene, 0, .04, z);
    const curb = mat('#b6b3aa'); for (const z of [-5.82, 5.82]) box(100, .22, .2, curb, scene, 0, .06, z);
    const paintWhite = mat('#eceae2', { roughness: .8 }), paintYellow = mat('#e2b93c', { roughness: .8 });
    for (const z of [-5.2, 5.2]) box(100, .02, .12, paintWhite, scene, 0, .06, z);
    for (let x = -46; x <= 46; x += 4.4) box(2.3, .02, .12, paintYellow, scene, x, .06, 0);

    // Entorno: casas, árboles, postes de luz y señal de ceda cerca del lugar protegido.
    function house(x, z, w, d, wall, roof, facing = 1) {
        const group = new THREE.Group(); group.position.set(x, 0, z); scene.add(group); const walls = mat(wall), roofMat = mat(roof, { roughness: .85 }), windowMat = glass(), frameMat = mat('#f1efe8');
        box(w, 4.2, d, walls, group, 0, 2.1, 0);
        const ridge = mesh(new THREE.CylinderGeometry(0, 1, 1, 4), roofMat, group, 0, 5.4, 0); ridge.scale.set(w * .78, 2.4, d * .78); ridge.rotation.y = Math.PI / 4;
        for (let i = -1; i <= 1; i += 2) { box(1.3, 1.3, .12, frameMat, group, i * w * .27, 2.5, facing * (d / 2 + .02)); box(1.1, 1.1, .14, windowMat, group, i * w * .27, 2.5, facing * (d / 2 + .04)); }
        box(1.1, 2.2, .14, mat('#7a4f33'), group, 0, 1.1, facing * (d / 2 + .04)); return group;
    }
    house(-18, -17, 11, 8, '#e5d6b8', '#9b6047', 1); house(2, -18, 9, 8, '#d9e1e8', '#5f6b7a', 1); house(21, -17, 12, 8, '#efe3cf', '#8a5a44', 1); house(-28, 17, 10, 8, '#e8d9c4', '#8c5c45', -1); house(22, 20, 11, 8, '#dfe6d7', '#67594a', -1);
    function tree(x, z, scale = 1) { const trunk = cyl(.2 * scale, 3.4 * scale, mat('#5a4434'), scene, x, 1.7 * scale, z, 10); trunk.rotation.z = .03; for (const [dx, dy, dz, r] of [[0, 4.2, 0, 1.55], [.8, 3.7, .3, 1.1], [-.7, 3.8, -.4, 1.2], [.1, 5.1, .2, 1]]) { const crown = mesh(new THREE.IcosahedronGeometry(r * scale, 2), mat('#3d7a4d', { roughness: .95 }), scene, x + dx * scale, dy * scale, z + dz * scale); crown.scale.y = 1.1; } }
    tree(-14, 11.5); tree(24, 11.8, 1.1); tree(-26, -10); tree(14, -11.5, .9); tree(-4, 12.4, .8);
    function lamp(x, z) { const metal = mat('#4c5560', { metalness: .6, roughness: .4 }); cyl(.08, 5.4, metal, scene, x, 2.7, z, 10); tube([x, 5.4, z], [x, 5.6, z - Math.sign(z) * 1.3], .06, metal, scene); box(.6, .14, .3, metal, scene, x, 5.6, z - Math.sign(z) * 1.4); const light = box(.5, .06, .24, glow('#fff2c4', 1, .8), scene, x, 5.5, z - Math.sign(z) * 1.4); light.castShadow = false; }
    for (const x of [-30, -10, 10, 30]) { lamp(x, 8.7); lamp(x + 5, -8.7); }

    // Lugar protegido: acera lejos de la calzada, tras una barrera baja con una banca.
    const post = mat('#2f7d4f', { roughness: .55 }); for (let x = 8.6; x <= 15.4; x += 1.7) { cyl(.12, 1, post, scene, x, .62, 6.75, 14); mesh(new THREE.SphereGeometry(.13, 12, 8), post, scene, x, 1.14, 6.75); }
    box(7, .08, .1, post, scene, 12, .98, 6.75);
    const wood = mat('#9a6b43'); box(1.9, .08, .5, wood, scene, 14.6, .55, 8.1); box(1.9, .5, .08, wood, scene, 14.6, .85, 8.38); for (const x of [13.8, 15.4]) box(.1, .5, .46, mat('#3c4650', { metalness: .5 }), scene, x, .28, 8.1);
    const safeRing = new THREE.Mesh(new THREE.RingGeometry(1.2, 1.65, 48), glow('#2fa866', .75)); safeRing.rotation.x = -Math.PI / 2; safeRing.position.set(SAFE_SPOT.x, .18, SAFE_SPOT.z); scene.add(safeRing);
    const accessStrip = box(13, .03, .7, glow('#2fa866', .6, .8), scene, -10.5, .19, 7.4); accessStrip.castShadow = false;

    // Personas: torso, cuello, cabeza con cabello y rostro, brazos y piernas articulados, calzado.
    function person({ shirt, pants, skin = '#b07a55', hair = '#2a1d17', shoe = '#20262c', scale = 1 }) {
        const group = new THREE.Group(); scene.add(group); group.scale.setScalar(scale);
        const skinMat = mat(skin, { roughness: .6 }), shirtMat = mat(shirt, { roughness: .85 }), pantsMat = mat(pants, { roughness: .85 }), hairMat = mat(hair, { roughness: .9 }), shoeMat = mat(shoe, { roughness: .6 });
        const hips = mesh(new THREE.SphereGeometry(.2, 18, 12), pantsMat, group, 0, .96, 0); hips.scale.set(1.1, .7, .78);
        const torso = capsule(.19, .42, shirtMat, group, 0, 1.3, 0); torso.scale.set(1.15, 1, .75);
        cyl(.055, .12, skinMat, group, 0, 1.62, 0, 12);
        const head = mesh(new THREE.SphereGeometry(.115, 24, 18), skinMat, group, 0, 1.78, 0); head.scale.set(.95, 1.18, 1.02);
        const cap = mesh(new THREE.SphereGeometry(.122, 24, 14, 0, Math.PI * 2, 0, Math.PI * .56), hairMat, group, 0, 1.79, -.012); cap.scale.set(.98, 1.2, 1.06); cap.rotation.x = -.18;
        mesh(new THREE.SphereGeometry(.022, 10, 8), skinMat, group, 0, 1.765, .112);
        for (const side of [-1, 1]) mesh(new THREE.SphereGeometry(.012, 8, 6), mat('#14100e'), group, side * .043, 1.8, .105);
        const limbs = [], knees = [], elbows = [], hands = [];
        for (const side of [-1, 1]) {
            const leg = new THREE.Group(); leg.position.set(side * .1, .94, 0); group.add(leg); capsule(.085, .32, pantsMat, leg, 0, -.23, 0);
            const knee = new THREE.Group(); knee.position.set(0, -.46, 0); leg.add(knee); capsule(.068, .34, pantsMat, knee, 0, -.22, 0); box(.12, .08, .27, shoeMat, knee, 0, -.5, .06);
            limbs.push(leg); knees.push(knee);
            const arm = new THREE.Group(); arm.position.set(side * .27, 1.5, 0); group.add(arm); capsule(.052, .22, shirtMat, arm, 0, -.17, 0);
            const elbow = new THREE.Group(); elbow.position.set(0, -.33, 0); arm.add(elbow); capsule(.045, .2, skinMat, elbow, 0, -.15, 0); const hand = mesh(new THREE.SphereGeometry(.05, 12, 10), skinMat, elbow, 0, -.31, 0);
            limbs.push(arm); elbows.push(elbow); hands.push(hand);
        }
        return { group, limbs, knees, elbows, hands }; // limbs: [piernaIzq, brazoIzq, piernaDer, brazoDer]
    }
    const vos = person({ shirt: '#e8782c', pants: '#2f4257', skin: '#b98260', hair: '#2a1d17', scale: .99 });
    const phone = box(.075, .15, .012, mat('#12181f', { roughness: .3 }), vos.hands[1], .01, -.05, .045); box(.065, .135, .014, glow('#86d1ff', 1, 1.1), phone, 0, 0, .002).castShadow = false;
    const fallen = person({ shirt: '#3f6fd0', pants: '#3a4658', skin: '#d0a07a', hair: '#6b4a2b', scale: .99 });
    fallen.group.position.set(FALLEN.x, .3, FALLEN.z); fallen.group.rotation.set(0, .35, Math.PI / 2 - .1); fallen.knees[0].rotation.x = .9; fallen.knees[1].rotation.x = .25; fallen.limbs[0].rotation.x = -.6; fallen.limbs[2].rotation.x = -.15; fallen.limbs[1].rotation.x = .5; fallen.limbs[3].rotation.x = -.7; fallen.elbows[1].rotation.x = -.9;
    const helmet = mesh(new THREE.SphereGeometry(.14, 20, 12, 0, Math.PI * 2, 0, Math.PI * .55), paint('#f2c230'), fallen.group, 0, 1.8, 0); helmet.scale.set(1, 1.15, 1.1); helmet.rotation.x = -.15;

    // Bicicleta con cuadro de diamante, ruedas con radios, manubrio, sillín y pedales; queda tendida.
    function bicycle() {
        const group = new THREE.Group(); const frameMat = paint('#d6483c'), tireMat = mat('#16191c', { roughness: .9 }), metal = mat('#aab2ba', { metalness: .85, roughness: .3 });
        const bb = [0, .3, 0], seat = [-.14, .8, 0], head = [.4, .76, 0], rear = [-.56, .34, 0], front = [.56, .34, 0];
        for (const [a, b] of [[bb, seat], [bb, head], [seat, head], [seat, rear], [bb, rear]]) tube(a, b, .022, frameMat, group); tube(head, front, .02, metal, group); tube([.4, .76, 0], [.42, .92, 0], .02, metal, group);
        for (const hub of [rear, front]) { const wheel = mesh(new THREE.TorusGeometry(.34, .03, 10, 36), tireMat, group, hub[0], hub[1], 0); wheel.castShadow = true; for (let i = 0; i < 12; i++) { const spoke = cyl(.004, .66, metal, group, hub[0], hub[1], 0, 4); spoke.rotation.z = i * Math.PI / 6; spoke.castShadow = false; } cyl(.03, .08, metal, group, hub[0], hub[1], 0, 12).rotation.x = Math.PI / 2; }
        cyl(.012, .5, mat('#222'), group, .42, .93, 0, 8).rotation.x = Math.PI / 2; box(.26, .04, .12, mat('#1b1b1b'), group, -.16, .84, 0); cyl(.1, .02, metal, group, 0, .3, .04, 20).rotation.x = Math.PI / 2;
        box(.12, .02, .07, mat('#222'), group, .1, .22, .16); box(.12, .02, .07, mat('#222'), group, -.1, .38, -.16); return group;
    }
    const bike = bicycle(); scene.add(bike); bike.rotation.order = 'YXZ'; bike.position.set(FALLEN.x - 1.8, .33, FALLEN.z - .45); bike.rotation.set(Math.PI / 2 - .14, .5, 0);

    // Vehículos: carrocería extruida, vidrios, faros, luces de freno, parrilla, placa y ruedas con rin. Frente en +x.
    function car(color) {
        const group = new THREE.Group(); scene.add(group); const body = paint(color), dark = mat('#14181c', { roughness: .6 });
        const lower = new THREE.Shape(); lower.moveTo(-2.25, .38); lower.lineTo(2.25, .38); lower.lineTo(2.3, .62); lower.quadraticCurveTo(2.3, .92, 2.02, .96); lower.lineTo(.8, 1.03); lower.lineTo(-1.6, 1.05); lower.quadraticCurveTo(-2.26, 1, -2.3, .75); lower.closePath();
        const lowerGeo = new THREE.ExtrudeGeometry(lower, { depth: 1.7, bevelEnabled: true, bevelSize: .07, bevelThickness: .07, bevelSegments: 4, curveSegments: 14 }); lowerGeo.translate(0, 0, -.85); mesh(lowerGeo, body, group);
        const cabin = new THREE.Shape(); cabin.moveTo(.95, 1.0); cabin.lineTo(.4, 1.5); cabin.lineTo(-1.15, 1.52); cabin.quadraticCurveTo(-1.5, 1.45, -1.8, 1.02); cabin.closePath();
        const cabinGeo = new THREE.ExtrudeGeometry(cabin, { depth: 1.5, bevelEnabled: true, bevelSize: .05, bevelThickness: .05, bevelSegments: 3 }); cabinGeo.translate(0, 0, -.75); mesh(cabinGeo, glass(), group);
        box(1.5, .07, 1.6, body, group, -.38, 1.56, 0); for (const z of [-.76, .76]) { tube([.45, 1.48, z], [.98, 1.02, z], .035, body, group); tube([-1.2, 1.5, z], [-1.84, 1.02, z], .04, body, group); }
        const headMat = glow('#fff4d0', 1, 1.6), tailMat = track(new THREE.MeshStandardMaterial({ color: '#8a1616', emissive: '#ff2a1f', emissiveIntensity: .25 })), brakeMat = tailMat;
        for (const z of [-.62, .62]) { box(.06, .13, .36, headMat, group, 2.35, .8, z).castShadow = false; box(.06, .14, .34, tailMat, group, -2.35, .86, z); box(.06, .1, .22, mat('#d98b2a'), group, 2.34, .62, z * 1.28); }
        box(.08, .2, 1, dark, group, 2.36, .6, 0); box(.14, .16, 1.76, mat('#2a2f35', { roughness: .5 }), group, 2.3, .45, 0); box(.14, .16, 1.76, mat('#2a2f35', { roughness: .5 }), group, -2.3, .45, 0); box(.02, .13, .36, mat('#f4f4ee'), group, 2.42, .52, 0); box(.02, .13, .36, mat('#f4f4ee'), group, -2.42, .52, 0);
        for (const z of [-.95, .95]) box(.14, .1, .1, body, group, .62, 1.12, z * 1.03);
        const wheels = []; for (const x of [-1.42, 1.42]) for (const z of [-.86, .86]) { const wheelGroup = new THREE.Group(); wheelGroup.position.set(x, .38, z); group.add(wheelGroup); const tire = cyl(.38, .26, mat('#15181b', { roughness: .95 }), wheelGroup, 0, 0, 0, 28); const rim = cyl(.24, .27, mat('#c3cad1', { metalness: .9, roughness: .25 }), wheelGroup, 0, 0, 0, 20); cyl(.07, .29, dark, wheelGroup, 0, 0, 0, 12); for (let i = 0; i < 5; i++) { const spoke = box(.05, .4, .02, mat('#9ea6ad', { metalness: .9, roughness: .3 }), wheelGroup, 0, 0, z > 0 ? .14 : -.14); spoke.rotation.z = i * Math.PI * 2 / 5; } tire.rotation.x = rim.rotation.x = Math.PI / 2; wheelGroup.children.forEach(child => { if (child !== tire && child !== rim && child.geometry.type === 'CylinderGeometry') child.rotation.x = Math.PI / 2; }); wheels.push(wheelGroup); }
        return { group, wheels, brakeMat };
    }
    const carA = car('#2b7195'); carA.group.rotation.y = Math.PI; // carril cercano, hacia -x
    const carB = car('#b83b2f'); // carril lejano, hacia +x

    // Marcadores didácticos.
    const clueMat = glow('#e8b72f', .65), riskMat = glow('#df3e39', .9, 1.5);
    const clue = new THREE.Mesh(new THREE.RingGeometry(1.3, 1.7, 48), clueMat); clue.rotation.x = -Math.PI / 2; clue.position.set(FALLEN.x, .18, FALLEN.z); scene.add(clue);
    const risk = new THREE.Mesh(new THREE.RingGeometry(.95, 1.3, 48), riskMat); risk.rotation.x = -Math.PI / 2; risk.visible = false; scene.add(risk);
    const waves = [0, 1, 2].map(() => { const wave = new THREE.Mesh(new THREE.RingGeometry(.28, .33, 32), glow('#2fa866', .8, 1.5)); wave.visible = false; scene.add(wave); return wave; });
    const clock = new THREE.Group(); clock.visible = false; scene.add(clock); clock.position.set(FALLEN.x, 3.1, FALLEN.z);
    const face = cyl(.62, .08, mat('#fffaf0'), clock, 0, 0, 0, 36); face.rotation.x = Math.PI / 2; face.castShadow = false;
    const needlePivot = new THREE.Group(); clock.add(needlePivot); box(.06, .46, .05, mat('#df3e39'), needlePivot, 0, .22, .07);

    function walk(actor, curve, t, running) {
        const p = curve.getPointAt(Math.min(.999, t)), next = curve.getPointAt(Math.min(1, t + .01)); actor.group.position.copy(p); if (t < 1) actor.group.lookAt(next.x, p.y, next.z);
        const moving = t > 0 && t < 1, swing = moving ? Math.sin(t * (running ? 58 : 38)) * (running ? .62 : .36) : 0;
        [0, 2].forEach((leg, i) => { const s = swing * (i ? -1 : 1); actor.limbs[leg].rotation.x = s; actor.knees[i].rotation.x = Math.max(0, -s) * 1.5; });
        [1, 3].forEach((arm, i) => { actor.limbs[arm].rotation.x = -swing * (i ? -1 : 1) * .9; actor.elbows[i].rotation.x = -(moving ? (running ? 1 : .35) : .08); });
    }
    const curves = {
        protected: new THREE.CatmullRomCurve3([VOS_START, new THREE.Vector3(6, .1, 7.9), SAFE_SPOT]),
        expose: new THREE.CatmullRomCurve3([VOS_START, new THREE.Vector3(-1.4, .1, 5.7), new THREE.Vector3(-6, .1, 3.5)]),
    };

    let outcome = 'intro', progress = 0, paused = false, view = 'overview', disposed = false, frame = 0, last = performance.now(), simTime = 0, step = -1;
    function emitStep() { const next = outcome === 'intro' ? -1 : STEP_STARTS.reduce((acc, start, index) => progress >= start ? index : acc, 0); if (next !== step) { step = next; if (next >= 0) onStep(next); } }
    function reset() {
        vos.group.position.copy(VOS_START); vos.group.rotation.set(0, -Math.PI / 2, 0); vos.limbs.forEach(limb => { limb.rotation.x = 0; }); vos.knees.forEach(k => { k.rotation.x = 0; }); vos.elbows.forEach(e => { e.rotation.x = -.08; });
        risk.visible = false; clock.visible = false; waves.forEach(wave => { wave.visible = false; }); carA.brakeMat.emissiveIntensity = .25; step = -1;
    }
    function setOutcome(value) { outcome = value; progress = reduced && value !== 'intro' ? 1 : 0; reset(); }
    function setStep(index) { if (outcome === 'intro') return; progress = STEP_POSES[Math.max(0, Math.min(2, index))]; paused = true; }

    const observer = new ResizeObserver(() => { if (!host.clientWidth || !host.clientHeight) return; renderer.setSize(host.clientWidth, host.clientHeight, false); camera.aspect = host.clientWidth / host.clientHeight; camera.updateProjectionMatrix(); }); observer.observe(host);
    function render(now) {
        if (disposed) return; const elapsed = (now - last) / 1000; const dt = Number.isFinite(elapsed) && elapsed > 0 ? Math.min(elapsed, .05) : 0; last = now; const rect = host.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < innerHeight && !document.hidden) {
            if (!paused) { simTime += dt; if (outcome !== 'intro') progress = Math.min(1, progress + dt / DURATION); }
            // Tránsito: en bucle, salvo el vehículo cercano cuando alguien entra a la calzada.
            if (reduced) { carA.group.position.set(14, 0, LANE_A); carB.group.position.set(-14, 0, LANE_B); }
            else { carA.group.position.set(40 - ((simTime * 7 + 20) % 80), 0, LANE_A); carB.group.position.set(-40 + ((simTime * 8) % 80), 0, LANE_B); }
            phone.visible = false; vos.limbs[3].rotation.x = 0; let brake = false, spin = 6;
            if (outcome === 'protected') {
                const t = clamp01(progress / .36); walk(vos, curves.protected, smooth(t), false);
                if (t >= 1) { vos.group.rotation.y = -Math.PI / 2 - .35; const up = smooth((progress - .36) / .1); vos.limbs[3].rotation.x = -2.35 * up; vos.elbows[1].rotation.x = -.5 * up; phone.visible = true; phone.rotation.x = .3; }
                safeRing.scale.setScalar(1 + (t >= 1 ? Math.sin(simTime * 5) * .08 : 0));
                if (progress > .4) waves.forEach((wave, index) => { const phase = ((simTime * .7 + index / 3) % 1); wave.visible = true; wave.position.set(vos.group.position.x - .2, 2.55 + phase * .5, vos.group.position.z); wave.rotation.x = -Math.PI / 2; wave.scale.setScalar(.6 + phase * 4.5); wave.material.opacity = (1 - phase) * .8; });
            } else if (outcome === 'expose') {
                const t = smooth((progress - .1) / .38); walk(vos, curves.expose, t, true);
                const arrive = easeOut(progress / .72); carA.group.position.set(36 - arrive * 34, 0, LANE_A); brake = arrive > .35 && arrive < 1 && progress < .92; spin = 12 * (1 - arrive);
                risk.visible = t > .15; risk.position.set(vos.group.position.x, .2, vos.group.position.z); risk.scale.setScalar(1 + Math.sin(simTime * 8) * .12);
            } else if (outcome === 'record') {
                const up = smooth(progress / .08); vos.limbs[3].rotation.x = -1.25 * up; vos.elbows[1].rotation.x = -1.1 * up; phone.visible = true; phone.rotation.x = .4;
                clock.visible = progress > .28; clock.lookAt(camera.position); needlePivot.rotation.z = -progress * Math.PI * 6;
            }
            carA.brakeMat.emissiveIntensity = brake ? 2.4 : .25;
            carA.wheels.forEach(wheel => { wheel.rotation.z -= dt * spin; }); carB.wheels.forEach(wheel => { wheel.rotation.z -= dt * 6; });
            accessStrip.visible = outcome === 'protected' && progress > .7; safeRing.visible = outcome === 'protected' || outcome === 'intro';
            clue.visible = outcome !== 'protected' || progress < .7; clue.scale.setScalar(1 + Math.sin(simTime * 4) * .08); const clueColor = outcome === 'record' ? '#df3e39' : '#e8b72f'; clueMat.color.set(clueColor); clueMat.emissive.set(clueColor);
            emitStep();
            if (view === 'pedestrian') { const p = vos.group.position; camera.position.set(p.x + 1.5, 1.95, p.z + 1.7); camera.lookAt(FALLEN.x, .9, FALLEN.z); }
            else if (view === 'driver') { const p = carA.group.position; camera.position.set(p.x - 1.2, 1.35, p.z); camera.lookAt(p.x - 22, 1, p.z + .5); }
            else controls.update(); renderer.render(scene, camera);
        }
        frame = requestAnimationFrame(render);
    }
    setOutcome('intro'); frame = requestAnimationFrame(render);
    return {
        setOutcome, setStep, setPaused(value) { paused = value; },
        setView(value) { view = value; controls.enabled = value === 'overview'; if (controls.enabled) resetCamera(); },
        dispose() { disposed = true; cancelAnimationFrame(frame); observer.disconnect(); controls.dispose(); scene.traverse(object => object.geometry?.dispose()); materials.forEach(value => value.dispose()); disposables.forEach(value => value.dispose?.()); renderer.dispose(); renderer.domElement.remove(); },
    };
}
