<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code OTP</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
        }
        .container {
            max-width: 620px;
            margin: 24px auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }
        .header {
            background: #1d4ed8;
            color: #ffffff;
            text-align: center;
            padding: 24px 20px;
        }
        .logo {
            display: block;
            margin: 0 auto 12px;
            max-width: 220px;
            max-height: 90px;
            object-fit: contain;
            background: rgba(255,255,255,0.15);
            padding: 6px 10px;
            border-radius: 8px;
        }
        .content {
            padding: 24px 22px;
            line-height: 1.6;
        }
        .otp-box {
            margin: 20px 0;
            text-align: center;
            background: #eff6ff;
            border: 1px dashed #93c5fd;
            border-radius: 10px;
            padding: 16px;
        }
        .otp-code {
            font-size: 34px;
            letter-spacing: 6px;
            font-weight: 700;
            color: #1d4ed8;
        }
        .muted {
            color: #6b7280;
            font-size: 13px;
        }
        .footer {
            padding: 18px 22px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        @if(!empty($institutionLogoPath) && isset($message))
            <img src="{{ $message->embed($institutionLogoPath) }}" alt="{{ $institutionName ?? '' }}" class="logo">
        @elseif(!empty($institutionLogo))
            <img src="{{ $institutionLogo }}" alt="{{ $institutionName ?? '' }}" class="logo">
        @endif
        <h1 style="margin: 0; font-size: 22px;">Code de vérification</h1>
        @if(!empty($institutionName))
            <p style="margin: 8px 0 0; font-size: 13px; opacity: .9;">{{ $institutionName }}</p>
        @endif
    </div>

    <div class="content">
        <p>Bonjour {{ $recipientName ?? 'Utilisateur' }},</p>
        <p>Utilisez le code suivant pour finaliser votre connexion {{ !empty($audienceLabel) ? '('.$audienceLabel.')' : '' }} :</p>

        <div class="otp-box">
            <div class="otp-code">{{ $otp }}</div>
            <div class="muted" style="margin-top: 8px;">Ce code expire dans 10 minutes.</div>
        </div>

        <p class="muted">Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email en toute sécurité.</p>
    </div>

    <div class="footer">
        Message automatique — merci de ne pas répondre directement à cet email.
    </div>
</div>
</body>
</html>
