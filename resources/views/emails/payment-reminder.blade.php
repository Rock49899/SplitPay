<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel de Paiement</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .header .logo {
            max-width: 150px;
            max-height: 60px;
            margin: 0 auto 15px;
            display: block;
            object-fit: contain;
        }
        .content {
            padding: 30px 20px;
        }
        .alert-box {
            background: #f8f9fa;
            /* border-left: 4px solid #667eea; */
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-urgent {
            background: #fff3cd;
            border-left-color: #ffc107;
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
            color: #667eea;
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
            @if($institutionLogo)
                <img src="{{ $institutionLogo }}" alt="{{ $institutionName }}" class="logo">
            @endif
            <div class="icon">
                @if($daysBeforeDue === 0)
                    🚨
                @elseif($daysBeforeDue === 1)
                    ⏰
                @else
                    📅
                @endif
            </div>
            <h1>Rappel de Paiement</h1>
            @if($institutionName)
                <p style="margin: 5px 0 0; font-size: 14px; opacity: 0.9;">{{ $institutionName }}</p>
            @endif
        </div>

        <div class="content">
            <p>Bonjour,</p>

            <div class="alert-box {{ $daysBeforeDue <= 1 ? 'alert-urgent' : '' }}">
                <strong>{{ $customMessage }}</strong>
            </div>

            <div class="info-row">
                <span class="info-label">Étudiant(e) :</span>
                <span class="info-value">{{ $studentName }} ({{ $matricule }})</span>
            </div>

            <div class="info-row">
                <span class="info-label">Date limite :</span>
                <span class="info-value">{{ $dueDate }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">
                    @if($daysBeforeDue === 0)
                        Délai restant :
                    @elseif($daysBeforeDue === 1)
                        Délai restant :
                    @else
                        Délai avant échéance :
                    @endif
                </span>
                <span class="info-value">
                    @if($daysBeforeDue === 0)
                        <strong style="color: #dc3545;">AUJOURD'HUI</strong>
                    @elseif($daysBeforeDue === 1)
                        <strong style="color: #ffc107;">DEMAIN</strong>
                    @else
                        {{ $daysBeforeDue }} jour(s)
                    @endif
                </span>
            </div>

            <div class="amount">
                {{ $amount }}
            </div>

            <p style="text-align: center; color: #666; margin-top: 30px;">
                Merci de régulariser votre situation avant la date limite pour éviter toute pénalité.
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
