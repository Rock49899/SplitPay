<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\PaymentLink;
use App\Models\Payment;

class StudentProfileController extends Controller
{
    /** Récupère l'étudiant injecté par le middleware StudentAuth */
    private function getStudent(Request $request): Student
    {
        return $request->attributes->get('student');
    }

    /**
     * Profil complet + résumé financier
     * GET /api/student/profile
     */
    public function show(Request $request)
    {
        $student = $this->getStudent($request);
        $student->load('annexe.institution');

        $requestedYear = $request->query('school_year') ?: $request->input('school_year');

        $availableYears = $student->enrollments()
            ->select('school_year')
            ->distinct()
            ->orderByDesc('school_year')
            ->pluck('school_year')
            ->values();

        if ($request->attributes->get('school_year_available') === false) {
            return response()->json([
                'message' => 'Aucune donnée pour cette année scolaire.',
                'student' => [
                    'id' => $student->id,
                    'matricule' => $student->matricule,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'avatar_url' => $student->avatar_url,
                    'status' => $student->status,
                    'annexe' => $student->annexe,
                    'class' => null,
                    'school_year' => $requestedYear,
                ],
                'summary' => [
                    'tuition_total' => 0,
                    'tuition_paid' => 0,
                    'tuition_remaining' => 0,
                    'other_total' => 0,
                    'other_paid' => 0,
                    'other_remaining' => 0,
                    'total_paid' => 0,
                    'total_remaining' => 0,
                    'links_count' => 0,
                    'payments_count' => 0,
                ],
                'selected_year' => $requestedYear,
                'school_years' => $availableYears,
                'school_year_read_only' => true,
            ]);
        }

        $enrollment = $requestedYear
            ? $student->enrollments()->with('levelFee.studyLevel')->where('school_year', $requestedYear)->first()
            : $student->enrollments()->with('levelFee.studyLevel')->orderByDesc('school_year')->first();

        $selectedYear = $requestedYear ?: $enrollment?->school_year;

        $linksQuery = PaymentLink::where('student_id', $student->id);
        if ($selectedYear) {
            $linksQuery->where('school_year', $selectedYear);
        }
        $links   = $linksQuery->get();
        $linkIds = $links->pluck('id');

        $otherLinkIds = $links->where('type', '!=', 'tuition')->pluck('id');

        $successPayments = Payment::whereIn('payment_link_id', $linkIds)
            ->where('status', 'success')
            ->get();

        // Scolarité : source = enrollment de l'année sélectionnée
        $tuitionTotal     = (float) ($enrollment?->tuition_amount ?? 0);
        $tuitionPaid      = (float) ($enrollment?->amount_paid ?? 0);
        $tuitionRemaining = (float) max(0, $tuitionTotal - $tuitionPaid);

        // Autres frais : calculés depuis les liens de type != tuition
        $otherTotal = $links->where('type', '!=', 'tuition')->sum('amount');
        $otherPaid  = $successPayments->whereIn('payment_link_id', $otherLinkIds)->sum('amount');

        $studentPayload = [
            'id' => $student->id,
            'matricule' => $student->matricule,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'email' => $student->email,
            'phone' => $student->phone,
            'avatar_url' => $student->avatar_url,
            'status' => $student->status,
            'annexe' => $student->annexe,
            'class' => $enrollment?->levelFee?->studyLevel?->name,
            'school_year' => $selectedYear,
        ];

        return response()->json([
            'student' => $studentPayload,
            'summary' => [
                'tuition_total'     => $tuitionTotal,
                'tuition_paid'      => $tuitionPaid,
                'tuition_remaining' => $tuitionRemaining,
                'other_total'       => (float) $otherTotal,
                'other_paid'        => (float) $otherPaid,
                'other_remaining'   => (float) max(0, $otherTotal - $otherPaid),
                'total_paid'        => (float) ($tuitionPaid + $otherPaid),
                'total_remaining'   => (float) ($tuitionRemaining + max(0, $otherTotal - $otherPaid)),
                'links_count'       => $links->count(),
                'payments_count'    => $successPayments->count(),
            ],
            'selected_year' => $selectedYear,
            'school_years' => $availableYears,
            'school_year_read_only' => (bool) $request->attributes->get('school_year_read_only', false),
        ]);
    }

    /**
     * Liens de paiement de l'étudiant 
     */
    public function paymentLinks(Request $request)
    {
        $student = $this->getStudent($request);
        $type    = $request->query('type');   // tuition | other
        $status  = $request->query('status'); // active | used | expired | pending
        $requestedYear = $request->query('school_year') ?: $request->input('school_year');

        if ($request->attributes->get('school_year_available') === false) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 10,
                'total' => 0,
            ]);
        }

        $query = PaymentLink::where('student_id', $student->id)
            ->with([
                'installments' => fn ($q) => $q->orderBy('tranche_number'),
                'installments.payments' => fn ($q) => $q->where('status', 'success')->select('id', 'installment_id', 'payment_link_id', 'amount', 'paid_at', 'reference'),
                'payments' => fn ($q) => $q->where('status', 'success')->select('id', 'payment_link_id', 'amount', 'paid_at', 'reference', 'method'),
            ])
            ->orderByDesc('created_at');

        if ($requestedYear) {
            $query->where('school_year', $requestedYear);
        }

        if ($type === 'tuition') {
            $query->where('type', 'tuition');
        } elseif ($type === 'other') {
            $query->where('type', '!=', 'tuition');
        }

        if ($status) {
            $query->where('status', $status);
        }

        $links = $query->paginate(10);

        // Calcul des montants payés (success seulement) pour chaque lien
        $links->getCollection()->transform(function ($link) {
            $paid = $link->payments->sum('amount');
            $link->amount_paid_success = (float) $paid;
            $link->remaining_success   = (float) max(0, $link->amount - $paid);
            $link->payment_url         = url("/payment/{$link->token}");
            return $link;
        });

        return response()->json($links);
    }

    /**
     * Historique des paiements confirmés filtrable
     */
    public function payments(Request $request)
    {
        $student = $this->getStudent($request);
        $type    = $request->query('type');   // tuition | other
        $from    = $request->query('from');
        $to      = $request->query('to');
        $method  = $request->query('method'); // mtn | moov
        $requestedYear = $request->query('school_year') ?: $request->input('school_year');

        if ($request->attributes->get('school_year_available') === false) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'total' => 0,
            ]);
        }

        $query = Payment::where('student_id', $student->id)
            ->where('status', 'success')
            ->with([
                'paymentLink:id,type,description,currency,amount',
                'installment:id,description,tranche_number,amount',
            ])
            ->orderByDesc('paid_at');

        if ($requestedYear) {
            $query->whereHas('paymentLink', fn ($q) => $q->where('school_year', $requestedYear));
        }

        if ($type === 'tuition') {
            $query->whereHas('paymentLink', fn ($q) => $q->where('type', 'tuition'));
        } elseif ($type === 'other') {
            $query->whereHas('paymentLink', fn ($q) => $q->where('type', '!=', 'tuition'));
        }

        if ($from)   $query->whereDate('paid_at', '>=', $from);
        if ($to)     $query->whereDate('paid_at', '<=', $to);
        if ($method) $query->where('method', $method);

        return response()->json($query->paginate(15));
    }
}
