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

export function remainingBookingTime(endsAt: string, now: Date): string {
    const diff = new Date(endsAt).getTime() - now.getTime();

    if (diff <= 0) {
        return 'Selesai';
    }

    const hours = Math.floor(diff / 3600000);
    const minutes = Math.floor((diff % 3600000) / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);

    if (hours > 0) {
        return `${hours} jam ${minutes} menit`;
    }

    return `${minutes} menit ${seconds} detik`;
}
