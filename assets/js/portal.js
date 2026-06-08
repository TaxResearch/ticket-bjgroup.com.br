/* BJGROUP Suporte - Portal do Funcionário */

let currentUserData = null;
let portalForm = null;

document.addEventListener('DOMContentLoaded', async function () {
    const token = DevDeck.getAuthToken();
    if (!token) {
        window.location.href = BASE_PATH + '/index.php';
        return;
    }

    // Carregar dados do usuário
    try {
        const user = await DevDeck.fetchApi('/user/me');
        if (user) {
            currentUserData = user;
            DevDeck.setUserData(user.email, user.name, user.id);
            const firstName = user.name.split(' ')[0];
            const letter = user.name.charAt(0).toUpperCase();
            document.getElementById('portal-welcome').textContent = `Olá, ${firstName}!`;
            document.getElementById('portal-user-name').textContent =
                user.company ? `${user.name} · ${user.company}` : user.name;
            document.getElementById('portal-user-name').classList.remove('hidden');
            document.getElementById('portal-avatar-letter').textContent = letter;
        }
    } catch (e) {
        console.error('Erro ao carregar usuário:', e);
    }

    // User menu dropdown
    const userDropdown = document.getElementById('portal-user-dropdown');
    document.getElementById('portal-user-btn').addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
    });
    document.addEventListener('click', () => userDropdown.classList.add('hidden'));

    // Logout
    document.getElementById('portal-logout-btn').addEventListener('click', async () => {
        await fetch(BASE_PATH + '/api/logout.php', { method: 'POST', body: 'action=logout' });
        DevDeck.clearAuthData();
        window.location.href = BASE_PATH + '/index.php';
    });

    // Profile modal
    document.getElementById('portal-profile-btn').addEventListener('click', () => {
        userDropdown.classList.add('hidden');
        openProfileModal();
    });
    document.getElementById('profile-backdrop').addEventListener('click', closeProfileModal);
    document.getElementById('profile-close-btn').addEventListener('click', closeProfileModal);
    document.getElementById('profile-cancel-btn').addEventListener('click', closeProfileModal);
    document.getElementById('profile-save-btn').addEventListener('click', saveProfile);

    // Alert modal
    document.getElementById('portal-alert-ok').addEventListener('click', () => {
        document.getElementById('portal-alert-modal').classList.add('hidden');
    });

    // Ticket detail modal
    document.getElementById('detail-close-btn').addEventListener('click', closeTicketDetail);
    document.getElementById('detail-close-footer').addEventListener('click', closeTicketDetail);
    document.getElementById('detail-backdrop').addEventListener('click', closeTicketDetail);

    // Abrir modal
    document.getElementById('open-ticket-btn').addEventListener('click', () => openTicketModal());

    // Fechar modal
    document.getElementById('modal-close-btn').addEventListener('click', closeTicketModal);
    document.getElementById('modal-backdrop').addEventListener('click', closeTicketModal);

    // Formulário de ticket compartilhado (mesmo do widget público — fonte única dos campos).
    mountTicketForm();

    // Carregar tickets
    await loadMyTickets();
});

function openTicketModal() {
    if (portalForm) portalForm.reset();
    document.getElementById('ticket-modal').classList.remove('hidden');
    const inner = document.getElementById('ticket-modal-inner');
    if (inner) {
        inner.style.animation = 'none';
        void inner.offsetWidth;
        inner.style.animation = 'scaleIn 0.25s cubic-bezier(0.22, 1, 0.36, 1) both';
    }
    document.body.style.overflow = 'hidden';
}

function closeTicketModal() {
    document.getElementById('ticket-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Monta o formulário de ticket compartilhado (mesmo do widget público) no modal do portal.
// Modo autenticado: sem passo de identidade (vem da sessão), POST em /tasks/employee-submit.
function mountTicketForm() {
    const root = document.getElementById('tkf-portal-root');
    if (!root || typeof TicketForm === 'undefined') return;
    portalForm = TicketForm.mount(root, {
        submitUrl: API_BASE_URL + '/tasks/employee-submit',
        captureIdentity: false,
        getHeaders: () => ({ 'Authorization': 'Bearer ' + DevDeck.getAuthToken() }),
        prefill: { company: currentUserData ? currentUserData.company : '' },
        onSuccess: () => loadMyTickets(),
    });
}

async function loadMyTickets() {
    const loading = document.getElementById('tickets-loading');
    const empty = document.getElementById('tickets-empty');
    const table = document.getElementById('tickets-table');
    const list = document.getElementById('tickets-list');
    const count = document.getElementById('tickets-count');

    loading.classList.remove('hidden');
    empty.classList.add('hidden');
    table.classList.add('hidden');

    try {
        const tickets = await DevDeck.fetchApi('/tasks/my-tickets');

        loading.classList.add('hidden');

        if (!tickets || tickets.length === 0) {
            empty.classList.remove('hidden');
            count.textContent = '';
            return;
        }

        count.textContent = `${tickets.length} ticket${tickets.length !== 1 ? 's' : ''}`;
        list.innerHTML = '';

        tickets.forEach((ticket, index) => {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 px-6 py-4 border-b border-[#2a2a2a]/40 hover:bg-white/[0.02] transition-colors items-center stagger-item';
            row.style.setProperty('--i', index);

            const date = new Date(ticket.createdAt).toLocaleDateString('pt-BR', {
                day: '2-digit', month: '2-digit', year: '2-digit'
            });

            const statusLabel = { TODO: 'Na Fila', DOING: 'Em Andamento', DONE: 'Concluído' }[ticket.status] || ticket.status;
            const assignedName = ticket.assignedUser?.name
                ? escapeHtml(ticket.assignedUser.name)
                : '<span class="text-[#444444] italic">Aguardando</span>';

            row.innerHTML = `
                <div class="col-span-1 text-[#555555] text-xs font-mono">#${ticket.id}</div>
                <div class="col-span-3 text-[#e0e0e0] text-sm font-medium truncate pr-2">${escapeHtml(ticket.title)}</div>
                <div class="col-span-2 text-[#888888] text-xs truncate">${escapeHtml(ticket.category || '-')}</div>
                <div class="col-span-2">
                    <span class="status-badge status-${ticket.status}">${statusLabel}</span>
                </div>
                <div class="col-span-2 text-[#888888] text-xs truncate">${assignedName}</div>
                <div class="col-span-1 text-[#555555] text-xs">${date}</div>
                <div class="col-span-1 flex justify-end">
                    <button class="ticket-view-btn w-7 h-7 flex items-center justify-center rounded-lg text-[#555555] hover:text-white hover:bg-[#2a2a2a] transition-colors" title="Ver detalhes">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            `;

            row.querySelector('.ticket-view-btn').addEventListener('click', () => openTicketDetail(ticket));
            list.appendChild(row);
        });

        table.classList.remove('hidden');

    } catch (err) {
        loading.classList.add('hidden');
        empty.classList.remove('hidden');
        console.error('Erro ao carregar tickets:', err);
    }
}

function openProfileModal() {
    if (!currentUserData) return;
    const letter = currentUserData.name.charAt(0).toUpperCase();
    document.getElementById('profile-avatar-big').textContent = letter;
    document.getElementById('profile-display-name').textContent = currentUserData.name;
    document.getElementById('profile-display-company').textContent = currentUserData.company || '';
    document.getElementById('profile-name-input').value = currentUserData.name;
    document.getElementById('profile-email-display').textContent = currentUserData.email;
    document.getElementById('profile-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('profile-name-input').focus(), 100);
}

function closeProfileModal() {
    document.getElementById('profile-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function saveProfile() {
    const name = document.getElementById('profile-name-input').value.trim();
    if (!name) {
        showPortalAlert('O nome não pode estar vazio.');
        return;
    }

    const saveBtn = document.getElementById('profile-save-btn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Salvando...';

    try {
        const updated = await DevDeck.fetchApi('/user/settings', {
            method: 'PATCH',
            body: JSON.stringify({ name })
        });

        currentUserData = { ...currentUserData, name: updated?.name || name };
        const firstName = currentUserData.name.split(' ')[0];
        const letter = currentUserData.name.charAt(0).toUpperCase();
        document.getElementById('portal-welcome').textContent = `Olá, ${firstName}!`;
        document.getElementById('portal-user-name').textContent =
            currentUserData.company
                ? `${currentUserData.name} · ${currentUserData.company}`
                : currentUserData.name;
        document.getElementById('portal-avatar-letter').textContent = letter;
        DevDeck.setUserData(currentUserData.email, currentUserData.name, currentUserData.id);
        closeProfileModal();
    } catch (err) {
        showPortalAlert('Erro ao salvar: ' + (err.message || 'Tente novamente.'));
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Salvar';
    }
}

function showPortalAlert(msg) {
    document.getElementById('portal-alert-message').textContent = msg;
    document.getElementById('portal-alert-modal').classList.remove('hidden');
}

function openTicketDetail(ticket) {
    const statusLabels = { TODO: 'Na Fila', DOING: 'Em Andamento', DONE: 'Concluído' };

    document.getElementById('detail-id').textContent = '#' + ticket.id;
    document.getElementById('detail-title').textContent = ticket.title;
    document.getElementById('detail-category').textContent = ticket.category || '';
    document.getElementById('detail-date').textContent = new Date(ticket.createdAt).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });

    const assignedEl = document.getElementById('detail-assigned');
    if (ticket.assignedUser?.name) {
        assignedEl.textContent = ticket.assignedUser.name;
        assignedEl.style.color = '';
    } else {
        assignedEl.innerHTML = '<span style="color:#555555;font-style:italic">Aguardando atendimento</span>';
    }

    const dueDateEl = document.getElementById('detail-duedate');
    if (ticket.dueDate) {
        dueDateEl.textContent = new Date(ticket.dueDate).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } else {
        dueDateEl.innerHTML = '<span style="color:#444444">—</span>';
    }

    const statusEl = document.getElementById('detail-status');
    statusEl.textContent = statusLabels[ticket.status] || ticket.status;
    statusEl.className = `status-badge status-${ticket.status}`;

    // Descrição
    const descSection = document.getElementById('detail-desc-section');
    const descEl = document.getElementById('detail-description');
    if (ticket.description) {
        descEl.textContent = ticket.description;
        descSection.classList.remove('hidden');
    } else {
        descSection.classList.add('hidden');
    }

    // Anexos
    const attachSection = document.getElementById('detail-attachments-section');
    const attachList = document.getElementById('detail-attachments-list');
    attachList.innerHTML = '';

    let attachments = [];
    try { attachments = ticket.attachments ? JSON.parse(ticket.attachments) : []; } catch {}

    if (attachments.length > 0) {
        attachments.forEach(path => {
            const isImage = /\.(png|jpe?g|gif|webp|bmp)$/i.test(path);
            const url = API_BASE_URL.replace(/\/api\/?$/, '') + path;
            if (isImage) {
                const a = document.createElement('a');
                a.href = url;
                a.target = '_blank';
                a.innerHTML = `<img src="${url}" alt="anexo" style="width:120px;height:90px;object-fit:cover;border-radius:8px;border:1px solid #2a2a2a;cursor:pointer;transition:border-color .15s;" onmouseover="this.style.borderColor='#555'" onmouseout="this.style.borderColor='#2a2a2a'">`;
                attachList.appendChild(a);
            } else {
                const a = document.createElement('a');
                a.href = url;
                a.target = '_blank';
                a.style.cssText = 'display:flex;align-items:center;gap:8px;background:#1c1c1c;border:1px solid #2a2a2a;border-radius:8px;padding:8px 12px;text-decoration:none;color:#e0e0e0;font-size:13px;transition:border-color .15s;';
                a.onmouseover = () => a.style.borderColor = '#555';
                a.onmouseout = () => a.style.borderColor = '#2a2a2a';
                a.innerHTML = `<svg style="width:16px;height:16px;flex-shrink:0;color:#888" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg><span style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${path.split('/').pop()}</span>`;
                attachList.appendChild(a);
            }
        });
        attachSection.classList.remove('hidden');
    } else {
        attachSection.classList.add('hidden');
    }

    document.getElementById('ticket-detail-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeTicketDetail() {
    document.getElementById('ticket-detail-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
