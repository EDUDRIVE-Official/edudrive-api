// Shared by the renderer and geometry checks. Coordinates: east +x, south +z.
export function pilotTurnState(seconds, choice) {
    // In the early-crossing branch the whole scene freezes while the car is
    // still turning, not after it has yielded. No contact is represented.
    const carSeconds = choice === 'go' ? Math.min(seconds, 2.5) * .7 : seconds;
    const t = Math.max(0, Math.min(carSeconds / 3, 1));
    const eased = t * t * (3 - 2 * t);
    const angle = -Math.PI / 2 + (.12 + .46 * eased) * Math.PI / 2;
    const crossing = choice === 'wait'
        ? Math.max(0, Math.min((seconds - 4) / 5, 1))
        : choice === 'go' ? Math.max(0, Math.min(seconds / 2.5, 1)) * .3 : 0;
    return {
        carX: -5 + 3.5 * Math.cos(angle), carZ: 5 + 3.5 * Math.sin(angle),
        carYaw: Math.atan2(-Math.sin(angle), Math.cos(angle)),
        crossing, lunaX: 4.3 - 8.6 * crossing, adultX: 4.9 - 9.4 * crossing,
    };
}
