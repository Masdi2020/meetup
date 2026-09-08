import { router } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { onBeforeUnmount, onMounted } from 'vue';

export function useBookingUpdates(refresh: () => void | Promise<void>) {
    let active = true;
    let running = false;
    let pending = false;
    let timer: ReturnType<typeof setTimeout> | undefined;

    function scheduleRefresh() {
        if (!active) {
            return;
        }

        pending = true;

        if (running || timer !== undefined) {
            return;
        }

        timer = setTimeout(() => void refreshData(), 150);
    }

    async function refreshData() {
        timer = undefined;

        if (!active) {
            return;
        }

        running = true;
        pending = false;

        try {
            await refresh();
        } catch (error) {
            console.error('Gagal memperbarui data booking.', error);
        } finally {
            running = false;

            // A change received during a request still needs another refresh.
            if (pending) {
                scheduleRefresh();
            }
        }
    }

    useEcho(
        'bookings',
        ['.bookings.updated', '.pusher:subscription_succeeded'],
        scheduleRefresh,
    );

    function onVisibilityChange() {
        if (document.visibilityState === 'visible') {
            scheduleRefresh();
        }
    }

    onMounted(() => {
        // Catch up on initial subscription, reconnection, and returning tabs.
        scheduleRefresh();
        document.addEventListener('visibilitychange', onVisibilityChange);
    });

    onBeforeUnmount(() => {
        active = false;
        clearTimeout(timer);
        document.removeEventListener('visibilitychange', onVisibilityChange);
    });

    return scheduleRefresh;
}

export function useBookingPageUpdates(only: string[]) {
    let cancelRequest: (() => void) | undefined;
    const foregroundVisits = new Set<string>();

    const scheduleRefresh = useBookingUpdates(
        () =>
            new Promise<void>((resolve) => {
                if (foregroundVisits.size > 0) {
                    resolve();

                    return;
                }

                router.reload({
                    // Refresh flash props too, so an old toast isn't replayed.
                    only: [...only, 'flash'],
                    async: true,
                    showProgress: false,
                    preserveUrl: true,
                    onCancelToken: (token) => {
                        cancelRequest = () => token.cancel();
                    },
                    onFinish: () => {
                        cancelRequest = undefined;
                        resolve();
                    },
                });
            }),
    );

    const stopStartListener = router.on('start', ({ detail: { visit } }) => {
        if (!visit.async) {
            foregroundVisits.add(visit.id);
            cancelRequest?.();
        }
    });

    const stopFinishListener = router.on('finish', ({ detail: { visit } }) => {
        if (foregroundVisits.delete(visit.id)) {
            scheduleRefresh();
        }
    });

    onBeforeUnmount(() => {
        cancelRequest?.();
        stopStartListener();
        stopFinishListener();
    });
}
