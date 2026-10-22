export function localDateString(date = new Date()): string {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

export function bookingDateForInput(value: string): string {
    const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value);

    if (match) {
        return `${match[3]}-${match[2]}-${match[1]}`;
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return value;
    }

    return '';
}
