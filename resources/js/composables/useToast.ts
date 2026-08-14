import { ref } from "vue";

export type ToastType = 'success' | 'error';

interface ToastState {
    message: string;
    type: ToastType;
}

const toast = ref<ToastState | null>(null);
let timer = 0;

export function useToast() {
    function showToast(message: string, type: ToastType = 'success', duration = 3500) {
        toast.value = { message, type };
        window.clearTimeout(timer);

        timer = window.setTimeout(() => {
            toast.value = null;
        }, duration);
    }

    function hideToast() {
        window.clearTimeout(timer);
        toast.value = null;
    }

    return { toast, showToast, hideToast };
}
