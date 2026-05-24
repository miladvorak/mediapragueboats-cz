<?php
/**
 * Handle the "send notification" action: resize dropped photos, build the
 * HTML email (inline previews + attachments) and dispatch it through Resend.
 */

declare(strict_types=1);

require_once __DIR__ . '/images.php';
require_once __DIR__ . '/resend.php';

function pb_handle_send(): void
{
    $settings = pb_settings();

    $boat        = trim((string) ($_POST['boat'] ?? ''));
    $dropboxLink = trim((string) ($_POST['dropbox_link'] ?? ''));
    $subject     = trim((string) ($_POST['subject'] ?? ''));
    $bodyText    = (string) ($_POST['body'] ?? '');
    $toEmails    = $_POST['recipients'] ?? [];

    if (!is_array($toEmails)) {
        $toEmails = [];
    }
    $toEmails = array_values(array_filter(array_map('trim', $toEmails), static fn($x) => filter_var($x, FILTER_VALIDATE_EMAIL)));

    if ($boat === '')        json_out(['ok' => false, 'error' => 'Vyberte loď.'], 400);
    if ($subject === '')     json_out(['ok' => false, 'error' => 'Doplňte předmět.'], 400);
    if (empty($toEmails))    json_out(['ok' => false, 'error' => 'Vyberte aspoň jednoho příjemce.'], 400);
    if (empty($settings['from_email'])) json_out(['ok' => false, 'error' => 'V Nastavení chybí odesílací adresa.'], 400);

    // ---- Resize uploaded photos -------------------------------------------
    $attachments = [];   // for Resend
    $inlineCids  = [];   // cid => filename, for HTML preview
    $skipped     = [];

    if (!empty($_FILES['photos']) && is_array($_FILES['photos']['tmp_name'])) {
        if (!pb_gd_available()) {
            json_out(['ok' => false, 'error' => 'Na serveru chybí PHP rozšíření GD pro zmenšení fotek.'], 500);
        }
        $count = count($_FILES['photos']['tmp_name']);
        for ($i = 0; $i < $count; $i++) {
            if (($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp  = $_FILES['photos']['tmp_name'][$i];
            $orig = $_FILES['photos']['name'][$i] ?? ('photo' . $i . '.jpg');
            if (!is_uploaded_file($tmp)) {
                continue;
            }
            $jpeg = pb_resize_to_jpeg($tmp, 1280, 80);
            if ($jpeg === null) {
                $skipped[] = $orig;
                continue;
            }
            $base = preg_replace('/\.[^.]+$/', '', basename($orig));
            $base = preg_replace('/[^A-Za-z0-9_-]+/', '-', $base ?: ('photo' . $i));
            $filename = $base . '.jpg';
            $cid = 'photo' . $i . '@pragueboats';

            $attachments[] = [
                'filename'     => $filename,
                'content'      => base64_encode($jpeg),
                'content_type' => 'image/jpeg',
                'content_id'   => $cid,
            ];
            $inlineCids[$cid] = $filename;
        }
    }

    // ---- Build HTML body ---------------------------------------------------
    $html = pb_build_email_html($bodyText, $inlineCids, $boat);

    $fromName = $settings['from_name'] !== '' ? $settings['from_name'] : 'Prague Boats Media';
    $from = sprintf('%s <%s>', $fromName, $settings['from_email']);

    // Blind copy (comma-separated, validated).
    $bcc = array_values(array_filter(
        array_map('trim', explode(',', (string) ($settings['bcc'] ?? ''))),
        static fn($x) => filter_var($x, FILTER_VALIDATE_EMAIL)
    ));

    $result = pb_resend_send([
        'api_key'     => $settings['resend_api_key'],
        'from'        => $from,
        'to'          => $toEmails,
        'bcc'         => $bcc,
        'subject'     => $subject,
        'html'        => $html,
        'reply_to'    => $settings['reply_to'] ?: '',
        'attachments' => $attachments,
    ]);

    if (!$result['ok']) {
        json_out(['ok' => false, 'error' => $result['error'] ?? 'Odeslání selhalo.'], 502);
    }

    json_out([
        'ok'        => true,
        'id'        => $result['id'] ?? null,
        'sent_to'   => count($toEmails),
        'photos'    => count($attachments),
        'skipped'   => $skipped,
        'message'   => 'Odesláno ' . count($toEmails) . ' příjemcům (' . count($attachments) . ' fotek).',
    ]);
}

/** Turn the plain-text body into a simple, email-client-safe HTML document. */
function pb_build_email_html(string $bodyText, array $inlineCids, string $boat = ''): string
{
    $boatEsc = htmlspecialchars(trim($boat), ENT_QUOTES, 'UTF-8');
    $boldBoat = static function (string $html) use ($boatEsc): string {
        return $boatEsc !== '' ? str_replace($boatEsc, '<strong>' . $boatEsc . '</strong>', $html) : $html;
    };

    // Render line by line so "Popisek: https://…" lines become a bold label
    // with a tidy button instead of an ugly wrapping URL.
    $lines = preg_split('/\r\n|\r|\n/', $bodyText) ?: [];
    $parts = [];
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '') {
            $parts[] = '<div style="height:12px;line-height:12px;font-size:0;">&nbsp;</div>';
            continue;
        }

        if (preg_match('~^(.*\S)\s*:\s*(https?://\S+)$~u', $trim, $m)) {
            $label   = $boldBoat(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'));
            $urlAttr = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
            $isDropbox = stripos($m[2], 'dropbox.') !== false;
            $btnLabel = $isDropbox ? '📁&nbsp;&nbsp;Otevřít složku' : '↗&nbsp;&nbsp;Otevřít odkaz';
            $parts[] =
                '<div style="margin:16px 0;">'
                . '<div style="font-weight:700;color:#1f2733;font-size:14px;margin-bottom:8px;">' . $label . '</div>'
                . '<a href="' . $urlAttr . '" style="display:inline-block;background:#1a5fb4;color:#ffffff;'
                . 'text-decoration:none;padding:11px 20px;border-radius:9px;font-weight:600;font-size:14px;'
                . 'font-family:Arial,Helvetica,sans-serif;">' . $btnLabel . '</a>'
                . '</div>';
            continue;
        }

        // Plain text line: escape, linkify stray URLs, bold the boat name.
        $esc = htmlspecialchars($trim, ENT_QUOTES, 'UTF-8');
        $esc = preg_replace_callback(
            '~(https?://[^\s<]+)~u',
            static fn($x) => '<a href="' . $x[1] . '" style="color:#1a5fb4;">' . $x[1] . '</a>',
            $esc
        );
        $parts[] = '<div style="margin:0 0 4px;">' . $boldBoat($esc) . '</div>';
    }

    $bodyHtml = implode("\n", $parts);

    $images = '';
    foreach ($inlineCids as $cid => $filename) {
        $images .= '<img src="cid:' . htmlspecialchars($cid, ENT_QUOTES) . '" alt="' . htmlspecialchars($filename, ENT_QUOTES) . '" '
            . 'style="display:block;width:100%;max-width:520px;height:auto;border-radius:8px;margin:0 0 12px;" />';
    }

    return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f4f5f7;">'
        . '<div style="max-width:600px;margin:0 auto;padding:24px;font-family:Arial,Helvetica,sans-serif;color:#222;font-size:15px;line-height:1.6;">'
        . '<div style="background:#fff;border-radius:12px;padding:28px;">'
        . '<div style="margin-bottom:20px;">' . $bodyHtml . '</div>'
        . ($images !== '' ? '<div style="margin-top:8px;">' . $images . '</div>' : '')
        . '</div>'
        . '<p style="text-align:center;color:#9aa0a6;font-size:12px;line-height:1.6;margin:18px auto 4px;max-width:460px;">'
        . 'Tenhle e-mail se posílá automaticky, není třeba odpovídat. 🙂</p>'
        . '<p style="text-align:center;color:#c2c7cd;font-size:11px;margin:4px 0 0;">Prague Boats — Media Hub</p>'
        . '</div></body></html>';
}
