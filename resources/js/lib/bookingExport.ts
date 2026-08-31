export type BookingExportFormat = 'pdf' | 'xlsx' | 'csv';

export type BookingExportColumnKey =
    | 'code'
    | 'borrower'
    | 'room'
    | 'activity'
    | 'date'
    | 'start'
    | 'end'
    | 'participants'
    | 'status'
    | 'request'
    | 'processed_notes'
    | 'submitted_at'
    | 'processed_at';

export interface BookingExportColumn {
    key: BookingExportColumnKey;
    label: string;
}

export interface BookingExportPayload {
    columns: BookingExportColumn[];
    rows: Array<Partial<Record<BookingExportColumnKey, string | number>>>;
    meta: {
        total: number;
        exported_at: string;
    };
}

const fileDate = () => new Date().toISOString().slice(0, 10);

const downloadBlob = (blob: Blob, filename: string) => {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
};

const cellValue = (
    row: BookingExportPayload['rows'][number],
    column: BookingExportColumn,
) => row[column.key] ?? '';

const safeCsvValue = (value: string | number) => {
    let text = String(value);

    // Prevent spreadsheet programs from interpreting user-entered text as a formula.
    if (/^[=+\-@\t\r]/.test(text)) {
        text = `'${text}`;
    }

    return `"${text.replaceAll('"', '""')}"`;
};

const exportCsv = (payload: BookingExportPayload) => {
    const lines = [
        payload.columns.map((column) => safeCsvValue(column.label)).join(','),
        ...payload.rows.map((row) =>
            payload.columns
                .map((column) => safeCsvValue(cellValue(row, column)))
                .join(','),
        ),
    ];

    downloadBlob(
        new Blob([`\uFEFF${lines.join('\r\n')}`], {
            type: 'text/csv;charset=utf-8',
        }),
        `peminjaman-${fileDate()}.csv`,
    );
};

const exportExcel = async (payload: BookingExportPayload) => {
    const { default: writeXlsxFile } = await import('write-excel-file/browser');
    const sheetData = [
        payload.columns.map((column) => ({
            value: column.label,
            fontWeight: 'bold' as const,
            backgroundColor: '#2563EB',
            textColor: '#FFFFFF',
            align: 'center' as const,
        })),
        ...payload.rows.map((row) =>
            payload.columns.map((column) => ({
                value: cellValue(row, column),
                wrap: true,
                verticalAlign: 'top' as const,
            })),
        ),
    ];
    const columns = payload.columns.map((column) => ({
        width: Math.min(
            45,
            Math.max(
                14,
                column.label.length + 2,
                ...payload.rows.map(
                    (row) => String(cellValue(row, column)).length + 2,
                ),
            ),
        ),
    }));

    await writeXlsxFile(sheetData, {
        sheet: 'Peminjaman',
        columns,
        stickyRowsCount: 1,
    }).toFile(`peminjaman-${fileDate()}.xlsx`);
};

const exportPdf = async (payload: BookingExportPayload) => {
    const [{ jsPDF }, { default: autoTable }] = await Promise.all([
        import('jspdf'),
        import('jspdf-autotable'),
    ]);
    const document = new jsPDF({
        orientation: payload.columns.length > 5 ? 'landscape' : 'portrait',
        unit: 'mm',
        format: 'a4',
    });

    document.setFontSize(16);
    document.text('Laporan Peminjaman Ruangan', 14, 16);
    document.setFontSize(9);
    document.setTextColor(90);
    document.text(
        `Diekspor: ${payload.meta.exported_at} | Total data: ${payload.meta.total}`,
        14,
        22,
    );

    autoTable(document, {
        startY: 27,
        head: [payload.columns.map((column) => column.label)],
        body: payload.rows.map((row) =>
            payload.columns.map((column) => String(cellValue(row, column))),
        ),
        styles: { fontSize: 7, cellPadding: 1.8, overflow: 'linebreak' },
        headStyles: { fillColor: [37, 99, 235], textColor: 255 },
        alternateRowStyles: { fillColor: [248, 250, 252] },
        margin: { left: 10, right: 10 },
        didDrawPage: ({ pageNumber }) => {
            const pageWidth = document.internal.pageSize.getWidth();
            const pageHeight = document.internal.pageSize.getHeight();
            document.setFontSize(8);
            document.setTextColor(120);
            document.text(
                `Halaman ${pageNumber}`,
                pageWidth - 10,
                pageHeight - 6,
                { align: 'right' },
            );
        },
    });
    document.save(`peminjaman-${fileDate()}.pdf`);
};

export const downloadBookingExport = async (
    format: BookingExportFormat,
    payload: BookingExportPayload,
) => {
    if (format === 'csv') {
        exportCsv(payload);

        return;
    }

    if (format === 'xlsx') {
        await exportExcel(payload);

        return;
    }

    await exportPdf(payload);
};
