/* Prague Boats — Admin front-end */
(function () {
  'use strict';
  var PB = window.PB || {};

  /* ---- helpers ---- */
  function $(sel, root) { return (root || document).querySelector(sel); }
  function post(action, fields, files) {
    var fd = new FormData();
    fd.append('action', action);
    fd.append('csrf', PB.csrf);
    Object.keys(fields || {}).forEach(function (k) {
      var v = fields[k];
      if (Array.isArray(v)) { v.forEach(function (x) { fd.append(k + '[]', x); }); }
      else { fd.append(k, v); }
    });
    (files || []).forEach(function (f) { fd.append('photos[]', f, f.name); });
    return fetch('index.php', { method: 'POST', body: fd }).then(function (r) {
      return r.text().then(function (text) {
        try { return JSON.parse(text); }
        catch (e) {
          // Salvage JSON if a PHP warning/notice leaked in front of it.
          var i = text.indexOf('{');
          if (i > 0) { try { return JSON.parse(text.slice(i)); } catch (e2) {} }
          var snippet = (text || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 240);
          throw new Error('Server vrátil neočekávanou odpověď (HTTP ' + r.status + ').'
            + (snippet ? ' ' + snippet : ' Prázdná odpověď — zkontroluj limity uploadu a PHP rozšíření (curl, gd).'));
        }
      });
    });
  }
  function showMsg(el, text, ok) {
    el.textContent = text;
    el.className = 'msg show ' + (ok ? 'ok' : 'err');
  }
  function hideMsg(el) { el.className = 'msg'; }

  /* Downscale an image File in the browser to keep uploads small. Falls back
     to the original file if anything goes wrong. */
  function downscaleImage(file, maxEdge, quality) {
    return new Promise(function (resolve) {
      if (!file.type || file.type.indexOf('image/') !== 0 || !window.HTMLCanvasElement) {
        resolve(file); return;
      }
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        URL.revokeObjectURL(url);
        var w = img.naturalWidth || img.width, h = img.naturalHeight || img.height;
        if (!w || !h) { resolve(file); return; }
        var scale = Math.min(1, maxEdge / Math.max(w, h));
        var nw = Math.max(1, Math.round(w * scale)), nh = Math.max(1, Math.round(h * scale));
        try {
          var canvas = document.createElement('canvas');
          canvas.width = nw; canvas.height = nh;
          var ctx = canvas.getContext('2d');
          ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, nw, nh);
          ctx.drawImage(img, 0, 0, nw, nh);
          canvas.toBlob(function (blob) {
            if (!blob) { resolve(file); return; }
            var name = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
            try { resolve(new File([blob], name, { type: 'image/jpeg' })); }
            catch (e) { blob.name = name; resolve(blob); }
          }, 'image/jpeg', quality);
        } catch (e) { resolve(file); }
      };
      img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
      img.src = url;
    });
  }

  /* ---- logout (present on every authed page) ---- */
  var logoutBtn = $('#logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function () {
      post('logout', {}).then(function (j) { location.href = (j && j.redirect) || 'index.php?page=login'; });
    });
  }

  /* ================= COMPOSE ================= */
  var composeForm = $('#composeForm');
  if (composeForm) initCompose();

  function initCompose() {
    var boatSelect = $('#boatSelect');
    var dropboxLink = $('#dropboxLink');
    var subjectEl = $('#subject');
    var bodyEl = $('#body');
    var dropzone = $('#dropzone');
    var fileInput = $('#fileInput');
    var thumbsEl = $('#thumbs');
    var sendBtn = $('#sendBtn');
    var sendMsg = $('#sendMsg');

    var files = [];               // File[]
    var subjectDirty = false, bodyDirty = false;
    var settings = PB.settings || {};

    /* Build the boat/folder dropdown from parsed site data. */
    (PB.boats || []).forEach(function (boat) {
      var group = document.createElement('optgroup');
      group.label = boat.boat;
      boat.folders.forEach(function (folder) {
        folder.links.forEach(function (link, li) {
          var opt = document.createElement('option');
          var multi = folder.links.length > 1;
          opt.textContent = folder.title + (multi ? ' · ' + link.label : '');
          opt.value = boat.id + '|' + folder.anchor + '|' + li;
          opt.dataset.boat = boat.boat;
          opt.dataset.url = link.url;
          group.appendChild(opt);
        });
      });
      boatSelect.appendChild(group);
    });

    function currentOption() { return boatSelect.options[boatSelect.selectedIndex]; }

    function applyTemplate() {
      var opt = currentOption();
      if (!opt) return;
      var boat = opt.dataset.boat || '';
      var url = opt.dataset.url || '';
      function fill(t) {
        return (t || '')
          .replace(/\{boat\}/g, boat)
          .replace(/\{dropbox_link\}/g, url)
          .replace(/\{media_url\}/g, settings.media_url || '');
      }
      dropboxLink.value = url;
      if (!subjectDirty) subjectEl.value = fill(settings.subject_template);
      if (!bodyDirty) bodyEl.value = fill(settings.body_template);
    }

    boatSelect.addEventListener('change', applyTemplate);
    subjectEl.addEventListener('input', function () { subjectDirty = true; });
    bodyEl.addEventListener('input', function () { bodyDirty = true; });
    if (boatSelect.options.length) applyTemplate();

    /* Recipients checkboxes */
    var checks = $('#recipientChecks');
    var rec = PB.recipients || [];
    if (!rec.length) { $('#noRecipients').style.display = 'block'; }
    rec.forEach(function (r) {
      var label = document.createElement('label');
      var cb = document.createElement('input');
      cb.type = 'checkbox'; cb.value = r.email; cb.checked = !!r.active;
      var span = document.createElement('span');
      span.textContent = r.name ? (r.name + ' <' + r.email + '>') : r.email;
      label.appendChild(cb); label.appendChild(span);
      checks.appendChild(label);
    });

    /* Dropzone */
    function renderThumbs() {
      thumbsEl.innerHTML = '';
      files.forEach(function (f, idx) {
        var div = document.createElement('div');
        div.className = 'thumb';
        var img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        img.onload = function () { URL.revokeObjectURL(img.src); };
        var btn = document.createElement('button');
        btn.type = 'button'; btn.textContent = '×'; btn.title = 'Odebrat';
        btn.addEventListener('click', function () { files.splice(idx, 1); renderThumbs(); });
        div.appendChild(img); div.appendChild(btn);
        thumbsEl.appendChild(div);
      });
    }
    var processing = 0;
    function setBusy(on) {
      processing += on ? 1 : -1;
      dropzone.style.opacity = processing > 0 ? '.6' : '';
      sendBtn.disabled = processing > 0;
    }
    function addFiles(list) {
      var imgs = Array.prototype.filter.call(list, function (f) { return f.type && f.type.indexOf('image/') === 0; });
      if (!imgs.length) return;
      setBusy(true);
      Promise.all(imgs.map(function (f) { return downscaleImage(f, 1600, 0.82); }))
        .then(function (small) {
          small.forEach(function (f) { files.push(f); });
          renderThumbs();
        })
        .finally(function () { setBusy(false); });
    }
    dropzone.addEventListener('click', function () { fileInput.click(); });
    fileInput.addEventListener('change', function () { addFiles(fileInput.files); fileInput.value = ''; });
    ['dragenter', 'dragover'].forEach(function (ev) {
      dropzone.addEventListener(ev, function (e) { e.preventDefault(); dropzone.classList.add('drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      dropzone.addEventListener(ev, function (e) { e.preventDefault(); dropzone.classList.remove('drag'); });
    });
    dropzone.addEventListener('drop', function (e) {
      if (e.dataTransfer && e.dataTransfer.files) addFiles(e.dataTransfer.files);
    });

    /* Send */
    composeForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      hideMsg(sendMsg);
      var opt = currentOption();
      if (!opt) { showMsg(sendMsg, 'Vyber loď.', false); return; }
      var to = Array.prototype.slice.call(checks.querySelectorAll('input:checked')).map(function (c) { return c.value; });
      if (!to.length) { showMsg(sendMsg, 'Vyber aspoň jednoho příjemce.', false); return; }

      sendBtn.disabled = true;
      var orig = sendBtn.innerHTML;
      sendBtn.innerHTML = '<span class="spinner"></span> Odesílám…';

      post('send', {
        boat: opt.dataset.boat || '',
        folder: opt.value,
        dropbox_link: dropboxLink.value,
        subject: subjectEl.value,
        body: bodyEl.value,
        recipients: to
      }, files).then(function (j) {
        sendBtn.disabled = false; sendBtn.innerHTML = orig;
        if (j.ok) {
          var extra = (j.skipped && j.skipped.length) ? ' (přeskočeno: ' + j.skipped.join(', ') + ')' : '';
          showMsg(sendMsg, (j.message || 'Odesláno.') + extra, true);
          files = []; renderThumbs();
        } else {
          showMsg(sendMsg, j.error || 'Odeslání selhalo.', false);
        }
      }).catch(function (err) {
        sendBtn.disabled = false; sendBtn.innerHTML = orig;
        showMsg(sendMsg, (err && err.message) ? err.message : 'Síťová chyba při odesílání.', false);
      });
    });
  }

  /* ================= RECIPIENTS ================= */
  var addRecipientForm = $('#addRecipientForm');
  if (addRecipientForm) initRecipients();

  function initRecipients() {
    var list = $('#recList');
    var empty = $('#recEmpty');
    var addMsg = $('#addMsg');

    function render(recs) {
      list.innerHTML = '';
      if (!recs.length) { empty.style.display = 'block'; return; }
      empty.style.display = 'none';
      recs.forEach(function (r) {
        var li = document.createElement('li');
        li.className = 'rec-item';

        var meta = document.createElement('div');
        meta.className = 'rec-meta';
        meta.innerHTML = '<div class="em"></div><div class="nm"></div>';
        meta.querySelector('.em').textContent = r.email;
        meta.querySelector('.nm').textContent = r.name || '';

        var pill = document.createElement('span');
        pill.className = 'pill ' + (r.active ? 'on' : 'off');
        pill.textContent = r.active ? 'aktivní' : 'vypnuto';

        var toggle = document.createElement('button');
        toggle.className = 'btn secondary';
        toggle.style.cssText = 'padding:7px 12px;font-size:13px;flex:0 0 auto;';
        toggle.textContent = r.active ? 'Vypnout' : 'Zapnout';
        toggle.addEventListener('click', function () {
          post('toggle_recipient', { email: r.email }).then(function (j) { if (j.ok) render(j.recipients); });
        });

        var del = document.createElement('button');
        del.className = 'btn danger';
        del.style.cssText = 'flex:0 0 auto;';
        del.textContent = 'Smazat';
        del.addEventListener('click', function () {
          if (!confirm('Smazat ' + r.email + '?')) return;
          post('delete_recipient', { email: r.email }).then(function (j) { if (j.ok) render(j.recipients); });
        });

        li.appendChild(meta);
        li.appendChild(pill);
        li.appendChild(toggle);
        li.appendChild(del);
        list.appendChild(li);
      });
    }

    render(PB.recipients || []);

    addRecipientForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      hideMsg(addMsg);
      var fd = new FormData(addRecipientForm);
      post('add_recipient', { email: fd.get('email'), name: fd.get('name') }).then(function (j) {
        if (j.ok) { addRecipientForm.reset(); render(j.recipients); }
        else { showMsg(addMsg, j.error || 'Chyba.', false); }
      }).catch(function (err) { showMsg(addMsg, (err && err.message) || 'Chyba.', false); });
    });
  }

  /* ================= SETTINGS ================= */
  var settingsForm = $('#settingsForm');
  if (settingsForm) {
    settingsForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var msg = $('#settingsMsg');
      var btn = $('#saveBtn');
      hideMsg(msg);
      var fd = new FormData(settingsForm);
      var fields = {};
      fd.forEach(function (v, k) { fields[k] = v; });
      btn.disabled = true;
      post('save_settings', fields).then(function (j) {
        btn.disabled = false;
        showMsg(msg, j.ok ? 'Nastavení uloženo.' : (j.error || 'Chyba.'), !!j.ok);
      }).catch(function (err) {
        btn.disabled = false;
        showMsg(msg, (err && err.message) || 'Chyba.', false);
      });
    });
  }
})();
