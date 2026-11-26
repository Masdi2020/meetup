import { readonly, shallowRef } from 'vue';

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
