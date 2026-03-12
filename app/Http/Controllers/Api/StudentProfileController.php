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

        $links   = PaymentLink::where('student_id', $student->id)->get();
        $linkIds = $links->pluck('id');

        $otherLinkIds = $links->where('type', '!=', 'tuition')->pluck('id');

        $successPayments = Payment::whereIn('payment_link_id', $linkIds)
            ->where('status', 'success')
            ->get();

        // Scolarité : directement depuis la table students
        $tuitionTotal     = (float) $student->tuition_amount;
        $tuitionPaid      = (float) $student->amount_paid;
        $tuitionRemaining = (float) max(0, $tuitionTotal - $tuitionPaid);

        // Autres frais : calculés depuis les liens de type != tuition
        $otherTotal = $links->where('type', '!=', 'tuition')->sum('amount');
        $otherPaid  = $successPayments->whereIn('payment_link_id', $otherLinkIds)->sum('amount');

        return response()->json([
            'student' => $student,
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

        $query = PaymentLink::where('student_id', $student->id)
            ->with([
                'installments' => fn ($q) => $q->orderBy('tranche_number'),
                'installments.payments' => fn ($q) => $q->where('status', 'success')->select('id', 'installment_id', 'payment_link_id', 'amount', 'paid_at', 'reference'),
                'payments' => fn ($q) => $q->where('status', 'success')->select('id', 'payment_link_id', 'amount', 'paid_at', 'reference', 'method'),
            ])
            ->orderByDesc('created_at');

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

        $query = Payment::where('student_id', $student->id)
            ->where('status', 'success')
            ->with([
                'paymentLink:id,type,description,currency,amount',
                'installment:id,description,tranche_number,amount',
            ])
            ->orderByDesc('paid_at');

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
