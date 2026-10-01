<?php

namespace Database\Seeders;

use App\Models\Annexe;
use App\Models\LevelFee;
use App\Models\SchoolYear;
use App\Models\Specialization;
use App\Models\StudyLevel;
use Illuminate\Database\Seeder;

/**
 * Catalogue académique (niveaux, filières, barèmes).
 * Le catalogue est propre à chaque annexe : il est créé pour toutes les annexes existantes.
 */
class AcademicSeeder extends Seeder
{
    public const LEVELS = [
        ['code' => 'L1', 'label' => 'Licence 1', 'order' => 1, 'description' => 'Première année de licence'],
        ['code' => 'L2', 'label' => 'Licence 2', 'order' => 2, 'description' => 'Deuxième année de licence'],
        ['code' => 'L3', 'label' => 'Licence 3', 'order' => 3, 'description' => 'Troisième année de licence'],
        ['code' => 'M1', 'label' => 'Master 1',  'order' => 4, 'description' => 'Première année de master'],
        ['code' => 'M2', 'label' => 'Master 2',  'order' => 5, 'description' => 'Deuxième année de master'],
    ];

    public const SPECIALIZATIONS = [
        ['code' => 'INFO',    'label' => 'Informatique', 'description' => 'Génie logiciel et systèmes informatiques'],
        ['code' => 'GESTION', 'label' => 'Gestion',      'description' => 'Management et gestion des entreprises'],
        ['code' => 'COMPTA',  'label' => 'Comptabilité', 'description' => 'Comptabilité et finance'],
        ['code' => 'DROIT',   'label' => 'Droit',        'description' => 'Sciences juridiques'],
    ];

    // Tarif générique par niveau (FCFA) et supplément pour la filière Informatique
    private const GENERIC_FEES = ['L1' => 450_000, 'L2' => 450_000, 'L3' => 500_000, 'M1' => 600_000, 'M2' => 600_000];
    private const INFO_EXTRA   = ['L1' =>  50_000, 'L2' =>  50_000, 'L3' =>  50_000, 'M1' =>  75_000, 'M2' =>  75_000];

    public function run(): void
    {
        $current = SchoolYear::currentYearLabel();
        $previous = self::previousYear($current);

        $annexes = Annexe::all();
        foreach ($annexes as $annexe) {
            $this->seedForAnnexe($annexe, [$previous, $current]);
        }

        $this->command?->info("Catalogue académique créé pour {$annexes->count()} annexe(s) ({$previous}, {$current})");
    }

    /**
     * Crée (ou met à jour) le catalogue d'une annexe pour les années données.
     *
     * @return array{levels: \Illuminate\Support\Collection, specializations: \Illuminate\Support\Collection}
     */
    public function seedForAnnexe(Annexe $annexe, array $years): array
    {
        $levels = collect(self::LEVELS)->mapWithKeys(fn ($d) => [
            $d['code'] => StudyLevel::updateOrCreate(
                ['annexe_id' => $annexe->id, 'code' => $d['code']],
                ['label' => $d['label'], 'order' => $d['order'], 'description' => $d['description']]
            ),
        ]);

        $specializations = collect(self::SPECIALIZATIONS)->mapWithKeys(fn ($d) => [
            $d['code'] => Specialization::updateOrCreate(
                ['annexe_id' => $annexe->id, 'code' => $d['code']],
                ['label' => $d['label'], 'description' => $d['description']]
            ),
        ]);

        foreach ($years as $year) {
            foreach (self::GENERIC_FEES as $code => $amount) {
                LevelFee::updateOrCreate(
                    ['annexe_id' => $annexe->id, 'study_level_id' => $levels[$code]->id, 'specialization_id' => null, 'school_year' => $year],
                    ['tuition_amount' => $amount]
                );

                LevelFee::updateOrCreate(
                    ['annexe_id' => $annexe->id, 'study_level_id' => $levels[$code]->id, 'specialization_id' => $specializations['INFO']->id, 'school_year' => $year],
                    ['tuition_amount' => $amount + self::INFO_EXTRA[$code], 'notes' => 'Supplément laboratoire informatique']
                );
            }
        }

        return ['levels' => $levels, 'specializations' => $specializations];
    }

    public static function previousYear(string $year): string
    {
        [$start] = explode('-', $year);

        return ((int) $start - 1) . '-' . (int) $start;
    }
}
