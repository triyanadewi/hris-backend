<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LetterFormatsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('letter_formats')->insert([
            [
                'name' => 'Surat Pengunduran Diri',
                'content' => 'Saya mengucapkan terimakaih atas kesempatan yang diberikan 
                dan mohon maaf apabila terdapat kesalahan yang disengaja maupun tidak disengaja',
                'type' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Surat Permohonan Mutasi Divisi',
                'content' => 'Saya mengucapkan permohonan untuk melakukan mutasi divisi',
                'type' =>'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Surat Permohonan Kenaikan Gaji',
                'content' => 'Saya rasa saya dapat mendapatkan kenaikan gaji',
                'type' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Surat Permohonan Cuti',
                'content' => 'Saya ingin mengajukan cuti',
                'type' => 'published', // Fix this line
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
