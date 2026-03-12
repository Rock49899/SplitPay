<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retard de Paiement</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px 20px;
        }
        .alert-box {
            background: #fff3cd;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-weight: 600;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .amount {
            font-size: 28px;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin: 20px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .footer-contact {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        .action-box {
            background: #e7f3ff;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .content {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">🚨</div>
            <h1>Retard de Paiement</h1>
        </div>

        <div class="content">
            <p>Bonjour,</p>

            <div class="alert-box">
                ⚠️ Votre paiement est en retard depuis {{ $daysOverdue }} jour(s).
            </div>

            <p>Nous vous rappelons qu'un montant reste impayé pour les frais de scolarité.</p>

            <div class="info-row">
                <span class="info-label">Étudiant(e) :</span>
                <span class="info-value">{{ $studentName }} ({{ $matricule }})</span>
            </div>

            <div class="info-row">
                <span class="info-label">Date d'échéance :</span>
                <span class="info-value"><strong>{{ $dueDate }}</strong></span>
            </div>

            <div class="info-row">
                <span class="info-label">Retard :</span>
                <span class="info-value"><strong style="color: #dc3545;">{{ $daysOverdue }} jour(s)</strong></span>
            </div>

            <div class="amount">
                {{ $amount }}
            </div>

            <div class="action-box">
                <p style="margin: 0; font-weight: 600; color: #0056b3;">
                    ⚡ Action requise : Merci de régulariser votre situation dans les plus brefs délais.
                </p>
            </div>

            <p style="color: #666; margin-top: 20px;">
                Pour toute difficulté de paiement, nous vous invitons à prendre contact avec notre service de gestion 
                afin de trouver une solution adaptée.
            </p>
        </div>

        <div class="footer">
            <strong>{{ $annexeName }}</strong>
            
            <div class="footer-contact">
                @if($annexePhone)
                    <div>📞 {{ $annexePhone }}</div>
                @endif
                @if($annexeEmail)
                    <div>✉️ {{ $annexeEmail }}</div>
                @endif
            </div>

            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                Ceci est un message automatique, merci de ne pas y répondre directement.
            </p>
        </div>
    </div>
</body>
</html>
