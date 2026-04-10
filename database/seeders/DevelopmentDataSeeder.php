<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Annexe;
use App\Models\User;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyLevel;
use App\Models\Specialization;
use App\Models\LevelFee;
use App\Models\Enrollment;
use App\Models\PaymentLink;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevelopmentDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Création des données de test...');

        // ── Institution + Annexes ──────────────────────────────────────────
        $institution = Institution::updateOrCreate(
            ['email' => 'contact@ist-edu.com'],
            [
                'name'      => 'Institut Supérieur de Technologie',
                'phone'     => '+229 97 00 00 00',
                'address'   => 'Avenue de la République',
                'city'      => 'Cotonou',
                'is_active' => true,
            ]
        );

        $annexeNord = Annexe::updateOrCreate(
            ['institution_id' => $institution->id, 'name' => 'Campus Nord'],
            [
                'address'   => 'Quartier Akpakpa',
                'city'      => 'Cotonou',
                'is_active' => true,
            ]
        );

        $annexeSud = Annexe::updateOrCreate(
            ['institution_id' => $institution->id, 'name' => 'Campus Sud'],
            [
                'address'   => 'Quartier Fidjrossè',
                'city'      => 'Cotonou',
                'is_active' => true,
            ]
        );
        $this->command->info('1 institution + 2 annexes créées');

        // ── Utilisateurs ───────────────────────────────────────────────────
        $roleSuperAdmin   = Role::where('code', 'super_admin_institution')->first();
        $roleGestionnaire = Role::where('code', 'gestionnaire')->first();

        User::updateOrCreate(
            ['email' => 'platform@splitpay.test'],
            [
                'annexe_id' => null,
                'name'      => 'SplitPay Platform Admin',
                'password'  => Hash::make('password'),
                'phone'     => '+229 90 00 00 00',
                'is_active' => true,
                'scope'     => 'platform',
            ]
        );

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@ist-edu.com'],
            [
                'annexe_id' => $annexeNord->id,
                'name'      => 'Admin Principal',
                'password'  => Hash::make('password'),
                'phone'     => '+229 97 11 11 11',
                'is_active' => true,
                'scope'     => 'institution',
            ]
        );
        $superAdmin->annexes()->attach($annexeNord->id, [
            'role_id'      => $roleSuperAdmin->id,
            'is_principal' => true,
            'assigned_at'  => now(),
        ]);

        foreach ([
            ['name' => 'Marie Dupont', 'email' => 'marie@ist-edu.com', 'annexe' => $annexeNord, 'phone' => '+229 97 22 22 22'],
            ['name' => 'Jean Martin',  'email' => 'jean@ist-edu.com',  'annexe' => $annexeSud,  'phone' => '+229 97 33 33 33'],
        ] as $g) {
            $user = User::updateOrCreate(
                ['email' => $g['email']],
                [
                    'annexe_id' => $g['annexe']->id,
                    'name'      => $g['name'],
                    'password'  => Hash::make('password'),
                    'phone'     => $g['phone'],
                    'is_active' => true,
                    'scope'     => 'annexe',
                ]
            );
            $user->annexes()->attach($g['annexe']->id, [
                'role_id'      => $roleGestionnaire->id,
                'is_principal' => true,
                'assigned_by'  => $superAdmin->id,
                'assigned_at'  => now(),
            ]);
        }
        $this->command->info('3 utilisateurs créés (admin@ist-edu.com | password)');

        // ── Récupérer niveaux et spécialisations (créés par AcademicSeeder) ─
        $levels = StudyLevel::all()->keyBy('code');
        $specs  = Specialization::all()->keyBy('code');

        if ($levels->isEmpty() || $specs->isEmpty()) {
            $this->command->warn('AcademicSeeder doit être exécuté avant DevelopmentDataSeeder.');
            $this->command->warn('Lancer : php artisan db:seed --class=AcademicSeeder d\'abord.');
            return;
        }

        // ── Étudiants Campus Nord — spécialisation : Informatique ──────────
        $nordStudents = [
            ['matricule' => 'ETU2024001', 'first_name' => 'Amina',     'last_name' => 'Kouassi', 'level' => 'L1'],
            ['matricule' => 'ETU2024002', 'first_name' => 'Koffi',     'last_name' => 'Mensah',  'level' => 'L1'],
            ['matricule' => 'ETU2024003', 'first_name' => 'Fatoumata', 'last_name' => 'Diallo',  'level' => 'L2'],
            ['matricule' => 'ETU2024004', 'first_name' => 'Ibrahim',   'last_name' => 'Traoré',  'level' => 'L2'],
            ['matricule' => 'ETU2024005', 'first_name' => 'Aïcha',     'last_name' => 'Camara',  'level' => 'M1'],
        ];

        // ── Étudiants Campus Sud — spécialisation : Gestion ────────────────
        $sudStudents = [
            ['matricule' => 'ETU2024006', 'first_name' => 'Sébastien', 'last_name' => 'Kouadio', 'level' => 'L1'],
            ['matricule' => 'ETU2024007', 'first_name' => 'Aminata',   'last_name' => 'Sow',     'level' => 'L1'],
            ['matricule' => 'ETU2024008', 'first_name' => 'Moussa',    'last_name' => 'Keita',   'level' => 'L2'],
            ['matricule' => 'ETU2024009', 'first_name' => 'Mariama',   'last_name' => 'Barry',   'level' => 'L2'],
            ['matricule' => 'ETU2024010', 'first_name' => 'Youssouf',  'last_name' => 'Touré',   'level' => 'M1'],
        ];

        $this->seedStudents($nordStudents, $annexeNord, $specs['INFO'],    $levels, '2024-2025');
        $this->seedStudents($sudStudents,  $annexeSud,  $specs['GESTION'], $levels, '2024-2025');

        // ── Liens de paiement et paiements de test ───────────────────────────
        $this->seedPaymentLinksAndPayments('2024-2025');

        $this->command->newLine();
        $this->command->info('RÉSUMÉ :');
        $this->command->info('  - 1 institution, 2 annexes');
        $this->command->info('  - 4 utilisateurs (1 Admin Plateforme + 1 Super Admin + 2 Gestionnaires)');
        $this->command->info('  - 10 étudiants avec enrollments 2024-2025 + paiements partiels');
        $this->command->info('  - Liens de paiement (+8) avec transactions de test');
        $this->command->info('  Connexion : admin@ist-edu.com | password');
        $this->command->info('  Connexion plateforme : platform@splitpay.test | password');
    }

    private function seedStudents(array $list, Annexe $annexe, Specialization $spec, $levels, string $year): void
    {
        foreach ($list as $data) {
            $level = $levels[$data['level']] ?? null;

            if (!$level) {
                $this->command->warn("  ⚠ Niveau '{$data['level']}' introuvable — étudiant {$data['matricule']} ignoré");
                continue;
            }

            $student = Student::updateOrCreate(
                ['matricule' => $data['matricule']],
                [
                    'id'                => (string) Str::uuid(),
                    'annexe_id'         => $annexe->id,
                    'first_name'        => $data['first_name'],
                    'last_name'         => $data['last_name'],
                    'email'             => Str::lower(iconv('UTF-8', 'ASCII//TRANSLIT', $data['first_name'])) . '@etudiant.com',
                    'phone'             => '+229 97 ' . rand(40, 59) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                    'specialization_id' => $spec->id,
                    'status'            => 'active',
                ]
            );

            // Résoudre le barème : d'abord filière spécifique, sinon générique
            $fee = LevelFee::resolve($level->id, $spec->id, $year);

            if (!$fee) {
                $this->command->warn("  ⚠ Aucun barème trouvé pour {$level->code} / {$spec->code} / {$year} — enrollment ignoré pour {$student->matricule}");
                continue;
            }

            $tuition    = (int) $fee->tuition_amount;
            $amountPaid = rand(0, $tuition);

            Enrollment::updateOrCreate(
                [
                    'student_id'  => $student->id,
                    'school_year' => $year,
                ],
                [
                    'level_fee_id'   => $fee->id,
                    'tuition_amount' => $tuition,
                    'amount_paid'    => $amountPaid,
                    'status'         => 'active',
                ]
            );
        }
        $this->command->info('  ' . count($list) . ' étudiants créés → ' . $annexe->name . ' (' . $spec->label . ')');
    }

    private function seedPaymentLinksAndPayments(string $year): void
    {
        // Récupérer quelques students pour créer des liens de paiement
        $students = Student::limit(8)->get();
        $linksCount = 0;
        $paymentsCount = 0;

        foreach ($students as $student) {
            // Créer 1 lien de paiement par étudiant
            $link = PaymentLink::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'school_year' => $year,
                ],
                [
                    'id'              => (string) Str::uuid(),
                    'token'           => (string) Str::random(32),
                    'type'            => 'tuition',
                    'currency'        => 'XOF',
                    'amount'          => 0,
                    'status'          => 'active',
                    'school_year'     => $year,
                    'expire_at'       => now()->addMonths(3),
                ]
            );
            $linksCount++;

            // Créer 1-2 paiements par lien (pour tester partial et completed)
            $enrollment = Enrollment::where('student_id', $student->id)
                ->where('school_year', $year)
                ->first();

            if ($enrollment) {
                $tuition = $enrollment->tuition_amount;
                $link->update(['amount' => $tuition]);
                $amountToCreate = rand(1, 2); // 1 ou 2 paiements

                for ($i = 0; $i < $amountToCreate; $i++) {
                    $partialAmount = $amountToCreate === 1 
                        ? rand((int)($tuition * 0.3), (int)($tuition * 0.7))
                        : rand((int)($tuition * 0.1), (int)($tuition * 0.4));

                    Payment::create([
                        'id'              => (string) Str::uuid(),
                        'payment_link_id' => $link->id,
                        'student_id'      => $student->id,
                        'amount'          => $partialAmount,
                        'reference'       => 'REF_' . Str::upper(Str::random(12)),
                        'payplus_transaction_id' => 'PPL_' . Str::upper(Str::random(20)),
                        'status'          => 'success',
                        'method'          => ['mtn', 'moov', 'payplus'][rand(0, 2)],
                        'paid_at'         => now()->subDays(rand(1, 20)),
                    ]);
                    $paymentsCount++;
                }
            }
        }

        $this->command->info("  {$linksCount} liens de paiement créés avec {$paymentsCount} transactions");
    }
}
