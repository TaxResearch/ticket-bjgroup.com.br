/*!
 * BJGROUP Suporte — Widget de Tickets
 * Hospedado em: https://ticket.bjgroup.com.br/widget.js
 *
 * Uso mínimo (botão flutuante):
 *   <script src="https://ticket.bjgroup.com.br/widget.js" defer></script>
 *
 * Uso com botão próprio:
 *   <script src="https://ticket.bjgroup.com.br/widget.js" data-no-btn defer></script>
 *   <button onclick="BJGWidget.open()">Solicitar Suporte</button>
 *
 * Sem data-token o chamado cai no kanban coletivo (board principal) da holding.
 * Veja INTEGRACAO-WIDGET.md para todos os atributos e exemplos por plataforma.
 */
(function () {
  var script = document.currentScript || (function () {
    var scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();

  // data-base-url é OPCIONAL: por padrão usa a origem de onde o widget.js foi servido
  // (ex.: https://ticket.bjgroup.com.br). Só defina se o front estiver em outro domínio.
  var baseUrl = script.getAttribute('data-base-url');
  if (!baseUrl) {
    try { baseUrl = new URL(script.src).origin; } catch (e) { baseUrl = ''; }
  }
  baseUrl = baseUrl.replace(/\/+$/, ''); // remove barra final

  // data-token é OPCIONAL: sem token o chamado vai para o board principal de tickets.
  // Use um token apenas se quiser direcionar para um board público específico.
  var token = script.getAttribute('data-token');
  var label = script.getAttribute('data-label') || 'Suporte';
  var position = script.getAttribute('data-position') || 'bottom-right';
  var prefillName = script.getAttribute('data-name') || '';
  var prefillEmail = script.getAttribute('data-email') || '';
  var noBtn = script.hasAttribute('data-no-btn');

  var posStyle = {
    'bottom-right': 'bottom:24px;right:24px;',
    'bottom-left': 'bottom:24px;left:24px;',
    'top-right': 'top:24px;right:24px;',
    'top-left': 'top:24px;left:24px;',
  }[position] || 'bottom:24px;right:24px;';

  // Botão flutuante (só quando data-no-btn não está presente)
  var btn = document.createElement('button');
  btn.id = '__bjg_widget_btn';
  btn.type = 'button';
  btn.innerHTML = '<svg style="width:22px;height:22px;margin-right:8px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>' + label;
  btn.style.cssText = 'position:fixed;' + posStyle + 'display:flex;align-items:center;background:#ffffff;color:#000000;border:none;border-radius:999px;padding:12px 20px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 20px rgba(0,0,0,0.35);z-index:9998;font-family:system-ui,sans-serif;transition:transform .15s,box-shadow .15s;';
  btn.onmouseenter = function () { btn.style.transform = 'scale(1.05)'; btn.style.boxShadow = '0 6px 28px rgba(0,0,0,0.45)'; };
  btn.onmouseleave = function () { btn.style.transform = ''; btn.style.boxShadow = '0 4px 20px rgba(0,0,0,0.35)'; };

  // Overlay + iframe
  var overlay = document.createElement('div');
  overlay.id = '__bjg_widget_overlay';
  overlay.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:9999;align-items:center;justify-content:center;padding:16px;';

  var frame = document.createElement('iframe');
  frame.style.cssText = 'width:100%;max-width:720px;height:620px;max-height:90vh;border:none;border-radius:16px;background:#141414;transition:height .2s ease;';
  var frameUrl = baseUrl + '/ticket.php?embedded=1';
  if (token) frameUrl += '&token=' + encodeURIComponent(token);
  if (prefillName) frameUrl += '&name=' + encodeURIComponent(prefillName);
  if (prefillEmail) frameUrl += '&email=' + encodeURIComponent(prefillEmail);
  frame.src = frameUrl;

  overlay.appendChild(frame);

  function openWidget() {
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
  function closeWidget() {
    overlay.style.display = 'none';
    document.body.style.overflow = '';
  }

  overlay.onclick = function (e) { if (e.target === overlay) closeWidget(); };

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && overlay.style.display !== 'none') closeWidget();
  });

  window.addEventListener('message', function (e) {
    if (e.data === 'ticket-submitted') {
      closeWidget();
      showToast('Chamado enviado com sucesso!');
      return;
    }
    // X dentro do modal (iframe) pede para fechar
    if (e.data === 'ticket-close') {
      closeWidget();
      return;
    }
    // Altura dinâmica: o iframe acompanha o conteúdo (limitado a 90vh via max-height).
    if (e.data && e.data.type === 'ticket-resize' && typeof e.data.height === 'number') {
      frame.style.height = e.data.height + 'px';
    }
  });

  function showToast(msg) {
    var t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:80px;' + (position.indexOf('left') !== -1 ? 'left:24px;' : 'right:24px;') + 'background:#fff;color:#000;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:600;font-family:system-ui,sans-serif;box-shadow:0 4px 20px rgba(0,0,0,.35);z-index:9999;transition:opacity .4s;';
    document.body.appendChild(t);
    setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 400); }, 3500);
  }

  function mount() {
    if (!noBtn) {
      btn.onclick = openWidget;
      document.body.appendChild(btn);
    }
    document.body.appendChild(overlay);
  }

  // Garante que document.body existe (caso o script esteja no <head> sem defer)
  if (document.body) {
    mount();
  } else {
    document.addEventListener('DOMContentLoaded', mount);
  }

  // API programática: abra/feche de qualquer botão da sua página
  window.BJGWidget = { open: openWidget, close: closeWidget };
})();
