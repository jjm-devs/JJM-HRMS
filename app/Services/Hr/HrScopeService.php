<?php

namespace App\Services\Hr;

use App\Models\HrScopeAssignment;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class HrScopeService
{
    private ?Collection $assignments = null;

    private function assignments(): Collection
    {
        if ($this->assignments === null) {
            $this->assignments = HrScopeAssignment::query()
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->where('can_view', true)
                ->get();
        }

        return $this->assignments;
    }

    public function isUnrestricted(): bool
    {
        return $this->assignments()->isEmpty();
    }

    public function isHeadOfficeHr(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user || ! $user->hasRole('hr')) {
            return false;
        }

        return HrScopeAssignment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('can_view', true)
            ->where('is_ho', true)
            ->exists();
    }

    public function scopedOrgUnitIds(): ?Collection
    {
        if ($this->isUnrestricted()) {
            return null;
        }

        $orgUnitIds = collect();

        foreach ($this->assignments() as $assignment) {
            if ($assignment->org_unit_id === null) {
                continue;
            }

            $orgUnitIds->push($assignment->org_unit_id);

            if ($assignment->include_child_units) {
                $this->collectChildIds($assignment->org_unit_id, $orgUnitIds);
            }
        }

        return $orgUnitIds->unique()->values();
    }

    public function applyToEmployeeQuery(Builder $query): Builder
    {
        if ($this->isUnrestricted()) {
            return $query;
        }

        $assignments = $this->assignments();

        $query->where(function (Builder $q) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $q->orWhere(function (Builder $inner) use ($assignment): void {
                    $orgUnitIds = collect([$assignment->org_unit_id]);

                    if ($assignment->include_child_units) {
                        $this->collectChildIds($assignment->org_unit_id, $orgUnitIds);
                    }

                    $inner->whereIn('org_unit_id', $orgUnitIds);

                    if ($assignment->department_stream_id !== null) {
                        $inner->where('department_stream_id', $assignment->department_stream_id);
                    }

                    if ($assignment->employment_type_id !== null) {
                        $inner->where('employment_type_id', $assignment->employment_type_id);
                    }
                });
            }
        });

        return $query;
    }

    public function applyToLeaveQuery(Builder $query): Builder
    {
        if ($this->isUnrestricted()) {
            return $query;
        }

        return $query->whereHas(
            'employee',
            fn (Builder $eq) => $this->applyToEmployeeQuery($eq)
        );
    }

    public function applyToEmployeeRelatedQuery(Builder $query, string $relation = 'employee'): Builder
    {
        if ($this->isUnrestricted()) {
            return $query;
        }

        return $query->whereHas(
            $relation,
            fn (Builder $eq) => $this->applyToEmployeeQuery($eq)
        );
    }

    private function collectChildIds(int $parentId, Collection &$ids): void
    {
        $children = OrgUnit::query()
            ->where('parent_id', $parentId)
            ->where('status', 'active')
            ->pluck('id');

        foreach ($children as $childId) {
            if ($ids->contains($childId)) {
                continue;
            }

            $ids->push($childId);
            $this->collectChildIds($childId, $ids);
        }
    }
}
