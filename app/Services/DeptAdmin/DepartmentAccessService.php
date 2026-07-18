<?php

namespace App\Services\DeptAdmin;

use App\Models\StaffDepartmentAccess;
use App\Models\TblDepartment;
use App\Models\TblUser;
use Illuminate\Support\Collection;

class DepartmentAccessService
{
    /**
     * @return list<int>
     */
    public function allowedDepartmentIds(TblUser $staff): array
    {
        $username = trim((string) $staff->username);
        if ($username !== '') {
            $assigned = StaffDepartmentAccess::query()
                ->where('username', $username)
                ->pluck('department_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ($assigned !== []) {
                return $assigned;
            }
        }

        return $this->legacyAllowedDepartmentIds($staff);
    }

    /**
     * กฎเดิมจากระบบเก่า — ใช้เมื่อยังไม่มีการกำหนดสาขาในสิทธิ์
     *
     * @return list<int>
     */
    private function legacyAllowedDepartmentIds(TblUser $staff): array
    {
        $departmentId = (int) $staff->department_id;
        $userId = (string) ($staff->userid ?? $staff->username);

        $ids = match (true) {
            $departmentId === 25 => [25, 36, 31, 35],
            $departmentId === 17 && $userId === '113615' => [5, 6, 7, 8, 9, 10, 11, 12, 25, 31, 32, 36, 35],
            $departmentId === 17 => [17, 36, 34],
            $userId === '116412' => [25, 22, 36, 31, 35],
            $userId === '113615' => [5, 6, 7, 8, 9, 10, 11, 12, 25, 31, 32, 36, 35],
            default => [$departmentId],
        };

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @return Collection<int, TblDepartment>
     */
    public function allowedDepartments(TblUser $staff): Collection
    {
        $ids = $this->allowedDepartmentIds($staff);

        if ($ids === []) {
            return collect();
        }

        return TblDepartment::query()
            ->whereIn('department_id', $ids)
            ->orderBy('department_name')
            ->get();
    }

    public function canAccessDepartment(TblUser $staff, int $departmentId): bool
    {
        return in_array($departmentId, $this->allowedDepartmentIds($staff), true);
    }
}
