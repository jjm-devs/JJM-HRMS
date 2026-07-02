<?php

namespace App\Services\Hr;

use App\Models\DepartmentStream;
use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrgUnitStreamService
{
    /**
     * Return null when no org-unit-specific restriction should be applied.
     *
     * During rollout, org units with no explicit stream mapping continue to allow
     * all active streams. Once all org units are mapped, this can become strict.
     *
     * @return array<int, int>|null
     */
    public function allowedActiveIdsFor(mixed $orgUnitId): ?array
    {
        if (blank($orgUnitId)) {
            return null;
        }

        $orgUnit = OrgUnit::query()->find((int) $orgUnitId);

        if (! $orgUnit) {
            return null;
        }

        if (! $orgUnit->departmentStreams()->exists()) {
            return null;
        }

        return $orgUnit->departmentStreams()
            ->where('department_streams.status', 'active')
            ->orderBy('department_streams.name')
            ->pluck('department_streams.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function activeOptionsFor(mixed $orgUnitId): array
    {
        return $this->activeQueryFor($orgUnitId)
            ->pluck('name', 'id')
            ->all();
    }

    public function activeQueryFor(mixed $orgUnitId): Builder
    {
        $query = DepartmentStream::query()
            ->where('status', 'active')
            ->orderBy('name');

        $allowedIds = $this->allowedActiveIdsFor($orgUnitId);

        if ($allowedIds !== null) {
            $query->whereIn('id', $allowedIds);
        }

        return $query;
    }

    /**
     * Union of the allowed active stream ids across several org units.
     * Returns null (no restriction — all active streams) when the list is empty
     * or when any selected office has no explicit mapping (lenient rollout).
     *
     * @param  array<int, mixed>  $orgUnitIds
     * @return array<int, int>|null
     */
    public function allowedActiveIdsForAny(array $orgUnitIds): ?array
    {
        if (empty($orgUnitIds)) {
            return null;
        }

        $union = collect();

        foreach ($orgUnitIds as $orgUnitId) {
            $ids = $this->allowedActiveIdsFor($orgUnitId);

            if ($ids === null) {
                return null; // an unmapped office contributes all streams
            }

            $union = $union->merge($ids);
        }

        return $union->unique()->values()->all();
    }

    /**
     * @param  array<int, mixed>  $orgUnitIds
     * @return array<int, string>
     */
    public function activeOptionsForAny(array $orgUnitIds): array
    {
        $allowedIds = $this->allowedActiveIdsForAny($orgUnitIds);

        $query = DepartmentStream::query()
            ->where('status', 'active')
            ->orderBy('name');

        if ($allowedIds !== null) {
            $query->whereIn('id', $allowedIds);
        }

        return $query->pluck('name', 'id')->all();
    }

    public function streamIsAllowedFor(mixed $orgUnitId, mixed $departmentStreamId): bool
    {
        if (blank($departmentStreamId)) {
            return true;
        }

        $allowedIds = $this->allowedActiveIdsFor($orgUnitId);

        if ($allowedIds === null) {
            return DepartmentStream::query()
                ->whereKey((int) $departmentStreamId)
                ->where('status', 'active')
                ->exists();
        }

        return in_array((int) $departmentStreamId, $allowedIds, true);
    }

    /**
     * @param  array<int, mixed>  $departmentStreamIds
     * @return array<int, int>
     */
    public function filterAllowedIds(mixed $orgUnitId, array $departmentStreamIds): array
    {
        return collect($departmentStreamIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $this->streamIsAllowedFor($orgUnitId, $id))
            ->unique()
            ->values()
            ->all();
    }

    /*
    |----------------------------------------------------------------------------
    | Strict variants — only the streams explicitly mapped to the org unit.
    |
    | Unlike the lenient methods above (which fall back to all active streams for
    | unmapped org units during rollout), these return an empty set when the org
    | has no stream mapping. Used by the employee form, where a stream must be one
    | of the office's configured streams.
    |----------------------------------------------------------------------------
    */

    /**
     * @return array<int, string>  id => name (empty when no org / no mapping)
     */
    public function mappedActiveOptionsFor(mixed $orgUnitId): array
    {
        return $this->mappedActiveStreams($orgUnitId)->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, int>
     */
    public function mappedActiveIdsFor(mixed $orgUnitId): array
    {
        return $this->mappedActiveStreams($orgUnitId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function isStreamMappedTo(mixed $orgUnitId, mixed $departmentStreamId): bool
    {
        if (blank($departmentStreamId)) {
            return false;
        }

        return in_array((int) $departmentStreamId, $this->mappedActiveIdsFor($orgUnitId), true);
    }

    /**
     * @return Collection<int, DepartmentStream>
     */
    private function mappedActiveStreams(mixed $orgUnitId): Collection
    {
        if (blank($orgUnitId)) {
            return collect();
        }

        $orgUnit = OrgUnit::query()->find((int) $orgUnitId);

        if (! $orgUnit) {
            return collect();
        }

        return $orgUnit->departmentStreams()
            ->where('department_streams.status', 'active')
            ->orderBy('department_streams.name')
            ->get();
    }
}
