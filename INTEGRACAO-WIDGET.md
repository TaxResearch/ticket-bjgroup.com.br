# BJGROUP Suporte — Widget de Tickets (Guia de Integração)

Como colocar o botão **"Solicitar Suporte"** em qualquer site/painel da holding.
O widget abre um modal onde a pessoa abre o chamado; o ticket cai automaticamente
no **Kanban Coletivo** (board principal) que o time de dev acompanha.

> **TL;DR** — cole **uma linha** antes do `</body>`:
> ```html
> <script src="https://ticket.bjgroup.com.br/widget.js" defer></script>
> ```
> Pronto: aparece um botão flutuante "Suporte" no canto da tela. Sem token, sem nada.

---

## 1. Como funciona (visão geral)

1. O `widget.js` injeta um botão flutuante (ou você usa o seu próprio botão).
2. Ao clicar, abre um overlay escuro com um `<iframe>` apontando para
   `https://ticket.bjgroup.com.br/ticket.php?embedded=1`.
3. A pessoa preenche em **3 passos**: Categoria → Detalhes/anexos → Seus dados (nome + e-mail).
4. Ao enviar, o modal fecha sozinho e aparece um toast "Chamado enviado com sucesso!".
5. O ticket aparece no **Kanban Coletivo** do dev, na coluna "A Fazer".

**Fechar o modal:** botão **X** no canto do card, clicar **fora**, ou tecla **ESC**.

---

## 2. Pré-requisito (feito UMA vez pelo time de dev)

O widget sem token entrega no **board principal de tickets**. Por isso, **um** board
precisa estar marcado como principal:

1. Entrar no dashboard: `https://ticket.bjgroup.com.br/views/dashboard.php`
2. Editar um board (ícone de lápis) → ativar **"Board Principal de Tickets"** → salvar.

> Sem isso, o modal abre mas mostra "Link inválido" (a API responde 404 "Nenhum
> board principal de tickets configurado"). É configuração única — não precisa repetir
> por plataforma.

**Teste rápido** (confirma que o board principal existe):
```
GET https://apiticket.bjgroup.com.br/api/boards/public-main
```
Se voltar um JSON com `{"name": "...", ...}`, está tudo pronto.

---

## 3. Onde colocar o código

Cole o `<script>` **uma vez por página**, de preferência **logo antes do `</body>`**.
Em sites com layout/template compartilhado (header/footer incluído em todas as páginas),
coloque no **rodapé comum** — assim vale para o site inteiro de uma vez.

> Use `defer` (ou coloque no fim do body) para não atrasar o carregamento da página.

---

## 4. Modos de uso

### Modo A — Botão flutuante pronto (mais simples)

O widget cria e posiciona o botão sozinho:

```html
<script src="https://ticket.bjgroup.com.br/widget.js"
        data-label="Suporte"
        data-position="bottom-right"
        defer></script>
```

### Modo B — Seu próprio botão (recomendado p/ painéis)

Use `data-no-btn` para **não** criar o botão flutuante e chame o widget a partir de
qualquer elemento seu (link de menu, botão na sidebar, etc.):

```html
<!-- 1) carregue o widget sem o botão flutuante -->
<script src="https://ticket.bjgroup.com.br/widget.js" data-no-btn defer></script>

<!-- 2) qualquer botão/elemento chama BJGWidget.open() -->
<button type="button" onclick="if(window.BJGWidget) BJGWidget.open()">
  Solicitar Suporte
</button>
```

> `window.BJGWidget` só existe depois que o `widget.js` carrega. O `if(window.BJGWidget)`
> evita erro caso alguém clique antes de carregar. API: `BJGWidget.open()` / `BJGWidget.close()`.

---

## 5. Pré-preencher nome e e-mail (opcional, mas recomendado)

Se a plataforma já tem o usuário logado, passe **nome** e **e-mail** para o widget.
Assim a pessoa não digita de novo e — importante — o **e-mail vira a identidade**
do solicitante (fica travado no modal), garantindo o histórico de chamados dela.

```html
<script src="https://ticket.bjgroup.com.br/widget.js"
        data-name="João Vitor"
        data-email="joao@empresa.com.br"
        data-no-btn
        defer></script>
```

- O **e-mail** preenchido fica **read-only** no modal (identidade estável).
- O **nome** fica editável (é só rótulo de exibição).
- Sem `data-email`, a pessoa digita o e-mail no último passo (fluxo aberto).

---

## 6. Tabela de atributos `data-*`

| Atributo | Obrigatório | Padrão | Para que serve |
|---|---|---|---|
| `src` | **sim** | — | `https://ticket.bjgroup.com.br/widget.js` |
| `data-name` | não | vazio | Pré-preenche o nome do solicitante (rótulo) |
| `data-email` | não | vazio | Pré-preenche e **trava** o e-mail (identidade) |
| `data-no-btn` | não | ausente | Não cria o botão flutuante (você usa o seu) |
| `data-label` | não | `Suporte` | Texto do botão flutuante |
| `data-position` | não | `bottom-right` | `bottom-right`, `bottom-left`, `top-right`, `top-left` |
| `data-base-url` | não | origem do script | Só se o front estiver em outro domínio |
| `data-token` | não | — | Direciona para um board público específico (ver §9) |

---

## 7. Exemplos por plataforma

### 7.1 HTML puro / qualquer site
Antes do `</body>`:
```html
<script src="https://ticket.bjgroup.com.br/widget.js" defer></script>
```

### 7.2 PHP com sessão (ex.: painel administrativo)
No `footer.php` (ou template comum), passando os dados do usuário logado:
```php
<script
  src="https://ticket.bjgroup.com.br/widget.js"
  data-name="<?php echo htmlspecialchars($_SESSION['nome_usuario'] ?? '', ENT_QUOTES); ?>"
  data-email="<?php echo htmlspecialchars($_SESSION['email_usuario'] ?? '', ENT_QUOTES); ?>"
  data-no-btn
  defer></script>

<!-- botão no menu/sidebar -->
<button type="button" onclick="if(window.BJGWidget) BJGWidget.open()">Solicitar Suporte</button>
```
> Ajuste os nomes das variáveis de sessão (`$_SESSION[...]`) para os da sua plataforma.

### 7.3 WordPress
No `footer.php` do tema (ou via plugin "insert headers and footers"):
```php
<script src="https://ticket.bjgroup.com.br/widget.js" defer></script>
```
Com usuário logado:
```php
<?php $u = wp_get_current_user(); ?>
<script src="https://ticket.bjgroup.com.br/widget.js"
        data-name="<?php echo esc_attr($u->display_name); ?>"
        data-email="<?php echo esc_attr($u->user_email); ?>"
        defer></script>
```

### 7.4 React / Vue / SPA
O `<script>` no HTML não roda bem em SPA; injete dinamicamente uma vez:
```js
// chame uma vez no boot do app (ex.: App.tsx useEffect [])
useEffect(() => {
  if (document.getElementById('bjg-widget')) return;
  const s = document.createElement('script');
  s.id = 'bjg-widget';
  s.src = 'https://ticket.bjgroup.com.br/widget.js';
  s.setAttribute('data-no-btn', '');
  s.setAttribute('data-name', user.name);
  s.setAttribute('data-email', user.email);
  s.defer = true;
  document.body.appendChild(s);
}, []);

// depois, em qualquer botão:
<button onClick={() => window.BJGWidget?.open()}>Solicitar Suporte</button>
```

---

## 8. Sem widget: link direto para o formulário

Para um link em e-mail/menu (abre o formulário em página inteira, sem iframe):
```
https://ticket.bjgroup.com.br/ticket.php
```
Pré-preenchendo:
```
https://ticket.bjgroup.com.br/ticket.php?name=Jo%C3%A3o&email=joao%40empresa.com.br
```

---

## 9. (Opcional) Board específico por token

Por padrão tudo cai no board principal (kanban coletivo). Se um time quiser um board
**separado**, o dev ativa "Board Público de Tickets" naquele board (gera um `publicToken`)
e a plataforma usa:
```html
<script src="https://ticket.bjgroup.com.br/widget.js"
        data-token="COLE_O_PUBLIC_TOKEN_AQUI" defer></script>
```
> Para a maioria dos casos **não use token** — o board principal já reúne tudo e é o
> recomendado. Como o dev sabe de qual empresa veio: pelo **e-mail/nome** do solicitante
> (e a categoria do chamado), que aparecem no card e no modal do dev.

---

## 10. Solução de problemas

| Sintoma | Causa provável | Solução |
|---|---|---|
| Botão "Solicitar Suporte" não faz nada | `widget.js` não carregou / cache antigo | Confirme o `<script>` no HTML e dê hard refresh (Ctrl+Shift+R) |
| Modal abre mas mostra "Link inválido" | Nenhum board marcado como principal | Faça o passo §2 (Board Principal de Tickets) |
| Modal abre em branco | `data-base-url` errado ou front fora do ar | Teste abrir `https://ticket.bjgroup.com.br/ticket.php` direto |
| `BJGWidget is not defined` | Clicou antes do script carregar | Use `if(window.BJGWidget) BJGWidget.open()` e coloque o `<script>` antes do botão |
| Anexo não aparece pro dev | — | Os anexos vão pra modal do ticket no dashboard (aba de info do ticket) |

---

## 11. Atualizações

O `widget.js` é **hospedado em um lugar só** (`https://ticket.bjgroup.com.br/widget.js`).
Qualquer melhoria no widget vale para **todas as plataformas** automaticamente — elas
não precisam atualizar nada, só manter a linha do `<script>` apontando para essa URL.

> Dica de cache: navegadores podem guardar o `widget.js`. Se precisar forçar atualização
> imediata em todos, versione a URL no `<script>` (ex.: `widget.js?v=2`).
