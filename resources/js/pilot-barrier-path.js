export function pilotBarrierState(seconds, choice) {
    const t=Math.max(0,Math.min(seconds/4,1));
    const p=t*t*(3-2*t);
    return {
        lunaX: -(choice === 'wait' ? 2.4*p : -.55*p),
        lunaZ: 4.45-(choice === 'go' ? 1.75*p : 0),
        adultX: -1-(choice === 'wait' ? 2.4*p : 0),
        adultZ: 5.15,
        message: choice === 'wait' && seconds>=2,
        walking: seconds>0 && seconds<4,
    };
}
