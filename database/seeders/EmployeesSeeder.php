<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Support\Facades\Hash;

class EmployeesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing companies, branches, divisions, and positions
        $companies = Company::all();
        $branches = Branch::all();
        $divisions = Division::all();
        $positions = Position::all();

        if ($companies->isEmpty() || $branches->isEmpty() || $divisions->isEmpty() || $positions->isEmpty()) {
            $this->command->info('Please run CompaniesSeeder, BranchesSeeder, DivisionsSeeder, and PositionsSeeder first.');
            return;
        }

        $employees = [
            [
                'EmployeeID' => 'EMP001',
                'FirstName' => 'John',
                'LastName' => 'Doe',
                'Gender' => 'Male',
                'Address' => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'PhoneNumber' => '081234567890',
                'Status' => 'Active',
                'NIK' => '3201234567890001',
                'LastEducation' => 'S1 Informatika',
                'PlaceOfBirth' => 'Jakarta',
                'BirthDate' => '1990-05-15',
                'ContractType' => 'Permanent',
                'Bank' => 'BCA',
                'BankAccountNumber' => '1234567890',
                'BankAccountHolderName' => 'John Doe',
                'photo' => 'employee_photos/john_doe.jpg',
                'Notes' => 'Experienced software engineer',
            ],
            [
                'EmployeeID' => 'EMP002',
                'FirstName' => 'Jane',
                'LastName' => 'Smith',
                'Gender' => 'Female',
                'Address' => 'Jl. Thamrin No. 456, Jakarta Pusat',
                'PhoneNumber' => '081234567891',
                'Status' => 'Active',
                'NIK' => '3201234567890002',
                'LastEducation' => 'S1 Akuntansi',
                'PlaceOfBirth' => 'Bandung',
                'BirthDate' => '1992-08-20',
                'ContractType' => 'Permanent',
                'Bank' => 'Mandiri',
                'BankAccountNumber' => '2345678901',
                'BankAccountHolderName' => 'Jane Smith',
                'photo' => 'employee_photos/jane_smith.jpg',
                'Notes' => 'Finance specialist',
            ],
            [
                'EmployeeID' => 'EMP003',
                'FirstName' => 'Ahmad',
                'LastName' => 'Rahman',
                'Gender' => 'Male',
                'Address' => 'Jl. Gatot Subroto No. 789, Jakarta Selatan',
                'PhoneNumber' => '081234567892',
                'Status' => 'Active',
                'NIK' => '3201234567890003',
                'LastEducation' => 'S1 Manajemen',
                'PlaceOfBirth' => 'Surabaya',
                'BirthDate' => '1988-12-10',
                'ContractType' => 'Contract',
                'Bank' => 'BRI',
                'BankAccountNumber' => '3456789012',
                'BankAccountHolderName' => 'Ahmad Rahman',
                'photo' => 'employee_photos/ahmad_rahman.jpg',
                'Notes' => 'HR Manager',
            ],
            [
                'EmployeeID' => 'EMP004',
                'FirstName' => 'Siti',
                'LastName' => 'Nurhaliza',
                'Gender' => 'Female',
                'Address' => 'Jl. Kuningan No. 321, Jakarta Selatan',
                'PhoneNumber' => '081234567893',
                'Status' => 'Active',
                'NIK' => '3201234567890004',
                'LastEducation' => 'D3 Desain Grafis',
                'PlaceOfBirth' => 'Medan',
                'BirthDate' => '1995-03-25',
                'ContractType' => 'Permanent',
                'Bank' => 'BNI',
                'BankAccountNumber' => '4567890123',
                'BankAccountHolderName' => 'Siti Nurhaliza',
                'photo' => 'employee_photos/siti_nurhaliza.jpg',
                'Notes' => 'Creative designer',
            ],
            [
                'EmployeeID' => 'EMP005',
                'FirstName' => 'Budi',
                'LastName' => 'Santoso',
                'Gender' => 'Male',
                'Address' => 'Jl. Senayan No. 654, Jakarta Pusat',
                'PhoneNumber' => '081234567894',
                'Status' => 'Active',
                'NIK' => '3201234567890005',
                'LastEducation' => 'S1 Teknik Industri',
                'PlaceOfBirth' => 'Yogyakarta',
                'BirthDate' => '1991-07-08',
                'ContractType' => 'Permanent',
                'Bank' => 'CIMB Niaga',
                'BankAccountNumber' => '5678901234',
                'BankAccountHolderName' => 'Budi Santoso',
                'photo' => 'employee_photos/budi_santoso.jpg',
                'Notes' => 'Operations manager',
            ]
        ];

        foreach ($employees as $employeeData) {
            // Select a company that has branches
            $companyWithBranches = $companies->first();
            $availableBranches = $branches->where('company_id', $companyWithBranches->id);
            
            // If no branches for this company, use any branch and its company
            if ($availableBranches->isEmpty()) {
                $selectedBranch = $branches->first();
                $selectedCompany = $companies->find($selectedBranch->company_id);
            } else {
                $selectedBranch = $availableBranches->random();
                $selectedCompany = $companyWithBranches;
            }

            // Create user for employee
            $user = User::create([
                'company_id' => $selectedCompany->id,
                'name' => $employeeData['FirstName'] . ' ' . $employeeData['LastName'],
                'email' => strtolower($employeeData['FirstName'] . '.' . $employeeData['LastName'] . '@company.com'),
                'password' => Hash::make('password'),
                'role' => 'employee',
                'isProfileCompany' => false,
            ]);

            // Get a division from the selected branch
            $availableDivisions = $divisions->where('branch_id', $selectedBranch->id);
            $selectedDivision = $availableDivisions->isNotEmpty() ? $availableDivisions->random() : $divisions->first();
            
            // Get a position from the selected division
            $availablePositions = $positions->where('division_id', $selectedDivision->id);
            $selectedPosition = $availablePositions->isNotEmpty() ? $availablePositions->random() : $positions->first();

            // Create employee
            Employee::create([
                'Company_id' => $user->company_id,
                'user_id' => $user->id,
                'Branch_id' => $selectedBranch->id,
                'Division_id' => $selectedDivision->id,
                'Position_id' => $selectedPosition->id,
                'EmployeeID' => $employeeData['EmployeeID'],
                'FirstName' => $employeeData['FirstName'],
                'LastName' => $employeeData['LastName'],
                'Gender' => $employeeData['Gender'],
                'Address' => $employeeData['Address'],
                'PhoneNumber' => $employeeData['PhoneNumber'],
                'Status' => $employeeData['Status'],
                'NIK' => $employeeData['NIK'],
                'LastEducation' => $employeeData['LastEducation'],
                'PlaceOfBirth' => $employeeData['PlaceOfBirth'],
                'BirthDate' => $employeeData['BirthDate'],
                'ContractType' => $employeeData['ContractType'],
                'Bank' => $employeeData['Bank'],
                'BankAccountNumber' => $employeeData['BankAccountNumber'],
                'BankAccountHolderName' => $employeeData['BankAccountHolderName'],
                'photo' => $employeeData['photo'],
                'Notes' => $employeeData['Notes'],
            ]);
        }

        $this->command->info('Employees seeded successfully!');
    }
}
