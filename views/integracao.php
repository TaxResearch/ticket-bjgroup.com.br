<?php
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    redirect(url('index.php'));
}

// Guia de integração é ferramenta interna do time de dev. Usuário comum vai pro portal.
if (!getIsDevTeam()) {
    redirect(url('views/portal.php'));
}

// URL canônica do front (é o que os devs colam nas plataformas deles — sempre produção).
$WIDGET_BASE = 'https://ticket.bjgroup.com.br';
$API_PUBLIC  = 'https://apiticket.bjgroup.com.br/api';

$pageTitle = 'Integração do Widget - BJGROUP Suporte';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
    .intg-wrap { width: 100%; max-width: 56rem; margin: 0 auto; }
    .intg-card { background: #141414; border: 1px solid #2a2a2a; border-radius: 16px; }
    .intg-code { position: relative; background: #0d0d0d; border: 1px solid #2a2a2a; border-radius: 10px; }
    .intg-code pre { margin: 0; padding: 16px 16px; overflow-x: auto; }
    .intg-code code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12.5px; line-height: 1.6; color: #e0e0e0; white-space: pre; }
    .intg-code::-webkit-scrollbar, .intg-code pre::-webkit-scrollbar { height: 8px; }
    .intg-code pre::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 8px; }
    .copy-btn { position: absolute; top: 8px; right: 8px; z-index: 2; font-size: 11px; font-weight: 600; color: #888888;
        background: #1c1c1c; border: 1px solid #2a2a2a; border-radius: 6px; padding: 4px 10px; cursor: pointer; transition: all .15s; }
    .copy-btn:hover { color: #fff; border-color: #555555; background: #222; }
    .copy-btn.copied { color: #86efac; border-color: #2f5f3f; }
    .inline-code { background: #1c1c1c; color: #e0e0e0; border: 1px solid #2a2a2a; padding: 1px 6px; border-radius: 5px; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12.5px; }
    .step-num { flex: 0 0 auto; width: 26px; height: 26px; border-radius: 50%; background: #1c1c1c; border: 1px solid #2a2a2a; color: #fff; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .intg-toc a { color: #888888; transition: color .15s; }
    .intg-toc a:hover { color: #fff; }
    .intg-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .intg-table th, .intg-table td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #2a2a2a; vertical-align: top; }
    .intg-table th { color: #888888; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
    .intg-table td { color: #cccccc; }
    html { scroll-behavior: smooth; }
    .anchor { scroll-margin-top: 24px; }
    .badge-ok { display: inline-block; font-size: 10.5px; font-weight: 700; color: #86efac; background: rgba(134,239,172,.08); border: 1px solid rgba(134,239,172,.25); padding: 2px 8px; border-radius: 999px; }
</style>

<!-- Top bar -->
<header class="w-full max-w-7xl flex items-center justify-between mb-8 sm:mb-10 px-4">
    <a href="<?php echo url('views/dashboard.php'); ?>" class="flex items-center flex-shrink-0">
        <img src="<?php echo url('img/logo-white.png'); ?>" alt="BJGROUP Suporte" class="h-9 sm:h-10" />
    </a>
    <a href="<?php echo url('views/dashboard.php'); ?>" class="flex items-center gap-2 text-sm text-[#888888] hover:text-white bg-[#141414] border border-[#2a2a2a] hover:border-[#404040] px-4 py-2 rounded-lg transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Voltar ao Dashboard
    </a>
</header>

<main class="intg-wrap px-4 pb-20">

    <!-- Hero -->
    <div class="mb-8">
        <span class="inline-flex items-center gap-2 text-xs font-semibold text-[#888888] uppercase tracking-wider mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            Guia de Integração
        </span>
        <h1 class="text-3xl sm:text-4xl font-bold text-white mb-3">Widget de Tickets nas plataformas da holding</h1>
        <p class="text-[#888888] text-base leading-relaxed">
            Como colocar o botão <strong class="text-white">"Solicitar Suporte"</strong> em qualquer site ou painel.
            O widget abre um modal onde a pessoa abre o chamado — e o ticket cai automaticamente no
            <strong class="text-white">Kanban Coletivo</strong> que o time de dev acompanha.
        </p>
    </div>

    <!-- TL;DR -->
    <div class="intg-card p-5 sm:p-6 mb-8">
        <div class="flex items-center gap-2 mb-3">
            <span class="badge-ok">TL;DR</span>
            <span class="text-sm text-[#888888]">Cole <strong class="text-white">uma linha</strong> antes do <span class="inline-code">&lt;/body&gt;</span> — sem token, sem nada:</span>
        </div>
        <div class="intg-code">
            <button class="copy-btn" type="button">Copiar</button>
            <pre><code>&lt;script src="<?php echo $WIDGET_BASE; ?>/widget.js" defer&gt;&lt;/script&gt;</code></pre>
        </div>
        <p class="text-xs text-[#555555] mt-3">Pronto: aparece um botão flutuante "Suporte" no canto da tela.</p>
    </div>

    <!-- Sumário -->
    <div class="intg-card p-5 sm:p-6 mb-10 intg-toc">
        <h2 class="text-xs font-semibold text-[#888888] uppercase tracking-wider mb-3">Sumário</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 text-sm">
            <a href="#como-funciona">1. Como funciona</a>
            <a href="#pre-requisito">2. Pré-requisito (1x pelo time)</a>
            <a href="#onde-colocar">3. Onde colocar o código</a>
            <a href="#modos">4. Modos de uso</a>
            <a href="#prefill">5. Pré-preencher nome e e-mail</a>
            <a href="#atributos">6. Atributos <span class="inline-code" style="font-size:11px">data-*</span></a>
            <a href="#exemplos">7. Exemplos por plataforma</a>
            <a href="#link-direto">8. Link direto (sem widget)</a>
            <a href="#token">9. Board específico por token</a>
            <a href="#problemas">10. Solução de problemas</a>
        </div>
    </div>

    <!-- 1. Como funciona -->
    <section id="como-funciona" class="anchor mb-10">
        <h2 class="text-xl font-bold text-white mb-4">1. Como funciona</h2>
        <div class="intg-card p-5 sm:p-6 space-y-4">
            <div class="flex gap-3"><span class="step-num">1</span><p class="text-sm text-[#cccccc] pt-0.5">O <span class="inline-code">widget.js</span> injeta um botão flutuante (ou você usa o seu próprio botão).</p></div>
            <div class="flex gap-3"><span class="step-num">2</span><p class="text-sm text-[#cccccc] pt-0.5">Ao clicar, abre um overlay escuro com o formulário (<span class="inline-code">ticket.php?embedded=1</span>) num iframe.</p></div>
            <div class="flex gap-3"><span class="step-num">3</span><p class="text-sm text-[#cccccc] pt-0.5">A pessoa preenche em <strong class="text-white">3 passos</strong>: Categoria → Detalhes/anexos → Seus dados (nome + e-mail).</p></div>
            <div class="flex gap-3"><span class="step-num">4</span><p class="text-sm text-[#cccccc] pt-0.5">Ao enviar, o modal fecha sozinho e aparece um toast <span class="inline-code">Chamado enviado com sucesso!</span></p></div>
            <div class="flex gap-3"><span class="step-num">5</span><p class="text-sm text-[#cccccc] pt-0.5">O ticket aparece no <strong class="text-white">Kanban Coletivo</strong>, na coluna "A Fazer".</p></div>
            <div class="pt-1 border-t border-[#2a2a2a]">
                <p class="text-xs text-[#888888]"><strong class="text-white">Fechar o modal:</strong> botão <span class="inline-code">X</span> no card, clicar <strong>fora</strong>, ou tecla <span class="inline-code">ESC</span>.</p>
            </div>
        </div>
    </section>

    <!-- 2. Pré-requisito -->
    <section id="pre-requisito" class="anchor mb-10">
        <h2 class="text-xl font-bold text-white mb-4">2. Pré-requisito <span class="text-sm font-normal text-[#555555]">(feito UMA vez pelo time de dev)</span></h2>
        <div class="intg-card p-5 sm:p-6">
            <p class="text-sm text-[#cccccc] mb-4">O widget sem token entrega no <strong class="text-white">board principal de tickets</strong>. Por isso, <strong class="text-white">um</strong> board precisa estar marcado como principal:</p>
            <div class="space-y-3 mb-4">
                <div class="flex gap-3"><span class="step-num">1</span><p class="text-sm text-[#cccccc] pt-0.5">Entrar no <a href="<?php echo url('views/dashboard.php'); ?>" class="text-white underline hover:text-[#888888]">Dashboard</a>.</p></div>
                <div class="flex gap-3"><span class="step-num">2</span><p class="text-sm text-[#cccccc] pt-0.5">Editar um board (ícone de lápis) → ativar <strong class="text-white">"Board Principal de Tickets"</strong> → salvar.</p></div>
            </div>
            <div class="p-3 rounded-lg bg-[#1c1c1c] border-l-2 border-[#fdba74] mb-4">
                <p class="text-xs text-[#cccccc]">⚠️ Sem isso, o modal abre mas mostra <span class="inline-code">Link inválido</span> (a API responde 404 "Nenhum board principal configurado"). É config única — não repete por plataforma.</p>
            </div>
            <p class="text-xs text-[#888888] mb-2"><strong class="text-white">Teste rápido</strong> — se voltar um JSON com <span class="inline-code">{"name": "..."}</span>, está pronto:</p>
            <div class="intg-code">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code>GET <?php echo $API_PUBLIC; ?>/boards/public-main</code></pre>
            </div>
        </div>
    </section>

    <!-- 3. Onde colocar -->
    <section id="onde-colocar" class="anchor mb-10">
        <h2 class="text-xl font-bold text-white mb-4">3. Onde colocar o código</h2>
        <div class="intg-card p-5 sm:p-6">
            <p class="text-sm text-[#cccccc] leading-relaxed">Cole o <span class="inline-code">&lt;script&gt;</span> <strong class="text-white">uma vez por página</strong>, de preferência <strong class="text-white">logo antes do <span class="inline-code">&lt;/body&gt;</span></strong>. Em sites com layout/template compartilhado (header/footer comum), coloque no <strong class="text-white">rodapé comum</strong> — assim vale para o site inteiro de uma vez.</p>
            <p class="text-xs text-[#555555] mt-3">💡 Use <span class="inline-code">defer</span> (ou coloque no fim do body) para não atrasar o carregamento da página.</p>
        </div>
    </section>

    <!-- 4. Modos de uso -->
    <section id="modos" class="anchor mb-10">
        <h2 class="text-xl font-bold text-white mb-4">4. Modos de uso</h2>

        <div class="intg-card p-5 sm:p-6 mb-4">
            <h3 class="text-base font-semibold text-white mb-1">Modo A — Botão flutuante pronto <span class="text-xs font-normal text-[#555555]">(mais simples)</span></h3>
            <p class="text-sm text-[#888888] mb-3">O widget cria e posiciona o botão sozinho.</p>
            <div class="intg-code">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code>&lt;script src="<?php echo $WIDGET_BASE; ?>/widget.js"
        data-label="Suporte"
        data-position="bottom-right"
        defer&gt;&lt;/script&gt;</code></pre>
            </div>
        </div>

        <div class="intg-card p-5 sm:p-6">
            <h3 class="text-base font-semibold text-white mb-1">Modo B — Seu próprio botão <span class="badge-ok">recomendado p/ painéis</span></h3>
            <p class="text-sm text-[#888888] mb-3">Use <span class="inline-code">data-no-btn</span> para <strong class="text-white">não</strong> criar o botão flutuante e chame o widget a partir de qualquer elemento seu (link de menu, botão na sidebar, etc.).</p>
            <div class="intg-code">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code>&lt;!-- 1) carregue o widget sem o botão flutuante --&gt;
&lt;script src="<?php echo $WIDGET_BASE; ?>/widget.js" data-no-btn defer&gt;&lt;/script&gt;

&lt;!-- 2) qualquer botão/elemento chama BJGWidget.open() --&gt;
&lt;button type="button" onclick="if(window.BJGWidget) BJGWidget.open()"&gt;
  Solicitar Suporte
&lt;/button&gt;</code></pre>
            </div>
            <p class="text-xs text-[#555555] mt-3"><span class="inline-code">window.BJGWidget</span> só existe depois que o <span class="inline-code">widget.js</span> carrega. O <span class="inline-code">if(window.BJGWidget)</span> evita erro se clicarem antes. API: <span class="inline-code">BJGWidget.open()</span> / <span class="inline-code">BJGWidget.close()</span>.</p>
        </div>
    </section>

    <!-- 5. Prefill -->
    <section id="prefill" class="anchor mb-10">
        <h2 class="text-xl font-bold text-white mb-4">5. Pré-preencher nome e e-mail <span class="text-sm font-normal text-[#555555]">(opcional, mas recomendado)</span></h2>
        <div class="intg-card p-5 sm:p-6">
            <p class="text-sm text-[#cccccc] mb-3">Se a plataforma já tem o usuário logado, passe <strong class="text-white">nome</strong> e <strong class="text-white">e-mail</strong>. Assim a pessoa não digita de novo e — importante — o <strong class="text-white">e-mail vira a identidade</strong> do solicitante (fica travado no modal), garantindo o histórico de chamados dela.</p>
            <div class="intg-code">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code>&lt;script src="<?php echo $WIDGET_BASE; ?>/widget.js"
        data-name="João Vitor"
        data-email="joao@empresa.com.br"
        data-no-btn
        defer&gt;&lt;/script&gt;</code></pre>
            </div>
            <ul class="mt-3 space-y-1 text-xs text-[#888888]">
                <li>• O <strong class="text-white">e-mail</strong> preenchido fica <span class="inline-code">read-only</span> no modal (identidade estável).</li>
                <li>• O <strong class="text-white">nome</strong> fica editável (é só rótulo de exibição).</li>
                <li>• Sem <span class="inline-code">data-email</span>, a pessoa digita o e-mail no último passo (fluxo aberto).</li>
            </ul>
        </div>
    </section>

    <!-- 6. Atributos data-* -->
    <section id="atributos" class="anchor mb-10">
        <h2 class="text-xl font-bold text-white mb-4">6. Tabela de atributos <span class="inline-code">data-*</span></h2>
        <div class="intg-card overflow-x-auto">
            <table class="intg-table">
                <thead>
                    <tr><th>Atributo</th><th>Obrig.</th><th>Padrão</th><th>Para que serve</th></tr>
                </thead>
                <tbody>
                    <tr><td><span class="inline-code">src</span></td><td><span class="text-white">sim</span></td><td>—</td><td><?php echo $WIDGET_BASE; ?>/widget.js</td></tr>
                    <tr><td><span class="inline-code">data-name</span></td><td>não</td><td>vazio</td><td>Pré-preenche o nome do solicitante (rótulo)</td></tr>
                    <tr><td><span class="inline-code">data-email</span></td><td>não</td><td>vazio</td><td>Pré-preenche e <strong class="text-white">trava</strong> o e-mail (identidade)</td></tr>
                    <tr><td><span class="inline-code">data-no-btn</span></td><td>não</td><td>ausente</td><td>Não cria o botão flutuante (você usa o seu)</td></tr>
                    <tr><td><span class="inline-code">data-label</span></td><td>não</td><td>Suporte</td><td>Texto do botão flutuante</td></tr>
                    <tr><td><span class="inline-code">data-position</span></td><td>não</td><td>bottom-right</td><td><span class="inline-code">bottom-right</span>, <span class="inline-code">bottom-left</span>, <span class="inline-code">top-right</span>, <span class="inline-code">top-left</span></td></tr>
                    <tr><td><span class="inline-code">data-base-url</span></td><td>não</td><td>origem do script</td><td>Só se o front estiver em outro domínio</td></tr>
                    <tr><td><span class="inline-code">data-token</span></td><td>não</td><td>—</td><td>Direciona para um board público específico (ver §9)</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- 7. Exemplos -->
    <section id="exemplos" class="anchor mb-10">
        <h2 class="text-xl font-bold text-white mb-4">7. Exemplos por plataforma</h2>

        <div class="intg-card p-5 sm:p-6 mb-4">
            <h3 class="text-base font-semibold text-white mb-3">7.1 — HTML puro / qualquer site</h3>
            <div class="intg-code">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code>&lt;script src="<?php echo $WIDGET_BASE; ?>/widget.js" defer&gt;&lt;/script&gt;</code></pre>
            </div>
        </div>

        <div class="intg-card p-5 sm:p-6 mb-4">
            <h3 class="text-base font-semibold text-white mb-1">7.2 — PHP com sessão <span class="text-xs font-normal text-[#555555]">(painel administrativo)</span></h3>
            <p class="text-sm text-[#888888] mb-3">No <span class="inline-code">footer.php</span> (template comum), passando os dados do usuário logado:</p>
            <div class="intg-code">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code>&lt;script
  src="<?php echo $WIDGET_BASE; ?>/widget.js"
  data-name="&lt;?php echo htmlspecialchars($_SESSION['nome_usuario'] ?? '', ENT_QUOTES); ?&gt;"
  data-email="&lt;?php echo htmlspecialchars($_SESSION['email_usuario'] ?? '', ENT_QUOTES); ?&gt;"
  data-no-btn
  defer&gt;&lt;/script&gt;

&lt;!-- botão no menu/sidebar --&gt;
&lt;button type="button" onclick="if(window.BJGWidget) BJGWidget.open()"&gt;Solicitar Suporte&lt;/button&gt;</code></pre>
            </div>
            <p class="text-xs text-[#555555] mt-3">Ajuste os nomes das variáveis de sessão (<span class="inline-code">$_SESSION[...]</span>) para os da sua plataforma.</p>
        </div>

        <div class="intg-card p-5 sm:p-6 mb-4">
            <h3 class="text-base font-semibold text-white mb-1">7.3 — WordPress</h3>
            <p class="text-sm text-[#888888] mb-3">No <span class="inline-code">footer.php</span> do tema (ou via plugin "insert headers and footers"), com usuário logado:</p>
            <div class="intg-code">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code>&lt;?php $u = wp_get_current_user(); ?&gt;
&lt;script src="<?php echo $WIDGET_BASE; ?>/widget.js"
        data-name="&lt;?php echo esc_attr($u-&gt;display_name); ?&gt;"
        data-email="&lt;?php echo esc_attr($u-&gt;user_email); ?&gt;"
        defer&gt;&lt;/script&gt;</code></pre>
            </div>
        </div>

        <div class="intg-card p-5 sm:p-6">
            <h3 class="text-base font-semibold text-white mb-1">7.4 — React / Vue / SPA</h3>
            <p class="text-sm text-[#888888] mb-3">O <span class="inline-code">&lt;script&gt;</span> no HTML não roda bem em SPA; injete dinamicamente uma vez:</p>
            <div class="intg-code">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code>// chame uma vez no boot do app (ex.: App.tsx useEffect [])
useEffect(() =&gt; {
  if (document.getElementById('bjg-widget')) return;
  const s = document.createElement('script');
  s.id = 'bjg-widget';
  s.src = '<?php echo $WIDGET_BASE; ?>/widget.js';
  s.setAttribute('data-no-btn', '');
  s.setAttribute('data-name', user.name);
  s.setAttribute('data-email', user.email);
  s.defer = true;
  document.body.appendChild(s);
}, []);

// depois, em qualquer botão:
&lt;button onClick={() =&gt; window.BJGWidget?.open()}&gt;Solicitar Suporte&lt;/button&gt;</code></pre>
            </div>
        </div>
    </section>

    <!-- 8. Link direto -->
    <section id="link-direto" class="anchor mb-10">
        <h2 class="text-xl font-bold text-white mb-4">8. Sem widget: link direto para o formulário</h2>
        <div class="intg-card p-5 sm:p-6">
            <p class="text-sm text-[#cccccc] mb-3">Para um link em e-mail/menu (abre o formulário em página inteira, sem iframe):</p>
            <div class="intg-code mb-4">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code><?php echo $WIDGET_BASE; ?>/ticket.php</code></pre>
            </div>
            <p class="text-sm text-[#888888] mb-3">Pré-preenchendo nome e e-mail:</p>
            <div class="intg-code">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code><?php echo $WIDGET_BASE; ?>/ticket.php?name=Jo%C3%A3o&amp;email=joao%40empresa.com.br</code></pre>
            </div>
        </div>
    </section>

    <!-- 9. Token -->
    <section id="token" class="anchor mb-10">
        <h2 class="text-xl font-bold text-white mb-4">9. <span class="text-[#888888] font-normal text-base">(Opcional)</span> Board específico por token</h2>
        <div class="intg-card p-5 sm:p-6">
            <p class="text-sm text-[#cccccc] mb-3">Por padrão tudo cai no board principal (kanban coletivo). Se um time quiser um board <strong class="text-white">separado</strong>, o dev ativa "Board Público de Tickets" naquele board (gera um <span class="inline-code">publicToken</span>) e a plataforma usa:</p>
            <div class="intg-code">
                <button class="copy-btn" type="button">Copiar</button>
                <pre><code>&lt;script src="<?php echo $WIDGET_BASE; ?>/widget.js"
        data-token="COLE_O_PUBLIC_TOKEN_AQUI" defer&gt;&lt;/script&gt;</code></pre>
            </div>
            <p class="text-xs text-[#555555] mt-3">Para a maioria dos casos <strong class="text-[#888888]">não use token</strong> — o board principal já reúne tudo e é o recomendado. O dev sabe de qual empresa veio pelo e-mail/nome do solicitante e a categoria do chamado.</p>
        </div>
    </section>

    <!-- 10. Problemas -->
    <section id="problemas" class="anchor mb-10">
        <h2 class="text-xl font-bold text-white mb-4">10. Solução de problemas</h2>
        <div class="intg-card overflow-x-auto">
            <table class="intg-table">
                <thead><tr><th>Sintoma</th><th>Causa provável</th><th>Solução</th></tr></thead>
                <tbody>
                    <tr><td>Botão não faz nada</td><td><span class="inline-code">widget.js</span> não carregou / cache antigo</td><td>Confirme o <span class="inline-code">&lt;script&gt;</span> e dê hard refresh (Ctrl+Shift+R)</td></tr>
                    <tr><td>Modal mostra "Link inválido"</td><td>Nenhum board marcado como principal</td><td>Faça o passo §2 (Board Principal de Tickets)</td></tr>
                    <tr><td>Modal abre em branco</td><td><span class="inline-code">data-base-url</span> errado ou front fora do ar</td><td>Teste abrir <span class="inline-code"><?php echo $WIDGET_BASE; ?>/ticket.php</span> direto</td></tr>
                    <tr><td><span class="inline-code">BJGWidget is not defined</span></td><td>Clicou antes do script carregar</td><td>Use <span class="inline-code">if(window.BJGWidget)</span> e coloque o <span class="inline-code">&lt;script&gt;</span> antes do botão</td></tr>
                    <tr><td>Anexo não aparece pro dev</td><td>—</td><td>Os anexos vão pra modal do ticket no dashboard (aba de info)</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Atualizações -->
    <div class="intg-card p-5 sm:p-6 border-l-2 border-[#86efac]">
        <h3 class="text-base font-semibold text-white mb-2">🔄 Atualizações</h3>
        <p class="text-sm text-[#cccccc] leading-relaxed">O <span class="inline-code">widget.js</span> é hospedado em um lugar só (<span class="inline-code"><?php echo $WIDGET_BASE; ?>/widget.js</span>). Qualquer melhoria vale para <strong class="text-white">todas as plataformas</strong> automaticamente — elas não precisam atualizar nada, só manter a linha do <span class="inline-code">&lt;script&gt;</span> apontando para essa URL.</p>
        <p class="text-xs text-[#555555] mt-3">💡 Cache: se precisar forçar atualização imediata em todos, versione a URL (ex.: <span class="inline-code">widget.js?v=2</span>).</p>
    </div>

</main>

<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.copy-btn');
    if (!btn) return;
    const code = btn.parentElement.querySelector('code');
    if (!code) return;
    const text = code.innerText;
    const done = () => {
        const original = btn.textContent;
        btn.textContent = 'Copiado!';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = original; btn.classList.remove('copied'); }, 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
    } else {
        fallbackCopy(text, done);
    }
});
function fallbackCopy(text, done) {
    const ta = document.createElement('textarea');
    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); done(); } catch (err) {}
    document.body.removeChild(ta);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
