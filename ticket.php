<?php
require_once __DIR__ . '/config/config.php';
$token = $_GET['token'] ?? '';
$embedded = isset($_GET['embedded']) && $_GET['embedded'] == '1';
$prefillName = trim($_GET['name'] ?? '');
$prefillEmail = trim($_GET['email'] ?? '');
?>
<?php if (!$embedded): ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrir Chamado - BJGROUP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="font-family:'Inter',sans-serif;background:#0d0d0d;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;">
<?php endif; ?>

<style>
body { margin: 0; }
.ticket-wrap { position: relative; width: 100%; max-width: 720px; margin: 0 auto; background: #141414; border: 1px solid #2a2a2a; border-radius: 16px; padding: 40px; box-sizing: border-box; }
</style>

<?php if ($embedded): ?>
<style>
/* No iframe do widget o próprio iframe já é o card (fundo + cantos arredondados).
   O wrap preenche tudo e dispensa borda/raio próprios, evitando a linha dupla. */
.ticket-wrap { max-width: none; border: none; border-radius: 0; }
</style>
<?php endif; ?>

<div class="ticket-wrap" id="ticket-wrap">
    <?php if ($embedded): ?>
    <button type="button" onclick="window.parent.postMessage('ticket-close','*')" aria-label="Fechar" title="Fechar"
        style="position:absolute;top:16px;right:16px;width:36px;height:36px;border-radius:50%;background:#1c1c1c;border:1px solid #2a2a2a;color:#999;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;z-index:5;padding:0;"
        onmouseover="this.style.color='#fff';this.style.borderColor='#555';this.style.background='#222';"
        onmouseout="this.style.color='#999';this.style.borderColor='#2a2a2a';this.style.background='#1c1c1c';"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="display:block;"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
    <?php endif; ?>
    <div style="text-align:center;margin-bottom:24px;">
        <img src="<?php echo url('img/logo-white.png'); ?>" alt="BJGROUP" style="height:44px;margin:0 auto 14px;display:block;" onerror="this.style.display='none'">
        <p style="font-size:15px;color:#888;margin:0;" id="board-name">BJGROUP Suporte</p>
    </div>

    <!-- Formulário compartilhado (ticket-form.js) -->
    <div id="tkf-root"></div>
</div>

<script src="<?php echo url('assets/js/ticket-form.js'); ?>"></script>
<script>
(function () {
    const API_URL = '<?php echo API_BASE_URL; ?>';
    const TOKEN = '<?php echo htmlspecialchars($token, ENT_QUOTES); ?>';
    const EMBEDDED = <?php echo $embedded ? 'true' : 'false'; ?>;
    const PREFILL_NAME = '<?php echo htmlspecialchars($prefillName, ENT_QUOTES); ?>';
    const PREFILL_EMAIL = '<?php echo htmlspecialchars($prefillEmail, ENT_QUOTES); ?>';

    // Sem token => kanban coletivo (board principal). Com token => board público específico.
    const BOARD_INFO_URL = TOKEN ? `${API_URL}/boards/public/${TOKEN}` : `${API_URL}/boards/public-main`;
    const SUBMIT_URL = TOKEN ? `${API_URL}/tasks/ticket/${TOKEN}` : `${API_URL}/tasks/ticket`;

    TicketForm.mount(document.getElementById('tkf-root'), {
        submitUrl: SUBMIT_URL,
        captureIdentity: true,
        boardInfoUrl: BOARD_INFO_URL,
        prefill: { name: PREFILL_NAME, email: PREFILL_EMAIL, lockEmail: !!PREFILL_EMAIL },
        onBoard: (data) => { document.getElementById('board-name').textContent = data.name || 'BJGROUP Suporte'; },
        onSuccess: () => { if (EMBEDDED) setTimeout(() => window.parent.postMessage('ticket-submitted', '*'), 500); },
    });

    // Auto-resize no iframe (modo embedded): acompanha a altura do conteúdo a cada passo.
    if (EMBEDDED) {
        const wrapEl = document.getElementById('ticket-wrap');
        const postHeight = () => window.parent.postMessage({ type: 'ticket-resize', height: Math.ceil(wrapEl.offsetHeight) + 4 }, '*');
        if (window.ResizeObserver) new ResizeObserver(postHeight).observe(wrapEl);
        window.addEventListener('load', postHeight);
        setTimeout(postHeight, 50);
    }
})();
</script>

<?php if (!$embedded): ?>
</body>
</html>
<?php endif; ?>
