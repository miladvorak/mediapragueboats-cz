<?php
/**
 * Minimal Resend API client (https://resend.com/docs).
 */

declare(strict_types=1);

/**
 * Send an email through Resend.
 *
 * @param array{
 *   api_key:string, from:string, to:array<int,string>, subject:string,
 *   html:string, reply_to?:string,
 *   attachments?:array<int,array{filename:string,content:string,content_type?:string,content_id?:string}>
 * } $msg
 * @return array{ok:bool, id?:string, error?:string, status?:int}
 */
function pb_resend_send(array $msg): array
{
    if (empty($msg['api_key'])) {
        return ['ok' => false, 'error' => 'Chybí Resend API klíč (Nastavení).'];
    }
    if (empty($msg['from'])) {
        return ['ok' => false, 'error' => 'Chybí odesílací adresa (Nastavení).'];
    }
    if (empty($msg['to'])) {
        return ['ok' => false, 'error' => 'Nebyl vybrán žádný příjemce.'];
    }

    $payload = [
        'from'    => $msg['from'],
        'to'      => array_values($msg['to']),
        'subject' => $msg['subject'] ?? '',
        'html'    => $msg['html'] ?? '',
    ];
    if (!empty($msg['reply_to'])) {
        $payload['reply_to'] = $msg['reply_to'];
    }
    if (!empty($msg['bcc'])) {
        $payload['bcc'] = $msg['bcc'];
    }
    if (!empty($msg['attachments'])) {
        $payload['attachments'] = $msg['attachments'];
    }

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $msg['api_key'],
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    $resp   = curl_exec($ch);
    $errno  = curl_errno($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    if (PHP_VERSION_ID < 80000) {
        curl_close($ch);
    }

    if ($errno !== 0) {
        return ['ok' => false, 'error' => 'Spojení s Resend selhalo: ' . $curlErr];
    }

    $body = json_decode((string) $resp, true);
    if ($status >= 200 && $status < 300 && !empty($body['id'])) {
        return ['ok' => true, 'id' => $body['id'], 'status' => $status];
    }

    $apiMsg = $body['message'] ?? ($body['error']['message'] ?? ('HTTP ' . $status));
    return ['ok' => false, 'error' => 'Resend: ' . $apiMsg, 'status' => $status];
}
