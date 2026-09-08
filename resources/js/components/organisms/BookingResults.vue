<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { FileText, ImagePlus, Upload, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import ActionIconButton from '@/components/atoms/ActionIconButton.vue';
import ConfirmModal from '@/components/organisms/ConfirmModal.vue';
import type { BookingResultFile } from '@/types/booking';

const props = defineProps<{
    baseUrl: string;
    documentations: BookingResultFile[];
    meetingMinutes: BookingResultFile | null;
    editable: boolean;
}>();
const emit = defineEmits<{ busy: [value: boolean] }>();
const documentationInput = ref<HTMLInputElement | null>(null);
const minutesInput = ref<HTMLInputElement | null>(null);
const documentationForm = useForm({ documentation: [] as File[] });
const minutesForm = useForm({ meeting_minutes: null as File | null });
const previews = ref<Array<{ file: File; url: string }>>([]);
const attachmentToDelete = ref<BookingResultFile | null>(null);
const deleting = ref(false);
const feedback = ref('');
const deleteError = ref('');
const busy = computed(
    () =>
        documentationForm.processing ||
        minutesForm.processing ||
        deleting.value,
);
watch(busy, (value) => emit('busy', value), { flush: 'sync' });
const documentationErrors = computed(() =>
    Object.values(documentationForm.errors),
);

function clearDocumentation() {
    previews.value.forEach(({ url }) => URL.revokeObjectURL(url));
    previews.value = [];
    documentationForm.reset();
    documentationForm.clearErrors();
}
function clearMinutes() {
    minutesForm.reset();
    minutesForm.clearErrors();
}
function selectDocumentation(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    input.value = '';

    if (!files.length || busy.value) {
        return;
    }

    feedback.value = '';
    documentationForm.clearErrors();

    if (previews.value.length + files.length > 10) {
        documentationForm.setError(
            'documentation',
            'Maksimal 10 foto dalam satu unggahan.',
        );

        return;
    }

    if (
        files.some(
            (file) =>
                !/\.(jpe?g|png)$/i.test(file.name) ||
                file.size > 5 * 1024 * 1024,
        )
    ) {
        documentationForm.setError(
            'documentation',
            'Gunakan foto JPG atau PNG dengan ukuran maksimal 5 MB per file.',
        );

        return;
    }

    previews.value.push(
        ...files.map((file) => ({ file, url: URL.createObjectURL(file) })),
    );
    documentationForm.documentation = previews.value.map(({ file }) => file);
}
function removePreview(index: number) {
    const [preview] = previews.value.splice(index, 1);
    URL.revokeObjectURL(preview.url);
    documentationForm.documentation = previews.value.map(({ file }) => file);
    documentationForm.clearErrors();
}
function selectMinutes(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file || busy.value) {
        return;
    }

    feedback.value = '';
    minutesForm.clearErrors();

    if (!/\.(pdf|doc|docx)$/i.test(file.name) || file.size > 10 * 1024 * 1024) {
        minutesForm.setError(
            'meeting_minutes',
            'Gunakan PDF, DOC, atau DOCX dengan ukuran maksimal 10 MB.',
        );

        return;
    }

    minutesForm.meeting_minutes = file;
}
function saveDocumentation() {
    if (busy.value || !props.editable || !previews.value.length) {
        return;
    }

    feedback.value = '';
    documentationForm.post(`${props.baseUrl}/results/documentation`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearDocumentation();
            feedback.value = 'Dokumentasi berhasil disimpan.';
        },
    });
}
function saveMinutes() {
    if (busy.value || !props.editable || !minutesForm.meeting_minutes) {
        return;
    }

    feedback.value = '';
    minutesForm.post(`${props.baseUrl}/results/meeting-minutes`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearMinutes();
            feedback.value = 'Notulensi berhasil disimpan.';
        },
    });
}
function deleteAttachment() {
    if (busy.value || !props.editable || !attachmentToDelete.value) {
        return;
    }

    deleting.value = true;
    feedback.value = '';
    deleteError.value = '';
    router.delete(
        `${props.baseUrl}/attachments/${attachmentToDelete.value.id}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                attachmentToDelete.value = null;
                feedback.value = 'File berhasil dihapus.';
            },
            onError: () => {
                deleteError.value = 'File gagal dihapus. Silakan coba lagi.';
            },
            onFinish: () => {
                deleting.value = false;
            },
        },
    );
}
onBeforeUnmount(() => {
    clearDocumentation();
    emit('busy', false);
});
</script>

<template>
    <div class="space-y-5">
        <p v-if="!editable" class="text-sm text-slate-500">
            Hasil rapat dapat diunggah setelah peminjaman selesai.
        </p>
        <p
            v-if="feedback"
            role="status"
            class="rounded-lg bg-green-50 p-3 text-sm text-green-700"
        >
            {{ feedback }}
        </p>
        <section
            class="space-y-3 rounded-xl border border-slate-200 p-4"
            :aria-busy="documentationForm.processing"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-slate-800">Dokumentasi</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        JPG atau PNG <strong> Maks. 5 MB per foto </strong> 10
                        foto per unggahan
                    </p>
                </div>
                <button
                    v-if="editable"
                    type="button"
                    class="ui-button"
                    :disabled="busy"
                    @click="documentationInput?.click()"
                >
                    <ImagePlus :size="16" aria-hidden="true" />Tambah foto
                </button>
            </div>
            <input
                ref="documentationInput"
                type="file"
                accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                multiple
                class="hidden"
                :disabled="busy || !editable"
                @change="selectDocumentation"
            />
            <div
                v-if="documentations.length || previews.length"
                class="grid grid-cols-2 gap-3 sm:grid-cols-3"
            >
                <div
                    v-for="attachment in documentations"
                    :key="attachment.id"
                    class="relative min-w-0 overflow-hidden rounded-lg border border-slate-200"
                >
                    <a
                        :href="`/storage/${attachment.path}`"
                        target="_blank"
                        rel="noreferrer"
                        :aria-label="`Lihat ${attachment.original_filename}`"
                        ><img
                            :src="`/storage/${attachment.path}`"
                            :alt="attachment.original_filename"
                            class="aspect-video w-full object-cover"
                        />
                        <p
                            class="truncate p-2 text-xs text-slate-600"
                            :title="attachment.original_filename"
                        >
                            {{ attachment.original_filename }}
                        </p></a
                    >
                    <ActionIconButton
                        v-if="editable"
                        action="delete"
                        :label="`Hapus ${attachment.original_filename}`"
                        class="absolute top-2 right-2"
                        :disabled="busy"
                        @click="
                            attachmentToDelete = attachment;
                            deleteError = '';
                        "
                    />
                </div>
                <div
                    v-for="(preview, index) in previews"
                    :key="preview.url"
                    class="relative min-w-0 overflow-hidden rounded-lg border-2 border-dashed border-blue-300 bg-blue-50"
                >
                    <img
                        :src="preview.url"
                        :alt="preview.file.name"
                        class="aspect-video w-full object-cover"
                    />
                    <button
                        type="button"
                        class="absolute top-2 right-2 rounded-full bg-white p-1 text-slate-700 shadow focus-visible:ring-2 focus-visible:ring-blue-500 disabled:opacity-50"
                        :aria-label="`Batalkan ${preview.file.name}`"
                        :title="`Batalkan ${preview.file.name}`"
                        :disabled="busy"
                        @click="removePreview(index)"
                    >
                        <X :size="18" aria-hidden="true" />
                    </button>
                    <div class="p-2 text-xs">
                        <p
                            class="truncate text-slate-700"
                            :title="preview.file.name"
                        >
                            {{ preview.file.name }}
                        </p>
                        <p class="mt-1 text-blue-700">Belum disimpan</p>
                    </div>
                </div>
            </div>
            <p
                v-else
                class="rounded-lg bg-slate-50 p-4 text-center text-sm text-slate-500"
            >
                Belum ada dokumentasi.
            </p>
            <p
                v-for="(error, index) in documentationErrors"
                :key="index"
                role="alert"
                class="text-sm text-red-600"
            >
                {{ error }}
            </p>
            <div
                v-if="previews.length"
                class="flex flex-wrap items-center justify-end gap-2"
            >
                <span class="mr-auto text-xs text-slate-500"
                    >{{ previews.length }} foto dipilih</span
                >
                <button
                    type="button"
                    class="ui-button"
                    :disabled="busy"
                    @click="clearDocumentation"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="ui-button ui-button--primary"
                    :disabled="busy || !editable"
                    @click="saveDocumentation"
                >
                    {{
                        documentationForm.processing
                            ? 'Menyimpan...'
                            : 'Simpan dokumentasi'
                    }}
                </button>
            </div>
            <p
                v-if="documentationForm.progress"
                role="status"
                class="text-xs text-blue-700"
            >
                Mengunggah {{ documentationForm.progress.percentage }}%
            </p>
        </section>
        <section
            class="space-y-3 rounded-xl border border-slate-200 p-4"
            :aria-busy="minutesForm.processing"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-slate-800">Notulensi</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        PDF, DOC, atau DOCX <strong> Maks. 10 MB </strong> 1
                        file
                    </p>
                </div>
                <button
                    v-if="editable"
                    type="button"
                    class="ui-button"
                    :disabled="busy"
                    @click="minutesInput?.click()"
                >
                    <Upload :size="16" aria-hidden="true" />{{
                        meetingMinutes || minutesForm.meeting_minutes
                            ? 'Ganti file'
                            : 'Pilih file'
                    }}
                </button>
            </div>
            <input
                ref="minutesInput"
                type="file"
                accept=".pdf,.doc,.docx"
                class="hidden"
                :disabled="busy || !editable"
                @change="selectMinutes"
            />
            <div
                v-if="meetingMinutes"
                class="flex items-center gap-3 rounded-lg bg-slate-50 p-3"
            >
                <FileText
                    :size="24"
                    class="shrink-0 text-slate-400"
                    aria-hidden="true"
                />
                <a
                    :href="`/storage/${meetingMinutes.path}`"
                    target="_blank"
                    rel="noreferrer"
                    class="min-w-0 flex-1 text-sm break-words text-blue-700 hover:underline"
                    >{{ meetingMinutes.original_filename
                    }}<span class="mt-1 block text-xs text-slate-500"
                        >Lihat file tersimpan</span
                    ></a
                >
                <ActionIconButton
                    v-if="editable"
                    action="delete"
                    label="Hapus notulensi"
                    :disabled="busy"
                    @click="
                        attachmentToDelete = meetingMinutes;
                        deleteError = '';
                    "
                />
            </div>
            <p
                v-else-if="!minutesForm.meeting_minutes"
                class="rounded-lg bg-slate-50 p-4 text-center text-sm text-slate-500"
            >
                Belum ada notulensi.
            </p>
            <div
                v-if="minutesForm.meeting_minutes"
                class="flex items-center gap-3 rounded-lg border border-dashed border-blue-300 bg-blue-50 p-3"
            >
                <FileText
                    :size="24"
                    class="shrink-0 text-blue-500"
                    aria-hidden="true"
                />
                <div class="min-w-0 flex-1 text-sm break-words text-slate-700">
                    {{ minutesForm.meeting_minutes.name }}
                    <p class="mt-1 text-xs text-blue-700">
                        {{
                            meetingMinutes
                                ? 'Menggantikan file lama setelah disimpan.'
                                : 'Belum disimpan'
                        }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full p-1 text-slate-600 focus-visible:ring-2 focus-visible:ring-blue-500 disabled:opacity-50"
                    aria-label="Batalkan file notulensi"
                    title="Batalkan file notulensi"
                    :disabled="busy"
                    @click="clearMinutes"
                >
                    <X :size="18" aria-hidden="true" />
                </button>
            </div>
            <p
                v-if="minutesForm.errors.meeting_minutes"
                role="alert"
                class="text-sm text-red-600"
            >
                {{ minutesForm.errors.meeting_minutes }}
            </p>
            <div
                v-if="minutesForm.meeting_minutes"
                class="flex flex-wrap justify-end gap-2"
            >
                <button
                    type="button"
                    class="ui-button"
                    :disabled="busy"
                    @click="clearMinutes"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="ui-button ui-button--primary"
                    :disabled="busy || !editable"
                    @click="saveMinutes"
                >
                    {{
                        minutesForm.processing
                            ? 'Menyimpan...'
                            : 'Simpan notulensi'
                    }}
                </button>
            </div>
            <p
                v-if="minutesForm.progress"
                role="status"
                class="text-xs text-blue-700"
            >
                Mengunggah {{ minutesForm.progress.percentage }}%
            </p>
        </section>
        <ConfirmModal
            v-if="attachmentToDelete"
            title="Hapus hasil rapat"
            :message="
                deleteError ||
                `Hapus file ${attachmentToDelete.original_filename}? File yang dihapus tidak dapat dikembalikan.`
            "
            confirm-label="Hapus"
            :processing="deleting"
            @close="!deleting && (attachmentToDelete = null)"
            @confirm="deleteAttachment"
        />
    </div>
</template>
