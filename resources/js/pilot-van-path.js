export function pilotVanState(seconds, choice) {
    const t = Math.max(0, Math.min(seconds / 4, 1));
    const p = t*t*(3-2*t);
    return {
        x: 2.6 + (choice === 'wait' ? 4.4*p : 0),
        z: 4.5 - (choice === 'go' ? 1.65*p : 0),
        adultX: 4.1 + (choice === 'wait' ? 4.4*p : 0),
        adultZ: 5.3 - (choice === 'go' ? 1.65*p : 0),
        walking: seconds > 0 && seconds < 4,
    };
}
