<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

type Vault = { id: number; name: string; owner_id: number; can_edit?: boolean };
type NoteSummary = { id: string; vault_id: number; title: string; version: number; updated_at: string };
type Note = NoteSummary & { body: string };
type Envelope<T> = { data: T };

const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
const vaults = ref<Vault[]>([]);
const notes = ref<NoteSummary[]>([]);
const currentVaultId = ref<number | null>(null);
const currentNote = ref<Note | null>(null);
const draftTitle = ref('');
const draftBody = ref('');
const busy = ref(false);
const message = ref('Pronto');
const error = ref('');

const currentVault = computed(() => vaults.value.find((v) => v.id === currentVaultId.value) ?? null);
const dirty = computed(() => currentNote.value !== null &&
    (draftTitle.value !== currentNote.value.title || draftBody.value !== currentNote.value.body));

async function api<T>(url: string, method = 'GET', payload?: unknown): Promise<T> {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            ...(payload !== undefined ? { 'Content-Type': 'application/json' } : {}),
        },
        body: payload === undefined ? undefined : JSON.stringify(payload),
    });
    if (response.status === 401) {
        window.location.assign('/login');
        throw new Error('Sua sessao expirou.');
    }
    const data = await response.json().catch(() => ({ message: 'Resposta invalida do servidor.' })) as {
        message?: string; errors?: Record<string, string[]>;
    };
    if (!response.ok) {
        const firstFieldError = Object.values(data.errors ?? {})[0]?.[0];
        throw new Error(firstFieldError ?? data.message ?? 'Falha na requisicao.');
    }
    return data as T;
}

function reportFailure(reason: unknown): void {
    error.value = reason instanceof Error ? reason.message : 'Erro inesperado.';
    message.value = 'Operacao nao concluida';
}

function canDiscard(): boolean {
    return !dirty.value || window.confirm('Descartar as alteracoes nao salvas?');
}

async function loadVaults(): Promise<void> {
    try {
        const result = await api<Envelope<Vault[]>>('/api/vaults');
        vaults.value = result.data;
        if (result.data.length) await selectVault(result.data[0].id);
    } catch (reason) {
        reportFailure(reason);
    }
}

async function selectVault(id: number): Promise<void> {
    if (!canDiscard()) return;
    const selectedId = id;
    currentVaultId.value = selectedId;
    currentNote.value = null;
    draftTitle.value = '';
    draftBody.value = '';
    notes.value = [];
    error.value = '';
    try {
        const result = await api<Envelope<NoteSummary[]>>('/api/vaults/' + selectedId + '/notes');
        if (currentVaultId.value === selectedId) notes.value = result.data;
    } catch (reason) {
        reportFailure(reason);
    }
}

async function selectNote(id: string): Promise<void> {
    if (!canDiscard() || currentVaultId.value === null) return;
    const vaultId = currentVaultId.value;
    error.value = '';
    try {
        const result = await api<Envelope<Note>>('/api/vaults/' + vaultId + '/notes/' + id);
        if (currentVaultId.value !== vaultId) return;
        currentNote.value = result.data;
        draftTitle.value = result.data.title;
        draftBody.value = result.data.body;
        message.value = 'Nota carregada';
    } catch (reason) {
        reportFailure(reason);
    }
}

async function createVault(): Promise<void> {
    if (!canDiscard()) return;
    const name = window.prompt('Nome do novo cofre:')?.trim();
    if (!name) return;
    busy.value = true;
    error.value = '';
    try {
        const result = await api<Envelope<Vault>>('/api/vaults', 'POST', { name });
        vaults.value.push({ ...result.data, can_edit: true });
        vaults.value.sort((a, b) => a.name.localeCompare(b.name));
        currentNote.value = null;
        draftTitle.value = '';
        draftBody.value = '';
        await selectVault(result.data.id);
        message.value = 'Cofre criado';
    } catch (reason) {
        reportFailure(reason);
    } finally {
        busy.value = false;
    }
}

async function createNote(): Promise<void> {
    if (currentVaultId.value === null || !currentVault.value?.can_edit || !canDiscard()) return;
    const title = window.prompt('Titulo da nova nota:')?.trim();
    if (!title) return;
    busy.value = true;
    error.value = '';
    try {
        const result = await api<Envelope<NoteSummary>>('/api/vaults/' + currentVaultId.value + '/notes', 'POST', {
            title, body: '',
        });
        notes.value.push(result.data);
        notes.value.sort((a, b) => a.title.localeCompare(b.title));
        currentNote.value = null;
        draftTitle.value = '';
        draftBody.value = '';
        await selectNote(result.data.id);
        message.value = 'Nota criada';
    } catch (reason) {
        reportFailure(reason);
    } finally {
        busy.value = false;
    }
}

async function saveNote(): Promise<void> {
    if (!currentNote.value || currentVaultId.value === null || !currentVault.value?.can_edit) return;
    const title = draftTitle.value.trim();
    if (!title) { error.value = 'O titulo nao pode ficar vazio.'; return; }
    busy.value = true;
    error.value = '';
    try {
        const result = await api<Envelope<NoteSummary>>(
            '/api/vaults/' + currentVaultId.value + '/notes/' + currentNote.value.id,
            'PUT',
            { title, body: draftBody.value, version: currentNote.value.version },
        );
        currentNote.value = { ...result.data, body: draftBody.value };
        draftTitle.value = result.data.title;
        notes.value = notes.value.map((note) => note.id === result.data.id ? result.data : note);
        notes.value.sort((a, b) => a.title.localeCompare(b.title));
        message.value = 'Alteracoes salvas';
    } catch (reason) {
        reportFailure(reason);
    } finally {
        busy.value = false;
    }
}

async function archiveNote(): Promise<void> {
    if (!currentNote.value || currentVaultId.value === null || !currentVault.value?.can_edit) return;
    if (!canDiscard()) return;
    if (!window.confirm('Arquivar esta nota? O arquivo Markdown sera preservado.')) return;
    busy.value = true;
    error.value = '';
    try {
        await api('/api/vaults/' + currentVaultId.value + '/notes/' + currentNote.value.id, 'DELETE', {
            version: currentNote.value.version,
        });
        notes.value = notes.value.filter((note) => note.id !== currentNote.value?.id);
        currentNote.value = null;
        draftTitle.value = '';
        draftBody.value = '';
        message.value = 'Nota arquivada';
    } catch (reason) {
        reportFailure(reason);
    } finally {
        busy.value = false;
    }
}

onMounted(loadVaults);
</script>

<template>
    <div class="workspace">
        <aside class="vault-sidebar">
            <header class="brand">
                <span class="brand-symbol" aria-hidden="true">◆</span>
                <span>Cianita</span>
            </header>
            <div class="section-heading"><span>COFRES</span><button title="Criar cofre" :disabled="busy" @click="createVault">+</button></div>
            <nav aria-label="Cofres" class="items">
                <button v-for="vault in vaults" :key="vault.id"
                    :class="['item', { active: currentVaultId === vault.id }]" @click="selectVault(vault.id)">
                    <span aria-hidden="true">◇</span> {{ vault.name }}
                </button>
            </nav>
            <div class="sidebar-bottom">
                <span class="sidebar-version">Cianita · base inicial</span>
                <form method="post" action="/logout">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button type="submit" class="logout">Sair da conta</button>
                </form>
            </div>
        </aside>

        <aside class="notes-sidebar">
            <div class="section-heading"><span>NOTAS</span>
                <button title="Nova nota" :disabled="busy || !currentVault?.can_edit" @click="createNote">+</button>
            </div>
            <p v-if="!currentVault" class="helper">Crie um cofre para comecar.</p>
            <p v-else-if="notes.length === 0" class="helper">Este cofre ainda nao tem notas.</p>
            <nav v-else aria-label="Notas" class="items">
                <button v-for="note in notes" :key="note.id"
                    :class="['item', { active: currentNote?.id === note.id }]" @click="selectNote(note.id)">
                    <span aria-hidden="true">▤</span> {{ note.title }}
                </button>
            </nav>
        </aside>

        <main class="editor-pane">
            <div v-if="currentNote" class="editor">
                <div class="editor-top">
                    <div class="breadcrumb">{{ currentVault?.name }} / Notas</div>
                    <div class="actions">
                        <button class="button-subtle" :disabled="busy || !currentVault?.can_edit" @click="archiveNote">Arquivar</button>
                        <button class="button-primary" :disabled="busy || !dirty || !currentVault?.can_edit" @click="saveNote">
                            {{ busy ? 'Salvando...' : 'Salvar' }}
                        </button>
                    </div>
                </div>
                <input v-model="draftTitle" class="note-title" aria-label="Titulo da nota" placeholder="Sem titulo"
                    :readonly="!currentVault?.can_edit" maxlength="180">
                <div class="editor-subtitle">Markdown · {{ dirty ? 'Alteracoes nao salvas' : 'Todas as alteracoes salvas' }}</div>
                <textarea v-model="draftBody" class="markdown-editor" aria-label="Conteudo Markdown"
                    :readonly="!currentVault?.can_edit" spellcheck="true" placeholder="Comece a escrever em Markdown..."></textarea>
            </div>
            <div v-else class="empty-state">
                <div class="empty-gem" aria-hidden="true">◆</div>
                <h1>Seu espaco de conhecimento.</h1>
                <p>Selecione uma nota ou crie um cofre para comecar a organizar suas ideias.</p>
                <p class="small">Edicao em Markdown, com dados armazenados na sua infraestrutura.</p>
            </div>
            <footer class="status-bar">
                <span :class="{ 'status-error': !!error }">{{ error || message }}</span>
                <span>{{ currentVault?.name ?? 'Nenhum cofre selecionado' }}</span>
            </footer>
        </main>
    </div>
</template>
