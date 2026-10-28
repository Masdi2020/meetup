<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import { onBeforeUnmount, ref } from 'vue';
import AppModal from '@/components/organisms/AppModal.vue';

const props = defineProps<{ password: string }>();
const emit = defineEmits<{ close: [] }>();
const copyStatus = ref<'idle' | 'copied' | 'error'>('idle');
const copyLockDuration = 3000;
let copyUnlockTimer: ReturnType<typeof setTimeout> | undefined;

async function copyPassword() {
    if (copyStatus.value === 'copied' || !props.password) {
        return;
    }

    try {
        await navigator.clipboard.writeText(props.password);
        copyStatus.value = 'copied';

        copyUnlockTimer = window.setTimeout(() => {
            copyStatus.value = 'idle';
            copyUnlockTimer = undefined;
        }, copyLockDuration);
    } catch {
        copyStatus.value = 'error';
    }
}

onBeforeUnmount(() => {
    if (copyUnlockTimer) {
        window.clearTimeout(copyUnlockTimer);
    }
});
</script>

<template>
    <AppModal title="Password baru" max-width="md" @close="emit('close')">
        <p class="mt-2 text-gray-500">
            Password ini hanya ditampilkan sekali. Salin dan berikan kepada
            pengguna.
        </p>

        <div class="mt-4">
            <div class="flex items-center gap-3 rounded-lg bg-gray-100 p-3">
                <code
                    class="min-w-0 flex-1 overflow-x-auto font-mono text-lg font-semibold text-gray-800"
                >
                    {{ password }}
                </code>

                <button
                    type="button"
                    :disabled="copyStatus === 'copied'"
                    :aria-label="
                        copyStatus === 'copied'
                            ? 'Password sudah disalin'
                            : 'Salin password'
                    "
                    :title="
                        copyStatus === 'copied'
                            ? 'Password sudah disalin'
                            : 'Salin password'
                    "
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-emerald-600"
                    @click="copyPassword"
                >
                    <Check
                        v-if="copyStatus === 'copied'"
                        class="h-5 w-5"
                        aria-hidden="true"
                    />
                    <Copy v-else class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>

            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="-translate-y-1 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-1 opacity-0"
            >
                <div v-if="copyStatus !== 'idle'" class="mt-2 flex justify-end">
                    <p
                        :class="
                            copyStatus === 'copied'
                                ? 'bg-emerald-600'
                                : 'bg-red-600'
                        "
                        class="rounded-lg px-3 py-2 text-sm font-medium text-white shadow-lg"
                        :role="copyStatus === 'copied' ? 'status' : 'alert'"
                    >
                        {{
                            copyStatus === 'copied'
                                ? 'Password berhasil disalin.'
                                : 'Password gagal disalin. Silakan coba kembali.'
                        }}
                    </p>
                </div>
            </Transition>
        </div>

        <div class="mt-6 flex justify-end">
            <button
                type="button"
                class="rounded-lg bg-gray-800 px-5 py-2 text-white transition hover:bg-gray-900"
                @click="emit('close')"
            >
                Tutup
            </button>
        </div>
    </AppModal>
</template>
