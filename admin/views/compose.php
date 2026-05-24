<?php /** Compose & send a photo notification. */ ?>
<h1 class="page-title">Nová notifikace</h1>
<p class="page-sub">Vyber loď, přetáhni ukázkové fotky, zkontroluj text a odešli.</p>

<?php if (empty($settings['resend_api_key'])): ?>
  <div class="msg err show" style="margin-bottom:20px;">
    Není nastavený Resend API klíč. Doplň ho v <a href="index.php?page=settings">Nastavení</a>, jinak nepůjde odeslat.
  </div>
<?php endif; ?>

<form id="composeForm">
  <div class="card">
    <h2>1 · Loď a složka</h2>
    <label class="field">
      <span>Loď / složka</span>
      <select id="boatSelect" name="folder"></select>
    </label>
    <label class="field">
      <span>Odkaz na konkrétní Dropbox <span class="hint">(doplní se podle výběru, lze upravit)</span></span>
      <input type="url" id="dropboxLink" name="dropbox_link" placeholder="https://www.dropbox.com/...">
    </label>
  </div>

  <div class="card">
    <h2>2 · Ukázkové fotky</h2>
    <div class="dropzone" id="dropzone">
      <p style="margin:0;"><strong>Přetáhni fotky sem</strong> nebo klikni pro výběr</p>
      <p style="margin:6px 0 0;font-size:13px;">Systém je sám zmenší (max. 1280 px) a přiloží do mailu.</p>
      <input type="file" id="fileInput" accept="image/*" multiple hidden>
    </div>
    <div class="thumbs" id="thumbs"></div>
  </div>

  <div class="card">
    <h2>3 · Text e-mailu</h2>
    <label class="field">
      <span>Předmět</span>
      <input type="text" id="subject" name="subject">
    </label>
    <label class="field">
      <span>Text <span class="hint">(odkazy se v mailu samy zaktivní)</span></span>
      <textarea id="body" name="body" rows="9"></textarea>
    </label>
  </div>

  <div class="card">
    <h2>4 · Příjemci</h2>
    <div class="checks" id="recipientChecks"></div>
    <p class="hint" id="noRecipients" style="display:none;">
      Zatím nemáš žádné příjemce. Přidej je v <a href="index.php?page=recipients">Příjemci</a>.
    </p>
  </div>

  <button class="btn" id="sendBtn" type="submit">Odeslat notifikaci</button>
  <div class="msg" id="sendMsg"></div>
</form>
