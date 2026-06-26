<?php
/**
 * Resend (https://resend.com) webhook receiver.
 *
 * Resend delivers signed webhooks (Svix format) for email events. We act on bounces
 * and spam complaints: the recipient's `email_bounced` user property is set so we stop
 * emailing that address (in particular, the storage/inactivity warning crons refuse to
 * send to -- and therefore never delete based on -- a bounced address).
 *
 * Configure the webhook in the Resend dashboard to POST to:  /mail/resendWebhook
 * and set the signing secret in configsSite/config.override.inc.php:
 *   $CONFIG['Mail']['Resend']['AuxConfig']['webhookSecret'] = 'whsec_...';
 */

$payload = file_get_contents('php://input');
$secret = ConfigManager::getConfig('Mail', 'Resend')->AuxConfig->webhookSecret;

// Fail closed: without a configured secret or a valid signature we reject the request.
if (empty($secret) || !verifyResendWebhookSignature($payload, $secret)) {
    http_response_code(401);
    echo "Invalid signature";
    exit;
}

$event = json_decode($payload, true);
$type = isset($event['type']) ? $event['type'] : null;

// Treat hard bounces and spam complaints as "do not email this address anymore".
if (in_array($type, ['email.bounced', 'email.complained'], true) && !empty($event['data'])) {
    $recipients = isset($event['data']['to']) ? $event['data']['to'] : [];
    if (is_string($recipients)) {
        $recipients = [$recipients];
    }

    foreach ($recipients as $email) {
        $email = trim($email);
        if ($email === '') {
            continue;
        }

        $filter = new UsersFilter();
        $filter->setEmail(Reg::get('sql')->escapeString($email));
        $users = Reg::get('userMgr')->getUsersList($filter);

        foreach ($users as $user) {
            if (empty($user->props->emailBounced)) {
                $user->props->emailBounced = 1;
                Reg::get('userMgr')->updateUser($user);
                logInDbAndKeybase("bounce", null,
                    "Resend reported {$type} for {$user->login}; marked email as bounced");
            }
        }
    }
}

http_response_code(200);
echo "ok";
exit;


/**
 * Verify a Svix-format webhook signature (the scheme Resend uses).
 *
 * Signed content is "<svix-id>.<svix-timestamp>.<raw body>", HMAC-SHA256'd with the
 * secret bytes (the part after "whsec_", base64-decoded). The svix-signature header is
 * a space-separated list of "v1,<base64 signature>" entries; any match passes.
 */
function verifyResendWebhookSignature($payload, $secret) {
    $svixId = isset($_SERVER['HTTP_SVIX_ID']) ? $_SERVER['HTTP_SVIX_ID'] : null;
    $svixTimestamp = isset($_SERVER['HTTP_SVIX_TIMESTAMP']) ? $_SERVER['HTTP_SVIX_TIMESTAMP'] : null;
    $svixSignature = isset($_SERVER['HTTP_SVIX_SIGNATURE']) ? $_SERVER['HTTP_SVIX_SIGNATURE'] : null;

    if (empty($svixId) || empty($svixTimestamp) || empty($svixSignature)) {
        return false;
    }

    $secretPart = (strpos($secret, '_') !== false)
        ? substr($secret, strpos($secret, '_') + 1)
        : $secret;
    $secretBytes = base64_decode($secretPart, true);
    if ($secretBytes === false) {
        return false;
    }

    $signedContent = $svixId . '.' . $svixTimestamp . '.' . $payload;
    $expected = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

    foreach (explode(' ', $svixSignature) as $versionedSignature) {
        $parts = explode(',', $versionedSignature, 2);
        if (count($parts) === 2 && hash_equals($expected, $parts[1])) {
            return true;
        }
    }

    return false;
}
