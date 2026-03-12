<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lien de Paiement</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
        }
        .header {
            background: #2563eb;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: normal;
        }
        .content {
            padding: 30px 25px;
        }
        .info-box {
            background: #f9fafb;
            padding: 15px;
            margin: 20px 0;
            border-left: 3px solid #2563eb;
        }
        .amount {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
            text-align: center;
            margin: 25px 0;
            padding: 20px;
            background: #eff6ff;
        }
        .button {
            display: inline-block;
            background: #2563eb;
            color: white !important;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 15px;
        }
        .button-secondary {
            display: inline-block;
            background: transparent;
            color: #6b7280 !important;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
            margin: 0 5px;
        }
        .button-secondary:hover {
            background: #f9fafb;
            color: #374151 !important;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .secondary-actions {
            text-align: center;
            margin: 15px 0 10px 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 20px 0;
        }
        .info-item {
            background: #f9fafb;
            padding: 12px;
        }
        .info-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }
        .deadline {
            background: #fef3c7;
            padding: 12px;
            margin: 20px 0;
            text-align: center;
            border-left: 3px solid #f59e0b;
        }
        .deadline.urgent {
            background: #fee2e2;
            border-left-color: #dc2626;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        @media only screen and (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    @php
        $type = $messageType ?? 'initial';
        $daysRemaining = null;
        $isOverdue = false;
        
        if (isset($link->due_date) && $link->due_date) {
            $deadline = \Carbon\Carbon::parse($link->due_date);
            $daysRemaining = now()->diffInDays($deadline, false);
            $isOverdue = $daysRemaining < 0;
        }
        
        $titles = [
            'initial' => 'Lien de Paiement',
            'reminder' => 'Rappel de Paiement',
            'urgent' => 'Paiement Urgent',
            'final' => 'Dernier Rappel'
        ];
        
        $messages = [
            'initial' => 'Vous avez un paiement en attente. Nous vous invitons à effectuer le règlement dès que possible.',
            'reminder' => 'Nous vous rappelons qu\'un paiement reste en attente. Merci de procéder au règlement.',
            'urgent' => 'La date d\'échéance de ce paiement approche. Veuillez régler rapidement pour éviter tout désagrément.',
            'final' => 'Dernier rappel : ce paiement doit être effectué sans délai pour éviter des conséquences administratives.'
        ];
        
        $title = $titles[$type] ?? $titles['initial'];
        $message = $messages[$type] ?? $messages['initial'];
    @endphp
    
    <div class="container">
        <div class="header">
            <h1>{{ $title }}</h1>
        </div>

        <div class="content">
            <p>Bonjour,</p>

            <div class="info-box">
                {{ $message }}
            </div>

            @if(isset($link->student))
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Étudiant(e)</div>
                    <div class="info-value">{{ $link->student->full_name ?? ($link->student->first_name . ' ' . $link->student->last_name) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Matricule</div>
                    <div class="info-value">{{ $link->student->matricule ?? '-' }}</div>
                </div>
            </div>
            @endif

            <div class="amount">
                {{ number_format($link->amount ?? 0, 0, ',', ' ') }} {{ $link->currency ?? 'FCFA' }}
            </div>

            @if($link->description)
            <div class="info-item" style="margin: 20px 0;">
                <div class="info-label">Description</div>
                <div class="info-value">{{ $link->description }}</div>
            </div>
            @endif

            @if(isset($link->due_date) && $link->due_date)
            <div class="deadline {{ ($isOverdue || ($daysRemaining !== null && $daysRemaining <= 3)) ? 'urgent' : '' }}">
                <strong>
                    @if($isOverdue)
                        Échéance dépassée
                    @elseif($daysRemaining !== null && $daysRemaining <= 3)
                        Échéance imminente
                    @else
                        Date limite
                    @endif
                </strong>
                <div style="font-size: 18px; font-weight: bold; margin: 5px 0;">
                    {{ \Carbon\Carbon::parse($link->due_date)->format('d/m/Y') }}
                </div>
                @if(!$isOverdue && $daysRemaining !== null)
                    <div style="font-size: 13px; color: #6b7280;">
                        @if($daysRemaining == 0)
                            Aujourd'hui
                        @elseif($daysRemaining == 1)
                            Demain
                        @else
                            {{ $daysRemaining }} jour{{ $daysRemaining > 1 ? 's' : '' }} restant{{ $daysRemaining > 1 ? 's' : '' }}
                        @endif
                    </div>
                @elseif($isOverdue)
                    <div style="font-size: 13px; color: #dc2626; font-weight: bold;">
                        Retard de {{ abs($daysRemaining) }} jour{{ abs($daysRemaining) > 1 ? 's' : '' }}
                    </div>
                @endif
            </div>
            @endif

            <p style="margin: 25px 0;">
                Cliquez sur le bouton ci-dessous pour accéder à la page de paiement sécurisée.
            </p>

            <div class="button-container">
                <a href="{{ $url }}" class="button">Payer Maintenant</a>
            </div>

            <div class="secondary-actions">
                <a href="mailto:?subject=Lien de paiement - {{ $link->student->full_name ?? 'Étudiant' }}&body=Bonjour,%0D%0A%0D%0AVoici le lien de paiement sécurisé :%0D%0A{{ $url }}%0D%0A%0D%0AMontant : {{ number_format($link->amount ?? 0, 0, ',', ' ') }} {{ $link->currency ?? 'FCFA' }}%0D%0A%0D%0ACordialement." class="button-secondary">Partager par email</a>
            </div>

            <p style="text-align: center; color: #9ca3af; font-size: 12px; margin: 15px 0 5px 0;">
                Lien de paiement à copier/partager :
            </p>
            <p style="text-align: center; background: #f9fafb; padding: 10px; border: 1px dashed #d1d5db; font-size: 12px; color: #6b7280; word-break: break-all; margin: 0 0 20px 0;">
                {{ $url }}
            </p>

            <p style="text-align: center; color: #6b7280; font-size: 13px; margin-top: 20px; padding: 12px; background: #f9fafb;">
                <strong>Paiement sécurisé</strong><br>
                Ce lien est personnel et expire après utilisation.
            </p>
        </div>

        <div class="footer">
            @if(isset($link->student->annexe))
                <strong>{{ $link->student->annexe->name ?? config('app.name') }}</strong>
                <div style="margin-top: 10px;">
                    @if(isset($link->student->annexe->phone) && $link->student->annexe->phone)
                        <div>Tél: {{ $link->student->annexe->phone }}</div>
                    @endif
                    @if(isset($link->student->annexe->email) && $link->student->annexe->email)
                        <div>Email: {{ $link->student->annexe->email }}</div>
                    @endif
                </div>
            @else
                <strong>{{ config('app.name') }}</strong>
            @endif
            <p style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
                Message automatique - Pour toute question, contactez-nous via les coordonnées ci-dessus.
            </p>
        </div>
    </div>
</body>
</html>
