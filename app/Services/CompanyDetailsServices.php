<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CompanyDetailsService
{
    /**
     * Save company details (branches, divisions, positions)
     */
    public function saveCompanyDetails(array $data, int $companyId): array
    {
        return DB::transaction(function () use ($data, $companyId) {
            // Clear existing data
            $this->clearExistingData($companyId);

            // Save branches and get mapping
            $branchMapping = $this->saveBranches($data['branches'], $companyId);

            // Save divisions and get mapping
            $divisionMapping = $this->saveDivisions($data['divisions'], $branchMapping);

            // Save positions
            $this->savePositions($data['positions'], $divisionMapping);

            return [
                'branches_created' => count($branchMapping),
                'divisions_created' => count($divisionMapping),
                'positions_created' => count($data['positions']),
            ];
        });
    }

    /**
     * Get company details with relationships
     */
    public function getCompanyDetails(int $companyId): Collection
    {
        return Branch::with([
            'divisions' => function ($query) {
                $query->orderBy('name');
            },
            'divisions.positions' => function ($query) {
                $query->orderBy('name');
            }
        ])
        ->where('company_id', $companyId)
        ->orderBy('name')
        ->get();
    }

    /**
     * Clear existing company data
     */
    private function clearExistingData(int $companyId): void
    {
        // Delete branches (cascade will handle divisions and positions)
        Branch::where('company_id', $companyId)->delete();
    }

    /**
     * Save branches and return ID mapping
     */
    private function saveBranches(array $branches, int $companyId): array
    {
        $branchMapping = [];

        foreach ($branches as $index => $branchData) {
            $branch = Branch::create([
                'company_id' => $companyId,
                'name' => trim($branchData['name']),
                'branch_address' => trim($branchData['branch_address']),
                'branch_phone' => trim($branchData['branch_phone']),
                'branch_phone_backup' => !empty($branchData['branch_phone_backup']) 
                    ? trim($branchData['branch_phone_backup']) 
                    : null,
                'description' => !empty($branchData['description']) 
                    ? trim($branchData['description']) 
                    : null,
            ]);

            $branchMapping[$index] = $branch->id;
        }

        return $branchMapping;
    }

    /**
     * Save divisions and return ID mapping
     */
    private function saveDivisions(array $divisions, array $branchMapping): array
    {
        $divisionMapping = [];

        foreach ($divisions as $index => $divisionData) {
            $branchId = $branchMapping[$divisionData['branch_id']] ?? null;

            if ($branchId) {
                $division = Division::create([
                    'branch_id' => $branchId,
                    'name' => trim($divisionData['name']),
                    'description' => !empty($divisionData['description']) 
                        ? trim($divisionData['description']) 
                        : null,
                ]);

                $divisionMapping[$index] = $division->id;
            }
        }

        return $divisionMapping;
    }

    /**
     * Save positions
     */
    private function savePositions(array $positions, array $divisionMapping): void
    {
        foreach ($positions as $positionData) {
            $divisionId = $divisionMapping[$positionData['division_id']] ?? null;

            if ($divisionId) {
                Position::create([
                    'division_id' => $divisionId,
                    'name' => trim($positionData['name']),
                    'description' => !empty($positionData['description']) 
                        ? trim($positionData['description']) 
                        : null,
                ]);
            }
        }
    }

    /**
     * Get statistics for company structure
     */
    public function getCompanyStatistics(int $companyId): array
    {
        $branches = Branch::where('company_id', $companyId);
        $branchIds = $branches->pluck('id');

        $divisions = Division::whereIn('branch_id', $branchIds);
        $divisionIds = $divisions->pluck('id');

        $positions = Position::whereIn('division_id', $divisionIds);

        return [
            'total_branches' => $branches->count(),
            'total_divisions' => $divisions->count(),
            'total_positions' => $positions->count(),
            'branches_with_divisions' => $branches->has('divisions')->count(),
            'divisions_with_positions' => $divisions->has('positions')->count(),
        ];
    }

    /**
     * Validate data relationships
     */
    public function validateDataRelationships(array $data): array
    {
        $errors = [];

        // Check if divisions reference valid branch indices
        foreach ($data['divisions'] as $index => $division) {
            $branchIndex = $division['branch_id'];
            if (!isset($data['branches'][$branchIndex])) {
                $errors[] = "Division at index {$index} references invalid branch index {$branchIndex}";
            }
        }

        // Check if positions reference valid division indices
        foreach ($data['positions'] as $index => $position) {
            $divisionIndex = $position['division_id'];
            if (!isset($data['divisions'][$divisionIndex])) {
                $errors[] = "Position at index {$index} references invalid division index {$divisionIndex}";
            }
        }

        return $errors;
    }

    /**
     * Format data for frontend consumption
     */
    public function formatForFrontend(Collection $branches): array
    {
        $formattedBranches = [];
        $formattedDivisions = [];
        $formattedPositions = [];

        foreach ($branches as $branchIndex => $branch) {
            $formattedBranches[] = [
                'id' => $branch->id,
                'name' => $branch->name,
                'branch_address' => $branch->branch_address,
                'branch_phone' => $branch->branch_phone,
                'branch_phone_backup' => $branch->branch_phone_backup,
                'description' => $branch->description,
            ];

            foreach ($branch->divisions as $divisionIndex => $division) {
                $formattedDivisions[] = [
                    'id' => $division->id,
                    'branch_id' => $branchIndex, // Frontend index
                    'name' => $division->name,
                    'description' => $division->description,
                ];

                foreach ($division->positions as $position) {
                    $formattedPositions[] = [
                        'id' => $position->id,
                        'division_id' => count($formattedDivisions) - 1, // Frontend index
                        'name' => $position->name,
                        'description' => $position->description,
                    ];
                }
            }
        }

        return [
            'branches' => $formattedBranches,
            'divisions' => $formattedDivisions,
            'positions' => $formattedPositions,
        ];
    }
}
