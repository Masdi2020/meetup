<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { AppButton, AppInput, AppSelect } from '@/components/atoms';
import AppModal from '@/components/organisms/AppModal.vue';
import { downloadBookingExport } from '@/lib/bookingExport';
import type {
    BookingExportColumnKey,
    BookingExportFormat,
    BookingExportPayload,
    BookingExportOptions,
} from '@/lib/bookingExport';
import { exportData } from '@/routes/admin/bookings';
import type { BookingStatus } from '@/types/admin';

const props = defineProps<{
    open: boolean;
    options: BookingExportOptions;
    search: string;
    room: string;
    status: string;
}>();
const emit = defineEmits<{ close: [] }>();

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
const exportStatusOptions = computed(() => props.options.statuses);
const exportColumnOptions = computed(() => props.options.columns);
const selectedExportStatuses = ref<BookingStatus[]>(
    exportStatusOptions.value.map((status) => status.value),
);
const selectedExportColumns = ref<BookingExportColumnKey[]>([
    'borrower',
    'room',
    'activity',
    'date',
    'start',
    'end',
]);
const allExportColumnsSelected = computed(
    () =>
        selectedExportColumns.value.length === exportColumnOptions.value.length,
);
const allExportStatusesSelected = computed(
    () =>
        selectedExportStatuses.value.length ===
        exportStatusOptions.value.length,
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
const previewRows = computed(() => exportPreview.value?.rows ?? []);
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

function openExportModal() {
    const activeStatus = exportStatusOptions.value.find(
        (status) => status.value === props.status,
    )?.value;

    exportError.value = '';
    exportPreview.value = null;
    selectedExportStatuses.value = activeStatus
        ? [activeStatus]
        : exportStatusOptions.value.map((status) => status.value);
}

function closeExportModal() {
    if (exportBusy.value) {
        return;
    }

    exportError.value = '';
    exportPreview.value = null;
    emit('close');
}

function toggleAllExportColumns() {
    selectedExportColumns.value = allExportColumnsSelected.value
        ? []
        : exportColumnOptions.value.map((column) => column.key);
}

function toggleAllExportStatuses() {
    selectedExportStatuses.value = allExportStatusesSelected.value
        ? []
        : exportStatusOptions.value.map((status) => status.value);
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

async function fetchExport(preview: boolean): Promise<BookingExportPayload> {
    const query: Record<string, string | string[]> = {
        search: props.search,
        room: props.room,
        columns: selectedExportColumns.value,
        statuses: selectedExportStatuses.value.map((status) =>
            status.toUpperCase(),
        ),
        period: exportPeriod.value,
        preview: preview ? '1' : '0',
    };

    if (exportPeriod.value === 'date') {
        query.date = exportDate.value;
    } else if (exportPeriod.value === 'range') {
        query.start_date = exportStartDate.value;
        query.end_date = exportEndDate.value;
    } else if (exportPeriod.value === 'month') {
        query.month = exportMonth.value;
    } else if (exportPeriod.value === 'year') {
        query.year = exportYear.value;
    }

    const response = await fetch(exportData.url({ query }), {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error('Data export tidak dapat dimuat.');
    }

    return (await response.json()) as BookingExportPayload;
}

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
        const payload = await fetchExport(true);
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
        const payload = await fetchExport(false);
        await downloadBookingExport(exportFormat.value, payload);
        emit('close');
    } catch (error) {
        exportError.value =
            error instanceof Error
                ? error.message
                : 'Terjadi kesalahan saat membuat file export.';
    } finally {
        downloadLoading.value = false;
    }
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            openExportModal();
        }
    },
);
</script>

<template>
    <AppModal
        v-if="open"
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
                Export akan memuat seluruh data yang sesuai dengan filter aktif,
                bukan hanya halaman yang sedang terlihat.
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
                        <thead class="sticky top-0 z-10 bg-blue-600 text-white">
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
                    Preview dibatasi 20 baris. File hasil export tetap mencakup
                    seluruh {{ exportPreview.meta.total }} data.
                </p>
            </div>

            <p
                v-if="exportError"
                class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
            >
                {{ exportError }}
            </p>

            <div class="flex justify-end gap-3">
                <AppButton
                    type="button"
                    variant="neutral"
                    size="sm"
                    :disabled="exportBusy"
                    @click="closeExportModal"
                >
                    Batal
                </AppButton>
                <AppButton
                    v-if="exportPreview"
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="exportBusy"
                    @click="loadExportPreview"
                >
                    {{ previewLoading ? 'Memuat...' : 'Muat Ulang Preview' }}
                </AppButton>
                <AppButton
                    type="button"
                    variant="primary"
                    size="sm"
                    :disabled="
                        exportBusy ||
                        selectedExportColumns.length === 0 ||
                        selectedExportStatuses.length === 0 ||
                        !exportPeriodValid
                    "
                    @click="
                        exportPreview ? downloadPreview() : loadExportPreview()
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
                </AppButton>
            </div>
        </div>
    </AppModal>
</template>
