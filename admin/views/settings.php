<?php /** Resend + template settings. */ ?>
<h1 class="page-title">Nastavení</h1>
<p class="page-sub">Odesílání přes Resend a výchozí šablona e-mailu.</p>

<form id="settingsForm">
  <div class="card">
    <h2>Resend</h2>
    <label class="field">
      <span>Resend API klíč</span>
      <input type="password" name="resend_api_key" placeholder="re_..." value="<?= e($settings['resend_api_key']) ?>" autocomplete="off">
    </label>
    <div class="row">
      <label class="field">
        <span>Odesílací adresa <span class="hint">(z ověřené domény)</span></span>
        <input type="email" name="from_email" placeholder="media@tvoje-domena.cz" value="<?= e($settings['from_email']) ?>">
      </label>
      <label class="field">
        <span>Jméno odesílatele</span>
        <input type="text" name="from_name" value="<?= e($settings['from_name']) ?>">
      </label>
    </div>
    <label class="field">
      <span>Reply-To <span class="hint">(nepovinné)</span></span>
      <input type="email" name="reply_to" value="<?= e($settings['reply_to']) ?>">
    </label>
    <label class="field">
      <span>Skrytá kopie (BCC) <span class="hint">(na každý odeslaný mail; více adres odděl čárkou)</span></span>
      <input type="text" name="bcc" value="<?= e($settings['bcc']) ?>" placeholder="mia@miladvorak.com">
    </label>
  </div>

  <div class="card">
    <h2>Odkazy</h2>
    <label class="field">
      <span>Obecný odkaz na Media Hub <span class="hint">(do patičky šablony – {media_url})</span></span>
      <input type="url" name="media_url" placeholder="https://media.pragueboats.cz" value="<?= e($settings['media_url']) ?>">
    </label>
  </div>

  <div class="card">
    <h2>Výchozí šablona</h2>
    <p class="hint" style="margin-top:-8px;margin-bottom:14px;">
      Zástupné značky: <code>{boat}</code> = loď/složka, <code>{dropbox_link}</code> = konkrétní Dropbox, <code>{media_url}</code> = obecný odkaz.
    </p>
    <label class="field">
      <span>Předmět</span>
      <input type="text" name="subject_template" value="<?= e($settings['subject_template']) ?>">
    </label>
    <label class="field">
      <span>Text</span>
      <textarea name="body_template" rows="9"><?= e($settings['body_template']) ?></textarea>
    </label>
  </div>

  <button class="btn" id="saveBtn" type="submit">Uložit nastavení</button>
  <div class="msg" id="settingsMsg"></div>
</form>
