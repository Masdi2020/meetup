export const roomColors = [
    '#fbcfe8',
    '#c7d2fe',
    '#bbf7d0',
    '#fed7aa',
    '#a5f3fc',
    '#fde68a',
    '#f5d0fe',
    '#d8b4fe',
    '#fef08a',
    '#a7f3d0',
];

export function getRoomColor(roomName: string) {
    const hash = roomName
        .split('')
        .reduce((acc, char) => acc + char.charCodeAt(0), 0);

    return roomColors[hash % roomColors.length];
}

export function getRoomTextColor() {
    return '#1f2937';
}
