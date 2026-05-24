<?php /** Manage the notification recipient list. */ ?>
<h1 class="page-title">Příjemci</h1>
<p class="page-sub">Komu se notifikace posílají. Zaškrtnutí určuje, kdo je předvybraný při odeslání.</p>

<div class="card">
  <h2>Přidat příjemce</h2>
  <form id="addRecipientForm" class="row" style="align-items:flex-end;">
    <label class="field" style="margin-bottom:0;">
      <span>E-mail</span>
      <input type="email" name="email" required placeholder="jmeno@example.com">
    </label>
    <label class="field" style="margin-bottom:0;">
      <span>Jméno <span class="hint">(nepovinné)</span></span>
      <input type="text" name="name" placeholder="Jan Novák">
    </label>
    <button class="btn" type="submit" style="flex:0 0 auto;">Přidat</button>
  </form>
  <div class="msg err" id="addMsg"></div>
</div>

<div class="card">
  <h2>Seznam</h2>
  <ul class="rec-list" id="recList"></ul>
  <p class="hint" id="recEmpty" style="display:none;">Zatím nikdo. Přidej prvního příjemce výše.</p>
</div>
