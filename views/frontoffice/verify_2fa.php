<?php
declare(strict_types=1);

$pending2fa = $_SESSION['pending_2fa'] ?? [];
$maskedPhone = $pending2fa['masked_phone'] ?? 'votre numéro WhatsApp';
$expiresAt = $pending2fa['expires_at'] ?? null;
$remainingSeconds = 0;

if (is_int($expiresAt) || ctype_digit((string) $expiresAt)) {
    $remainingSeconds = max(0, ((int) $expiresAt) - time());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification 2FA - DocTime</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #eef7f5 0%, #ffffff 100%);
            color: #16302b;
        }
        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(17, 57, 49, 0.12);
            padding: 32px;
        }
        .badge {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e8f7f1;
            color: #18a96f;
            font-size: 28px;
            margin-bottom: 18px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 28px;
        }
        p {
            margin: 0 0 18px;
            line-height: 1.5;
        }
        .alert {
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert-error {
            background: #fff1f1;
            color: #a23232;
        }
        .alert-success {
            background: #effaf5;
            color: #237a52;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }
        input[type="text"] {
            width: 100%;
            border: 1px solid #d5e4de;
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 18px;
            letter-spacing: 0.25em;
            text-align: center;
            box-sizing: border-box;
        }
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }
        .btn {
            appearance: none;
            border: none;
            border-radius: 14px;
            padding: 14px 18px;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            flex: 1;
        }
        .btn-primary {
            background: #18a96f;
            color: #fff;
        }
        .btn-secondary {
            background: #eef5f2;
            color: #16302b;
        }
        .meta {
            margin-top: 18px;
            font-size: 14px;
            color: #55736b;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="badge">✓</div>
        <h1>Vérification WhatsApp</h1>
        <p>Un code à 6 chiffres a été envoyé sur <strong><?= htmlspecialchars((string) $maskedPhone) ?></strong>.</p>

        <?php if (!empty($_SESSION['errors']['__form'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars((string) $_SESSION['errors']['__form']) ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars((string) $_SESSION['success']) ?></div>
        <?php endif; ?>

        <?php unset($_SESSION['errors'], $_SESSION['success']); ?>

        <form method="POST" action="index.php?page=verify_2fa" autocomplete="one-time-code">
            <label for="verification_code">Code de vérification</label>
            <input id="verification_code" name="verification_code" type="text" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Valider</button>
                <a class="btn btn-secondary" href="index.php?page=resend_2fa">Renvoyer</a>
            </div>
        </form>

        <div class="meta">
            <?php if ($remainingSeconds > 0): ?>
                Le code expire dans environ <?= htmlspecialchars((string) ceil($remainingSeconds / 60)) ?> minute(s).
            <?php else: ?>
                Le code a expiré. Demande-en un nouveau.
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
