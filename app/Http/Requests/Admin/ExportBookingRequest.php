<?php

namespace App\Http\Requests\Admin;

use App\Services\Admin\AdminBookingQueryService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportBookingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('status')) {
            $this->merge([
                'status' => strtoupper($this->string('status')->trim()->toString()),
            ]);
        }

        $statuses = $this->input('statuses');

        if (is_array($statuses)) {
            $this->merge([
                'statuses' => collect($statuses)
                    ->map(fn (mixed $status) => strtoupper((string) $status))
                    ->all(),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(array_keys(AdminBookingQueryService::EXPORT_COLUMNS)),
            ],
            'statuses' => ['sometimes', 'array', 'min:1'],
            'statuses.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(AdminBookingQueryService::EXPORT_STATUSES),
            ],
            'period' => ['sometimes', 'string', Rule::in(['all', 'date', 'range', 'month', 'year'])],
            'date' => ['required_if:period,date', 'date_format:Y-m-d'],
            'start_date' => ['required_if:period,range', 'date_format:Y-m-d'],
            'end_date' => ['required_if:period,range', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'month' => ['required_if:period,month', 'date_format:Y-m'],
            'year' => ['required_if:period,year', 'integer', 'min:2000', 'max:2100'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(AdminBookingQueryService::EXPORT_STATUSES)],
            'room' => ['nullable', 'integer', 'exists:rooms,id'],
        ];
    }

    /** @return array<string, mixed> */
    public function exportOptions(): array
    {
        return $this->safe()->only([
            'columns',
            'statuses',
            'period',
            'date',
            'start_date',
            'end_date',
            'month',
            'year',
        ]);
    }

    public function searchTerm(): string
    {
        return $this->string('search')->trim()->toString();
    }

    public function statusFilter(): string
    {
        return $this->string('status')->trim()->upper()->toString();
    }

    public function roomId(): ?int
    {
        $roomId = $this->integer('room');

        return $roomId > 0 ? $roomId : null;
    }
}
