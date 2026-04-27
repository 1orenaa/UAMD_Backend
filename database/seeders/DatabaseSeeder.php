<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seed i vetem per UAMD.
 *
 * Krijon te dhena testuese te gjithanshme per demonstrim:
 *  - Users (1 admin, 2 pedagog, 3 student) me password: 'password123'
 *  - 1 Fakultet + 2 Departamente
 *  - 1 Vit Akademik + 2 Semestra
 *  - 3 Salla, 2 Pedagog (records), 3 Student (records)
 *  - 5 Lende, 4 Seksione, 2 Regjistrime shembull
 *
 * Perdorimi:
 *   php artisan migrate:fresh --seed
 *
 * Logini testues:
 *   admin:   admin@uamd.edu.al    / password123
 *   pedagog: anisa.hoxha@uamd.edu.al / password123
 *   student: arber.berisha@uamd.edu.al / password123
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function () {
            // ── 1. Users (auth) ──────────────────────────────────────────────
            $admin = User::create([
                'name'     => 'Admin UAMD',
                'email'    => 'admin@uamd.edu.al',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
            ]);

            $pedagogUser1 = User::create([
                'name'     => 'Anisa Hoxha',
                'email'    => 'anisa.hoxha@uamd.edu.al',
                'password' => Hash::make('password123'),
                'role'     => 'pedagog',
            ]);

            $pedagogUser2 = User::create([
                'name'     => 'Bledi Marku',
                'email'    => 'bledi.marku@uamd.edu.al',
                'password' => Hash::make('password123'),
                'role'     => 'pedagog',
            ]);

            $studentUser1 = User::create([
                'name'     => 'Arber Berisha',
                'email'    => 'arber.berisha@uamd.edu.al',
                'password' => Hash::make('password123'),
                'role'     => 'student',
            ]);

            $studentUser2 = User::create([
                'name'     => 'Erisa Krasniqi',
                'email'    => 'erisa.krasniqi@uamd.edu.al',
                'password' => Hash::make('password123'),
                'role'     => 'student',
            ]);

            $studentUser3 = User::create([
                'name'     => 'Fatos Dervishi',
                'email'    => 'fatos.dervishi@uamd.edu.al',
                'password' => Hash::make('password123'),
                'role'     => 'student',
            ]);

            // ── 2. Fakultet ──────────────────────────────────────────────────
            $fakId = DB::table('FAKULTET')->insertGetId([
                'FAK_EM'     => 'Fakulteti i Teknologjise se Informacionit',
                'PED_ID'     => null, // do te caktohet pasi krijohen pedagoget
                'created_at' => now(),
                'updated_at' => now(),
            ], 'FAK_ID');

            // ── 3. Departamente (PED_ID nullable per tani) ───────────────────
            $depInfoId = DB::table('DEPARTAMENT')->insertGetId([
                'DEP_EM'     => 'Informatike',
                'FAK_ID'     => $fakId,
                'PED_ID'     => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'DEP_ID');

            $depTelekomId = DB::table('DEPARTAMENT')->insertGetId([
                'DEP_EM'     => 'Telekomunikacion',
                'FAK_ID'     => $fakId,
                'PED_ID'     => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'DEP_ID');

            // ── 4. Viti Akademik + Semestra ──────────────────────────────────
            $vitId = DB::table('VIT_AKADEMIK')->insertGetId([
                'VIT_EM'     => '2025-2026',
                'VIT_DT_FILL'=> '2025-10-01',
                'VIT_DT_MBR' => '2026-07-15',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'VIT_ID');

            $semester1Id = DB::table('SEMESTER')->insertGetId([
                'SEM_NR'      => 1,
                'SEM_DT_FILL' => '2025-10-01',
                'SEM_DT_MBR'  => '2026-02-15',
                'VIT_ID'      => $vitId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ], 'SEM_ID');

            $semester2Id = DB::table('SEMESTER')->insertGetId([
                'SEM_NR'      => 2,
                'SEM_DT_FILL' => '2026-02-16',
                'SEM_DT_MBR'  => '2026-07-15',
                'VIT_ID'      => $vitId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ], 'SEM_ID');

            // ── 5. Salla ─────────────────────────────────────────────────────
            $sallaA201Id = DB::table('SALLE')->insertGetId([
                'SALLE_EM'   => 'A-201',
                'SALLE_KAP'  => 40,
                'FAK_ID'     => $fakId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'SALLE_ID');

            $sallaLab3Id = DB::table('SALLE')->insertGetId([
                'SALLE_EM'   => 'Lab-3',
                'SALLE_KAP'  => 25,
                'FAK_ID'     => $fakId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'SALLE_ID');

            $sallaB105Id = DB::table('SALLE')->insertGetId([
                'SALLE_EM'   => 'B-105',
                'SALLE_KAP'  => 30,
                'FAK_ID'     => $fakId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'SALLE_ID');

            // ── 6. Pedagog (records) ─────────────────────────────────────────
            $ped1Id = DB::table('PEDAGOG')->insertGetId([
                'PED_EM'     => 'Anisa',
                'PED_MR'     => 'Hoxha',
                'PED_EMAIL'  => 'anisa.hoxha@uamd.edu.al',
                'PED_TIT'    => 'Prof. Dr.',
                'PED_DTL'    => '1980-05-14',
                'DEP_ID'     => $depInfoId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'PED_ID');

            $ped2Id = DB::table('PEDAGOG')->insertGetId([
                'PED_EM'     => 'Bledi',
                'PED_MR'     => 'Marku',
                'PED_EMAIL'  => 'bledi.marku@uamd.edu.al',
                'PED_TIT'    => 'Dr.',
                'PED_DTL'    => '1985-09-22',
                'DEP_ID'     => $depTelekomId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'PED_ID');

            // Update Departament me PED_ID te kryetarit
            DB::table('DEPARTAMENT')->where('DEP_ID', $depInfoId)->update(['PED_ID' => $ped1Id]);
            DB::table('DEPARTAMENT')->where('DEP_ID', $depTelekomId)->update(['PED_ID' => $ped2Id]);
            // Dekan i fakultetit
            DB::table('FAKULTET')->where('FAK_ID', $fakId)->update(['PED_ID' => $ped1Id]);

            // ── 7. Student (records) ─────────────────────────────────────────
            $std1Id = DB::table('STUDENT')->insertGetId([
                'STD_EM'     => 'Arber',
                'STD_MB'     => 'Berisha',
                'STD_EMAIL'  => 'arber.berisha@uamd.edu.al',
                'STD_DTL'    => '2003-03-10',
                'STD_GJINI'  => 'M',
                'DEP_ID'     => $depInfoId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'STD_ID');

            $std2Id = DB::table('STUDENT')->insertGetId([
                'STD_EM'     => 'Erisa',
                'STD_MB'     => 'Krasniqi',
                'STD_EMAIL'  => 'erisa.krasniqi@uamd.edu.al',
                'STD_DTL'    => '2004-07-25',
                'STD_GJINI'  => 'F',
                'DEP_ID'     => $depInfoId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'STD_ID');

            $std3Id = DB::table('STUDENT')->insertGetId([
                'STD_EM'     => 'Fatos',
                'STD_MB'     => 'Dervishi',
                'STD_EMAIL'  => 'fatos.dervishi@uamd.edu.al',
                'STD_DTL'    => '2003-11-02',
                'STD_GJINI'  => 'M',
                'DEP_ID'     => $depTelekomId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'STD_ID');

            // ── 8. Lende ─────────────────────────────────────────────────────
            $lendet = [
                ['LEN_EM' => 'Programim i Avancuar Web',       'LEN_KOD' => 'PAW301', 'LEN_KRED' => 6, 'DEP_ID' => $depInfoId],
                ['LEN_EM' => 'Bazat e te Dhenave',             'LEN_KOD' => 'BDH201', 'LEN_KRED' => 5, 'DEP_ID' => $depInfoId],
                ['LEN_EM' => 'Strukturat e te Dhenave',        'LEN_KOD' => 'STR202', 'LEN_KRED' => 6, 'DEP_ID' => $depInfoId],
                ['LEN_EM' => 'Inxhinieri Softueri',            'LEN_KOD' => 'INS401', 'LEN_KRED' => 5, 'DEP_ID' => $depInfoId],
                ['LEN_EM' => 'Rrjetat Kompjuterike',           'LEN_KOD' => 'RRJ301', 'LEN_KRED' => 6, 'DEP_ID' => $depTelekomId],
            ];

            $lendeIds = [];
            foreach ($lendet as $lenda) {
                $lendeIds[$lenda['LEN_KOD']] = DB::table('LENDE')->insertGetId(array_merge($lenda, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]), 'LEN_ID');
            }

            // ── 9. Seksione ──────────────────────────────────────────────────
            $seksione = [
                [
                    'SEK_DATA'    => '2026-04-22',
                    'SEK_DRAFILL' => '2026-04-22 09:00:00',
                    'SEK_DRAMBIA' => '2026-04-22 10:30:00',
                    'PED_ID'      => $ped1Id,
                    'SALLE_ID'    => $sallaLab3Id,
                    'LEN_ID'      => $lendeIds['PAW301'],
                    'SEM_ID'      => $semester2Id,
                ],
                [
                    'SEK_DATA'    => '2026-04-22',
                    'SEK_DRAFILL' => '2026-04-22 11:00:00',
                    'SEK_DRAMBIA' => '2026-04-22 12:30:00',
                    'PED_ID'      => $ped1Id,
                    'SALLE_ID'    => $sallaA201Id,
                    'LEN_ID'      => $lendeIds['BDH201'],
                    'SEM_ID'      => $semester2Id,
                ],
                [
                    'SEK_DATA'    => '2026-04-23',
                    'SEK_DRAFILL' => '2026-04-23 09:00:00',
                    'SEK_DRAMBIA' => '2026-04-23 10:30:00',
                    'PED_ID'      => $ped2Id,
                    'SALLE_ID'    => $sallaB105Id,
                    'LEN_ID'      => $lendeIds['RRJ301'],
                    'SEM_ID'      => $semester2Id,
                ],
                [
                    'SEK_DATA'    => '2026-04-23',
                    'SEK_DRAFILL' => '2026-04-23 13:00:00',
                    'SEK_DRAMBIA' => '2026-04-23 14:30:00',
                    'PED_ID'      => $ped1Id,
                    'SALLE_ID'    => $sallaA201Id,
                    'LEN_ID'      => $lendeIds['INS401'],
                    'SEM_ID'      => $semester2Id,
                ],
                [
                    'SEK_DATA'    => '2026-04-24',
                    'SEK_DRAFILL' => '2026-04-24 10:00:00',
                    'SEK_DRAMBIA' => '2026-04-24 11:30:00',
                    'PED_ID'      => $ped1Id,
                    'SALLE_ID'    => $sallaLab3Id,
                    'LEN_ID'      => $lendeIds['STR202'],
                    'SEM_ID'      => $semester2Id,
                ],
            ];

            $sekIds = [];
            foreach ($seksione as $s) {
                $sekIds[] = DB::table('SEKSION')->insertGetId(array_merge($s, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]), 'SEK_ID');
            }

            // ── 10. Regjistrime shembull ─────────────────────────────────────
            // Arberi eshte regjistruar ne 2 seksione, Erisa ne 1
            DB::table('REGJISTRIM')->insert([
                [
                    'REGJL_DT'     => now()->toDateString(),
                    'REGJL_STATUS' => 'aktiv',
                    'REGJL_NOTA'   => null,
                    'REGJL_PRU'    => false,
                    'STD_ID'       => $std1Id,
                    'SEK_ID'       => $sekIds[0], // PAW301
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'REGJL_DT'     => now()->toDateString(),
                    'REGJL_STATUS' => 'aktiv',
                    'REGJL_NOTA'   => null,
                    'REGJL_PRU'    => false,
                    'STD_ID'       => $std1Id,
                    'SEK_ID'       => $sekIds[1], // BDH201
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'REGJL_DT'     => now()->toDateString(),
                    'REGJL_STATUS' => 'aktiv',
                    'REGJL_NOTA'   => null,
                    'REGJL_PRU'    => false,
                    'STD_ID'       => $std2Id,
                    'SEK_ID'       => $sekIds[0], // PAW301
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
            ]);
        });

        $this->command->info('✔ Seed perfunduar. Logini testues:');
        $this->command->line('   admin:   admin@uamd.edu.al       / password123');
        $this->command->line('   pedagog: anisa.hoxha@uamd.edu.al / password123');
        $this->command->line('   student: arber.berisha@uamd.edu.al / password123');
    }
}
