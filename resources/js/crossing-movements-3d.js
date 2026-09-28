import * as THREE from 'three';

export function addCrossingMovements({ scene, box, mesh, person, cars }) {
    const car = cars[0];
    cars[1].visible = false;
    car.position.set(0, 0, -9);
    car.rotation.y = Math.PI / 2;
    person.position.set(4.2, 0.16, -5.35);
    person.rotation.y = Math.PI / 2;

    // Garaje y entrada vehicular que cruza la acera.
    box(10, 5.5, 5, '#ddd5c2', scene, 0, 2.65, -12.5);
    box(5.5, 3.3, 0.16, '#253f50', scene, 0, 1.65, -9.95);
    for (let x = -2.5; x <= 2.5; x += 0.55) box(0.04, 3.1, 0.05, '#6f7d82', scene, x, 1.65, -9.84);
    box(5.7, 0.12, 6, '#aeb3ad', scene, 0, 0.09, -7.2);
    for (const x of [-2.85, 2.85]) box(0.18, 0.07, 6, '#e7ddd0', scene, x, 0.17, -7.2);

    const bicycle = new THREE.Group();
    scene.add(bicycle);
    bicycle.position.set(-15, 0.1, -3.15);
    const tube = (a, b, radius, color, parent) => {
        const start = new THREE.Vector3(...a);
        const end = new THREE.Vector3(...b);
        const rod = mesh(new THREE.CylinderGeometry(radius, radius, start.distanceTo(end), 10), color, parent, 0, 0, 0);
        rod.position.copy(start.clone().add(end).multiplyScalar(0.5));
        rod.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), end.sub(start).normalize());
        return rod;
    };
    for (const x of [-0.7, 0.7]) {
        mesh(new THREE.TorusGeometry(0.4, 0.05, 10, 28), '#263139', bicycle, x, 0.42, 0);
        for (let i = 0; i < 8; i++) {
            const angle = i * Math.PI / 4;
            tube([x, 0.42, 0], [x + Math.cos(angle) * 0.36, 0.42 + Math.sin(angle) * 0.36, 0], 0.007, '#b4bec2', bicycle);
        }
    }
    for (const [a, b] of [
        [[-0.7, .42, 0], [-.25, 1, 0]], [[-.25, 1, 0], [0, .42, 0]], [[0, .42, 0], [-.7, .42, 0]],
        [[-.25, 1, 0], [.45, 1.05, 0]], [[.45, 1.05, 0], [0, .42, 0]], [[.45, 1.05, 0], [.7, .42, 0]],
    ]) tube(a, b, .035, '#d56d35', bicycle);
    box(.32, .08, .22, '#263139', bicycle, -.25, 1.1, 0);
    tube([.45, 1.05, 0], [.52, 1.32, 0], .03, '#7e9098', bicycle);
    tube([.52, 1.32, -.25], [.52, 1.32, .25], .03, '#7e9098', bicycle);
    tube([-.2, 1.15, 0], [.02, 1.7, 0], .17, '#227b83', bicycle);
    mesh(new THREE.SphereGeometry(.18, 16, 14), '#a97353', bicycle, .07, 1.97, 0);
    mesh(new THREE.SphereGeometry(.2, 16, 12, 0, Math.PI * 2, 0, Math.PI / 2), '#f0b33c', bicycle, .07, 2.02, 0);
    for (const side of [-1, 1]) {
        tube([.02, 1.68, side * .17], [.51, 1.33, side * .2], .06, '#a97353', bicycle);
        tube([-.2, 1.12, side * .1], [.2, .78, side * .12], .085, '#344856', bicycle);
        tube([.2, .78, side * .12], [0, .45, side * .14], .075, '#344856', bicycle);
    }
    bicycle.rotation.y = Math.PI;

    const conflict = mesh(new THREE.CircleGeometry(1.25, 36), '#e75d45', scene, 0, 0.085, -3.2);
    conflict.rotation.x = -Math.PI / 2;
    conflict.material = new THREE.MeshBasicMaterial({ color: '#e75d45', transparent: true, opacity: 0.42, depthWrite: false });
    conflict.visible = false;
    const pathMaterial = new THREE.LineBasicMaterial({ color: '#f4a63a', transparent: true, opacity: 0.9 });
    const carPath = new THREE.Line(new THREE.BufferGeometry().setFromPoints([new THREE.Vector3(0, .18, -9), new THREE.Vector3(0, .18, -2)]), pathMaterial);
    const bikePath = new THREE.Line(new THREE.BufferGeometry().setFromPoints([new THREE.Vector3(-12, .2, -3.15), new THREE.Vector3(5, .2, -3.15)]), pathMaterial);
    scene.add(carPath, bikePath);

    let carStart = car.position.clone(), bikeStart = bicycle.position.clone(), personStart = person.position.clone();
    let carTarget = carStart.clone(), bikeTarget = bikeStart.clone(), personTarget = personStart.clone(), state = 0;
    return {
        state(next) {
            state = next;
            carStart = car.position.clone(); bikeStart = bicycle.position.clone(); personStart = person.position.clone();
            if (next === 1) {
                carTarget = new THREE.Vector3(0, 0, -1.8); bikeTarget = new THREE.Vector3(3.2, .1, -3.15); personTarget = new THREE.Vector3(.5, .16, -5.35);
            } else if (next === 2) {
                carTarget = new THREE.Vector3(0, 0, 7); bikeTarget = new THREE.Vector3(15, .1, -3.15); personTarget = new THREE.Vector3(4.2, .16, -5.35);
            } else if (next === 3) {
                carTarget = new THREE.Vector3(0, 0, -3); bikeTarget = new THREE.Vector3(1, .1, -3.15); personTarget = new THREE.Vector3(1, .16, -3.8);
            } else {
                carTarget = new THREE.Vector3(0, 0, -9); bikeTarget = new THREE.Vector3(-15, .1, -3.15); personTarget = new THREE.Vector3(4.2, .16, -5.35);
            }
            conflict.visible = next === 1 || next === 3;
            carPath.visible = bikePath.visible = next !== 2;
        },
        update(ease, view, camera) {
            car.position.lerpVectors(carStart, carTarget, ease);
            bicycle.position.lerpVectors(bikeStart, bikeTarget, ease);
            person.position.lerpVectors(personStart, personTarget, ease);
            person.rotation.y = state === 2 ? Math.sin(ease * Math.PI * 2) * .6 : -Math.PI / 2;
            if (view === 'pedestrian') {
                camera.position.set(4.5, 2.1, -6.8); camera.lookAt(0, .9, -3.1);
            } else {
                camera.position.set(14, 16, 20); camera.lookAt(0, .8, -4);
            }
        },
        dispose() { conflict.material.dispose(); pathMaterial.dispose(); },
    };
}
