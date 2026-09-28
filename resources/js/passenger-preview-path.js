export const passengerModes = ['moving', 'boarding', 'descent', 'after'];
export const passengerChoices = ['segura', 'impulso', 'copiar'];
const mix = (a, b, t) => a + (b - a) * Math.max(0, Math.min(1, t));

// Coordinates shared by the renderer and trajectory tests. No collision is depicted.
export function passengerPose(mode, choice, seconds) {
    if (!passengerModes.includes(mode)) throw new Error('Unknown passenger scene');
    if (choice && !passengerChoices.includes(choice)) throw new Error('Unknown passenger choice');
    const t = Math.max(0, Math.min(seconds, 10));
    const p = { x: 2, y: .22, z: 6, busX: 0, walk: false, signal: false, door: 1, caption: '' };
    if (mode === 'moving') {
        p.busX = mix(-6, 0, t / 6);
        p.caption = 'El autobús se acerca con la puerta abierta. Luna y su acompañante esperan en la acera.';
        if (choice === 'segura') {
            p.signal = t > 2;
            p.caption = t < 6 ? 'Luna espera lejos del borde mientras el autobús todavía se mueve.' : 'El autobús se detuvo. Antes de acercarse, deben comprobar el acceso con el acompañante.';
        } else if (choice) {
            p.x = choice === 'impulso' ? mix(2, -1, t / 3) : mix(2, 3, t / 3);
            p.z = mix(6, 4.25, t / 3); p.walk = t < 3;
            p.busX = mix(-6, -3, Math.min(t, 3) / 6);
            p.caption = t < 3 ? (choice === 'impulso' ? 'Luna camina hacia la puerta de un vehículo que sigue moviéndose.' : 'Luna sigue a otra persona hacia el borde de la acera.') : 'Pausa de seguridad: acercarse al borde no detiene el autobús. No se muestra un impacto.';
        }
    } else if (mode === 'boarding') {
        p.caption = 'La puerta disponible queda del lado del tránsito. Luna permanece con el adulto en la acera.';
        if (choice === 'segura') {
            p.x = mix(2, .8, t / 2); p.walk = t < 2; p.signal = t >= 2;
            p.caption = 'Luna se acerca a su acompañante y señala el acceso expuesto. Esperan en la acera otra alternativa.';
        } else if (choice) {
            p.x = mix(2, choice === 'impulso' ? 4.8 : -4.8, t / 4);
            p.z = mix(6, 4.15, t / 4); p.walk = t < 4;
            p.caption = t < 4 ? (choice === 'impulso' ? 'Luna se apresura hacia el extremo del carro para rodearlo.' : 'Luna sigue a otra persona que intenta rodear el carro.') : 'La acción se detiene en el borde: para llegar a esa puerta tendría que entrar al espacio del tránsito.';
        }
    } else if (mode === 'descent') {
        p.x = 1; p.y = .65; p.z = .35; p.door = 0;
        p.caption = 'Vista abierta del carro: Luna sigue dentro. La fila está detenida, pero no hay acceso directo a la acera.';
        if (choice === 'segura') {
            p.signal = t > 1;
            p.caption = 'Luna avisa a su acompañante y permanece dentro. La puerta sigue cerrada mientras buscan un punto protegido.';
        } else if (choice) {
            p.door = mix(0, 1, t / 1.5);
            p.z = mix(.35, 1.55, (t - 1.5) / 2.5); p.y = mix(.65, .08, (t - 1.5) / 2.5);
            p.x = mix(1, choice === 'copiar' ? -.3 : 1, (t - 1.5) / 2.5); p.walk = t > 1.5 && t < 4;
            p.caption = t < 4 ? (choice === 'impulso' ? 'Luna abre y empieza a bajar hacia el espacio entre vehículos.' : 'Luna ve bajar a otra persona y comienza a imitarla.') : 'Pausa de seguridad: quedó entre vehículos, no en la acera. La fila permanece detenida; no se muestra un impacto.';
        }
    } else {
        // Intro demonstrates a real descent through the open doorway; choice starts on sidewalk.
        if (!choice) {
            p.x = 2; p.z = mix(2.5, 6, t / 4); p.y = mix(.78, .22, t / 4); p.walk = t < 4;
            p.caption = t < 4 ? 'El autobús está detenido. Luna baja por la puerta hacia la acera con su acompañante.' : 'Ya están en la acera. El autobús tapa parte de la vía: bajar no es una señal para cruzar.';
        } else if (choice === 'segura') {
            p.x = mix(2, 9, t / 6); p.z = 6; p.walk = t < 6; p.signal = t >= 6;
            p.caption = t < 6 ? 'Caminan por la acera hacia un punto de observación más despejado.' : 'Se detienen en la acera y vuelven a observar. No cruzan: todavía deben comprobar el tránsito.';
        } else {
            p.x = mix(2, choice === 'impulso' ? 5.3 : -5.3, t / 4);
            p.z = mix(6, 4.15, (t - 3) / 2); p.walk = t < 5;
            p.caption = t < 5 ? (choice === 'impulso' ? 'Luna va hacia la parte delantera del autobús para cruzar.' : 'Luna sigue a otras personas hacia la parte trasera del autobús.') : 'Pausa de seguridad antes de entrar a la calle: el autobús oculta otros movimientos.';
        }
    }
    return p;
}
