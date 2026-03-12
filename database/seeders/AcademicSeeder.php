<?php

namespace Database\Seeders;

use App\Models\StudyLevel;
use App\Models\Specialization;
use App\Models\LevelFee;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        // ── Niveaux d'études (avec ordre de progression) ──────────────────
        $levelDefs = [
            ['code' => 'L1', 'label' => 'Licence 1', 'order' => 1, 'description' => 'Première année de licence'],
            ['code' => 'L2', 'label' => 'Licence 2', 'order' => 2, 'description' => 'Deuxième année de licence'],
            ['code' => 'L3', 'label' => 'Licence 3', 'order' => 3, 'description' => 'Troisième année de licence'],
            ['code' => 'M1', 'label' => 'Master 1',  'order' => 4, 'description' => 'Première année de master'],
            ['code' => 'M2', 'label' => 'Master 2',  'order' => 5, 'description' => 'Deuxième année de master'],
        ];
        $studyLevels = [];
        foreach ($levelDefs as $d) {
            $studyLevels[$d['code']] = StudyLevel::updateOrCreate(
                ['code' => $d['code']],
                ['label' => $d['label'], 'order' => $d['order'], 'description' => $d['description']]
            );
        }
        $this->command->info('Study levels créés : L1(1) L2(2) L3(3) M1(4) M2(5)');

        // ── Spécialisations ───────────────────────────────────────────────
        $specDefs = [
            ['code' => 'INFO',    'label' => 'Informatique',  'description' => 'Génie logiciel et systèmes informatiques'],
            ['code' => 'GESTION', 'label' => 'Gestion',       'description' => 'Management et gestion des entreprises'],
            ['code' => 'COMPTA',  'label' => 'Comptabilité',  'description' => 'Comptabilité et finance'],
            ['code' => 'DROIT',   'label' => 'Droit',         'description' => 'Sciences juridiques'],
        ];
        $specializations = [];
        foreach ($specDefs as $d) {
            $specializations[$d['code']] = Specialization::updateOrCreate(
                ['code' => $d['code']],
                ['label' => $d['label'], 'description' => $d['description']]
            );
        }
        $this->command->info('Specializations créées : INFO, GESTION, COMPTA, DROIT');

        // ── Barèmes de scolarité (level_fees) ─────────────────────────────
        // Tarif générique (specialization_id = null => s'applique à toutes les filières)
        $genericFees = ['L1' => 450_000, 'L2' => 450_000, 'L3' => 500_000, 'M1' => 600_000, 'M2' => 600_000];
        // Supplément Informatique (tarif spécifique prioritaire)
        $infoExtra   = ['L1' =>  50_000, 'L2' =>  50_000, 'L3' =>  50_000, 'M1' =>  75_000, 'M2' =>  75_000];

        foreach (['2024-2025', '2025-2026'] as $year) {
            foreach ($genericFees as $code => $amount) {
                LevelFee::updateOrCreate(
                    ['study_level_id' => $studyLevels[$code]->id, 'specialization_id' => null, 'school_year' => $year],
                    ['tuition_amount' => $amount]
                );
            }
            foreach ($infoExtra as $code => $extra) {
                LevelFee::updateOrCreate(
                    [
                        'study_level_id'    => $studyLevels[$code]->id,
                        'specialization_id' => $specializations['INFO']->id,
                        'school_year'       => $year,
                    ],
                    ['tuition_amount' => $genericFees[$code] + $extra]
                );
            }
        }
        $this->command->info('Barèmes créés pour 2024-2025 et 2025-2026 (génériques + supplément INFO)');
        $this->command->info('✓ Academic data seeded successfully!');
    }
}
