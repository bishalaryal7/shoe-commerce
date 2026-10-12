const europeanSizeChart = [
    { size: 36, footLengthCm: 22.5 },
    { size: 37, footLengthCm: 23.1 },
    { size: 38, footLengthCm: 23.8 },
    { size: 39, footLengthCm: 24.5 },
    { size: 40, footLengthCm: 25.1 },
    { size: 41, footLengthCm: 25.8 },
    { size: 42, footLengthCm: 26.5 },
    { size: 43, footLengthCm: 27.1 },
    { size: 44, footLengthCm: 27.8 },
    { size: 45, footLengthCm: 28.5 },
];

export function recommendEuropeanSize(footLengthCm: number): number | null {
    if (
        !Number.isFinite(footLengthCm) ||
        footLengthCm < europeanSizeChart[0].footLengthCm ||
        footLengthCm >
            europeanSizeChart[europeanSizeChart.length - 1].footLengthCm
    ) {
        return null;
    }

    return europeanSizeChart.reduce((closest, entry) => {
        const distance = Math.abs(entry.footLengthCm - footLengthCm);
        const closestDistance = Math.abs(closest.footLengthCm - footLengthCm);
        return distance <= closestDistance ? entry : closest;
    }).size;
}
