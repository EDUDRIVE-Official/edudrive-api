export function pilotDescentState(seconds, choice) {
    const t = Math.max(0, Math.min(seconds / 4, 1));
    const p = t*t*(3-2*t);
    return {
        lunaX: .7 - (choice === 'wait' ? .8*p : 0),
        lunaZ: 2 + (choice === 'go' ? 1.8*p : 0),
        lunaY: .35 - (choice === 'go' ? .22*p : 0),
        message: choice === 'wait' && seconds >= 2,
        walking: seconds > 0 && seconds < 4,
    };
}
