<?php

namespace Database\Seeders;

use App\Models\StudyLevel;
use App\Models\Specialization;
use App\Models\StudentClass;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        // Niveaux d'études
        $l1 = StudyLevel::updateOrCreate(
            ['code' => 'L1'],
            ['label' => 'Licence 1', 'description' => 'Première année de licence']
        );
        $l2 = StudyLevel::updateOrCreate(
            ['code' => 'L2'],
            ['label' => 'Licence 2', 'description' => 'Deuxième année de licence']
        );
        $l3 = StudyLevel::updateOrCreate(
            ['code' => 'L3'],
            ['label' => 'Licence 3', 'description' => 'Troisième année de licence']
        );
        $m1 = StudyLevel::updateOrCreate(
            ['code' => 'M1'],
            ['label' => 'Master 1', 'description' => 'Première année de master']
        );
        $m2 = StudyLevel::updateOrCreate(
            ['code' => 'M2'],
            ['label' => 'Master 2', 'description' => 'Deuxième année de master']
        );

        $this->command->info('Study levels créés: L1, L2, L3, M1, M2');

        // Spécialisations
        $info = Specialization::updateOrCreate(
            ['code' => 'INFO'],
            ['label' => 'Informatique', 'description' => 'Génie logiciel et systèmes informatiques']
        );
        $gestion = Specialization::updateOrCreate(
            ['code' => 'GESTION'],
            ['label' => 'Gestion', 'description' => 'Management et gestion des entreprises']
        );
        $compta = Specialization::updateOrCreate(
            ['code' => 'COMPTA'],
            ['label' => 'Comptabilité', 'description' => 'Comptabilité et finance']
        );
        $droit = Specialization::updateOrCreate(
            ['code' => 'DROIT'],
            ['label' => 'Droit', 'description' => 'Sciences juridiques']
        );

        $this->command->info('Specializations créées: INFO, GESTION, COMPTA, DROIT');

        // Classes (croisement niveau × spécialisation)
        $classes = [
            // L1
            ['study_level_id' => $l1->id, 'specialization_id' => $info->id, 'code' => 'A', 'label' => 'L1 Informatique - Groupe A'],
            ['study_level_id' => $l1->id, 'specialization_id' => $info->id, 'code' => 'B', 'label' => 'L1 Informatique - Groupe B'],
            ['study_level_id' => $l1->id, 'specialization_id' => $gestion->id, 'code' => 'A', 'label' => 'L1 Gestion - Groupe A'],
            
            // L2
            ['study_level_id' => $l2->id, 'specialization_id' => $info->id, 'code' => 'A', 'label' => 'L2 Informatique - Groupe A'],
            ['study_level_id' => $l2->id, 'specialization_id' => $gestion->id, 'code' => 'A', 'label' => 'L2 Gestion - Groupe A'],
            ['study_level_id' => $l2->id, 'specialization_id' => $compta->id, 'code' => 'A', 'label' => 'L2 Comptabilité - Groupe A'],
            
            // L3
            ['study_level_id' => $l3->id, 'specialization_id' => $info->id, 'code' => 'A', 'label' => 'L3 Informatique - Groupe A'],
            ['study_level_id' => $l3->id, 'specialization_id' => $droit->id, 'code' => 'A', 'label' => 'L3 Droit - Groupe A'],
            
            // M1
            ['study_level_id' => $m1->id, 'specialization_id' => $info->id, 'code' => 'A', 'label' => 'M1 Informatique - Groupe A'],
            ['study_level_id' => $m1->id, 'specialization_id' => $gestion->id, 'code' => 'A', 'label' => 'M1 Gestion - Groupe A'],
        ];

        foreach ($classes as $classData) {
            StudentClass::updateOrCreate(
                [
                    'study_level_id' => $classData['study_level_id'],
                    'specialization_id' => $classData['specialization_id'],
                    'code' => $classData['code']
                ],
                ['label' => $classData['label']]
            );
        }

        $this->command->info('Classes créées: ' . count($classes) . ' classes');
        $this->command->info('✓ Academic data seeded successfully!');
    }
}
