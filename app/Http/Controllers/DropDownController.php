<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class DropDownController extends Controller
{
    public function getPositionBranchCompany()
    {
        $companyAdmin = 1;
        return DB::select("
        SELECT
        p.id as id_posisi,
        p.name as nama_posisi,
        d.id as id_divisi,
        d.name as name_divisi,
        b.id as id_branch,
        b.name as name_branch,
        c.id as id_company,
        c.name as name_company
        From positions p
        join divisions d on d.id=p.division_id
        join branches b on b.id=d.branch_id
        join companies c on c.id=b.company_id

        where c.id = ?", [$companyAdmin]);
    }
}
