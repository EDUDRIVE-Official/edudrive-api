// Model-space paths, not real-world distances. Opt-in for lessons that teach
// choosing a waiting point rather than completing a crossing.
const paths = {
    parked: [[.2, 0, 6.5], [16, 0, 6.5], [16, 0, -6.5], [10, 0, -8]],
    visibility: [[1.7, 0, 7], [17, 0, 7], [17, 0, -7]],
    route: [[-14, .3, 5.8], [-10, .3, 5.8], [-10, .3, -5.8], [14, .3, -5.8]],
    ramp: [[-6, .3, 6], [13, .3, 6], [13, .3, -6], [18, .3, -6]],
};

export function pedestrianDecisionPoints(scene, stopAtDecisionPoint = false) {
    if (!Object.hasOwn(paths, scene)) throw new Error(`Unknown pedestrian scene: ${scene}`);
    const points = paths[scene];
    // Only an explicit boolean enables the new teaching mode. Return fresh
    // arrays so one renderer cannot mutate another scene's path.
    return (stopAtDecisionPoint === true ? points.slice(0, 2) : points).map(point => [...point]);
}
