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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="font-family:'Inter',sans-serif;background:#0d0d0d;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;">
<?php endif; ?>

<style>
body { margin: 0; }
.ticket-wrap { position: relative; width: 100%; max-width: 720px; margin: 0 auto; background: #141414; border: 1px solid #2a2a2a; border-radius: 16px; padding: 40px; box-sizing: border-box; }
.step { display: none; }
.step.active { display: block; }
.t-title { font-size: 21px; font-weight: 600; color: #fff; margin: 0 0 8px; }
.t-sub { font-size: 15px; color: #888; margin: 0 0 24px; }
.cat-card { background: #1c1c1c; border: 1px solid #2a2a2a; border-radius: 12px; padding: 22px; cursor: pointer; transition: all .15s; text-align: center; }
.cat-card:hover { border-color: #555; background: #222; }
.cat-card.selected { border-color: #fff; background: #1c1c1c; }
.cat-card.selected .cat-icon { color: #fff; }
.cat-icon { font-size: 34px; margin-bottom: 10px; color: #888; }
.cat-label { font-size: 15px; font-weight: 600; color: #fff; }
.cat-desc { font-size: 12px; color: #888; margin-top: 4px; }
.t-input { width: 100%; background: #1c1c1c; border: 1px solid #2a2a2a; border-radius: 8px; padding: 14px 16px; color: #fff; font-size: 15px; outline: none; transition: border-color .15s; box-sizing: border-box; }
.t-input:focus { border-color: #555; }
.t-input::placeholder { color: #444; }
.t-label { display: block; font-size: 14px; font-weight: 500; color: #888; margin-bottom: 8px; }
.btn-primary { width: 100%; background: #fff; color: #000; font-weight: 700; font-size: 16px; padding: 15px; border-radius: 8px; border: none; cursor: pointer; transition: background .15s; }
.btn-primary:hover { background: #e0e0e0; }
.btn-primary:disabled { background: #333; color: #666; cursor: not-allowed; }
.btn-back { background: transparent; border: 1px solid #2a2a2a; color: #888; font-size: 15px; padding: 12px 20px; border-radius: 8px; cursor: pointer; transition: all .15s; }
.btn-back:hover { border-color: #555; color: #fff; }
.step-dots { display: flex; gap: 6px; justify-content: center; }
.dot { width: 7px; height: 7px; border-radius: 50%; background: #2a2a2a; transition: all .25s; }
.dot.done { background: #666; }
.dot.current { background: #fff; width: 24px; border-radius: 4px; }
.file-drop { border: 1px dashed #2a2a2a; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: border-color .15s; }
.file-drop:hover { border-color: #555; }
.file-list { margin-top: 8px; display: flex; flex-direction: column; gap: 4px; }
.file-item { background: #1c1c1c; border: 1px solid #2a2a2a; border-radius: 6px; padding: 6px 10px; font-size: 12px; color: #888; display: flex; align-items: center; justify-content: space-between; }
.file-item button { background: none; border: none; color: #555; cursor: pointer; font-size: 14px; padding: 0; line-height: 1; }
.file-item button:hover { color: #fff; }
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
        <p style="font-size:15px;color:#888;margin:0;" id="board-name">Carregando...</p>
    </div>

    <div id="step-indicator" style="margin-bottom:24px;">
        <p id="step-label" style="text-align:center;font-size:13px;color:#888;margin:0 0 10px;font-weight:600;letter-spacing:.02em;"></p>
        <div class="step-dots" id="step-dots"></div>
    </div>

    <!-- Passo 1: Categoria -->
    <div class="step active" id="step-1">
        <h3 class="t-title">Qual é o assunto?</h3>
        <p class="t-sub">Escolha a categoria do seu chamado.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;">
            <div class="cat-card" data-cat="Bug" onclick="selectCategory(this)">
                <div class="cat-icon">🐛</div>
                <div class="cat-label">Bug / Erro</div>
                <div class="cat-desc">Algo não está funcionando</div>
            </div>
            <div class="cat-card" data-cat="Nova Funcionalidade" onclick="selectCategory(this)">
                <div class="cat-icon">✨</div>
                <div class="cat-label">Nova Funcionalidade</div>
                <div class="cat-desc">Sugestão ou melhoria</div>
            </div>
            <div class="cat-card" data-cat="Acesso" onclick="selectCategory(this)">
                <div class="cat-icon">🔑</div>
                <div class="cat-label">Acesso</div>
                <div class="cat-desc">Problema de login ou permissão</div>
            </div>
            <div class="cat-card" data-cat="Solicitação Geral" onclick="selectCategory(this)">
                <div class="cat-icon">📋</div>
                <div class="cat-label">Solicitação Geral</div>
                <div class="cat-desc">Outros assuntos</div>
            </div>
        </div>
        <button class="btn-primary" id="btn-step1" disabled onclick="goToStep(2)">Continuar →</button>
    </div>

    <!-- Passo 2: Detalhes + Arquivos -->
    <div class="step" id="step-2">
        <h3 class="t-title">Detalhes do chamado</h3>
        <p class="t-sub">Descreva o problema com o máximo de detalhes.</p>
        <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:20px;">
            <div>
                <label class="t-label">Empresa do grupo <span style="color:#e05260">*</span></label>
                <select id="inp-company" class="t-input" required style="cursor:pointer;">
                    <option value="" disabled selected>Selecione a empresa…</option>
                    <option value="Previnity">Previnity</option>
                    <option value="TaxResearch">TaxResearch</option>
                    <option value="OkCarro">OkCarro</option>
                    <option value="Aplicari">Aplicari</option>
                </select>
            </div>
            <div>
                <label class="t-label">Assunto (resumo em uma linha)</label>
                <input type="text" id="inp-title" class="t-input" placeholder="Ex: Botão de salvar não responde" required>
            </div>
            <div>
                <label class="t-label">Descrição detalhada</label>
                <textarea id="inp-desc" class="t-input" rows="4" placeholder="Descreva o que aconteceu, quando ocorre e como reproduzir..." required style="resize:vertical;"></textarea>
            </div>
            <div>
                <label class="t-label">Anexos (opcional — máx. 5 arquivos)</label>
                <div class="file-drop" id="file-drop" onclick="document.getElementById('inp-files').click()">
                    <svg style="width:24px;height:24px;color:#444;margin:0 auto 6px;display:block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    <p style="font-size:12px;color:#555;margin:0;">Clique para anexar ou <kbd style="background:#2a2a2a;border:1px solid #404040;border-radius:4px;padding:1px 5px;font-size:11px;font-family:monospace;color:#888888;">Ctrl+V</kbd> para colar um print</p>
                </div>
                <input type="file" id="inp-files" multiple accept="image/*,.pdf" style="display:none;" onchange="handleFileSelect(this)">
                <div class="file-list" id="file-list"></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn-back" onclick="goToStep(1)">← Voltar</button>
            <button class="btn-primary" onclick="goToConfirm()">Continuar →</button>
        </div>
    </div>

    <!-- Passo 3: Seus dados (nome + e-mail) -->
    <div class="step" id="step-3">
        <h3 class="t-title">Quase lá! Confirme seus dados</h3>
        <p class="t-sub">É com esses dados que vamos te identificar e avisar sobre o andamento do chamado.</p>
        <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:20px;">
            <div>
                <label class="t-label">Seu nome completo</label>
                <input type="text" id="inp-name" class="t-input" placeholder="João Silva" required>
            </div>
            <div>
                <label class="t-label">Seu e-mail para acompanhamento</label>
                <input type="email" id="inp-email" class="t-input" placeholder="joao@empresa.com.br" required>
                <p id="email-hint" style="display:none;font-size:11px;color:#666;margin:6px 0 0;">🔒 Vinculado ao seu login — é assim que reunimos todos os seus chamados.</p>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn-back" onclick="goToStep(2)">← Voltar</button>
            <button class="btn-primary" id="btn-submit" onclick="submitTicket()">Enviar Chamado</button>
        </div>
    </div>

    <!-- Sucesso -->
    <div class="step" id="step-success" style="text-align:center;padding:16px 0;">
        <div style="width:56px;height:56px;background:#1c1c1c;border:1px solid #2a2a2a;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <svg style="width:28px;height:28px;color:#fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 style="font-size:18px;font-weight:700;color:#fff;margin:0 0 8px;">Chamado enviado!</h3>
        <p style="font-size:14px;color:#888;margin:0 0 24px;">Nossa equipe recebeu sua solicitação e entrará em contato em breve.</p>
        <button class="btn-primary" style="max-width:200px;margin:0 auto;display:block;" onclick="resetForm()">Abrir outro chamado</button>
    </div>

    <!-- Erro de carregamento -->
    <div class="step" id="step-error" style="text-align:center;padding:16px 0;">
        <div style="font-size:40px;margin-bottom:12px;">⚠️</div>
        <h3 style="font-size:16px;font-weight:600;color:#fff;margin:0 0 8px;">Link inválido</h3>
        <p style="font-size:13px;color:#888;margin:0;" id="error-text">Este link de chamado não é válido ou expirou.</p>
    </div>
</div>

<script>
const API_URL = '<?php echo API_BASE_URL; ?>';
const TOKEN = '<?php echo htmlspecialchars($token, ENT_QUOTES); ?>';
const EMBEDDED = <?php echo $embedded ? 'true' : 'false'; ?>;
const PREFILL_NAME = '<?php echo htmlspecialchars($prefillName, ENT_QUOTES); ?>';
const PREFILL_EMAIL = '<?php echo htmlspecialchars($prefillEmail, ENT_QUOTES); ?>';
// Sem token => kanban coletivo (board principal). Com token => board público específico.
const BOARD_INFO_URL = TOKEN ? `${API_URL}/boards/public/${TOKEN}` : `${API_URL}/boards/public-main`;
const SUBMIT_URL = TOKEN ? `${API_URL}/tasks/ticket/${TOKEN}` : `${API_URL}/tasks/ticket`;
let selectedCategory = null;
let selectedFiles = [];

// Pré-preenche o nome (editável — é só rótulo) e o e-mail.
if (PREFILL_NAME) document.getElementById('inp-name').value = PREFILL_NAME;
if (PREFILL_EMAIL) {
    const emailEl = document.getElementById('inp-email');
    emailEl.value = PREFILL_EMAIL;
    // E-mail do login = identidade estável: trava para não fragmentar o histórico do solicitante.
    emailEl.readOnly = true;
    emailEl.style.opacity = '0.7';
    emailEl.style.cursor = 'not-allowed';
    const hint = document.getElementById('email-hint');
    if (hint) hint.style.display = 'block';
}

// Fluxo de passos usado pelo indicador de progresso.
const STEP_FLOW = [
    { id: 1, label: 'Categoria' },
    { id: 2, label: 'Detalhes' },
    { id: 3, label: 'Seus dados' },
];
updateStepIndicator(1);

// Auto-resize: informa a altura real do conteúdo ao widget pai (modo embedded),
// para o iframe acompanhar o tamanho a cada passo e evitar espaço vazio/overflow.
if (EMBEDDED) {
    const wrapEl = document.getElementById('ticket-wrap');
    const postHeight = () => {
        window.parent.postMessage({ type: 'ticket-resize', height: Math.ceil(wrapEl.offsetHeight) + 4 }, '*');
    };
    if (window.ResizeObserver) new ResizeObserver(postHeight).observe(wrapEl);
    window.addEventListener('load', postHeight);
    setTimeout(postHeight, 50);
}

// Valida o board (por token ou board principal) ao carregar
fetch(BOARD_INFO_URL)
    .then(r => { if (!r.ok) throw new Error(); return r.json(); })
    .then(data => {
        document.getElementById('board-name').textContent = data.name || 'BJGROUP Suporte';
    })
    .catch(() => {
        showStep('step-error');
        document.getElementById('step-indicator').style.display = 'none';
    });

function selectCategory(el) {
    document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selectedCategory = el.dataset.cat;
    document.getElementById('btn-step1').disabled = false;
}

function goToConfirm() {
    const company = document.getElementById('inp-company').value;
    const title = document.getElementById('inp-title').value.trim();
    const desc = document.getElementById('inp-desc').value.trim();
    if (!company) { document.getElementById('inp-company').focus(); return; }
    if (!title) { document.getElementById('inp-title').focus(); return; }
    if (!desc) { document.getElementById('inp-desc').focus(); return; }
    goToStep(3);
    const nameEl = document.getElementById('inp-name');
    (nameEl.value.trim() ? document.getElementById('inp-email') : nameEl).focus();
}

function goToStep(n) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById('step-' + n).classList.add('active');
    updateStepIndicator(n);
}

function showStep(id) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById(id).classList.add('active');
}

// Indicador de progresso: destaca o passo atual e mostra "Passo X de N · Nome".
function updateStepIndicator(n) {
    const dots = document.getElementById('step-dots');
    const label = document.getElementById('step-label');
    const idx = STEP_FLOW.findIndex(s => s.id === n);
    if (idx === -1) return;
    dots.innerHTML = STEP_FLOW.map((s, i) =>
        `<span class="dot ${i === idx ? 'current' : (i < idx ? 'done' : '')}"></span>`
    ).join('');
    label.textContent = `Passo ${idx + 1} de ${STEP_FLOW.length} · ${STEP_FLOW[idx].label}`;
}

function addFiles(files) {
    files.forEach(f => {
        if (selectedFiles.length < 5) selectedFiles.push(f);
    });
    renderFileList();
}

function handleFileSelect(input) {
    addFiles(Array.from(input.files));
    input.value = '';
}

function flashTicketDrop() {
    const drop = document.getElementById('file-drop');
    if (!drop) return;
    drop.style.borderColor = '#ffffff';
    drop.style.background = 'rgba(255,255,255,0.04)';
    setTimeout(() => { drop.style.borderColor = ''; drop.style.background = ''; }, 600);
}

document.addEventListener('paste', (e) => {
    const step2 = document.getElementById('step-2');
    if (!step2 || !step2.classList.contains('active')) return;
    const items = Array.from(e.clipboardData?.items || []);
    const imageFiles = items
        .filter(item => item.kind === 'file' && item.type.startsWith('image/'))
        .map(item => {
            const file = item.getAsFile();
            const name = `print-${Date.now()}.${file.type.split('/')[1] || 'png'}`;
            return new File([file], name, { type: file.type });
        });
    if (imageFiles.length === 0) return;
    e.preventDefault();
    addFiles(imageFiles);
    flashTicketDrop();
});

function removeFile(idx) {
    selectedFiles.splice(idx, 1);
    renderFileList();
}

function renderFileList() {
    const list = document.getElementById('file-list');
    list.innerHTML = selectedFiles.map((f, i) => `
        <div class="file-item">
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${f.name}</span>
            <button onclick="removeFile(${i})" title="Remover">×</button>
        </div>`).join('');
    document.getElementById('file-drop').style.display = selectedFiles.length >= 5 ? 'none' : '';
}

async function submitTicket() {
    const company = document.getElementById('inp-company').value;
    const title = document.getElementById('inp-title').value.trim();
    const desc = document.getElementById('inp-desc').value.trim();
    const name = document.getElementById('inp-name').value.trim();
    const email = document.getElementById('inp-email').value.trim();

    if (!company) { goToStep(2); document.getElementById('inp-company').focus(); return; }
    if (!title || !desc) { goToStep(2); return; }
    if (!name) { document.getElementById('inp-name').focus(); return; }
    if (!email || !email.includes('@')) { document.getElementById('inp-email').focus(); return; }

    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.textContent = 'Enviando...';

    const fd = new FormData();
    fd.append('requesterName', name);
    fd.append('requesterEmail', email);
    fd.append('requesterCompany', company);
    fd.append('category', selectedCategory);
    fd.append('title', title);
    fd.append('description', desc);
    selectedFiles.forEach(f => fd.append('attachments', f));

    try {
        const res = await fetch(SUBMIT_URL, { method: 'POST', body: fd });
        if (!res.ok) throw new Error();
        document.getElementById('step-indicator').style.display = 'none';
        showStep('step-success');
        if (EMBEDDED) {
            setTimeout(() => window.parent.postMessage('ticket-submitted', '*'), 500);
        }
    } catch {
        alert('Erro ao enviar. Tente novamente.');
        btn.disabled = false;
        btn.textContent = 'Enviar Chamado';
    }
}

function resetForm() {
    selectedCategory = null;
    selectedFiles = [];
    document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('btn-step1').disabled = true;
    document.getElementById('inp-name').value = PREFILL_NAME || '';
    document.getElementById('inp-email').value = PREFILL_EMAIL || '';
    document.getElementById('inp-company').value = '';
    document.getElementById('inp-title').value = '';
    document.getElementById('inp-desc').value = '';
    document.getElementById('file-list').innerHTML = '';
    document.getElementById('file-drop').style.display = '';
    document.getElementById('step-indicator').style.display = '';
    const submitBtn = document.getElementById('btn-submit');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Enviar Chamado';
    goToStep(1);
}
</script>

<?php if (!$embedded): ?>
</body>
</html>
<?php endif; ?>
