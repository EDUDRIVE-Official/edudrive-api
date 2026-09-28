import * as THREE from 'three';

export function addRoadActors({ scene, box, mesh, person, cars, variant = 'actors' }) {
    cars.forEach(car => { car.visible = false; });
    person.position.set(-6, 0.16, 5.3);
    const bus = new THREE.Group();
    scene.add(bus);
    bus.position.set(-6, 0, 2);
    box(8.4, 2.5, 2.5, '#e7d8b3', bus, 0, 1.95, 0);
    box(8.45, 0.7, 2.55, '#167d83', bus, 0, 0.95, 0);
    box(8.5, 0.12, 2.6, '#eae7db', bus, 0, 3.24, 0);
    for (const side of [-1, 1]) {
        for (let x = -3.3; x <= 3.5; x += 1.3) box(1.1, 1, 0.035, '#284d60', bus, x, 2.42, side * 1.27);
        for (const x of [-2.7, 2.7]) {
            const wheel = mesh(new THREE.CylinderGeometry(0.53, 0.53, 0.25, 28), '#202831', bus, x, 0.56, side * 1.28);
            wheel.rotation.x = Math.PI / 2;
            const hub = mesh(new THREE.CylinderGeometry(0.28, 0.28, 0.28, 20), '#8d9aa3', bus, x, 0.56, side * 1.28, 0.7);
            hub.rotation.x = Math.PI / 2;
        }
        box(0.12, 0.24, 0.4, '#fff2be', bus, 4.23, 1.1, side * 0.83);
    }
    box(0.04, 1.3, 2.2, '#284d60', bus, 4.23, 2.32, 0);
    box(0.05, 0.12, 2.1, '#162b3c', bus, 4.26, 2.85, 0);
    box(0.5, 0.12, 0.12, '#414a50', bus, 3.8, 2.6, 1.5);
    box(0.15, 0.5, 0.3, '#6d7780', bus, 3.9, 2.4, 1.65);
    const cyclist = new THREE.Group(); scene.add(cyclist); cyclist.position.set(-6, 0.1, -2);
    const tube = (a, b, radius, color, parent) => {
        const start = new THREE.Vector3(...a), end = new THREE.Vector3(...b);
        const rod = mesh(new THREE.CylinderGeometry(radius, radius, start.distanceTo(end), 10), color, parent, 0, 0, 0);
        rod.position.copy(start.clone().add(end).multiplyScalar(0.5));
        rod.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), end.sub(start).normalize());
    };
    for (const x of [-0.7, 0.7]) {
        mesh(new THREE.TorusGeometry(0.38, 0.045, 10, 28), '#28343d', cyclist, x, 0.4, 0);
        for (let i = 0; i < 8; i++) {
            const angle = i * Math.PI / 4;
            tube([x, 0.4, 0], [x + Math.cos(angle) * 0.35, 0.4 + Math.sin(angle) * 0.35, 0], 0.007, '#a8afb4', cyclist);
        }
    }
    for (const [a, b] of [
        [[-0.7, 0.4, 0], [-0.25, 0.98, 0]], [[-0.25, 0.98, 0], [0, 0.4, 0]],
        [[0, 0.4, 0], [-0.7, 0.4, 0]], [[-0.25, 0.98, 0], [0.45, 1.04, 0]],
        [[0.45, 1.04, 0], [0, 0.4, 0]], [[0.45, 1.04, 0], [0.7, 0.4, 0]],
    ]) tube(a, b, 0.035, '#c46a35', cyclist);
    box(0.32, 0.08, 0.22, '#313a44', cyclist, -0.25, 1.07, 0);
    tube([0.45, 1, 0], [0.5, 1.27, 0], 0.03, '#8598a1', cyclist);
    tube([0.5, 1.27, -0.25], [0.5, 1.27, 0.25], 0.03, '#8598a1', cyclist);
    tube([-0.25, 1.15, 0], [0, 1.73, 0], 0.17, '#cf6b48', cyclist);
    mesh(new THREE.SphereGeometry(0.17, 16, 12), '#b98059', cyclist, 0.05, 1.97, 0);
    mesh(new THREE.SphereGeometry(0.19, 16, 12, 0, Math.PI * 2, 0, Math.PI / 2), '#edf0dc', cyclist, 0.05, 2.01, 0);
    for (const side of [-1, 1]) {
        tube([0, 1.7, side * 0.18], [0.5, 1.28, side * 0.2], 0.06, '#b98059', cyclist);
        tube([-0.25, 1.1, side * 0.1], [0.2, 0.77, side * 0.12], 0.085, '#354756', cyclist);
        tube([0.2, 0.77, side * 0.12], [0, 0.43, side * 0.15], 0.075, '#354756', cyclist);
    }
    cyclist.rotation.y = Math.PI;
    const chair = new THREE.Group(); scene.add(chair); chair.position.set(-1, 0.17, -5.3);
    box(0.58, 0.1, 0.55, '#334758', chair, 0, 0.59, 0);
    box(0.58, 0.57, 0.1, '#334758', chair, 0, 0.88, -0.27);
    for (const side of [-1, 1]) {
        const wheel = mesh(new THREE.TorusGeometry(0.42, 0.045, 10, 28), '#343d45', chair, side * 0.38, 0.43, -0.1);
        wheel.rotation.y = Math.PI / 2;
        for (let i = 0; i < 8; i++) {
            const a = i * Math.PI / 4;
            tube([side * 0.38, 0.43, -0.1], [side * 0.38, 0.43 + Math.sin(a) * 0.38, -0.1 + Math.cos(a) * 0.38], 0.009, '#a4b1b5', chair);
        }
        const caster = mesh(new THREE.TorusGeometry(0.09, 0.025, 8, 16), '#343d45', chair, side * 0.29, 0.11, 0.46);
        caster.rotation.y = Math.PI / 2;
        tube([side * 0.28, 0.57, 0], [side * 0.28, 0.13, 0.45], 0.025, '#a4b1b5', chair);
        tube([side * 0.22, 1.15, 0], [side * 0.32, 0.79, 0.12], 0.06, '#ad795a', chair);
        tube([side * 0.15, 0.73, 0], [side * 0.15, 0.7, 0.42], 0.09, '#394954', chair);
        tube([side * 0.15, 0.7, 0.42], [side * 0.15, 0.23, 0.43], 0.07, '#394954', chair);
    }
    mesh(new THREE.CapsuleGeometry(0.2, 0.24, 6, 14), '#75669e', chair, 0, 1, 0);
    mesh(new THREE.SphereGeometry(0.18, 16, 14), '#ad795a', chair, 0, 1.43, 0);
    chair.visible = variant !== 'bus-stop';
    // An illustrative blocked line of sight, not a measured vehicle blind zone.
    const points = [-7.5, 0.09, 0.65, -4.5, 0.09, 0.65, -1, 0.09, -4, -11, 0.09, -4];
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(points, 3));
    geometry.setIndex([0, 1, 2, 0, 2, 3]);
    const overlayMaterial = new THREE.MeshBasicMaterial({ color: '#ed8644', transparent: true, opacity: 0.3, side: THREE.DoubleSide, depthWrite: false });
    const overlay = new THREE.Mesh(geometry, overlayMaterial); scene.add(overlay);
    let start = -6, target = -6;
    return {
        state(step) {
            start = bus.position.x;
            target = step === 2 ? 18 : -6;
            overlay.visible = step === 1;
            cyclist.visible = step === 1 || step === 2;
        },
        update(ease, view, camera) {
            bus.position.x = THREE.MathUtils.lerp(start, target, ease);
            // Hide the shell only for the interior camera; its front looks toward the crossing.
            bus.visible = view !== 'driver';
            if (view === 'pedestrian') { camera.position.set(-4.8, 1.65, 7); camera.lookAt(-6, 1.1, -3); }
            else if (view === 'driver') { camera.position.set(bus.position.x + 3.4, 2.5, 1.45); camera.lookAt(bus.position.x + 13, 1.2, 2); }
            else { camera.position.set(5, 23, 17); camera.lookAt(-4, 0.5, 0); }
        },
        dispose() { overlayMaterial.dispose(); },
    };
}
