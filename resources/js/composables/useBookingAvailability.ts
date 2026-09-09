import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type { Ref } from 'vue';
import type { VCalendarTimeRules } from '@/components/atoms/VCalendarInput.vue';
import { useBookingUpdates } from '@/composables/useBookingUpdates';

export interface BookedInterval {
    start_time: string;
    end_time: string;
}

interface AvailabilityResponse {
    booked_intervals: BookedInterval[];
}

interface BookingAvailabilityOptions {
    roomId: Ref<number | null>;
    date: Ref<string>;
    startTime: Ref<string>;
    endTime: Ref<string>;
    ignoreBookingId?: Ref<number | null>;
    allowPast?: boolean;
}

const SLOT_INTERVAL = 15;
const FIRST_SLOT = 7 * 60;
const LAST_SLOT = 23 * 60 + 45;

function timeToMinutes(time: string): number {
    const [hours = 0, minutes = 0] = time.slice(0, 5).split(':').map(Number);

    return hours * 60 + minutes;
}

function minutesToTime(minutes: number): string {
    return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
}

export function localDateString(date = new Date()): string {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

function createTimeRules(slots: Ref<string[]>): Ref<VCalendarTimeRules> {
    return computed(() => {
        const minutes = new Set(slots.value.map(timeToMinutes));

        return {
            hours: (hour: number) =>
                slots.value.some((time) =>
                    time.startsWith(`${String(hour).padStart(2, '0')}:`),
                ),
            minutes: (minute: number, parts) =>
                minutes.has(parts.hours * 60 + minute),
            seconds: 0,
            milliseconds: 0,
        };
    });
}

export function useBookingAvailability(options: BookingAvailabilityOptions) {
    const bookedIntervals = ref<BookedInterval[]>([]);
    const isLoading = ref(false);
    const loadError = ref('');
    const ignoreBookingId = options.ignoreBookingId ?? ref<number | null>(null);
    let requestController: AbortController | null = null;

    const minimumStartMinute = computed(() => {
        if (options.allowPast) {
            return FIRST_SLOT;
        }

        if (options.date.value !== localDateString()) {
            return FIRST_SLOT;
        }

        const now = new Date();
        const currentMinute = now.getHours() * 60 + now.getMinutes();

        return Math.max(
            FIRST_SLOT,
            Math.ceil(currentMinute / SLOT_INTERVAL) * SLOT_INTERVAL,
        );
    });

    const bookedMinuteRanges = computed(() =>
        bookedIntervals.value.map((interval) => ({
            start: timeToMinutes(interval.start_time),
            end: timeToMinutes(interval.end_time),
        })),
    );

    const availableStartTimes = computed(() => {
        const slots: string[] = [];

        for (
            let minute = minimumStartMinute.value;
            minute <= LAST_SLOT - SLOT_INTERVAL;
            minute += SLOT_INTERVAL
        ) {
            const overlaps = bookedMinuteRanges.value.some(
                (booking) =>
                    minute < booking.end &&
                    minute + SLOT_INTERVAL > booking.start,
            );

            if (!overlaps) {
                slots.push(minutesToTime(minute));
            }
        }

        return slots;
    });

    const availableEndTimes = computed(() => {
        if (!options.startTime.value) {
            return [];
        }

        const start = timeToMinutes(options.startTime.value);
        const slots: string[] = [];

        for (
            let minute = start + SLOT_INTERVAL;
            minute <= LAST_SLOT;
            minute += SLOT_INTERVAL
        ) {
            const overlaps = bookedMinuteRanges.value.some(
                (booking) => start < booking.end && minute > booking.start,
            );

            if (overlaps) {
                break;
            }

            slots.push(minutesToTime(minute));
        }

        return slots;
    });

    const startRules = createTimeRules(availableStartTimes);
    const endRules = createTimeRules(availableEndTimes);

    async function loadAvailability() {
        requestController?.abort();
        bookedIntervals.value = [];
        loadError.value = '';

        if (!options.roomId.value || !options.date.value) {
            isLoading.value = false;

            return;
        }

        const controller = new AbortController();
        requestController = controller;
        isLoading.value = true;

        const query = new URLSearchParams({
            room_id: String(options.roomId.value),
            date: options.date.value,
        });

        if (ignoreBookingId.value) {
            query.set('ignore_booking_id', String(ignoreBookingId.value));
        }

        try {
            const response = await fetch(`/booking/availability?${query}`, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error('Availability request failed');
            }

            const data = (await response.json()) as AvailabilityResponse;

            if (controller.signal.aborted || requestController !== controller) {
                return;
            }

            bookedIntervals.value = data.booked_intervals;

            if (
                options.startTime.value &&
                !availableStartTimes.value.includes(options.startTime.value)
            ) {
                options.startTime.value = '';
            }
        } catch (error) {
            if (
                requestController === controller &&
                !controller.signal.aborted &&
                !(error instanceof Error && error.name === 'AbortError')
            ) {
                loadError.value = 'Jadwal terbooking gagal dimuat.';
            }
        } finally {
            if (requestController === controller) {
                isLoading.value = false;
            }
        }
    }

    useBookingUpdates(loadAvailability);

    watch(
        [options.roomId, options.date, ignoreBookingId],
        () => void loadAvailability(),
        { immediate: true },
    );

    watch(
        [availableEndTimes, options.endTime, isLoading, loadError],
        ([availableTimes, endTime, loading, error]) => {
            if (loading || error || availableTimes.includes(endTime)) {
                return;
            }

            // Replace an invalid end time directly with a valid slot. Clearing
            // it first can leave VCalendar's time controls in an invalid state.
            const nextEndTime = availableTimes[0] ?? '';

            if (endTime !== nextEndTime) {
                options.endTime.value = nextEndTime;
            }
        },
        { immediate: true },
    );

    onBeforeUnmount(() => requestController?.abort());

    return {
        bookedIntervals,
        isLoading,
        loadError,
        availableStartTimes,
        availableEndTimes,
        startRules,
        endRules,
        reloadAvailability: loadAvailability,
    };
}
