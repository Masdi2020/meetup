<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, toRef, watch } from 'vue';
import ActionIconButton from '@/components/atoms/ActionIconButton.vue';
import AppInput from '@/components/atoms/AppInput.vue';
import AppSelect from '@/components/atoms/AppSelect.vue';
import AppTextarea from '@/components/atoms/AppTextarea.vue';
import VCalendarInput from '@/components/atoms/VCalendarInput.vue';
import FormField from '@/components/molecules/FormField.vue';
import StatCard from '@/components/molecules/StatCard.vue';
import AdminSearchPanel from '@/components/organisms/AdminSearchPanel.vue';
import AppModal from '@/components/organisms/AppModal.vue';
import ConfirmModal from '@/components/organisms/ConfirmModal.vue';
import DetailModal from '@/components/organisms/DetailModal.vue';
import { useAdminFilters } from '@/composables/useAdminFilters';
import {
    localDateString,
    useBookingAvailability,
} from '@/composables/useBookingAvailability';
import { useBookingPageUpdates } from '@/composables/useBookingUpdates';
import { useModalManager } from '@/composables/useModal';
import { downloadBookingExport } from '@/lib/bookingExport';
import type {
    BookingExportColumnKey,
    BookingExportFormat,
    BookingExportPayload,
} from '@/lib/bookingExport';
import type {
    AdminBookingStatusOption,
    AdminBooking as Booking,
    BookingStatus,
    BookingStats as Stats,
    BorrowerOption,
    Pagination,
    RoomOption as Room,
} from '@/types/admin';

const props = defineProps<{
    bookings: Pagination<Booking>;
    rooms?: Room[];
    users?: BorrowerOption[];
    statuses?: AdminBookingStatusOption[];
    stats?: Stats;
    filters?: {
        search?: string;
        status?: string;
        room?: string;
    };
}>();

const search = ref(props.filters?.search ?? '');

useBookingPageUpdates(['bookings', 'stats']);
const statusFilter = ref(props.filters?.status ?? '');
const roomFilter = ref(props.filters?.room ?? '');
const { applyFilters, resetFilters, goToPage } = useAdminFilters(
    '/admin/bookings',
    {
        debouncedSources: [search],
        instantSources: [statusFilter, roomFilter],
        query: () => ({
            search: search.value,
            status: statusFilter.value,
            room: roomFilter.value,
        }),
        reset: () => {
            search.value = '';
            statusFilter.value = '';
            roomFilter.value = '';
        },
    },
);
const today = localDateString();

const showAddBookingModal = computed(() => isModalOpen('add'));
const showAddBookingSuccess = ref(false);
const { openModal, closeModal, isModalOpen } = useModalManager<
    'add' | 'edit' | 'approve' | 'reject' | 'finish' | 'detail' | 'export'
>();
const showEditBookingModal = computed(() => isModalOpen('edit'));
const showApproveModal = computed(() => isModalOpen('approve'));
const showRejectModal = computed(() => isModalOpen('reject'));
const showFinishModal = computed(() => isModalOpen('finish'));
const showDetailModal = computed(() => isModalOpen('detail'));
const showExportModal = computed(() => isModalOpen('export'));
const selectedBookingId = ref<number | null>(null);
const detailBooking = ref<Booking | null>(null);
const bookingToDelete = ref<Booking | null>(null);
const bookingToCancel = ref<Booking | null>(null);
const isCancellingBooking = ref(false);
const rejectReason = ref('');
const rejectValidationErrors = ref({ reason: '' });
const addBookingPreview = ref<string | null>(null);
const addBookingBannerInput = ref<HTMLInputElement | null>(null);
const addBookingSubmitted = ref(false);
const exportFormat = ref<BookingExportFormat>('pdf');
const exportPreview = ref<BookingExportPayload | null>(null);
const previewLoading = ref(false);
const downloadLoading = ref(false);
const exportError = ref('');
type ExportPeriod = 'all' | 'date' | 'range' | 'month' | 'year';
const exportPeriod = ref<ExportPeriod>('all');
const exportDate = ref('');
const exportStartDate = ref('');
const exportEndDate = ref('');
const exportMonth = ref('');
const exportYear = ref(String(new Date().getFullYear()));
const exportYearOptions = Array.from({ length: 51 }, (_, index) =>
    String(2050 - index),
);
const exportPeriodOptions: Array<{
    value: ExportPeriod;
    label: string;
    description: string;
}> = [
    { value: 'all', label: 'Semua', description: 'Tanpa batas waktu' },
    { value: 'date', label: 'Tanggal', description: 'Satu tanggal' },
    { value: 'range', label: 'Rentang', description: 'Tanggal mulai-selesai' },
    { value: 'month', label: 'Bulan', description: 'Satu bulan' },
    { value: 'year', label: 'Tahun', description: 'Satu tahun' },
];
const exportStatusOptions: Array<{ value: BookingStatus; label: string }> = [
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'finished', label: 'Finished' },
];
const selectedExportStatuses = ref<BookingStatus[]>(
    exportStatusOptions.map((status) => status.value),
);
const exportColumnOptions: Array<{
    key: BookingExportColumnKey;
    label: string;
}> = [
    { key: 'id', label: 'ID' },
    { key: 'borrower', label: 'Peminjam' },
    { key: 'room', label: 'Ruangan' },
    { key: 'activity', label: 'Kegiatan' },
    { key: 'date', label: 'Tanggal' },
    { key: 'start', label: 'Waktu Mulai' },
    { key: 'end', label: 'Waktu Selesai' },
    { key: 'participants', label: 'Jumlah Peserta' },
    { key: 'status', label: 'Status' },
    { key: 'request', label: 'Catatan/Request' },
    { key: 'processed_notes', label: 'Catatan Proses' },
    { key: 'submitted_at', label: 'Dibuat Pada' },
    { key: 'processed_at', label: 'Diproses Pada' },
];
const selectedExportColumns = ref<BookingExportColumnKey[]>([
    'borrower',
    'room',
    'activity',
    'date',
    'start',
    'end',
]);
const allExportColumnsSelected = computed(
    () => selectedExportColumns.value.length === exportColumnOptions.length,
);
const allExportStatusesSelected = computed(
    () => selectedExportStatuses.value.length === exportStatusOptions.length,
);
const exportBusy = computed(
    () => previewLoading.value || downloadLoading.value,
);
const exportPeriodValid = computed(() => {
    if (exportPeriod.value === 'date') {
        return Boolean(exportDate.value);
    }

    if (exportPeriod.value === 'month') {
        return Boolean(exportMonth.value);
    }

    if (exportPeriod.value === 'range') {
        return (
            Boolean(exportStartDate.value) &&
            Boolean(exportEndDate.value) &&
            exportStartDate.value <= exportEndDate.value
        );
    }

    if (exportPeriod.value === 'year') {
        const year = Number(exportYear.value);

        return Number.isInteger(year) && year >= 2000 && year <= 2100;
    }

    return true;
});
const previewRows = computed(
    () => exportPreview.value?.rows.slice(0, 20) ?? [],
);
const formatDisplayDate = (value: string) => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

    return match ? `${match[3]}/${match[2]}/${match[1]}` : value;
};
const exportPeriodDescription = computed(() => {
    if (exportPeriod.value === 'date') {
        return `Tanggal ${formatDisplayDate(exportDate.value)}`;
    }

    if (exportPeriod.value === 'month') {
        const [year, month] = exportMonth.value.split('-');

        return `Bulan ${month}/${year}`;
    }

    if (exportPeriod.value === 'range') {
        return `${formatDisplayDate(exportStartDate.value)} sampai ${formatDisplayDate(exportEndDate.value)}`;
    }

    if (exportPeriod.value === 'year') {
        return `Tahun ${exportYear.value}`;
    }

    return 'Semua waktu';
});

const addBookingForm = useForm({
    user_id: null as number | null,
    room_id: null as number | null,
    date: '',
    start_time: '',
    end_time: '',
    title: '',
    participants: null as number | null,
    request: '',
    status: 'pending' as BookingStatus,
    banner: null as File | null,
});

const editBookingForm = useForm({
    title: '',
    date: '',
    start_time: '',
    end_time: '',
});
const editingBooking = ref<Booking | null>(null);

const addAvailability = useBookingAvailability({
    roomId: toRef(addBookingForm, 'room_id'),
    date: toRef(addBookingForm, 'date'),
    startTime: toRef(addBookingForm, 'start_time'),
    endTime: toRef(addBookingForm, 'end_time'),
});

const editingRoomId = computed(() => editingBooking.value?.room_id ?? null);
const editingBookingId = computed(() => editingBooking.value?.id ?? null);
const editAvailability = useBookingAvailability({
    roomId: editingRoomId,
    date: toRef(editBookingForm, 'date'),
    startTime: toRef(editBookingForm, 'start_time'),
    endTime: toRef(editBookingForm, 'end_time'),
    ignoreBookingId: editingBookingId,
});

function bookingDateForInput(value: string): string {
    const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value);

    return match ? `${match[3]}-${match[2]}-${match[1]}` : value;
}

function openEditBookingModal(booking: Booking) {
    editingBooking.value = booking;
    editBookingForm.clearErrors();
    editBookingForm.title = booking.activity;
    editBookingForm.date = bookingDateForInput(booking.date);
    editBookingForm.start_time = booking.start;
    editBookingForm.end_time = booking.end;
    openModal('edit');
}

function closeEditBookingModal() {
    if (editBookingForm.processing) {
        return;
    }

    closeModal();
    editingBooking.value = null;
    editBookingForm.reset();
}

function submitEditBooking() {
    if (!editingBooking.value) {
        return;
    }

    editBookingForm.put(`/admin/bookings/${editingBooking.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditBookingModal(),
    });
}

function openCancelBookingModal(booking: Booking) {
    bookingToCancel.value = booking;
}

function closeCancelBookingModal() {
    if (!isCancellingBooking.value) {
        bookingToCancel.value = null;
    }
}

function cancelApprovedBooking() {
    if (!bookingToCancel.value) {
        return;
    }

    isCancellingBooking.value = true;
    router.put(
        `/admin/bookings/${bookingToCancel.value.id}/cancel`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isCancellingBooking.value = false;
                bookingToCancel.value = null;
            },
        },
    );
}

const addBookingHasWarning = (field: string): boolean => {
    if (!addBookingSubmitted.value) {
        return false;
    }

    switch (field) {
        case 'user_id':
            return addBookingForm.user_id === null;
        case 'room_id':
            return addBookingForm.room_id === null;
        case 'date':
            return !addBookingForm.date;
        case 'start_time':
        case 'end_time':
        case 'title':
            return !addBookingForm[field];
        case 'participants':
            return (
                !addBookingForm.participants || addBookingForm.participants < 1
            );
        default:
            return false;
    }
};

function closeAddBookingModal() {
    closeModal();
    showAddBookingSuccess.value = false;
    addBookingForm.reset();
    clearAddBookingBanner();
    addBookingSubmitted.value = false;
}

function openExportModal() {
    const activeStatus = exportStatusOptions.find(
        (status) => status.value === statusFilter.value,
    )?.value;

    exportError.value = '';
    exportPreview.value = null;
    selectedExportStatuses.value = activeStatus
        ? [activeStatus]
        : exportStatusOptions.map((status) => status.value);
    openModal('export');
}

function closeExportModal() {
    if (exportBusy.value) {
        return;
    }

    exportError.value = '';
    exportPreview.value = null;
    closeModal();
}

function toggleAllExportColumns() {
    selectedExportColumns.value = allExportColumnsSelected.value
        ? []
        : exportColumnOptions.map((column) => column.key);
}

function toggleAllExportStatuses() {
    selectedExportStatuses.value = allExportStatusesSelected.value
        ? []
        : exportStatusOptions.map((status) => status.value);
}

watch(
    [
        selectedExportColumns,
        selectedExportStatuses,
        exportPeriod,
        exportDate,
        exportStartDate,
        exportEndDate,
        exportMonth,
        exportYear,
    ],
    () => {
        exportPreview.value = null;
        exportError.value = '';
    },
    { deep: true },
);

async function loadExportPreview() {
    if (selectedExportColumns.value.length === 0) {
        exportError.value = 'Pilih minimal satu kolom untuk diekspor.';

        return;
    }

    if (selectedExportStatuses.value.length === 0) {
        exportError.value = 'Pilih minimal satu status untuk diekspor.';

        return;
    }

    if (!exportPeriodValid.value) {
        exportError.value =
            exportPeriod.value === 'range'
                ? 'Isi tanggal mulai dan tanggal selesai yang valid.'
                : `Pilih ${exportPeriod.value} yang ingin diekspor.`;

        return;
    }

    previewLoading.value = true;
    exportError.value = '';
    exportPreview.value = null;

    try {
        const params = new URLSearchParams();
        params.set('search', search.value);
        params.set('room', roomFilter.value);
        selectedExportColumns.value.forEach((column) =>
            params.append('columns[]', column),
        );
        selectedExportStatuses.value.forEach((status) =>
            params.append('statuses[]', status.toUpperCase()),
        );
        params.set('period', exportPeriod.value);

        if (exportPeriod.value === 'date') {
            params.set('date', exportDate.value);
        } else if (exportPeriod.value === 'range') {
            params.set('start_date', exportStartDate.value);
            params.set('end_date', exportEndDate.value);
        } else if (exportPeriod.value === 'month') {
            params.set('month', exportMonth.value);
        } else if (exportPeriod.value === 'year') {
            params.set('year', exportYear.value);
        }

        const response = await fetch(
            `/admin/bookings/export-data?${params.toString()}`,
            { headers: { Accept: 'application/json' } },
        );

        if (!response.ok) {
            throw new Error('Data export tidak dapat dimuat.');
        }

        const payload = (await response.json()) as BookingExportPayload;
        exportPreview.value = payload;
    } catch (error) {
        exportError.value =
            error instanceof Error
                ? error.message
                : 'Terjadi kesalahan saat memuat preview.';
    } finally {
        previewLoading.value = false;
    }
}

async function downloadPreview() {
    if (!exportPreview.value) {
        exportError.value = 'Tampilkan preview sebelum mengunduh file.';

        return;
    }

    downloadLoading.value = true;
    exportError.value = '';

    try {
        await downloadBookingExport(exportFormat.value, exportPreview.value);
        closeModal();
    } catch (error) {
        exportError.value =
            error instanceof Error
                ? error.message
                : 'Terjadi kesalahan saat membuat file export.';
    } finally {
        downloadLoading.value = false;
    }
}

function openApproveModal(id: number) {
    selectedBookingId.value = id;
    openModal('approve');
}

function closeApproveModal() {
    closeModal();
    selectedBookingId.value = null;
}

function openRejectModal(id: number) {
    selectedBookingId.value = id;
    rejectReason.value = '';
    rejectValidationErrors.value.reason = '';
    openModal('reject');
}

function closeRejectModal() {
    closeModal();
    selectedBookingId.value = null;
    rejectReason.value = '';
    rejectValidationErrors.value.reason = '';
}

function openFinishModal(id: number) {
    selectedBookingId.value = id;
    openModal('finish');
}

function closeFinishModal() {
    closeModal();
    selectedBookingId.value = null;
}

function openDetailModal(booking: Booking) {
    detailBooking.value = booking;
    openModal('detail');
}

function closeDetailModal() {
    closeModal();
    detailBooking.value = null;
}

function openDeleteModal(booking: Booking) {
    bookingToDelete.value = booking;
}
function closeDeleteModal() {
    bookingToDelete.value = null;
}
function deleteBooking() {
    if (!bookingToDelete.value) {
        return;
    }

    router.delete(`/admin/bookings/${bookingToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
}
function handleAddBookingFile(event: Event) {
    const target = event.target as HTMLInputElement;

    if (!target.files?.length) {
        return;
    }

    const file = target.files[0];

    if (addBookingPreview.value) {
        URL.revokeObjectURL(addBookingPreview.value);
    }

    addBookingForm.banner = file;
    addBookingPreview.value = URL.createObjectURL(file);
}

function clearAddBookingBanner() {
    if (addBookingPreview.value) {
        URL.revokeObjectURL(addBookingPreview.value);
    }

    addBookingForm.banner = null;
    addBookingPreview.value = null;
    addBookingForm.clearErrors('banner');

    if (addBookingBannerInput.value) {
        addBookingBannerInput.value.value = '';
    }
}

function submitAddBooking() {
    addBookingSubmitted.value = true;

    if (
        addBookingHasWarning('user_id') ||
        addBookingHasWarning('room_id') ||
        addBookingHasWarning('date') ||
        addBookingHasWarning('start_time') ||
        addBookingHasWarning('end_time') ||
        addBookingHasWarning('title') ||
        addBookingHasWarning('participants')
    ) {
        return;
    }

    addBookingForm.post('/admin/bookings', {
        forceFormData: true,
        onSuccess: () => {
            showAddBookingSuccess.value = true;
            addBookingForm.reset();
            clearAddBookingBanner();
            addBookingSubmitted.value = false;
        },
    });
}

watch(
    () => addBookingForm.room_id,
    (roomId) => {
        if (roomId !== 1 && addBookingForm.banner) {
            clearAddBookingBanner();
        }
    },
);

onBeforeUnmount(() => {
    if (addBookingPreview.value) {
        URL.revokeObjectURL(addBookingPreview.value);
    }
});

function badgeClass(status: BookingStatus) {
    return {
        pending: 'bg-yellow-100 text-yellow-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
        cancelled: 'bg-gray-200 text-gray-700',
        finished: 'bg-blue-100 text-blue-700',
    }[status];
}

function confirmApprove() {
    if (!selectedBookingId.value) {
        return;
    }

    router.patch(
        `/admin/bookings/${selectedBookingId.value}/approve`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeApproveModal();
            },
        },
    );
}

function confirmReject() {
    rejectValidationErrors.value.reason = '';

    if (!rejectReason.value.trim()) {
        rejectValidationErrors.value.reason = 'Alasan penolakan wajib diisi.';

        return;
    }

    if (!selectedBookingId.value) {
        return;
    }

    router.patch(
        `/admin/bookings/${selectedBookingId.value}/reject`,
        { reason: rejectReason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeRejectModal();
            },
        },
    );
}

function confirmFinish() {
    if (!selectedBookingId.value) {
        return;
    }

    router.patch(
        '/admin/bookings/' + selectedBookingId.value + '/finish',
        {},
        {
            preserveScroll: true,
            onSuccess: () => closeFinishModal(),
        },
    );
}

function approve(id: number) {
    openApproveModal(id);
}

function reject(id: number) {
    openRejectModal(id);
}
</script>

<template>
    <div class="app-page">
        <div class="page-header">
            <div class="page-heading">
                <h1 class="page-title">Booking</h1>

                <p class="text-gray-500">Kelola seluruh peminjaman ruangan.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    class="rounded-lg border border-blue-600 bg-white px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50"
                    @click="openExportModal"
                >
                    Export Data
                </button>
                <button
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    @click="openModal('add')"
                >
                    Tambah Peminjaman
                </button>
            </div>
        </div>

        <AppModal
            v-if="showExportModal"
            title="Export Data Peminjaman"
            max-width="3xl"
            @close="closeExportModal"
        >
            <div class="space-y-6">
                <div>
                    <p class="mb-3 text-sm font-semibold text-gray-800">
                        Pilih format file
                    </p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label
                            v-for="format in [
                                {
                                    value: 'pdf',
                                    label: 'PDF',
                                    description: 'Siap cetak',
                                },
                                {
                                    value: 'xlsx',
                                    label: 'Excel',
                                    description: 'File .xlsx',
                                },
                                {
                                    value: 'csv',
                                    label: 'CSV',
                                    description: 'Data universal',
                                },
                            ] as const"
                            :key="format.value"
                            class="cursor-pointer rounded-xl border p-4 transition"
                            :class="
                                exportFormat === format.value
                                    ? 'border-blue-600 bg-blue-50 ring-1 ring-blue-600'
                                    : 'border-gray-200 hover:border-blue-300'
                            "
                        >
                            <input
                                v-model="exportFormat"
                                type="radio"
                                :value="format.value"
                                :disabled="exportBusy"
                                class="sr-only"
                            />
                            <span class="block font-semibold text-gray-900">{{
                                format.label
                            }}</span>
                            <span class="text-xs text-gray-500">{{
                                format.description
                            }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">
                                Status yang diekspor
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Pilih satu atau beberapa status peminjaman.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="text-sm font-medium text-blue-600 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="exportBusy"
                            @click="toggleAllExportStatuses"
                        >
                            {{
                                allExportStatusesSelected
                                    ? 'Batalkan semua'
                                    : 'Pilih semua'
                            }}
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="status in exportStatusOptions"
                            :key="status.value"
                            class="flex cursor-pointer items-center gap-2 rounded-full border px-3 py-2 text-sm transition"
                            :class="
                                selectedExportStatuses.includes(status.value)
                                    ? 'border-blue-600 bg-blue-50 font-medium text-blue-700'
                                    : 'border-gray-200 bg-white text-gray-600 hover:border-blue-300'
                            "
                        >
                            <input
                                v-model="selectedExportStatuses"
                                type="checkbox"
                                :value="status.value"
                                :disabled="exportBusy"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600"
                            />
                            <span>{{ status.label }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <div class="mb-3">
                        <p class="text-sm font-semibold text-gray-800">
                            Periode peminjaman
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Batasi export berdasarkan tanggal, rentang tanggal,
                            bulan, atau tahun tertentu.
                        </p>
                    </div>
                    <div
                        class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5"
                    >
                        <label
                            v-for="period in exportPeriodOptions"
                            :key="period.value"
                            class="cursor-pointer rounded-lg border px-3 py-2.5 transition"
                            :class="
                                exportPeriod === period.value
                                    ? 'border-blue-600 bg-blue-50 ring-1 ring-blue-600'
                                    : 'border-gray-200 hover:border-blue-300'
                            "
                        >
                            <input
                                v-model="exportPeriod"
                                type="radio"
                                :value="period.value"
                                :disabled="exportBusy"
                                class="sr-only"
                            />
                            <span class="block text-sm font-semibold">{{
                                period.label
                            }}</span>
                            <span class="text-xs text-gray-500">{{
                                period.description
                            }}</span>
                        </label>
                    </div>

                    <div
                        v-if="exportPeriod !== 'all'"
                        class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3"
                    >
                        <label class="block text-sm font-medium text-gray-700">
                            {{
                                exportPeriod === 'date'
                                    ? 'Pilih tanggal'
                                    : exportPeriod === 'range'
                                      ? 'Pilih rentang tanggal'
                                      : exportPeriod === 'month'
                                        ? 'Pilih bulan'
                                        : 'Pilih tahun'
                            }}
                        </label>
                        <AppInput
                            v-if="exportPeriod === 'date'"
                            v-model="exportDate"
                            type="date"
                            appearance="admin"
                            :disabled="exportBusy"
                            class="mt-2 sm:max-w-xs"
                        />
                        <AppInput
                            v-else-if="exportPeriod === 'month'"
                            v-model="exportMonth"
                            type="month"
                            appearance="admin"
                            :disabled="exportBusy"
                            class="mt-2 sm:max-w-xs"
                        />
                        <div
                            v-else-if="exportPeriod === 'range'"
                            class="mt-2 grid gap-3 sm:grid-cols-2"
                        >
                            <label class="text-xs text-gray-600">
                                Tanggal mulai
                                <AppInput
                                    v-model="exportStartDate"
                                    type="date"
                                    appearance="admin"
                                    :max="exportEndDate || undefined"
                                    :disabled="exportBusy"
                                    class="mt-1"
                                />
                            </label>
                            <label class="text-xs text-gray-600">
                                Tanggal selesai
                                <AppInput
                                    v-model="exportEndDate"
                                    type="date"
                                    appearance="admin"
                                    :min="exportStartDate || undefined"
                                    :disabled="exportBusy"
                                    class="mt-1"
                                />
                            </label>
                        </div>
                        <AppSelect
                            v-else
                            v-model="exportYear"
                            :disabled="exportBusy"
                            class="mt-2 sm:max-w-xs"
                        >
                            <option
                                v-for="year in exportYearOptions"
                                :key="year"
                                :value="year"
                            >
                                {{ year }}
                            </option>
                        </AppSelect>
                    </div>
                </div>

                <div>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-800">
                            Pilih kolom
                        </p>
                        <button
                            type="button"
                            class="text-sm font-medium text-blue-600 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="exportBusy"
                            @click="toggleAllExportColumns"
                        >
                            {{
                                allExportColumnsSelected
                                    ? 'Batalkan semua'
                                    : 'Pilih semua'
                            }}
                        </button>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label
                            v-for="column in exportColumnOptions"
                            :key="column.key"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5 text-sm hover:bg-gray-50"
                        >
                            <input
                                v-model="selectedExportColumns"
                                type="checkbox"
                                :value="column.key"
                                :disabled="exportBusy"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600"
                            />
                            <span>{{ column.label }}</span>
                        </label>
                    </div>
                </div>

                <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
                    Export akan memuat seluruh data yang sesuai dengan filter
                    aktif, bukan hanya halaman yang sedang terlihat.
                </div>

                <div
                    v-if="exportPreview"
                    class="overflow-hidden rounded-xl border border-gray-200"
                >
                    <div
                        class="flex flex-col gap-1 border-b bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p class="text-sm font-semibold text-gray-900">
                            Preview Data
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ exportPeriodDescription }} · Menampilkan
                            {{ previewRows.length }} dari
                            {{ exportPreview.meta.total }} data
                        </p>
                    </div>

                    <div
                        v-if="exportPreview.meta.total > 0"
                        class="max-h-72 overflow-auto"
                    >
                        <table class="w-full min-w-max text-left text-sm">
                            <thead
                                class="sticky top-0 z-10 bg-blue-600 text-white"
                            >
                                <tr>
                                    <th
                                        v-for="column in exportPreview.columns"
                                        :key="column.key"
                                        class="px-3 py-2.5 font-semibold whitespace-nowrap"
                                    >
                                        {{ column.label }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, rowIndex) in previewRows"
                                    :key="rowIndex"
                                    class="border-t even:bg-slate-50"
                                >
                                    <td
                                        v-for="column in exportPreview.columns"
                                        :key="column.key"
                                        class="max-w-64 px-3 py-2 align-top whitespace-normal text-gray-700"
                                    >
                                        {{ row[column.key] ?? '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-8 text-center text-sm text-gray-500">
                        Tidak ada data yang sesuai dengan filter aktif.
                    </div>

                    <p
                        v-if="exportPreview.meta.total > previewRows.length"
                        class="border-t bg-amber-50 px-4 py-2 text-xs text-amber-700"
                    >
                        Preview dibatasi 20 baris. File hasil export tetap
                        mencakup seluruh {{ exportPreview.meta.total }} data.
                    </p>
                </div>

                <p
                    v-if="exportError"
                    class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                >
                    {{ exportError }}
                </p>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100 disabled:opacity-50"
                        :disabled="exportBusy"
                        @click="closeExportModal"
                    >
                        Batal
                    </button>
                    <button
                        v-if="exportPreview"
                        type="button"
                        class="rounded-lg border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50 disabled:opacity-50"
                        :disabled="exportBusy"
                        @click="loadExportPreview"
                    >
                        {{
                            previewLoading ? 'Memuat...' : 'Muat Ulang Preview'
                        }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="
                            exportBusy ||
                            selectedExportColumns.length === 0 ||
                            selectedExportStatuses.length === 0 ||
                            !exportPeriodValid
                        "
                        @click="
                            exportPreview
                                ? downloadPreview()
                                : loadExportPreview()
                        "
                    >
                        {{
                            previewLoading
                                ? 'Memuat preview...'
                                : downloadLoading
                                  ? 'Menyiapkan file...'
                                  : exportPreview
                                    ? `Download ${exportFormat.toUpperCase()}`
                                    : 'Tampilkan Preview'
                        }}
                    </button>
                </div>
            </div>
        </AppModal>

        <AppModal
            v-if="showAddBookingModal"
            title="Tambah Peminjaman"
            max-width="3xl"
            @close="closeAddBookingModal"
        >
            <div
                v-if="showAddBookingSuccess"
                class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"
            >
                Peminjaman berhasil ditambahkan.
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <FormField
                    label="Nama Peminjam"
                    appearance="admin"
                    required
                    :error="
                        addBookingForm.errors.user_id ||
                        (addBookingHasWarning('user_id')
                            ? 'Nama peminjam wajib dipilih.'
                            : '')
                    "
                >
                    <AppSelect v-model="addBookingForm.user_id">
                        <option :value="null" disabled hidden>
                            Pilih Peminjam
                        </option>
                        <option
                            v-for="user in users"
                            :key="user.id"
                            :value="user.id"
                        >
                            {{ user.name
                            }}{{ user.role === 'admin' ? ' (Admin)' : '' }}
                        </option>
                    </AppSelect>
                </FormField>
                <FormField
                    label="Status"
                    appearance="admin"
                    required
                    :error="addBookingForm.errors.status"
                >
                    <AppSelect v-model="addBookingForm.status">
                        <option
                            v-for="status in statuses"
                            :key="status.code"
                            :value="status.code"
                        >
                            {{ status.label }}
                        </option>
                    </AppSelect>
                </FormField>
                <FormField
                    label="Ruangan"
                    appearance="admin"
                    required
                    :error="
                        addBookingForm.errors.room_id ||
                        (addBookingHasWarning('room_id')
                            ? 'Ruangan wajib dipilih.'
                            : '')
                    "
                >
                    <AppSelect v-model="addBookingForm.room_id">
                        <option :value="null" disabled hidden>
                            Pilih Ruangan
                        </option>
                        <option
                            v-for="room in rooms"
                            :key="room.id"
                            :value="room.id"
                        >
                            {{ room.name }}
                        </option>
                    </AppSelect>
                </FormField>
                <FormField
                    label="Tanggal"
                    appearance="admin"
                    required
                    :error="
                        addBookingForm.errors.date ||
                        (addBookingHasWarning('date')
                            ? 'Tanggal wajib diisi.'
                            : '')
                    "
                >
                    <VCalendarInput
                        v-model="addBookingForm.date"
                        appearance="admin"
                        mode="date"
                    />
                </FormField>
                <div class="grid min-w-0 grid-cols-2 gap-3 md:col-span-2">
                    <FormField
                        label="Dari Jam"
                        appearance="admin"
                        required
                        :error="
                            addBookingForm.errors.start_time ||
                            (addBookingHasWarning('start_time')
                                ? 'Waktu mulai wajib diisi.'
                                : '')
                        "
                    >
                        <VCalendarInput
                            v-model="addBookingForm.start_time"
                            appearance="admin"
                            mode="time"
                            :rules="addAvailability.startRules.value"
                            :disabled="
                                !addBookingForm.room_id ||
                                !addBookingForm.date ||
                                addAvailability.isLoading.value ||
                                Boolean(addAvailability.loadError.value) ||
                                addAvailability.availableStartTimes.value
                                    .length === 0
                            "
                        />
                    </FormField>
                    <FormField
                        label="Sampai Jam"
                        appearance="admin"
                        required
                        :error="
                            addBookingForm.errors.end_time ||
                            (addBookingHasWarning('end_time')
                                ? 'Waktu selesai wajib diisi.'
                                : '')
                        "
                    >
                        <VCalendarInput
                            v-model="addBookingForm.end_time"
                            appearance="admin"
                            mode="time"
                            :rules="addAvailability.endRules.value"
                            :disabled="
                                !addBookingForm.start_time ||
                                addAvailability.isLoading.value ||
                                Boolean(addAvailability.loadError.value) ||
                                addAvailability.availableEndTimes.value
                                    .length === 0
                            "
                        />
                    </FormField>
                </div>
                <p
                    v-if="addAvailability.isLoading.value"
                    class="-mt-2 text-sm text-gray-500 md:col-span-2"
                >
                    Memuat jam yang tersedia...
                </p>
                <p
                    v-else-if="addAvailability.loadError.value"
                    class="-mt-2 text-sm text-red-600 md:col-span-2"
                >
                    {{ addAvailability.loadError.value }}
                </p>
                <p
                    v-else-if="
                        addBookingForm.room_id &&
                        addBookingForm.date &&
                        addAvailability.availableStartTimes.value.length === 0
                    "
                    class="-mt-2 text-sm text-red-600 md:col-span-2"
                >
                    Tidak ada jam yang tersedia pada tanggal ini.
                </p>
                <p
                    v-else-if="addAvailability.bookedIntervals.value.length"
                    class="-mt-2 text-sm text-gray-500 md:col-span-2"
                >
                    Jam yang sudah terbooking disembunyikan dari pilihan.
                </p>
                <FormField
                    label="Judul Kegiatan"
                    appearance="admin"
                    required
                    :error="
                        addBookingForm.errors.title ||
                        (addBookingHasWarning('title')
                            ? 'Judul wajib diisi.'
                            : '')
                    "
                >
                    <AppInput
                        v-model="addBookingForm.title"
                        appearance="admin"
                    />
                </FormField>
                <FormField
                    label="Jumlah Orang"
                    appearance="admin"
                    required
                    :error="
                        addBookingForm.errors.participants ||
                        (addBookingHasWarning('participants')
                            ? 'Jumlah orang wajib diisi dan minimal 1.'
                            : '')
                    "
                >
                    <AppInput
                        v-model="addBookingForm.participants"
                        appearance="admin"
                        type="number"
                        min="1"
                    />
                </FormField>
                <FormField
                    class="md:col-span-2"
                    label="Permintaan Khusus"
                    appearance="admin"
                    :error="addBookingForm.errors.request"
                >
                    <AppTextarea v-model="addBookingForm.request" rows="3" />
                </FormField>
                <FormField
                    v-if="addBookingForm.room_id === 1"
                    class="md:col-span-2"
                    label="Unggah Banner Rapat"
                    appearance="admin"
                    hint="Format yang didukung: JPG, JPEG, PNG"
                    :error="addBookingForm.errors.banner"
                >
                    <input
                        ref="addBookingBannerInput"
                        type="file"
                        accept=".jpg,.jpeg,.png"
                        class="w-full"
                        @change="handleAddBookingFile"
                    />
                    <div v-if="addBookingPreview" class="banner-preview mt-3">
                        <img :src="addBookingPreview" alt="Preview Banner" />
                        <button
                            type="button"
                            class="banner-remove"
                            aria-label="Batalkan unggahan banner"
                            title="Batalkan unggahan"
                            @click="clearAddBookingBanner"
                        >
                            <X :size="18" :stroke-width="2.5" />
                        </button>
                    </div>
                </FormField>
                <div class="flex justify-end gap-3 md:col-span-2">
                    <button
                        type="button"
                        class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100"
                        @click="closeAddBookingModal"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        :disabled="addBookingForm.processing"
                        @click="submitAddBooking"
                    >
                        {{
                            addBookingForm.processing
                                ? 'Menyimpan...'
                                : 'Simpan'
                        }}
                    </button>
                </div>
            </div>
        </AppModal>

        <AppModal
            v-if="showEditBookingModal"
            title="Edit Booking Approved"
            max-width="2xl"
            @close="closeEditBookingModal"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <FormField
                    class="md:col-span-2"
                    label="Judul Kegiatan"
                    appearance="admin"
                    required
                    :error="editBookingForm.errors.title"
                >
                    <AppInput
                        v-model="editBookingForm.title"
                        appearance="admin"
                    />
                </FormField>
                <FormField
                    label="Tanggal"
                    appearance="admin"
                    required
                    :error="editBookingForm.errors.date"
                >
                    <VCalendarInput
                        v-model="editBookingForm.date"
                        appearance="admin"
                        mode="date"
                        :min-date="today"
                    />
                </FormField>
                <div class="hidden md:block" />
                <div class="grid min-w-0 grid-cols-2 gap-3 md:col-span-2">
                    <FormField
                        label="Dari Jam"
                        appearance="admin"
                        required
                        :error="editBookingForm.errors.start_time"
                    >
                        <VCalendarInput
                            v-model="editBookingForm.start_time"
                            appearance="admin"
                            mode="time"
                            :rules="editAvailability.startRules.value"
                            :disabled="
                                editAvailability.isLoading.value ||
                                Boolean(editAvailability.loadError.value) ||
                                editAvailability.availableStartTimes.value
                                    .length === 0
                            "
                        />
                    </FormField>
                    <FormField
                        label="Sampai Jam"
                        appearance="admin"
                        required
                        :error="editBookingForm.errors.end_time"
                    >
                        <VCalendarInput
                            v-model="editBookingForm.end_time"
                            appearance="admin"
                            mode="time"
                            :rules="editAvailability.endRules.value"
                            :disabled="
                                !editBookingForm.start_time ||
                                editAvailability.isLoading.value ||
                                Boolean(editAvailability.loadError.value) ||
                                editAvailability.availableEndTimes.value
                                    .length === 0
                            "
                        />
                    </FormField>
                </div>
                <p
                    v-if="editAvailability.isLoading.value"
                    class="-mt-2 text-sm text-gray-500 md:col-span-2"
                >
                    Memuat jam yang tersedia...
                </p>
                <p
                    v-else-if="editAvailability.loadError.value"
                    class="-mt-2 text-sm text-red-600 md:col-span-2"
                >
                    {{ editAvailability.loadError.value }}
                </p>
                <p
                    v-else-if="editAvailability.bookedIntervals.value.length"
                    class="-mt-2 text-sm text-gray-500 md:col-span-2"
                >
                    Jam booking lain disembunyikan dari pilihan.
                </p>
                <div class="flex justify-end gap-3 md:col-span-2">
                    <button
                        type="button"
                        class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100"
                        :disabled="editBookingForm.processing"
                        @click="closeEditBookingModal"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        :disabled="editBookingForm.processing"
                        @click="submitEditBooking"
                    >
                        {{
                            editBookingForm.processing
                                ? 'Menyimpan...'
                                : 'Simpan Perubahan'
                        }}
                    </button>
                </div>
            </div>
        </AppModal>

        <DetailModal
            v-if="showDetailModal"
            title="Detail Booking"
            max-width="3xl"
            @close="closeDetailModal"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-500">ID Booking</p>
                    <p class="mt-1 font-medium">{{ detailBooking?.id }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="mt-1 font-medium capitalize">
                        {{ detailBooking?.status }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Peminjam</p>
                    <p class="mt-1 font-medium">
                        {{ detailBooking?.borrower }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ruangan</p>
                    <p class="mt-1 font-medium">{{ detailBooking?.room }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-500">Kegiatan</p>
                    <p class="mt-1 font-medium">
                        {{ detailBooking?.activity }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Tanggal</p>
                    <p class="mt-1 font-medium">{{ detailBooking?.date }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Waktu</p>
                    <p class="mt-1 font-medium">
                        {{ detailBooking?.start }} - {{ detailBooking?.end }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Catatan/Request</p>
                    <p class="mt-1 font-medium">
                        {{ detailBooking?.request || '-' }}
                    </p>
                </div>
                <div v-if="detailBooking?.processed_notes">
                    <p class="text-sm text-gray-500">Processed Note</p>
                    <p class="mt-1 font-medium">
                        {{ detailBooking.processed_notes }}
                    </p>
                </div>
            </div>
        </DetailModal>

        <AppModal
            v-if="showApproveModal"
            title="Setujui Booking"
            max-width="lg"
            @close="closeApproveModal"
        >
            <div class="space-y-4">
                <p class="text-sm text-gray-700">
                    Booking ID <strong>{{ selectedBookingId }}</strong> akan
                    langsung ditandai sebagai <strong>Approved</strong> dan
                    tercatat sebagai diproses.
                </p>
                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100"
                        @click="closeApproveModal"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                        @click="confirmApprove"
                    >
                        Setujui
                    </button>
                </div>
            </div>
        </AppModal>

        <AppModal
            v-if="showRejectModal"
            title="Tolak Booking"
            max-width="lg"
            @close="closeRejectModal"
        >
            <div class="space-y-4">
                <FormField
                    label="Alasan Penolakan"
                    appearance="admin"
                    required
                    :error="rejectValidationErrors.reason"
                    ><AppTextarea v-model="rejectReason" rows="4"
                /></FormField>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100"
                        @click="closeRejectModal"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                        @click="confirmReject"
                    >
                        Tolak
                    </button>
                </div>
            </div>
        </AppModal>

        <AppModal
            v-if="showFinishModal"
            title="Akhiri Peminjaman"
            max-width="lg"
            @close="closeFinishModal"
        >
            <div class="space-y-4">
                <p class="text-sm text-gray-700">
                    Peminjaman ID <strong>{{ selectedBookingId }}</strong> akan
                    langsung ditandai sebagai <strong>Finished</strong>.
                </p>
                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100"
                        @click="closeFinishModal"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        @click="confirmFinish"
                    >
                        Akhiri Sekarang
                    </button>
                </div>
            </div>
        </AppModal>

        <div class="stats-grid">
            <StatCard label="Total" :value="stats?.total" />
            <StatCard label="Pending" :value="stats?.pending" tone="warning" />
            <StatCard
                label="Approved"
                :value="stats?.approved"
                tone="success"
            />
            <StatCard label="Finished" :value="stats?.finished" />
        </div>
        <!-- Filter -->

        <AdminSearchPanel
            v-model="search"
            placeholder="Cari booking..."
            :filter-columns="2"
            @search="applyFilters"
            @reset="resetFilters"
        >
            <template #filters>
                <AppSelect
                    v-model="statusFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Status</option>
                    <option
                        v-for="status in statuses"
                        :key="status.code"
                        :value="status.code"
                    >
                        {{ status.label }}
                    </option>
                </AppSelect>

                <AppSelect
                    v-model="roomFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Ruangan</option>
                    <option
                        v-for="room in rooms"
                        :key="room.id"
                        :value="room.id"
                    >
                        {{ room.name }}
                    </option>
                </AppSelect>
            </template>
        </AdminSearchPanel>

        <!-- Table -->

        <div class="ui-card overflow-x-auto">
            <table class="w-full min-w-[1050px]">
                <thead class="bg-gray-100">
                    <tr class="text-left text-sm">
                        <th class="px-5 py-4">ID</th>
                        <th class="px-5 py-4">Peminjam</th>
                        <th class="px-5 py-4">Ruangan</th>
                        <th class="px-5 py-4">Kegiatan</th>
                        <th class="px-5 py-4">Jadwal</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="booking in bookings?.data"
                        :key="booking.id"
                        class="border-t hover:bg-gray-50"
                    >
                        <td class="px-5 py-4 font-medium">
                            {{ booking.id }}
                        </td>

                        <td class="px-5 py-4">
                            {{ booking.borrower }}
                        </td>

                        <td class="px-5 py-4">
                            {{ booking.room }}
                        </td>

                        <td class="px-5 py-4">
                            {{ booking.activity }}
                        </td>

                        <td class="px-5 py-4">
                            {{ booking.date }}

                            <div class="text-xs text-gray-500">
                                {{ booking.start }} - {{ booking.end }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <span
                                :class="badgeClass(booking.status)"
                                class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                            >
                                {{ booking.status }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap justify-end gap-2">
                                <ActionIconButton
                                    action="detail"
                                    @click="openDetailModal(booking)"
                                />

                                <ActionIconButton
                                    v-if="booking.status === 'pending'"
                                    action="approve"
                                    @click="approve(booking.id)"
                                />

                                <ActionIconButton
                                    v-if="booking.status === 'approved'"
                                    action="edit"
                                    @click="openEditBookingModal(booking)"
                                />

                                <ActionIconButton
                                    v-if="booking.status === 'approved'"
                                    action="cancel"
                                    @click="openCancelBookingModal(booking)"
                                />

                                <ActionIconButton
                                    v-if="booking.status === 'approved'"
                                    action="finish"
                                    @click="openFinishModal(booking.id)"
                                />

                                <ActionIconButton
                                    v-if="booking.status === 'pending'"
                                    action="reject"
                                    @click="reject(booking.id)"
                                />
                                <ActionIconButton
                                    action="delete"
                                    @click="openDeleteModal(booking)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->

        <div
            v-if="bookings.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-2 border-t p-4"
        >
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm transition disabled:cursor-not-allowed disabled:opacity-50"
                :class="bookings.current_page === 1 ? '' : 'hover:bg-gray-100'"
                :disabled="bookings?.current_page === 1"
                @click="goToPage(bookings.current_page - 1)"
            >
                Sebelumnya
            </button>

            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm transition disabled:cursor-not-allowed disabled:opacity-50"
                :class="
                    bookings.current_page === bookings.last_page
                        ? ''
                        : 'hover:bg-gray-100'
                "
                :disabled="bookings.current_page === bookings.last_page"
                @click="goToPage(bookings.current_page + 1)"
            >
                Berikutnya
            </button>
        </div>
        <ConfirmModal
            v-if="bookingToCancel"
            title="Batalkan Booking Approved"
            :message="`Booking ID ${bookingToCancel.id} milik ${bookingToCancel.borrower} akan dibatalkan.`"
            confirm-label="Batalkan Booking"
            :processing="isCancellingBooking"
            @close="closeCancelBookingModal"
            @confirm="cancelApprovedBooking"
        />
        <ConfirmModal
            v-if="bookingToDelete"
            title="Hapus Booking"
            :message="`Booking ID ${bookingToDelete.id} untuk ${bookingToDelete.room} akan dihapus. Jadwalnya akan tersedia kembali untuk dipinjam.`"
            confirm-label="Hapus"
            @close="closeDeleteModal"
            @confirm="deleteBooking"
        />
    </div>
</template>

<style scoped>
.banner-preview {
    position: relative;
    width: fit-content;
    max-width: 350px;
}

.banner-preview img {
    display: block;
    width: 100%;
    max-width: 350px;
    border-radius: 10px;
    border: 1px solid #ddd;
    object-fit: cover;
}

.banner-remove {
    position: absolute;
    top: 8px;
    right: 8px;
    display: grid;
    width: 32px;
    height: 32px;
    padding: 0;
    place-items: center;
    border: 0;
    border-radius: 9999px;
    color: white;
    background: rgba(17, 24, 39, 0.82);
    cursor: pointer;
}

.banner-remove:hover {
    background: #dc2626;
}

.banner-remove:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}
</style>
