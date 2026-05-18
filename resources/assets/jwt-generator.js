(function () {
  'use strict';

  /* BEGIN: CONFIG (auto-updated by openapi:inject-jwt-tools from config/openapi.php) */
  var CONFIG = {
    jwtTools: {
      globalApiKeyWidget:      true,
      jwtTokenGeneratorWidget: true,
      jwtValidatorWidget:      true,
    },
    jwtDefaultPayload: {},
  };
  /* END: CONFIG */

  var anyJwtTool = CONFIG.jwtTools.globalApiKeyWidget ||
    CONFIG.jwtTools.jwtTokenGeneratorWidget ||
    CONFIG.jwtTools.jwtValidatorWidget;
  if (!anyJwtTool) return;

  /* ════════════════════════════════════════════════════════
   * §1  CSS — inject canonical st-widget stylesheet
   * ════════════════════════════════════════════════════════ */
  (function injectCss() {
    if (document.getElementById('st-widget-css')) return;
    var s = document.createElement('style');
    s.id = 'st-widget-css';
    s.textContent = [
      '.st-widget{--st-bg:var(--scalar-background-2,#1c1c2e);--st-bg-input:var(--scalar-background-1,#0d0d1a);--st-border:var(--scalar-border-color,rgba(100,100,180,.25));--st-text:var(--scalar-color-1,#e2e8f0);--st-muted:var(--scalar-color-3,#9ca3af);--st-accent:var(--scalar-color-accent,#6366f1);--st-green:var(--scalar-color-green,#10b981);--st-red:var(--scalar-color-red,#f87171);--st-orange:var(--scalar-color-orange,#fbbf24);--st-font-code:var(--scalar-font-code,"JetBrains Mono","Fira Code","SF Mono",monospace);background:var(--st-bg);border:1px solid var(--st-border);border-top-width:2px;border-radius:10px;padding:24px;margin:16px 0;font-family:var(--st-font-code)}',
      '.st-widget--api-key{background:transparent;border-color:transparent}',
      '.st-widget--api-key input{border-color:transparent;background:transparent;box-shadow:none}',
      '.st-widget--api-key input:focus{border-color:transparent;box-shadow:none}',
      '.st-widget--jwt-gen{border-top-color:var(--st-accent)}',
      '.st-widget--jwt-val{border-top-color:#e879a6}',
      '.st-widget .widget-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}',
      '.st-widget h3{margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1.8px;font-weight:700;display:flex;align-items:center;gap:7px}',
      '.st-widget>.h3{margin-bottom:18px}',
      '.st-widget--api-key h3{color:var(--st-green)}',
      '.st-widget--jwt-gen h3{color:var(--st-accent)}',
      '.st-widget--jwt-val h3{color:#e879a6}',
      '.st-widget .btn-mode-toggle{background:none;border:1px solid var(--st-border);color:var(--st-muted);font-size:10px;font-family:var(--st-font-code);font-weight:600;cursor:pointer;padding:3px 10px;border-radius:4px;transition:border-color .15s,color .15s,background .15s}',
      '.st-widget .btn-mode-toggle:hover{border-color:var(--st-accent);color:var(--st-accent)}',
      '.st-widget .btn-mode-toggle.active{background:var(--st-accent);border-color:var(--st-accent);color:#1c1c2e}',
      '.st-widget .label-row{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px}',
      '.st-widget .label-row label{margin-bottom:0}',
      '.st-widget .btn-restore{display:none;background:none;border:1px solid var(--st-orange);color:var(--st-orange);font-size:10px;font-family:var(--st-font-code);font-weight:600;cursor:pointer;padding:1px 7px;border-radius:4px;opacity:.75;transition:opacity .15s;white-space:nowrap;flex-shrink:0}',
      '.st-widget .btn-restore:hover{opacity:1}',
      '.st-widget .btn-apply{display:none;background:none;border:1px solid var(--st-accent);color:var(--st-accent);font-size:10px;font-family:var(--st-font-code);font-weight:600;cursor:pointer;padding:1px 7px;border-radius:4px;opacity:.75;transition:opacity .15s,background .15s,color .15s;white-space:nowrap;flex-shrink:0}',
      '.st-widget .btn-apply:hover{background:var(--st-accent);color:#1c1c2e;opacity:1}',
      '.st-widget .btn-copy-val{position:absolute;right:36px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--st-muted);cursor:pointer;font-size:13px;padding:2px 3px;line-height:1;transition:color .15s}',
      '.st-widget .btn-copy-val:hover{color:var(--st-text)}',
      '.st-widget label{color:var(--st-muted);font-size:11px;display:block;margin-bottom:6px;font-weight:500}',
      '.st-widget label .hint{color:var(--scalar-color-3,#6b7280);font-weight:400;opacity:.8}',
      '.st-widget select,.st-widget input,.st-widget textarea{width:100%;box-sizing:border-box;background:var(--st-bg-input);border:1px solid var(--st-border);border-radius:6px;padding:10px 12px;color:var(--st-text);font-size:13px;outline:none;font-family:var(--st-font-code);transition:border-color .15s,box-shadow .15s}',
      '.st-widget select{padding:10px 30px 10px 12px;cursor:pointer;appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 10 6\'%3E%3Cpath d=\'M0 0l5 6 5-6z\' fill=\'%239ca3af\'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;background-size:10px 6px}',
      '.st-widget select:focus,.st-widget input:focus,.st-widget textarea:focus{border-color:var(--st-accent);box-shadow:0 0 0 3px rgba(99,102,241,.15)}',
      '.st-widget textarea{resize:none;font-size:11.5px;word-break:break-all;line-height:1.6}',
      '.st-widget textarea.json-editor{resize:vertical;word-break:normal;white-space:pre;min-height:100px}',
      '.st-widget textarea[readonly]{cursor:pointer;opacity:.9}',
      '.st-widget textarea.decoded-header{color:var(--st-accent)}',
      '.st-widget textarea.decoded-payload{color:#e879a6}',
      '.st-widget textarea.token-output{color:var(--st-green)}',
      '.st-widget .field-group{display:grid;gap:14px;margin-bottom:16px}',
      '.st-widget .field-row-2{display:grid;grid-template-columns:1fr 1fr;gap:10px}',
      '.st-widget .field-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}',
      '@media(max-width:640px){.st-widget .field-row-2,.st-widget .field-row-3{grid-template-columns:1fr}}',
      '.st-widget .btn-primary{background:var(--st-accent);color:#1c1c2e;border:none;border-radius:6px;padding:11px 20px;font-size:13px;cursor:pointer;font-family:var(--st-font-code);font-weight:700;width:100%;letter-spacing:.5px;transition:opacity .15s,transform .1s}',
      '.st-widget .btn-primary:hover{opacity:.85}.st-widget .btn-primary:active{transform:scale(.98)}.st-widget .btn-primary:disabled{opacity:.45;cursor:not-allowed}',
      '.st-widget .btn-secondary{background:none;border:1px solid var(--st-border);color:var(--st-muted);border-radius:6px;padding:10px 14px;font-size:12px;cursor:pointer;font-family:var(--st-font-code);font-weight:600;transition:border-color .15s,color .15s}',
      '.st-widget .btn-secondary:hover{border-color:var(--st-accent);color:var(--st-accent)}',
      '.st-widget .input-wrapper{position:relative}',
      '.st-widget .toggle-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--st-muted);cursor:pointer;font-size:14px;padding:0;line-height:1;transition:color .15s}',
      '.st-widget .toggle-eye:hover{color:var(--st-text)}',
      '.st-widget .error-box{display:none;margin-top:12px;padding:12px 14px;background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.35);border-radius:6px;color:var(--st-red);font-size:12px}',
      '.st-widget .result-box{display:none;margin-top:16px;position:relative}',
      '.st-widget .copy-hint{position:absolute;bottom:8px;right:10px;color:var(--st-muted);font-size:10px;pointer-events:none}',
      '.st-widget .success-hint{color:var(--st-green);font-size:11px;margin:8px 0 0;display:none}',
      '.st-widget .action-row{display:flex;gap:10px;margin-top:12px}',
      '.st-widget .footer-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:4px}',
      '.st-widget .footer-row select,.st-widget .footer-row input[type=number]{width:auto;flex:0 0 auto;padding:8px 10px;font-size:12px}',
      '.st-widget .footer-row .btn-primary{width:auto;flex:1}',
      '.st-widget .status-bar{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;background:var(--st-bg-input);border:1px solid var(--st-border);border-radius:6px;margin-top:12px}',
      '.st-widget .status-bar .status-label{color:var(--st-muted);font-size:12px}',
      '.st-widget .status-bar .status-value{margin-left:8px;font-size:12px;font-weight:700;color:var(--st-muted)}',
      '.st-widget .status-bar .expire-info{font-size:12px;color:var(--st-muted);display:none}',
      '.st-widget .decoded-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px}',
      '@media(max-width:640px){.st-widget .decoded-grid{grid-template-columns:1fr}}',
      '.st-timer{margin-left:12px;font-size:11px;padding:2px 8px;border-radius:10px;background:var(--scalar-background-2,#1e1e2e);border:1px solid var(--scalar-border-color,#3b3b5e);font-family:var(--scalar-font-code,monospace)}',
      '#st-docs-widgets{max-width:100%;margin:24px 0 8px}',
      '.st-widget .payload-preview{margin-top:8px;border:1px solid var(--st-border);border-radius:6px;overflow:hidden}',
      '.st-widget .payload-preview table{width:100%;border-collapse:collapse;font-size:12px;font-family:var(--st-font-code)}',
      '.st-widget .payload-preview td{padding:7px 12px;border-bottom:1px solid var(--st-border)}',
      '.st-widget .payload-preview tr:last-child td{border-bottom:none}',
      '.st-widget .payload-preview td:first-child{color:var(--st-accent);font-weight:600;width:40%;white-space:nowrap}',
      '.st-widget .payload-preview td:last-child{color:var(--st-text);word-break:break-all}',
      '.st-widget .payload-preview .preview-hint{padding:10px 12px;color:var(--st-red);font-size:11px}',
    ].join('');
    document.head.appendChild(s);
  })();

  /* ════════════════════════════════════════════════════════
   * §2  SHARED HELPERS
   * ════════════════════════════════════════════════════════ */
  function setupPasswordToggle(inputId, btnId) {
    var btn = document.getElementById(btnId);
    var inp = document.getElementById(inputId);
    if (!btn || !inp) return;
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      inp.type = inp.type === 'password' ? 'text' : 'password';
      btn.innerHTML = inp.type === 'password' ? '👁️' : '🙈';
    });
  }

  function setupCopyButton(btnId, inputId) {
    var btn = document.getElementById(btnId);
    var inp = document.getElementById(inputId);
    if (!btn || !inp) return;
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (!inp.value) return;
      navigator.clipboard.writeText(inp.value).then(function () {
        btn.textContent = '✓';
        btn.style.color = 'var(--st-green,#10b981)';
        setTimeout(function () { btn.textContent = '📋'; btn.style.color = ''; }, 1500);
      });
    });
  }


  function injectScalarAuth(value) {
    function fillInput(inp) {
      var setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
      setter.call(inp, value);
      inp.dispatchEvent(new Event('input', { bubbles: true }));
      inp.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function findInputNear(el) {
      var cur = el;
      for (var i = 0; i < 8 && cur; i++) {
        var inp = cur.querySelector('.scalar-password-input');
        if (inp) return inp;
        cur = cur.parentElement;
      }
      return null;
    }

    var tabs = document.querySelectorAll('[data-testid="auth-tabs"]');
    if (tabs.length === 0) {
      document.querySelectorAll('.scalar-password-input').forEach(fillInput);
      return;
    }

    tabs.forEach(function (container) {
      var buttons = Array.from(container.querySelectorAll('button'));
      // Try to find bearer tab by text, fall back to last button
      var bearerBtn = buttons.find(function (btn) {
        var t = btn.textContent.trim().toLowerCase();
        return t.indexOf('bearer') !== -1 || t === 'jwt_bearer_token';
      }) || buttons[buttons.length - 1];

      if (!bearerBtn) return;
      bearerBtn.click();

      setTimeout(function () {
        var inp = findInputNear(container);
        if (inp) fillInput(inp);
      }, 80);
    });
  }

  async function signHs256(secret, payloadObj) {
    var enc = new TextEncoder();
    var toB64url = function (buf) {
      return btoa(String.fromCharCode.apply(null, new Uint8Array(buf)))
        .replace(/[+]/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    };
    var safeB64url = function (str) {
      return toB64url(enc.encode(str));
    };
    var header = safeB64url(JSON.stringify({ alg: 'HS256', typ: 'JWT' }));
    var payload = safeB64url(JSON.stringify(payloadObj));
    var key = await crypto.subtle.importKey('raw', enc.encode(secret), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
    var sig = await crypto.subtle.sign('HMAC', key, enc.encode(header + '.' + payload));
    return header + '.' + payload + '.' + toB64url(sig);
  }

  async function verifyHs256(secret, token) {
    var parts = token.split('.');
    if (parts.length !== 3) return false;
    var enc = new TextEncoder();
    var pad = function (s) { var m = s.length % 4; return m ? s + '='.repeat(4 - m) : s; };
    var key = await crypto.subtle.importKey('raw', enc.encode(secret), { name: 'HMAC', hash: 'SHA-256' }, false, ['verify']);
    var sigBuf = Uint8Array.from(atob(pad(parts[2].replace(/-/g, '+').replace(/_/g, '/'))), function (c) { return c.charCodeAt(0); });
    return crypto.subtle.verify('HMAC', key, sigBuf, enc.encode(parts[0] + '.' + parts[1]));
  }

  function decodeB64Json(str) {
    var m = str.length % 4;
    if (m !== 0) str += '='.repeat(4 - m);
    return JSON.parse(decodeURIComponent(escape(atob(str.replace(/-/g, '+').replace(/_/g, '/')))));
  }

  /* ════════════════════════════════════════════════════════
   * §3  WIDGET: GLOBAL API KEY
   * ════════════════════════════════════════════════════════ */
  function mountApiKey() {
    if (!CONFIG.jwtTools.globalApiKeyWidget) return;
    var el = document.getElementById('global-api-key-mount');
    if (!el) return;

    el.innerHTML = [
      '<div class="st-widget st-widget--api-key">',
      '<h3>🔑 API Secret Key</h3>',
      '<div class="field-group">',
      '<div>',
      '<div class="label-row">',
      '<label>X-Api-Key <span class="hint">(Auto-injected into all requests)</span></label>',
      '</div>',
      '<div class="input-wrapper">',
      '<input id="global-api-key" type="password" readonly value="__API_KEY_PLACEHOLDER__" placeholder="Enter API Key" />',
      '<button id="copy-global-api-key" class="btn-copy-val">📋</button>',
      '<button id="toggle-global-api-key" class="toggle-eye">👁️</button>',
      '</div>',
      '</div>',
      '</div>',
      '</div>',
    ].join('');

    var inputEl = document.getElementById('global-api-key');

    function applyKey() {
      var key = inputEl.value.trim();
      document.querySelectorAll('input[name="X-Api-Key"]').forEach(function (f) { f.value = key; });
    }

    applyKey();

    setupPasswordToggle('global-api-key', 'toggle-global-api-key');
    setupCopyButton('copy-global-api-key', 'global-api-key');

  }

  /* ════════════════════════════════════════════════════════
   * §4  WIDGET: JWT GENERATOR
   * ════════════════════════════════════════════════════════ */
  function mountJwtGen() {
    if (!CONFIG.jwtTools.jwtTokenGeneratorWidget) return;
    var el = document.getElementById('jwt-generator-mount');
    if (!el) return;

    el.innerHTML = [
      '<div class="st-widget st-widget--jwt-gen">',
      '<h3>⚡ JWT Token Generator</h3>',
      '<div class="field-group">',
      '<div>',
      '<div class="label-row">',
      '<label>JWT Secret Key</label>',
      '<div style="display:flex;gap:6px;">',
      '<button id="apply-jwt-secret" class="btn-apply">✓ apply</button>',
      '<button id="restore-jwt-secret" class="btn-restore">↩ restore</button>',
      '</div>',
      '</div>',
      '<div class="input-wrapper">',
      '<input id="jwt-secret" type="password" value="__JWT_KEY_PLACEHOLDER__" placeholder="JWT_SECRET" />',
      '<button id="copy-jwt-secret" class="btn-copy-val">📋</button>',
      '<button id="toggle-jwt-secret" class="toggle-eye">👁️</button>',
      '</div>',
      '</div>',
      '<div>',
      '<label>expiry type</label>',
      '<select id="jwt-expire-type">',
      '<option value="60" selected>1 hour</option>',
      '<option value="480">8 hours</option>',
      '<option value="1440">24 hours</option>',
      '<option value="custom">Custom (min)</option>',
      '<option value="0">No expiry</option>',
      '</select>',
      '<input id="jwt-expire" type="number" min="1" placeholder="minutes" style="display:none;margin-top:8px;" />',
      '</div>',
      '<div>',
      '<div class="label-row">',
      '<label>Payload JSON</label>',
      '<button id="restore-jwt-payload" class="btn-restore">↩ restore</button>',
      '</div>',
      '<textarea id="jwt-payload-raw" rows="6" spellcheck="false" class="json-editor"></textarea>',
      '</div>',
      '<div id="jwt-payload-preview"></div>',
      '</div>',
      '<button id="jwt-btn" class="btn-primary">⚡ Generate JWT Token</button>',
      '<div id="jwt-result" class="result-box">',
      '<label>Generated Token <span class="hint">(click to copy)</span></label>',
      '<div style="position:relative;">',
      '<textarea id="jwt-output" readonly rows="4" class="token-output"></textarea>',
      '<span id="jwt-copy-hint" class="copy-hint">click to copy</span>',
      '</div>',
      '<div class="action-row">',
      '<button id="jwt-apply-btn" class="btn-secondary">✨ Apply to authentication</button>',
      '</div>',
      '<p id="jwt-apply-hint" class="success-hint">✅ Bearer token injected!</p>',
      '</div>',
      '<div id="jwt-error" class="error-box"></div>',
      '</div>',
    ].join('');

    var secretEl = document.getElementById('jwt-secret');
    var origSecret = secretEl.value;

    setupPasswordToggle('jwt-secret', 'toggle-jwt-secret');
    setupCopyButton('copy-jwt-secret', 'jwt-secret');
    var appliedSecret = origSecret;

    function syncSecretButtons() {
      document.getElementById('restore-jwt-secret').style.display = secretEl.value === origSecret ? 'none' : 'inline-block';
      document.getElementById('apply-jwt-secret').style.display = secretEl.value === appliedSecret ? 'none' : 'inline-block';
    }
    syncSecretButtons();
    secretEl.addEventListener('input', syncSecretButtons);

    document.getElementById('apply-jwt-secret').addEventListener('click', function (e) {
      e.preventDefault();
      appliedSecret = secretEl.value;
      syncSecretButtons();
    });

    document.getElementById('restore-jwt-secret').addEventListener('click', function (e) {
      e.preventDefault();
      secretEl.value = origSecret;
      appliedSecret = origSecret;
      syncSecretButtons();
    });

    document.getElementById('jwt-expire-type').addEventListener('change', function () {
      document.getElementById('jwt-expire').style.display = this.value === 'custom' ? 'block' : 'none';
    });

    function getExpireMins() {
      var v = document.getElementById('jwt-expire-type').value;
      if (v === '0') return null;
      if (v === 'custom') return parseInt(document.getElementById('jwt-expire').value || '60', 10);
      return parseInt(v, 10);
    }

    function escHtml(s) {
      return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function updatePayloadPreview() {
      var previewEl = document.getElementById('jwt-payload-preview');
      var raw = document.getElementById('jwt-payload-raw').value.trim();
      if (!raw) { previewEl.innerHTML = ''; return; }
      try {
        var obj = JSON.parse(raw);
        var keys = Object.keys(obj);
        if (keys.length === 0) { previewEl.innerHTML = ''; return; }
        var rows = keys.map(function (k) {
          var v = obj[k];
          var display = v === null ? 'null' : typeof v === 'object' ? JSON.stringify(v) : String(v);
          var cell = display === '' ? '<span style="opacity:.35">—</span>' : escHtml(display);
          return '<tr><td>' + escHtml(k) + '</td><td>' + cell + '</td></tr>';
        });
        previewEl.innerHTML = '<div class="payload-preview"><table>' + rows.join('') + '</table></div>';
      } catch (e) {
        previewEl.innerHTML = '<div class="payload-preview"><div class="preview-hint">⚠ ' + escHtml(e.message) + '</div></div>';
      }
    }

    var defaultPayload = JSON.stringify(CONFIG.jwtDefaultPayload || {}, null, 2);
    document.getElementById('jwt-payload-raw').value = defaultPayload;
    updatePayloadPreview();

    function syncPayloadRestoreBtn() {
      var btn = document.getElementById('restore-jwt-payload');
      btn.style.display = document.getElementById('jwt-payload-raw').value === defaultPayload ? 'none' : 'inline-block';
    }
    syncPayloadRestoreBtn();

    document.getElementById('restore-jwt-payload').addEventListener('click', function (e) {
      e.preventDefault();
      document.getElementById('jwt-payload-raw').value = defaultPayload;
      updatePayloadPreview();
      syncPayloadRestoreBtn();
    });

    document.getElementById('jwt-payload-raw').addEventListener('input', function () {
      updatePayloadPreview();
      syncPayloadRestoreBtn();
    });

    try {
      var saved = JSON.parse(localStorage.getItem('scribe_toolkit_jwt_gen') || 'null');
      if (saved) {
        if (saved.payload) {
          document.getElementById('jwt-payload-raw').value = saved.payload;
          updatePayloadPreview();
          syncPayloadRestoreBtn();
        }
        if (saved.expireType) {
          document.getElementById('jwt-expire-type').value = saved.expireType;
          if (saved.expireType === 'custom') {
            if (saved.customMin) document.getElementById('jwt-expire').value = saved.customMin;
            document.getElementById('jwt-expire').style.display = 'block';
          }
        }
      }
    } catch (e) { }

    document.getElementById('jwt-btn').addEventListener('click', async function () {
      var errEl = document.getElementById('jwt-error');
      var resEl = document.getElementById('jwt-result');
      errEl.style.display = 'none';
      resEl.style.display = 'none';

      var secret = secretEl.value.trim();
      if (!secret) { errEl.textContent = '❌ Please enter the JWT Secret Key.'; errEl.style.display = 'block'; return; }

      var payloadObj;
      try { payloadObj = JSON.parse(document.getElementById('jwt-payload-raw').value.trim()); }
      catch (e) { errEl.textContent = '❌ Invalid JSON: ' + e.message; errEl.style.display = 'block'; return; }

      try {
        var expMins = getExpireMins();
        if (expMins !== null)
          payloadObj.exp = Math.floor(Date.now() / 1000) + (expMins * 60);
        var expType = document.getElementById('jwt-expire-type').value;
        try { localStorage.setItem('scribe_toolkit_jwt_gen', JSON.stringify({ payload: document.getElementById('jwt-payload-raw').value, expireType: expType, customMin: document.getElementById('jwt-expire').value })); } catch (e) { }

        var token = await signHs256(secret, payloadObj);
        document.getElementById('jwt-output').value = token;
        resEl.style.display = 'block';
      } catch (e) {
        errEl.textContent = '❌ Error: ' + e.message;
        errEl.style.display = 'block';
      }
    });

    document.getElementById('jwt-output').addEventListener('click', function () {
      navigator.clipboard.writeText(this.value).then(function () {
        var hint = document.getElementById('jwt-copy-hint');
        hint.textContent = '✅ copied!';
        hint.style.color = 'var(--st-green,#10b981)';
        setTimeout(function () { hint.textContent = 'click to copy'; hint.style.color = ''; }, 2000);
      });
    });

    document.getElementById('jwt-apply-btn').addEventListener('click', function () {
      var token = document.getElementById('jwt-output').value.trim();
      if (!token) return;
      injectScalarAuth(token);
      var hint = document.getElementById('jwt-apply-hint');
      hint.style.display = 'block';
      setTimeout(function () { hint.style.display = 'none'; }, 3000);
    });

  }

  /* ════════════════════════════════════════════════════════
   * §5  WIDGET: JWT VALIDATOR
   * ════════════════════════════════════════════════════════ */
  function mountJwtVal() {
    if (!CONFIG.jwtTools.jwtValidatorWidget) return;
    var el = document.getElementById('jwt-validator-mount');
    if (!el) return;

    el.innerHTML = [
      '<div class="st-widget st-widget--jwt-val">',
      '<h3>🔍 JWT Validator</h3>',
      '<div class="field-group">',
      '<div>',
      '<div class="label-row">',
      '<label>JWT Secret Key <span class="hint">(for validation)</span></label>',
      '<div style="display:flex;gap:6px;">',
      '<button id="apply-jwt-val-secret" class="btn-apply">✓ apply</button>',
      '<button id="restore-jwt-val-secret" class="btn-restore">↩ restore</button>',
      '</div>',
      '</div>',
      '<div class="input-wrapper">',
      '<input id="jwt-val-secret" type="password" value="__JWT_KEY_PLACEHOLDER__" placeholder="JWT_SECRET" />',
      '<button id="copy-jwt-val-secret" class="btn-copy-val">📋</button>',
      '<button id="toggle-jwt-val-secret" class="toggle-eye">👁️</button>',
      '</div>',
      '</div>',
      '<div>',
      '<label>Encoded Token</label>',
      '<textarea id="jwt-val-input" rows="3" placeholder="Paste your JWT here (eyJhbGci...)" class="token-output"></textarea>',
      '</div>',
      '<div>',
      '<label>Decoded Header</label>',
      '<textarea id="jwt-val-header" readonly rows="4" class="decoded-header"></textarea>',
      '</div>',
      '<div>',
      '<label>Decoded Payload</label>',
      '<textarea id="jwt-val-payload" readonly rows="6" class="decoded-payload"></textarea>',
      '</div>',
      '</div>',
      '<div class="status-bar">',
      '<div>',
      '<span class="status-label">Signature Status:</span>',
      '<span class="status-value" id="jwt-val-status-text">Awaiting Token...</span>',
      '</div>',
      '<div class="expire-info" id="jwt-val-exp"></div>',
      '</div>',
      '</div>',
    ].join('');

    var secretEl = document.getElementById('jwt-val-secret');
    var origSecret = secretEl.value;

    setupPasswordToggle('jwt-val-secret', 'toggle-jwt-val-secret');
    setupCopyButton('copy-jwt-val-secret', 'jwt-val-secret');

    var validateToken = async function () {
      var token = document.getElementById('jwt-val-input').value.trim();
      var secret = secretEl.value.trim();
      var headerEl = document.getElementById('jwt-val-header');
      var payloadEl = document.getElementById('jwt-val-payload');
      var statusEl = document.getElementById('jwt-val-status-text');
      var expEl = document.getElementById('jwt-val-exp');

      if (!token) {
        headerEl.value = ''; payloadEl.value = '';
        statusEl.textContent = 'Awaiting Token...';
        statusEl.style.color = '';
        expEl.style.display = 'none';
        return;
      }

      var parts = token.split('.');
      if (parts.length !== 3) {
        statusEl.textContent = '❌ Invalid JWT format';
        statusEl.style.color = 'var(--st-red,#f87171)';
        return;
      }

      try {
        var headerObj = decodeB64Json(parts[0]);
        var payloadObj = decodeB64Json(parts[1]);
        headerEl.value = JSON.stringify(headerObj, null, 2);
        payloadEl.value = JSON.stringify(payloadObj, null, 2);

        if (payloadObj.exp) {
          var expDate = new Date(payloadObj.exp * 1000);
          var isExpired = Date.now() > expDate.getTime();
          expEl.style.display = 'block';
          expEl.innerHTML = '<span style="color:var(--st-muted,#9ca3af)">Expires:</span> '
            + '<span style="color:' + (isExpired ? 'var(--st-red,#f87171)' : 'var(--st-green,#10b981)') + ';">'
            + expDate.toLocaleString() + (isExpired ? ' (Expired)' : '') + '</span>';
        } else {
          expEl.style.display = 'block';
          expEl.innerHTML = '<span style="color:var(--st-muted,#9ca3af)">Expires:</span> <span style="color:var(--st-muted,#9ca3af)">No expire</span>';
        }

        if (!secret) {
          statusEl.textContent = '⚠️ Missing secret — decoded only';
          statusEl.style.color = 'var(--st-orange,#fbbf24)';
          return;
        }

        var isValid = await verifyHs256(secret, token);
        statusEl.textContent = isValid ? '✅ Signature Verified' : '❌ Invalid Signature';
        statusEl.style.color = isValid ? 'var(--st-green,#10b981)' : 'var(--st-red,#f87171)';

        try { localStorage.setItem('scribe_toolkit_jwt_val', token); } catch (e) { }
      } catch (e) {
        statusEl.textContent = '❌ Error: ' + e.message;
        statusEl.style.color = 'var(--st-red,#f87171)';
      }
    };

    var appliedValSecret = origSecret;

    function syncValSecretButtons() {
      document.getElementById('restore-jwt-val-secret').style.display = secretEl.value === origSecret ? 'none' : 'inline-block';
      document.getElementById('apply-jwt-val-secret').style.display = secretEl.value === appliedValSecret ? 'none' : 'inline-block';
    }
    syncValSecretButtons();
    secretEl.addEventListener('input', syncValSecretButtons);

    document.getElementById('apply-jwt-val-secret').addEventListener('click', function (e) {
      e.preventDefault();
      appliedValSecret = secretEl.value;
      validateToken();
      syncValSecretButtons();
    });

    document.getElementById('restore-jwt-val-secret').addEventListener('click', function (e) {
      e.preventDefault();
      secretEl.value = origSecret;
      appliedValSecret = origSecret;
      validateToken();
      syncValSecretButtons();
    });

    document.getElementById('jwt-val-input').addEventListener('input', validateToken);

    try {
      var saved = localStorage.getItem('scribe_toolkit_jwt_val');
      if (saved) { document.getElementById('jwt-val-input').value = saved; validateToken(); }
    } catch (e) { }
  }

  /* ════════════════════════════════════════════════════════
   * §6  MOUNT OBSERVER — wait for Scalar to render mount points
   * ════════════════════════════════════════════════════════ */
  (function waitAndMount() {
    var mounted = { apiKey: false, jwtGen: false, jwtVal: false };

    function tryMount() {
      if (!mounted.apiKey && document.getElementById('global-api-key-mount')) {
        mountApiKey(); mounted.apiKey = true;
      }
      if (!mounted.jwtGen && document.getElementById('jwt-generator-mount')) {
        mountJwtGen(); mounted.jwtGen = true;
      }
      if (!mounted.jwtVal && document.getElementById('jwt-validator-mount')) {
        mountJwtVal(); mounted.jwtVal = true;
      }
      var allDone =
        (!CONFIG.jwtTools.globalApiKeyWidget      || mounted.apiKey) &&
        (!CONFIG.jwtTools.jwtTokenGeneratorWidget || mounted.jwtGen) &&
        (!CONFIG.jwtTools.jwtValidatorWidget      || mounted.jwtVal);
      if (allDone) observer.disconnect();
    }

    var observer = new MutationObserver(tryMount);
    observer.observe(document.body, { childList: true, subtree: true });
    tryMount();

  })();
})();
