    public function showVerifyTwoFactor(): void {
        if (empty($_SESSION['pending_2fa'])) {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
        $viewPath = __DIR__ . '/../views/frontoffice/verify_2fa.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "Vue verify_2fa.php manquante.";
        }
    }

    public function verifyTwoFactorCode(): void {
        if (empty($_SESSION['pending_2fa'])) {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $code = trim((string)($_POST['code'] ?? ''));
        $pending = $_SESSION['pending_2fa'];

        if (time() > $pending['expires_at']) {
            $_SESSION['errors'] = ['__form' => 'Le code a expiré. Veuillez demander un nouveau code.'];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=verify_2fa');
            exit;
        }

        if ($code !== $pending['code']) {
            $_SESSION['errors'] = ['__form' => 'Code de vérification incorrect.'];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=verify_2fa');
            exit;
        }

        $user = $pending['user'];
        $redirect = $pending['redirect'];

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['nom'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['success']   = 'Connexion réussie.';

        unset($_SESSION['pending_2fa']);

        header('Location: ' . $this->getBaseUrl() . ltrim($redirect, '/'));
        exit;
    }

    public function resendTwoFactorCode(): void {
        if (empty($_SESSION['pending_2fa'])) {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $pending = $_SESSION['pending_2fa'];
        if (!$this->startTwoFactorChallenge($pending['user'], $pending['redirect'])) {
            $_SESSION['errors'] = ['__form' => 'Impossible de renvoyer le code.'];
        } else {
            $_SESSION['success'] = 'Un nouveau code a été envoyé.';
        }

        header('Location: ' . $this->getBaseUrl() . 'index.php?page=verify_2fa');
        exit;
    }

    private function buildPostLoginRedirect(array $user): string {
        return match($user['role'] ?? '') {
            'admin'   => 'index.php?page=dashboard',
            'medecin' => 'index.php?page=accueil',
            default   => 'index.php?page=accueil'
        };
    }

    private function startTwoFactorChallenge(array $user, string $redirect): bool {
        $code = (string) random_int(100000, 999999);
        $phone = $this->normalizeWhatsappNumber((string) ($user['telephone'] ?? ''));
        
        $whatsappSuccess = false;
        if ($phone !== null) {
            $whatsappSuccess = $this->sendWhatsAppVerificationCode($phone, $code, (string) ($user['prenom'] ?? ''));
        } else {
            error_log('2FA WhatsApp impossible: numéro utilisateur manquant ou invalide. Tentative par email.');
        }

        $emailSuccess = false;
        if (!$whatsappSuccess) {
            $email = $user['email'] ?? '';
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailBody = "
                    <h1>Code de vérification</h1>
                    <p>Bonjour " . htmlspecialchars((string)($user['prenom'] ?? '')) . ",</p>
                    <p>Votre code de vérification à 6 chiffres est : <strong>" . $code . "</strong></p>
                    <p>Il expirera dans 5 minutes.</p>
                ";
                try {
                    $emailSuccess = MailConfig::send($email, ($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''), 'Votre code de vérification DocTime', $emailBody);
                } catch (\Throwable $e) {
                    error_log('Erreur envoi email 2FA: ' . $e->getMessage());
                }
            }
        }

        if (!$whatsappSuccess && !$emailSuccess) {
            return false;
        }

        $_SESSION['pending_2fa'] = [
            'code' => $code,
            'expires_at' => time() + 300,
            'redirect' => $redirect,
            'user' => $user,
            'phone' => $phone ?? '',
            'masked_phone' => $phone !== null ? $this->maskPhoneNumber($phone) : 'Email',
            'method' => $whatsappSuccess ? 'WhatsApp' : 'Email'
        ];

        return true;
    }

    private function sendWhatsAppVerificationCode(string $phone, string $code, string $firstName = ''): bool {
        $token = $this->getEnvValue('WHATSAPP_ACCESS_TOKEN');
        $phoneNumberId = $this->getEnvValue('WHATSAPP_PHONE_NUMBER_ID');

        if ($token === '' || $phoneNumberId === '') {
            error_log('WhatsApp 2FA non configuré: token ou phone number id manquant.');
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => trim(sprintf(
                    "Bonjour%s, votre code de vérification DocTime est : %s. Il expire dans 5 minutes.",
                    $firstName !== '' ? ' ' . $firstName : '',
                    $code
                )),
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        try {
            if (function_exists('curl_init')) {
                $ch = curl_init('https://graph.facebook.com/v25.0/' . rawurlencode($phoneNumberId) . '/messages');
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ]);

                $raw = curl_exec($ch);
                if ($raw === false) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    throw new \RuntimeException($error);
                }

                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 400) {
                    throw new \RuntimeException('HTTP ' . $httpCode . ' - ' . $raw);
                }

                return true;
            }

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'ignore_errors' => true,
                    'timeout' => 20,
                ],
            ]);

            $raw = @file_get_contents('https://graph.facebook.com/v25.0/' . rawurlencode($phoneNumberId) . '/messages', false, $context);
            if ($raw === false) {
                throw new \RuntimeException('Échec de l’envoi WhatsApp.');
            }

            return true;
        } catch (\Throwable $e) {
            error_log('Erreur WhatsApp 2FA: ' . $e->getMessage());
            return false;
        }
    }

    private function normalizeWhatsappNumber(string $phone): ?string {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) < 8) return null;
        if (!str_starts_with($phone, '216') && strlen($phone) == 8) {
            $phone = '216' . $phone;
        }
        return $phone;
    }

    private function maskPhoneNumber(string $phone): string {
        $len = strlen($phone);
        if ($len <= 4) return $phone;
        return str_repeat('*', $len - 4) . substr($phone, -4);
    }

    private function getEnvValue(string $key, string $default = ''): string {
        $value = getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return trim((string) $value);
    }
}
?>
