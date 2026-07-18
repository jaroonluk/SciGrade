<?php

namespace App\Services\FacultyAdmin;

use App\Models\StaffDepartmentAccess;
use App\Models\TblPrivilege;
use Illuminate\Support\Facades\DB;

class StaffDepartmentAccessService
{
    /**
     * @param  list<int|string>  $departmentIds
     * @return list<int>
     */
    public function syncForUsername(string $username, int $level, array $departmentIds): array
    {
        $username = trim($username);
        if ($username === '') {
            return [];
        }

        if ($level !== TblPrivilege::LEVEL_DEPT) {
            $this->clearForUsername($username);

            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map(
            fn ($id) => (int) $id,
            $departmentIds,
        ), fn (int $id) => $id > 0)));

        DB::connection('scigrad')->transaction(function () use ($username, $ids) {
            StaffDepartmentAccess::query()
                ->where('username', $username)
                ->when($ids !== [], fn ($q) => $q->whereNotIn('department_id', $ids))
                ->delete();

            foreach ($ids as $departmentId) {
                StaffDepartmentAccess::query()->updateOrCreate(
                    [
                        'username' => $username,
                        'department_id' => $departmentId,
                    ],
                    [],
                );
            }
        });

        return $ids;
    }

    public function clearForUsername(string $username): void
    {
        StaffDepartmentAccess::query()
            ->where('username', trim($username))
            ->delete();
    }

    /**
     * @return list<int>
     */
    public function departmentIdsForUsername(string $username): array
    {
        return StaffDepartmentAccess::query()
            ->where('username', trim($username))
            ->orderBy('department_id')
            ->pluck('department_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
