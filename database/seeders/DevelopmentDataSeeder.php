<?php

namespace Database\Seeders;

use App\Models\Annexe;
use App\Models\Enrollment;
use App\Models\Installment;
use App\Models\Institution;
use App\Models\LevelFee;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Reminder;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Données de démonstration (à lancer sur une base vide : php artisan migrate:fresh --seed).
 *
 * - 1 institution, 2 annexes, 4 comptes (mot de passe : password)
 * - une année scolaire clôturée et une année en cours
 * - scolarité payée en 3 tranches (octobre, janvier, avril), avec des profils de payeurs variés :
 *   à jour, en retard, seulement la 1re tranche, rien payé
 * - les montants payés (échéances, liens, inscriptions) sont calculés à partir des paiements,
 *   exactement comme le fait l'application.
 */
class DevelopmentDataSeeder extends Seeder
{
    /** Découpage de la scolarité : part du montant et date d'échéance (mois/jour) */
    private const TRANCHES = [
        1 => ['share' => 0.40, 'month' => 10, 'day' => 15, 'label' => '1re tranche'],
        2 => ['share' => 0.30, 'month' => 1,  'day' => 15, 'label' => '2e tranche'],
        3 => ['share' => 0.30, 'month' => 4,  'day' => 15, 'label' => '3e tranche'],
    ];

    /** [prénom, nom, niveau, filière, profil de paiement] */
    private const NORD_STUDENTS = [
        ['Amina', 'Kouassi', 'L1', 'INFO', 'regular'],
        ['Koffi', 'Mensah', 'L1', 'INFO', 'late'],
        ['Fatoumata', 'Diallo', 'L2', 'INFO', 'regular'],
        ['Ibrahim', 'Traoré', 'L2', 'INFO', 'first_only'],
        ['Aïcha', 'Camara', 'L3', 'INFO', 'regular'],
        ['Rodrigue', 'Adjovi', 'L3', 'INFO', 'late'],
        ['Grâce', 'Houngbédji', 'M1', 'INFO', 'regular'],
        ['Serge', 'Ahouandjinou', 'M2', 'INFO', 'late'],
        ['Nadège', 'Agossou', 'L1', 'COMPTA', 'first_only'],
        ['Yannick', 'Dossou', 'L2', 'COMPTA', 'regular'],
        ['Carine', 'Zinsou', 'L3', 'COMPTA', 'nothing'],
        ['Fabrice', 'Gbaguidi', 'M1', 'COMPTA', 'late'],
    ];

    private const SUD_STUDENTS = [
        ['Sébastien', 'Kouadio', 'L1', 'GESTION', 'regular'],
        ['Aminata', 'Sow', 'L1', 'GESTION', 'late'],
        ['Moussa', 'Keita', 'L2', 'GESTION', 'regular'],
        ['Mariama', 'Barry', 'L2', 'GESTION', 'first_only'],
        ['Youssouf', 'Touré', 'L3', 'GESTION', 'regular'],
        ['Prisca', 'Akakpo', 'M1', 'GESTION', 'late'],
        ['Ulrich', 'Hounkpè', 'L1', 'DROIT', 'regular'],
        ['Esther', 'Ayivi', 'L2', 'DROIT', 'nothing'],
        ['Christelle', 'Sossou', 'L3', 'DROIT', 'late'],
        ['Arnaud', 'Tchibozo', 'M2', 'DROIT', 'regular'],
    ];

    private string $demoYear;
    private string $previousYear;
    private int $startYear;
    private Carbon $now;
    private int $matriculeSeq = 0;

    /** @var Collection<int, Payment> */
    private Collection $successPayments;

    public function run(): void
    {
        // Une seule transaction : beaucoup plus rapide (MySQL ne valide pas chaque insertion)
        DB::transaction(fn () => $this->seed());
    }

    private function seed(): void
    {
        mt_srand(2026); // données identiques à chaque génération (captures reproductibles)

        $this->now = now();
        $this->demoYear = self::demoSchoolYear();
        $this->previousYear = AcademicSeeder::previousYear($this->demoYear);
        $this->startYear = (int) explode('-', $this->demoYear)[0];
        $this->successPayments = collect();

        $this->command->info("Création des données de démonstration (année en cours : {$this->demoYear})...");

        // ── Institution, annexes, calendrier ───────────────────────────────
        $institution = Institution::updateOrCreate(
            ['email' => 'contact@ist-edu.com'],
            [
                'name'      => 'Institut Supérieur de Technologie',
                'phone'     => '+229 01 21 30 40 50',
                'address'   => 'Avenue de la République',
                'city'      => 'Cotonou',
                'is_active' => true,
            ]
        );

        $annexeNord = Annexe::updateOrCreate(
            ['institution_id' => $institution->id, 'name' => 'Campus Nord'],
            ['address' => 'Quartier Akpakpa', 'city' => 'Cotonou', 'is_active' => true,
             'annexe_details' => ['email' => 'nord@ist-edu.com', 'phone' => '+229 01 21 30 40 51']]
        );

        $annexeSud = Annexe::updateOrCreate(
            ['institution_id' => $institution->id, 'name' => 'Campus Sud'],
            ['address' => 'Quartier Fidjrossè', 'city' => 'Cotonou', 'is_active' => true,
             'annexe_details' => ['email' => 'sud@ist-edu.com', 'phone' => '+229 01 21 30 40 52']]
        );

        $this->resetDemoData([$annexeNord->id, $annexeSud->id]);

        SchoolYear::updateOrCreate(
            ['institution_id' => $institution->id, 'year' => $this->previousYear],
            [
                'status' => 'closed',
                'opened_at' => Carbon::create($this->startYear - 1, 9, 1),
                'closed_at' => Carbon::create($this->startYear, 8, 31),
                'promoted_to_year' => $this->demoYear,
            ]
        );
        SchoolYear::updateOrCreate(
            ['institution_id' => $institution->id, 'year' => $this->demoYear],
            ['status' => 'active', 'opened_at' => Carbon::create($this->startYear, 9, 1), 'closed_at' => null]
        );

        // ── Comptes ────────────────────────────────────────────────────────
        $superAdmin = $this->seedUsers($annexeNord, $annexeSud);

        // ── Catalogue académique (propre à chaque annexe) ──────────────────
        $academic = new AcademicSeeder();
        $catalogs = [
            $annexeNord->id => $academic->seedForAnnexe($annexeNord, [$this->previousYear, $this->demoYear]),
            $annexeSud->id  => $academic->seedForAnnexe($annexeSud, [$this->previousYear, $this->demoYear]),
        ];

        // ── Étudiants, inscriptions, liens et paiements ────────────────────
        $students = collect()
            ->merge($this->seedStudents(self::NORD_STUDENTS, $annexeNord, $catalogs[$annexeNord->id], $superAdmin))
            ->merge($this->seedStudents(self::SUD_STUDENTS, $annexeSud, $catalogs[$annexeSud->id], $superAdmin));

        // ── Rappels et notifications ───────────────────────────────────────
        $this->seedReminders([$annexeNord, $annexeSud]);
        $this->seedNotifications($students);

        $this->command->newLine();
        $this->command->info('RÉSUMÉ :');
        $this->command->info("  - 1 institution, 2 annexes, années {$this->previousYear} (clôturée) et {$this->demoYear} (en cours)");
        $this->command->info('  - ' . $students->count() . ' étudiants, ' . PaymentLink::count() . ' liens de paiement, ' . Payment::count() . ' paiements');
        $this->command->info('  Connexion : admin@ist-edu.com | password');
        $this->command->info('  Connexion plateforme : platform@splitpay.test | password');
    }

    /**
     * Année de démonstration : celle qui contient la date d'il y a 4 mois, pour que
     * l'année affichée ait toujours plusieurs mois de paiements (et pas seulement la rentrée).
     */
    public static function demoSchoolYear(): string
    {
        $ref = now()->subMonths(4);
        $base = $ref->month >= 9 ? $ref->year : $ref->year - 1;

        return $base . '-' . ($base + 1);
    }

    /** Supprime les données de démo existantes pour pouvoir relancer le seeder. */
    private function resetDemoData(array $annexeIds): void
    {
        // La suppression des étudiants supprime en cascade inscriptions, liens, échéances et paiements
        Student::whereIn('annexe_id', $annexeIds)->delete();
        Notification::whereIn('annexe_id', $annexeIds)->delete();
        Reminder::whereIn('annexe_id', $annexeIds)->delete();
    }

    private function seedUsers(Annexe $annexeNord, Annexe $annexeSud): User
    {
        $roleSuperAdmin   = Role::where('code', 'super_admin_institution')->firstOrFail();
        $roleGestionnaire = Role::where('code', 'gestionnaire')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'platform@splitpay.test'],
            ['annexe_id' => null, 'name' => 'Administrateur SplitPay', 'password' => Hash::make('password'),
             'phone' => '+229 01 90 00 00 00', 'is_active' => true, 'scope' => 'platform']
        );

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@ist-edu.com'],
            ['annexe_id' => $annexeNord->id, 'name' => 'Clarisse Adjanohoun', 'password' => Hash::make('password'),
             'phone' => '+229 01 97 11 11 11', 'is_active' => true, 'scope' => 'institution']
        );
        $superAdmin->annexes()->syncWithoutDetaching([
            $annexeNord->id => ['role_id' => $roleSuperAdmin->id, 'is_principal' => true, 'assigned_at' => now()],
        ]);

        foreach ([
            ['name' => 'Marie Dupont', 'email' => 'marie@ist-edu.com', 'annexe' => $annexeNord, 'phone' => '+229 01 97 22 22 22'],
            ['name' => 'Jean Martin',  'email' => 'jean@ist-edu.com',  'annexe' => $annexeSud,  'phone' => '+229 01 97 33 33 33'],
        ] as $g) {
            $user = User::updateOrCreate(
                ['email' => $g['email']],
                ['annexe_id' => $g['annexe']->id, 'name' => $g['name'], 'password' => Hash::make('password'),
                 'phone' => $g['phone'], 'is_active' => true, 'scope' => 'annexe']
            );
            $user->annexes()->syncWithoutDetaching([
                $g['annexe']->id => ['role_id' => $roleGestionnaire->id, 'is_principal' => true,
                                     'assigned_by' => $superAdmin->id, 'assigned_at' => now()],
            ]);
        }

        return $superAdmin;
    }

    /**
     * @param array{levels: Collection, specializations: Collection} $catalog
     * @return Collection<int, array{student: Student, profile: string}>
     */
    private function seedStudents(array $list, Annexe $annexe, array $catalog, User $creator): Collection
    {
        $created = collect();

        foreach ($list as [$firstName, $lastName, $levelCode, $specCode, $profile]) {
            $this->matriculeSeq++;
            $level = $catalog['levels'][$levelCode];
            $spec  = $catalog['specializations'][$specCode];

            $student = Student::create([
                'annexe_id'         => $annexe->id,
                'matricule'         => sprintf('IST-%d-%03d', $this->startYear, $this->matriculeSeq),
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'email'             => Str::lower(Str::ascii("{$firstName}.{$lastName}")) . '@etudiant-ist.bj',
                'phone'             => sprintf('+229 01 %02d %02d %02d %02d', mt_rand(90, 99), mt_rand(10, 99), mt_rand(10, 99), mt_rand(10, 99)),
                'specialization_id' => $spec->id,
                'status'            => 'active',
            ]);

            // Année précédente : les étudiants au-delà de la L1 étaient au niveau inférieur et ont tout réglé
            $previousLevel = $catalog['levels']->first(fn ($l) => (int) $l->order === (int) $level->order - 1);
            if ($previousLevel) {
                $this->seedPreviousYear($student, $previousLevel, $spec->id, $annexe->id, $creator);
            }

            // Année en cours : scolarité en 3 tranches
            $fee = LevelFee::resolve($level->id, $spec->id, $this->demoYear, $annexe->id);
            $enrollment = Enrollment::create([
                'student_id'     => $student->id,
                'level_fee_id'   => $fee->id,
                'tuition_amount' => $fee->tuition_amount,
                'amount_paid'    => 0,
                'school_year'    => $this->demoYear,
                'status'         => 'active',
            ]);

            $this->seedTuitionTranches($student, $enrollment, $profile, $creator);
            $this->syncEnrollment($enrollment);

            $created->push(['student' => $student, 'profile' => $profile]);
        }

        $this->command->info('  ' . count($list) . " étudiants créés → {$annexe->name}");

        return $created;
    }

    private function seedPreviousYear(Student $student, $previousLevel, int $specId, string $annexeId, User $creator): void
    {
        $fee = LevelFee::resolve($previousLevel->id, $specId, $this->previousYear, $annexeId);
        $prevStart = $this->startYear - 1;

        $enrollment = Enrollment::create([
            'student_id'     => $student->id,
            'level_fee_id'   => $fee->id,
            'tuition_amount' => $fee->tuition_amount,
            'amount_paid'    => 0,
            'school_year'    => $this->previousYear,
            'status'         => 'active',
            'promoted_at'    => Carbon::create($this->startYear, 8, 31),
        ]);

        [$link, $installment] = $this->createLink(
            $student, $this->previousYear, (float) $fee->tuition_amount, "Scolarité {$this->previousYear}",
            Carbon::create($prevStart + 1, 1, 31), Carbon::create($prevStart, 9, 10), 1, $creator
        );

        $first = $this->roundAmount($fee->tuition_amount * 0.5);
        $this->pay($link, $installment, $student, $first, Carbon::create($prevStart, 10, mt_rand(2, 25), mt_rand(8, 18), mt_rand(0, 59)));
        $this->pay($link, $installment, $student, (float) $fee->tuition_amount - $first, Carbon::create($prevStart + 1, 2, mt_rand(2, 25), mt_rand(8, 18), mt_rand(0, 59)));

        $this->syncInstallmentAndLink($installment, $link);
        $this->syncEnrollment($enrollment);
    }

    private function seedTuitionTranches(Student $student, Enrollment $enrollment, string $profile, User $creator): void
    {
        $tuition = (float) $enrollment->tuition_amount;
        $amounts = [];
        foreach (self::TRANCHES as $n => $t) {
            $amounts[$n] = $n < 3 ? $this->roundAmount($tuition * $t['share']) : $tuition - array_sum($amounts);
        }

        foreach (self::TRANCHES as $n => $t) {
            $due = Carbon::create($t['month'] >= 9 ? $this->startYear : $this->startYear + 1, $t['month'], $t['day']);
            $sentAt = $due->copy()->subDays(30)->max(Carbon::create($this->startYear, 9, 5));

            [$link, $installment] = $this->createLink(
                $student, $this->demoYear, $amounts[$n], "Scolarité {$this->demoYear} · {$t['label']}",
                $due, $sentAt, $n, $creator
            );

            $this->applyProfile($profile, $n, $link, $installment, $student, $amounts[$n], $due);
            $this->syncInstallmentAndLink($installment, $link);

            // Relances envoyées pour les tranches échues et non soldées
            $installment->refresh();
            if ($due->lt($this->now) && (float) $installment->amount_paid < (float) $installment->amount) {
                $installment->update([
                    'reminder_count' => mt_rand(1, 3),
                    'last_reminder_sent_at' => $due->copy()->subDay()->setTime(8, 0),
                ]);
            }
        }
    }

    /** Paiements d'une tranche selon le profil du payeur */
    private function applyProfile(string $profile, int $n, PaymentLink $link, Installment $inst, Student $student, float $amount, Carbon $due): void
    {
        $around = fn (int $min, int $max) => $due->copy()->addDays(mt_rand($min, $max))->setTime(mt_rand(7, 20), mt_rand(0, 59));

        switch ($profile) {
            case 'regular': // paie chaque tranche un peu avant l'échéance
                $this->pay($link, $inst, $student, $amount, $around(-12, -1));
                break;

            case 'late': // paie en retard, parfois en deux fois ; la dernière tranche n'est pas soldée
                if ($n === 1) {
                    $part = $this->roundAmount($amount * 0.6);
                    $this->pay($link, $inst, $student, $part, $around(-5, 2));
                    $this->pay($link, $inst, $student, $amount - $part, $around(15, 30));
                } elseif ($n === 2) {
                    $this->pay($link, $inst, $student, $amount, $around(10, 25));
                } else {
                    $this->pay($link, $inst, $student, $this->roundAmount($amount * 0.5), $around(8, 20));
                    $this->pay($link, $inst, $student, $amount, $around(21, 28), 'failed');
                }
                break;

            case 'first_only': // n'a réglé que la première tranche
                if ($n === 1) {
                    $this->pay($link, $inst, $student, $amount, $around(-3, 6));
                } elseif ($n === 2) {
                    $this->pay($link, $inst, $student, $amount, $around(3, 8), 'failed');
                }
                break;

            case 'nothing': // rien payé : une tentative échouée sur la première tranche
                if ($n === 1) {
                    $this->pay($link, $inst, $student, $amount, $around(5, 15), 'failed');
                }
                break;
        }
    }

    /** @return array{0: PaymentLink, 1: Installment} */
    private function createLink(Student $student, string $year, float $amount, string $description, Carbon $due, Carbon $createdAt, int $tranche, User $creator): array
    {
        $link = PaymentLink::forceCreate([
            'id'          => (string) Str::uuid(),
            'student_id'  => $student->id,
            'school_year' => $year,
            'type'        => 'tuition',
            'token'       => PaymentLink::generateUniqueToken(),
            'amount'      => $amount,
            'currency'    => 'XOF',
            'description' => $description,
            'due_date'    => $due->toDateString(),
            'status'      => 'active',
            'sent_at'     => $createdAt,
            'expire_at'   => null,
            'created_by'  => $creator->id,
            'created_at'  => $createdAt,
        ]);

        $installment = Installment::forceCreate([
            'id'              => (string) Str::uuid(),
            'payment_link_id' => $link->id,
            'tranche_number'  => $tranche,
            'description'     => $description,
            'amount'          => $amount,
            'amount_paid'     => 0,
            'due_date'        => $due->toDateString(),
            'status'          => 'active',
            'created_by'      => $creator->id,
            'created_at'      => $createdAt,
        ]);

        return [$link, $installment];
    }

    /** Enregistre un paiement (uniquement s'il est dans le passé). */
    private function pay(PaymentLink $link, Installment $inst, Student $student, float $amount, Carbon $date, string $status = 'success'): void
    {
        if ($amount <= 0 || $date->gt($this->now)) {
            return;
        }

        $date = $date->max(Carbon::create($this->startYear - 1, 9, 1));
        $payerIsParent = mt_rand(1, 100) <= 70;

        $payment = Payment::forceCreate([
            'id'                     => (string) Str::uuid(),
            'payment_link_id'        => $link->id,
            'installment_id'         => $inst->id,
            'student_id'             => $student->id,
            'amount'                 => $amount,
            'method'                 => mt_rand(1, 100) <= 60 ? 'mtn' : 'moov',
            'reference'              => 'PAY-' . Str::upper(Str::random(10)),
            'payplus_transaction_id' => 'pp_' . Str::lower(Str::random(24)),
            'payer_name'             => $payerIsParent ? (mt_rand(0, 1) ? 'M. ' : 'Mme ') . $student->last_name : $student->full_name,
            'payer_phone'            => sprintf('22901%02d%06d', mt_rand(90, 99), mt_rand(0, 999999)),
            'status'                 => $status,
            'metadata'               => $status === 'success'
                ? ['verified_via' => 'webhook', 'response_code' => '00']
                : ['verified_via' => 'polling', 'error' => 'Transaction annulée par le client'],
            'paid_at'                => $status === 'success' ? $date : null,
            'created_at'             => $date,
            'updated_at'             => $date,
        ]);

        if ($status === 'success') {
            $this->successPayments->push($payment);
        }
    }

    private function syncInstallmentAndLink(Installment $installment, PaymentLink $link): void
    {
        $paid = (float) Payment::where('installment_id', $installment->id)->where('status', 'success')->sum('amount');
        $installment->update([
            'amount_paid' => $paid,
            'status' => $paid >= (float) $installment->amount ? 'used' : 'active',
        ]);

        $link->update(['status' => $paid >= (float) $link->amount ? 'used' : 'active']);
    }

    private function syncEnrollment(Enrollment $enrollment): void
    {
        $paid = (float) Payment::where('student_id', $enrollment->student_id)
            ->where('status', 'success')
            ->whereHas('paymentLink', fn ($q) => $q->where('type', 'tuition')->where('school_year', $enrollment->school_year))
            ->sum('amount');

        $enrollment->update([
            'amount_paid' => $paid,
            'status' => $paid >= (float) $enrollment->tuition_amount ? 'completed' : 'active',
        ]);
    }

    private function seedReminders(array $annexes): void
    {
        foreach ($annexes as $annexe) {
            Reminder::create([
                'annexe_id'   => $annexe->id,
                'days_before' => 7,
                'message'     => 'Bonjour {student_name}, la prochaine tranche de scolarité ({amount}) arrive à échéance le {due_date}. Vous pouvez la régler par Mobile Money ici : {payment_link}',
                'is_active'   => true,
            ]);
            Reminder::create([
                'annexe_id'   => $annexe->id,
                'days_before' => 1,
                'message'     => 'Rappel : {amount} sont à régler demain ({due_date}) pour {student_name}. Lien de paiement : {payment_link}',
                'is_active'   => true,
            ]);
        }
    }

    /** Notifications internes cohérentes avec les paiements générés */
    private function seedNotifications(Collection $students): void
    {
        $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');

        // Derniers paiements reçus (les 3 plus récents restent non lus)
        $this->successPayments->sortByDesc('paid_at')->take(8)->values()->each(function (Payment $p, int $i) use ($fmt) {
            $student = $p->student;
            Notification::forceCreate([
                'id'         => (string) Str::uuid(),
                'annexe_id'  => $student->annexe_id,
                'title'      => 'Paiement reçu',
                'message'    => "Paiement de {$fmt($p->amount)} FCFA reçu de {$student->full_name} ({$student->matricule})",
                'type'       => 'payment_received',
                'is_read'    => $i >= 3,
                'created_at' => $p->paid_at,
            ]);
        });

        // Échéances dépassées pour les étudiants qui n'ont réglé que la première tranche
        $students->whereIn('profile', ['first_only', 'nothing'])->each(function (array $row) use ($fmt) {
            $student = $row['student'];
            $overdue = Installment::whereHas('paymentLink', fn ($q) => $q->where('student_id', $student->id)->where('school_year', $this->demoYear))
                ->whereDate('due_date', '<', $this->now)
                ->whereRaw('amount_paid < amount')
                ->orderByDesc('due_date')
                ->first();

            if ($overdue) {
                Notification::forceCreate([
                    'id'         => (string) Str::uuid(),
                    'annexe_id'  => $student->annexe_id,
                    'title'      => 'Échéance dépassée',
                    'message'    => "Échéance de {$fmt($overdue->amount)} FCFA dépassée pour {$student->full_name} ({$student->matricule})",
                    'type'       => 'payment_overdue',
                    'is_read'    => false,
                    'created_at' => $overdue->due_date->copy()->addDay()->setTime(9, 0),
                ]);
            }
        });
    }

    private function roundAmount(float $amount): float
    {
        return round($amount / 5000) * 5000;
    }
}
