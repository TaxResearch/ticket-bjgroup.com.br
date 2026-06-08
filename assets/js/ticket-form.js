/* ───────────────────────────────────────────────────────────────────────────
   Formulário de Ticket COMPARTILHADO — fonte única de campos e lógica.
   Usado pelo widget público (ticket.php) e pelo portal do funcionário (portal.php).
   Renderiza o próprio DOM + estilos (escopo .tkf), então é só dar um container.

   TicketForm.mount(container, config) -> { reset, show }

   config:
     submitUrl      string  (obrigatório) — endpoint do POST
     captureIdentity bool   — público: pede nome/e-mail num passo extra; portal: false
     getHeaders     fn      — headers extras no submit (ex.: Authorization no portal)
     prefill        {name,email,company,lockEmail}
     boardInfoUrl   string  — público: valida o board principal; se falhar mostra "erro"
     onBoard        fn(data)— recebe info do board (ex.: nome) — público
     onSuccess      fn(res) — após enviar (portal: recarrega lista; widget: postMessage)
   ─────────────────────────────────────────────────────────────────────────── */
(function () {
    'use strict';

    const CATEGORIES = [
        { id: 'Bug', icon: '🐛', label: 'Bug / Erro', desc: 'Algo não está funcionando' },
        { id: 'Nova Funcionalidade', icon: '✨', label: 'Nova Funcionalidade', desc: 'Sugestão ou melhoria' },
        { id: 'Acesso', icon: '🔑', label: 'Acesso', desc: 'Login ou permissão' },
        { id: 'Solicitação Geral', icon: '📋', label: 'Solicitação Geral', desc: 'Outros assuntos' },
    ];
    const COMPANIES = ['Previnity', 'TaxResearch', 'OkCarro', 'Aplicari'];
    const MAX_FILES = 5;

    let stylesInjected = false;
    function injectStyles() {
        if (stylesInjected) return;
        stylesInjected = true;
        const style = document.createElement('style');
        style.textContent = `
.tkf { font-family: 'Inter', system-ui, sans-serif; }
.tkf-dots { display:flex; gap:6px; justify-content:center; margin-bottom:24px; }
.tkf-dot { width:7px; height:7px; border-radius:50%; background:#2a2a2a; transition:all .25s; }
.tkf-dot.is-done { background:#666; }
.tkf-dot.is-cur { background:#fff; width:24px; border-radius:4px; }
.tkf-h { font-size:20px; font-weight:600; color:#fff; margin:0 0 8px; }
.tkf-p { font-size:14px; color:#888; margin:0 0 22px; }
.tkf-cats { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:22px; }
.tkf-cat { background:#1c1c1c; border:1px solid #2a2a2a; border-radius:12px; padding:18px; cursor:pointer; transition:all .15s; text-align:center; display:flex; flex-direction:column; align-items:center; gap:4px; }
.tkf-cat:hover { border-color:#555; background:#222; }
.tkf-cat.is-sel { border-color:#fff; }
.tkf-cat-ic { font-size:30px; line-height:1; }
.tkf-cat-l { font-size:14px; font-weight:600; color:#fff; }
.tkf-cat-d { font-size:12px; color:#888; }
.tkf-fields { display:flex; flex-direction:column; gap:14px; margin-bottom:22px; }
.tkf-lbl { display:block; font-size:13px; font-weight:500; color:#888; margin-bottom:7px; }
.tkf-req { color:#e05260; }
.tkf-opt { color:#555; font-weight:400; }
.tkf-input { width:100%; background:#1c1c1c; border:1px solid #2a2a2a; border-radius:8px; padding:13px 15px; color:#fff; font-size:15px; outline:none; transition:border-color .15s; box-sizing:border-box; font-family:inherit; }
.tkf-input:focus { border-color:#555; }
.tkf-input::placeholder { color:#444; }
select.tkf-input { cursor:pointer; }
textarea.tkf-input { resize:vertical; }
.tkf-locked { opacity:.7; cursor:not-allowed; }
.tkf-hint { font-size:11px; color:#666; margin:6px 0 0; }
.tkf-drop { border:1px dashed #2a2a2a; border-radius:8px; padding:18px; text-align:center; cursor:pointer; transition:all .15s; }
.tkf-drop:hover, .tkf-drop.is-over { border-color:#555; }
.tkf-drop.is-flash { border-color:#fff; background:rgba(255,255,255,.04); }
.tkf-drop svg { width:24px; height:24px; color:#444; margin:0 auto 6px; display:block; }
.tkf-drop span { font-size:12px; color:#555; }
.tkf-drop kbd { background:#2a2a2a; border:1px solid #404040; border-radius:4px; padding:1px 5px; font-size:11px; font-family:monospace; color:#888; }
.tkf-filelist { margin-top:8px; display:flex; flex-direction:column; gap:4px; }
.tkf-fileitem { background:#1c1c1c; border:1px solid #2a2a2a; border-radius:6px; padding:6px 10px; font-size:12px; color:#888; display:flex; align-items:center; justify-content:space-between; gap:8px; }
.tkf-fileitem span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.tkf-fileitem button { background:none; border:none; color:#555; cursor:pointer; font-size:15px; line-height:1; padding:0; }
.tkf-fileitem button:hover { color:#fff; }
.tkf-row { display:flex; gap:8px; }
.tkf-btn { flex:1; background:#fff; color:#000; font-weight:700; font-size:15px; padding:14px; border-radius:8px; border:none; cursor:pointer; transition:background .15s; font-family:inherit; }
.tkf-btn:hover { background:#e0e0e0; }
.tkf-btn:disabled { background:#333; color:#666; cursor:not-allowed; }
.tkf-btn-auto { flex:0 0 auto; min-width:200px; margin:0 auto; display:block; }
.tkf-back { background:transparent; border:1px solid #2a2a2a; color:#888; font-size:15px; padding:12px 20px; border-radius:8px; cursor:pointer; transition:all .15s; font-family:inherit; }
.tkf-back:hover { border-color:#555; color:#fff; }
.tkf-center { text-align:center; padding:8px 0; }
.tkf-okic { width:56px; height:56px; background:#1c1c1c; border:1px solid #2a2a2a; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
.tkf-okic svg { width:28px; height:28px; color:#fff; }
.tkf-warn { font-size:40px; margin-bottom:12px; }
`;
        document.head.appendChild(style);
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, c =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function mount(container, config) {
        injectStyles();
        const cfg = Object.assign({ captureIdentity: false, prefill: {} }, config || {});
        const prefill = cfg.prefill || {};
        const steps = cfg.captureIdentity
            ? ['categoria', 'detalhes', 'identidade']
            : ['categoria', 'detalhes'];
        let selectedCategory = null;
        let files = [];
        let current = 'categoria';

        container.classList.add('tkf');
        container.innerHTML = `
            <div class="tkf-dots"></div>

            <div class="tkf-step" data-step="categoria">
                <h3 class="tkf-h">Qual é o assunto?</h3>
                <p class="tkf-p">Escolha a categoria do seu chamado.</p>
                <div class="tkf-cats">
                    ${CATEGORIES.map(c => `
                        <button type="button" class="tkf-cat" data-cat="${esc(c.id)}">
                            <span class="tkf-cat-ic">${c.icon}</span>
                            <span class="tkf-cat-l">${esc(c.label)}</span>
                            <span class="tkf-cat-d">${esc(c.desc)}</span>
                        </button>`).join('')}
                </div>
                <div class="tkf-row">
                    <button type="button" class="tkf-btn" data-act="next" disabled>Continuar →</button>
                </div>
            </div>

            <div class="tkf-step" data-step="detalhes" hidden>
                <h3 class="tkf-h">Detalhes do chamado</h3>
                <p class="tkf-p">Conte o que você precisa com o máximo de detalhes.</p>
                <div class="tkf-fields">
                    <div>
                        <label class="tkf-lbl">Empresa do grupo <span class="tkf-req">*</span></label>
                        <select class="tkf-input tkf-company">
                            <option value="" disabled selected>Selecione a empresa…</option>
                            ${COMPANIES.map(c => `<option value="${esc(c)}">${esc(c)}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="tkf-lbl">Assunto (resumo em uma linha) <span class="tkf-req">*</span></label>
                        <input type="text" class="tkf-input tkf-titlein" maxlength="255" placeholder="Ex: Botão de salvar não responde">
                    </div>
                    <div>
                        <label class="tkf-lbl">Descrição</label>
                        <textarea class="tkf-input tkf-desc" rows="4" maxlength="2000" placeholder="Descreva o que aconteceu, quando ocorre e como reproduzir…"></textarea>
                    </div>
                    <div>
                        <label class="tkf-lbl">Anexos <span class="tkf-opt">(opcional — máx. 5)</span></label>
                        <div class="tkf-drop">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span>Clique para anexar ou <kbd>Ctrl+V</kbd> para colar um print</span>
                        </div>
                        <input type="file" class="tkf-fileinput" multiple accept="image/*,.pdf" hidden>
                        <div class="tkf-filelist"></div>
                    </div>
                </div>
                <div class="tkf-row">
                    <button type="button" class="tkf-back" data-act="back">← Voltar</button>
                    <button type="button" class="tkf-btn" data-act="${cfg.captureIdentity ? 'next' : 'submit'}" disabled>${cfg.captureIdentity ? 'Continuar →' : 'Enviar Chamado'}</button>
                </div>
            </div>

            ${cfg.captureIdentity ? `
            <div class="tkf-step" data-step="identidade" hidden>
                <h3 class="tkf-h">Quase lá! Confirme seus dados</h3>
                <p class="tkf-p">É com esses dados que vamos te identificar e avisar sobre o andamento.</p>
                <div class="tkf-fields">
                    <div>
                        <label class="tkf-lbl">Seu nome completo <span class="tkf-req">*</span></label>
                        <input type="text" class="tkf-input tkf-name" placeholder="João Silva">
                    </div>
                    <div>
                        <label class="tkf-lbl">Seu e-mail para acompanhamento <span class="tkf-req">*</span></label>
                        <input type="email" class="tkf-input tkf-email" placeholder="joao@empresa.com.br">
                        <p class="tkf-hint tkf-emailhint" hidden>🔒 Vinculado ao seu login — é assim que reunimos seus chamados.</p>
                    </div>
                </div>
                <div class="tkf-row">
                    <button type="button" class="tkf-back" data-act="back">← Voltar</button>
                    <button type="button" class="tkf-btn" data-act="submit">Enviar Chamado</button>
                </div>
            </div>` : ''}

            <div class="tkf-step tkf-center" data-step="sucesso" hidden>
                <div class="tkf-okic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div>
                <h3 class="tkf-h">Chamado enviado!</h3>
                <p class="tkf-p">Nossa equipe recebeu sua solicitação e entrará em contato em breve.</p>
                <button type="button" class="tkf-btn tkf-btn-auto" data-act="reset">Abrir outro chamado</button>
            </div>

            <div class="tkf-step tkf-center" data-step="erro" hidden>
                <div class="tkf-warn">⚠️</div>
                <h3 class="tkf-h">Indisponível</h3>
                <p class="tkf-p tkf-errtext">Não foi possível carregar o formulário. Tente novamente.</p>
            </div>
        `;

        const q = sel => container.querySelector(sel);
        const qa = sel => Array.from(container.querySelectorAll(sel));
        const dotsEl = q('.tkf-dots');
        const companyEl = q('.tkf-company');
        const titleEl = q('.tkf-titlein');
        const descEl = q('.tkf-desc');
        const fileInput = q('.tkf-fileinput');
        const fileListEl = q('.tkf-filelist');
        const dropEl = q('.tkf-drop');
        const nameEl = q('.tkf-name');
        const emailEl = q('.tkf-email');

        // ── prefill ──
        if (prefill.company && COMPANIES.includes(prefill.company)) companyEl.value = prefill.company;
        if (nameEl && prefill.name) nameEl.value = prefill.name;
        if (emailEl && prefill.email) {
            emailEl.value = prefill.email;
            if (prefill.lockEmail) {
                emailEl.readOnly = true;
                emailEl.classList.add('tkf-locked');
                const hint = q('.tkf-emailhint');
                if (hint) hint.hidden = false;
            }
        }

        // ── navegação ──
        function renderDots() {
            const idx = steps.indexOf(current);
            dotsEl.hidden = (current === 'sucesso' || current === 'erro' || idx === -1);
            dotsEl.innerHTML = steps.map((s, i) =>
                `<span class="tkf-dot ${i === idx ? 'is-cur' : (i < idx ? 'is-done' : '')}"></span>`
            ).join('');
        }
        function show(step) {
            current = step;
            qa('.tkf-step').forEach(el => { el.hidden = el.dataset.step !== step; });
            renderDots();
        }
        function goNext() {
            const i = steps.indexOf(current);
            if (i >= 0 && i < steps.length - 1) show(steps[i + 1]);
        }
        function goBack() {
            const i = steps.indexOf(current);
            if (i > 0) show(steps[i - 1]);
        }

        // ── validação ──
        function detalhesValid() {
            return !!companyEl.value && titleEl.value.trim().length > 0;
        }
        function updateDetalhesBtn() {
            const btn = container.querySelector('[data-step="detalhes"] .tkf-btn');
            btn.disabled = !detalhesValid();
        }
        companyEl.addEventListener('change', updateDetalhesBtn);
        titleEl.addEventListener('input', updateDetalhesBtn);

        // ── categoria ──
        qa('.tkf-cat').forEach(b => b.addEventListener('click', () => {
            qa('.tkf-cat').forEach(x => x.classList.remove('is-sel'));
            b.classList.add('is-sel');
            selectedCategory = b.dataset.cat;
            container.querySelector('[data-step="categoria"] .tkf-btn').disabled = false;
        }));

        // ── ações dos botões (delegação) ──
        container.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-act]');
            if (!trigger || !container.contains(trigger)) return;
            const act = trigger.dataset.act;
            if (act === 'next') {
                if (current === 'detalhes' && !detalhesValid()) return;
                goNext();
                setTimeout(() => {
                    if (current === 'detalhes') companyEl.focus();
                    else if (current === 'identidade' && nameEl) (nameEl.value.trim() ? emailEl : nameEl).focus();
                }, 50);
            } else if (act === 'back') {
                goBack();
            } else if (act === 'submit') {
                submit();
            } else if (act === 'reset') {
                reset();
            }
        });

        // ── anexos ──
        function renderFiles() {
            fileListEl.innerHTML = files.map((f, i) =>
                `<div class="tkf-fileitem"><span>${esc(f.name)}</span><button type="button" data-rm="${i}" title="Remover">×</button></div>`
            ).join('');
            dropEl.style.display = files.length >= MAX_FILES ? 'none' : '';
        }
        function addFiles(list) {
            list.forEach(f => { if (files.length < MAX_FILES) files.push(f); });
            renderFiles();
        }
        dropEl.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => { addFiles(Array.from(fileInput.files)); fileInput.value = ''; });
        fileListEl.addEventListener('click', (e) => {
            const rm = e.target.closest('[data-rm]');
            if (!rm) return;
            files.splice(parseInt(rm.dataset.rm, 10), 1);
            renderFiles();
        });
        ['dragover', 'dragenter'].forEach(ev => dropEl.addEventListener(ev, e => { e.preventDefault(); dropEl.classList.add('is-over'); }));
        ['dragleave', 'drop'].forEach(ev => dropEl.addEventListener(ev, e => { e.preventDefault(); dropEl.classList.remove('is-over'); }));
        dropEl.addEventListener('drop', e => { if (e.dataTransfer && e.dataTransfer.files.length) addFiles(Array.from(e.dataTransfer.files)); });
        document.addEventListener('paste', (e) => {
            if (current !== 'detalhes' || container.offsetParent === null) return;
            const imgs = Array.from((e.clipboardData && e.clipboardData.items) || [])
                .filter(it => it.kind === 'file' && it.type.startsWith('image/'))
                .map(it => {
                    const f = it.getAsFile();
                    return new File([f], `print-${Date.now()}.${(f.type.split('/')[1] || 'png')}`, { type: f.type });
                });
            if (!imgs.length) return;
            e.preventDefault();
            addFiles(imgs);
            dropEl.classList.add('is-flash');
            setTimeout(() => dropEl.classList.remove('is-flash'), 500);
        });

        // ── envio ──
        async function submit() {
            if (!selectedCategory) { show('categoria'); return; }
            if (!detalhesValid()) { show('detalhes'); updateDetalhesBtn(); return; }
            let name, email;
            if (cfg.captureIdentity) {
                name = nameEl.value.trim();
                email = emailEl.value.trim();
                if (!name) { show('identidade'); nameEl.focus(); return; }
                if (!email || !email.includes('@')) { show('identidade'); emailEl.focus(); return; }
            }
            const lastStep = cfg.captureIdentity ? 'identidade' : 'detalhes';
            const submitBtn = container.querySelector(`[data-step="${lastStep}"] [data-act="submit"]`);
            const prevTxt = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando…';

            const fd = new FormData();
            fd.append('title', titleEl.value.trim());
            fd.append('category', selectedCategory);
            fd.append('requesterCompany', companyEl.value);
            const desc = descEl.value.trim();
            if (desc) fd.append('description', desc);
            if (cfg.captureIdentity) {
                fd.append('requesterName', name);
                fd.append('requesterEmail', email);
            }
            files.forEach(f => fd.append('attachments', f));

            try {
                const headers = cfg.getHeaders ? cfg.getHeaders() : {};
                const res = await fetch(cfg.submitUrl, { method: 'POST', headers, body: fd });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || 'Erro ao enviar');
                }
                const result = await res.json().catch(() => ({}));
                show('sucesso');
                if (cfg.onSuccess) cfg.onSuccess(result);
            } catch (err) {
                submitBtn.disabled = false;
                submitBtn.textContent = prevTxt;
                alert((err && err.message) || 'Erro ao enviar. Tente novamente.');
            }
        }

        // ── reset ──
        function reset() {
            selectedCategory = null;
            files = [];
            qa('.tkf-cat').forEach(x => x.classList.remove('is-sel'));
            container.querySelector('[data-step="categoria"] .tkf-btn').disabled = true;
            companyEl.value = (prefill.company && COMPANIES.includes(prefill.company)) ? prefill.company : '';
            titleEl.value = '';
            descEl.value = '';
            if (nameEl) nameEl.value = prefill.name || '';
            if (emailEl && !prefill.lockEmail) emailEl.value = prefill.email || '';
            qa('[data-act="submit"]').forEach(b => { b.textContent = 'Enviar Chamado'; });
            renderFiles();
            updateDetalhesBtn();
            show('categoria');
        }

        // ── board principal (somente público) ──
        if (cfg.boardInfoUrl) {
            fetch(cfg.boardInfoUrl)
                .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                .then(data => { if (cfg.onBoard) cfg.onBoard(data); })
                .catch(() => {
                    const et = q('.tkf-errtext');
                    if (et) et.textContent = 'Este formulário não está disponível no momento.';
                    show('erro');
                });
        }

        // init
        renderFiles();
        updateDetalhesBtn();
        show('categoria');

        return { reset, show };
    }

    window.TicketForm = { mount, CATEGORIES, COMPANIES };
})();
