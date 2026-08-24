import { readonly, shallowRef } from 'vue';

export interface ModalOptions<T> {
    onOpen?: (payload: T | null) => void;
    onClose?: (payload: T | null) => void;
}

export function useModal<T = never>(options: ModalOptions<T> = {}) {
    const isOpen = shallowRef(false);
    const data = shallowRef<T | null>(null);

    function open(payload?: T) {
        data.value = payload ?? null;
        options.onOpen?.(data.value);
        isOpen.value = true;
    }

    function close() {
        const payload = data.value;

        isOpen.value = false;
        data.value = null;
        options.onClose?.(payload);
    }

    function toggle(payload?: T) {
        if (isOpen.value) {
            close();

            return;
        }

        open(payload);
    }

    return {
        isOpen: readonly(isOpen),
        data: readonly(data),
        open,
        close,
        toggle,
    };
}

export function useModalManager<Name extends string, Payload = unknown>() {
    const activeModal = shallowRef<Name | null>(null);
    const modalData = shallowRef<Payload | null>(null);

    function openModal(name: Name, payload?: Payload) {
        activeModal.value = name;
        modalData.value = payload ?? null;
    }

    function closeModal() {
        activeModal.value = null;
        modalData.value = null;
    }

    function isModalOpen(name: Name) {
        return activeModal.value === name;
    }

    return {
        activeModal: readonly(activeModal),
        modalData: readonly(modalData),
        openModal,
        closeModal,
        isModalOpen,
    };
}
