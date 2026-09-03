<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FacilityService
{
    public function __construct(private AuditService $audits) {}

    /** @return Collection<int, Facility> */
    public function search(string $search): Collection
    {
        return Facility::query()
            ->with('rooms:id,name')
            ->withCount('rooms')
            ->when(
                $search !== '',
                fn ($query) => $query->where('name', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->get();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, int $actorId): Facility
    {
        return DB::transaction(function () use ($data, $actorId) {
            $facility = Facility::create($data);

            $this->audits->record(
                'Facility',
                $facility->id,
                'created',
                null,
                $facility->only(['name']),
                $actorId,
                'fasilitas dibuat oleh admin',
            );

            return $facility;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Facility $facility, array $data, int $actorId): void
    {
        DB::transaction(function () use ($facility, $data, $actorId) {
            $oldValues = $facility->only(['name']);
            $facility->update($data);

            $this->audits->record(
                'Facility',
                $facility->id,
                'updated',
                $oldValues,
                $facility->only(['name']),
                $actorId,
                'fasilitas diperbarui oleh admin',
            );
        });
    }

    public function delete(Facility $facility, int $actorId): void
    {
        DB::transaction(function () use ($facility, $actorId) {
            $oldValues = $facility->only(['name']);
            $facility->delete();

            $this->audits->record(
                'Facility',
                $facility->id,
                'deleted',
                $oldValues,
                null,
                $actorId,
                'fasilitas dihapus oleh admin',
            );
        });
    }
}
