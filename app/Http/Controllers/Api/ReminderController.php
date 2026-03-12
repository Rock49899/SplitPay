<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reminder;
use Illuminate\Support\Facades\Validator;

class ReminderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:reminder.view')->only(['index', 'show', 'preview']);
        $this->middleware('permission:reminder.create')->only(['store']);
        $this->middleware('permission:reminder.edit')->only(['update', 'activate', 'deactivate', 'sendNow']);
        $this->middleware('permission:reminder.delete')->only(['destroy']);
    }

    /**
     * Liste des rappels configurés
     * GET /api/reminders
     */
    public function index(Request $request)
    {
        $query = Reminder::query()->orderedByDays();

        // Filtre : actifs uniquement
        if ($request->boolean('active_only')) {
            $query->active();
        }

        $reminders = $query->get();

        return response()->json($reminders);
    }

    /**
     * Détails d'un rappel
     */
    public function show(Reminder $reminder)
    {
        return response()->json($reminder->load('annexe'));
    }

    /**
     * Créer un nouveau rappel
     * POST /api/reminders
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'annexe_id' => 'required|uuid|exists:annexes,id',
            'days_before' => 'required|integer|min:0|max:30',
            'message' => 'required|string|max:1000',
            'is_active' => 'boolean',
        ]);

        // Vérifier si un rappel existe déjà pour cette annexe et ce délai
        $exists = Reminder::where('annexe_id', $validated['annexe_id'])
            ->where('days_before', $validated['days_before'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A reminder already exists for this annexe and delay',
            ], 422);
        }

        $reminder = Reminder::create($validated);

        return response()->json([
            'message' => 'Reminder created successfully',
            'reminder' => $reminder,
        ], 201);
    }

    /**
     * Mettre à jour un rappel
     * PUT/PATCH /api/reminders/{id}
     */
    public function update(Request $request, Reminder $reminder)
    {
        $validated = $request->validate([
            'days_before' => 'sometimes|integer|min:0|max:30',
            'message' => 'sometimes|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $reminder->update($validated);

        return response()->json([
            'message' => 'Reminder updated successfully',
            'reminder' => $reminder->fresh(),
        ]);
    }

    /**
     * Activer un rappel
     * PATCH /api/reminders/{id}/activate
     */
    public function activate(Reminder $reminder)
    {
        $reminder->activate();

        return response()->json([
            'message' => 'Reminder activated',
            'reminder' => $reminder->fresh(),
        ]);
    }

    /**
     * Désactiver un rappel
     * PATCH /api/reminders/{id}/deactivate
     */
    public function deactivate(Reminder $reminder)
    {
        $reminder->deactivate();

        return response()->json([
            'message' => 'Reminder deactivated',
            'reminder' => $reminder->fresh(),
        ]);
    }

    /**
     * Supprimer un rappel
     */
    public function destroy(Reminder $reminder)
    {
        $reminder->delete();

        return response()->json([
            'message' => 'Reminder deleted successfully',
        ]);
    }

    /**
     * Aperçu des étudiants concernés par un rappel
     * GET /api/reminders/{id}/preview
     */
    public function preview(Reminder $reminder)
    {
        $installments = $reminder->getTargetInstallments();

        $preview = $installments->map(function ($installment) use ($reminder) {
            $student = $installment->paymentLink->student;
            
            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'student_email' => $student->email,
                'parent_email' => $student->parent_email,
                'amount' => $installment->amount,
                'amount_paid' => $installment->amount_paid,
                'remaining' => $installment->amount - $installment->amount_paid,
                'due_date' => $installment->due_date,
                'message_preview' => $reminder->parseMessage([
                    'student_name' => $student->full_name,
                    'amount' => number_format($installment->amount - $installment->amount_paid, 0, ',', ' '),
                    'due_date' => $installment->due_date->format('d/m/Y'),
                    'payment_link' => url('/payment/' . $installment->paymentLink->token),
                ]),
            ];
        });

        return response()->json([
            'reminder' => $reminder,
            'count' => $preview->count(),
            'students' => $preview,
        ]);
    }

    /**
     * Envoyer le rappel immédiatement à tous les étudiants concernés
     * POST /api/reminders/{id}/send-now
     */
    public function sendNow(Reminder $reminder)
    {
        // Récupérer tous les installments concernés par ce rappel
        $installments = $reminder->getTargetInstallments();

        if ($installments->isEmpty()) {
            return response()->json([
                'message' => 'Aucun étudiant concerné actuellement',
                'count' => 0,
            ]);
        }

        // Dispatcher un job pour chaque installment
        $count = 0;
        foreach ($installments as $installment) {
            \App\Jobs\SendReminderJob::dispatch($reminder, $installment);
            $count++;
        }

        \Log::info('Manual reminder send triggered', [
            'reminder_id' => $reminder->id,
            'days_before' => $reminder->days_before,
            'annexe_id' => $reminder->annexe_id,
            'emails_queued' => $count,
        ]);

        return response()->json([
            'message' => "Rappel envoyé à {$count} étudiant(s)",
            'count' => $count,
        ]);
    }
}
