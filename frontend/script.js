const urlApi = 'http://localhost:8000/users';

// Eventos iniciais da página
document.addEventListener('DOMContentLoaded', () => {
    carregarLista();

    document.getElementById('busca-id').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') buscarPorId();
    });

    document.getElementById('busca').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') carregarLista();
    });
});

// Mensagens de alerta
function avisar(texto, tipo) {
    const caixa = document.getElementById('mensagem');
    caixa.textContent = texto;
    caixa.className = `alerta alerta-${tipo}`;

    setTimeout(() => {
        caixa.classList.add('escondido');
    }, 5000);
}

// Estado de carregamento
function mostrarCarregando(ativo) {
    const texto = document.getElementById('carregando');
    const botao = document.getElementById('btn-salvar');

    if (ativo) {
        texto.classList.remove('escondido');
        botao.disabled = true;
    } else {
        texto.classList.add('escondido');
        botao.disabled = false;
    }
}

// Monta as linhas da tabela
function desenharTabela(lista) {
    const tabela = document.getElementById('tabela');
    tabela.innerHTML = '';

    if (lista.length === 0) {
        tabela.innerHTML = '<tr><td colspan="6">Nenhum usuário encontrado.</td></tr>';
        return;
    }

    lista.forEach(user => {
        const status = user.active
            ? '<span class="status-ativo">Ativo</span>'
            : '<span class="status-inativo">Inativo</span>';

        const botoesAcao = user.active
            ? `
                <button class="btn-amarelo" onclick="abrirEdicao(${user.id})">Editar</button>
                <button class="btn-vermelho" onclick="apagar(${user.id}, '${user.name}')">Excluir</button>
              `
            : `
                <button class="btn-vermelho" onclick="excluirDefinitivo(${user.id}, '${user.name}')">Excluir Definitivamente</button>
                <button class="btn-verde" onclick="ativarUsuario(${user.id}, '${user.name}')">Reativar</button>
              `;

        const linha = document.createElement('tr');
        linha.innerHTML = `
            <td>${user.id}</td>
            <td>${user.name}</td>
            <td>${user.email}</td>
            <td>${user.login}</td>
            <td>${status}</td>
            <td>
                ${botoesAcao}
            </td>
        `;
        tabela.appendChild(linha);
    });
}

// Lista usuários ou filtra por busca
async function carregarLista() {
    mostrarCarregando(true);
    document.getElementById('busca-id').value = '';
    const busca = document.getElementById('busca').value.trim();

    let url = urlApi;
    if (busca !== '') {
        url += `?busca=${encodeURIComponent(busca)}`;
    }

    try {
        const resp = await fetch(url);
        const lista = await resp.json();
        desenharTabela(lista);
    } catch (erro) {
        avisar('Erro ao conectar com o servidor. Verifique se o PHP está rodando.', 'erro');
    } finally {
        mostrarCarregando(false);
    }
}

// Busca usuário pelo ID
async function buscarPorId() {
    const id = document.getElementById('busca-id').value.trim();

    if (id === '' || Number(id) < 1) {
        avisar('Informe um ID numérico válido.', 'erro');
        return;
    }

    mostrarCarregando(true);
    document.getElementById('busca').value = '';

    try {
        const resp = await fetch(`${urlApi}/${id}`);
        const resultado = await resp.json();

        if (!resp.ok) {
            desenharTabela([]);
            avisar(resultado.error || 'Usuário não encontrado.', 'erro');
            return;
        }

        desenharTabela([resultado]);
    } catch (erro) {
        avisar('Erro ao conectar com o servidor. Verifique se o PHP está rodando.', 'erro');
    } finally {
        mostrarCarregando(false);
    }
}

// Limpa os campos de filtro
function limparBusca() {
    document.getElementById('busca-id').value = '';
    document.getElementById('busca').value = '';
    carregarLista();
}

// Envio do formulário (cadastro e edição)
document.getElementById('form-usuario').addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const id = document.getElementById('id-usuario').value;
    const dados = {
        name: document.getElementById('nome').value,
        email: document.getElementById('email').value,
        login: document.getElementById('login').value,
        password: document.getElementById('senha').value,
        active: document.getElementById('ativo').checked
    };

    const editando = id !== '';
    const url = editando ? `${urlApi}/${id}` : urlApi;
    const metodo = editando ? 'PUT' : 'POST';

    mostrarCarregando(true);

    try {
        const resp = await fetch(url, {
            method: metodo,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });

        const resultado = await resp.json();

        if (!resp.ok) {
            avisar(resultado.error || 'Erro ao salvar.', 'erro');
            return;
        }

        avisar(editando ? 'Usuário atualizado!' : 'Usuário cadastrado!', 'sucesso');
        limpar();
        carregarLista();
    } catch (erro) {
        avisar('Erro de comunicação com a API.', 'erro');
    } finally {
        mostrarCarregando(false);
    }
});

// Preenche o formulário para edição
async function abrirEdicao(id) {
    mostrarCarregando(true);

    try {
        const resp = await fetch(`${urlApi}/${id}`);
        const user = await resp.json();

        if (!resp.ok) {
            avisar(user.error || 'Usuário não encontrado.', 'erro');
            return;
        }

        document.getElementById('id-usuario').value = user.id;
        document.getElementById('nome').value = user.name;
        document.getElementById('email').value = user.email;
        document.getElementById('login').value = user.login;
        document.getElementById('senha').value = '';
        document.getElementById('ativo').checked = user.active;

        document.getElementById('titulo-form').textContent = `Editando Usuário #${user.id}`;
        document.getElementById('dica-senha').textContent = '(deixe em branco para manter a atual)';
        document.getElementById('btn-cancelar').classList.remove('escondido');

        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (erro) {
        avisar('Erro ao carregar dados do usuário.', 'erro');
    } finally {
        mostrarCarregando(false);
    }
}

// Desativa o usuário (exclusão lógica)
async function apagar(id, nome) {
    if (!confirm(`Deseja realmente desativar "${nome}"?`)) {
        return;
    }

    mostrarCarregando(true);

    try {
        const resp = await fetch(`${urlApi}/${id}`, { method: 'DELETE' });
        const resultado = await resp.json();

        if (!resp.ok) {
            avisar(resultado.error || 'Erro ao desativar.', 'erro');
            return;
        }

        avisar('Usuário desativado!', 'sucesso');
        carregarLista();
    } catch (erro) {
        avisar('Erro ao conectar com o servidor.', 'erro');
    } finally {
        mostrarCarregando(false);
    }
}

// Reativa um usuário inativo
async function ativarUsuario(id, nome) {
    if (!confirm(`Deseja realmente reativar "${nome}"?`)) {
        return;
    }

    mostrarCarregando(true);

    try {
        // 1. Busca os dados atuais do usuário (GET /users/{id})
        const respBusca = await fetch(`${urlApi}/${id}`);
        const user = await respBusca.json();

        if (!respBusca.ok) {
            avisar(user.error || 'Usuário não encontrado.', 'erro');
            return;
        }

        // 2. Envia os dados de volta via PUT alterando apenas o active para true
        const resp = await fetch(`${urlApi}/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: user.name,
                email: user.email,
                login: user.login,
                active: true
            })
        });

        const resultado = await resp.json();

        if (!resp.ok) {
            avisar(resultado.error || 'Erro ao reativar usuário.', 'erro');
            return;
        }

        avisar('Usuário reativado!', 'sucesso');
        carregarLista();
    } catch (erro) {
        avisar('Erro ao conectar com o servidor.', 'erro');
    } finally {
        mostrarCarregando(false);
    }
}

// Exclui um usuário permanentemente
async function excluirDefinitivo(id, nome) {
    if (!confirm(`Deseja realmente excluir "${nome}" permanentemente? Esta ação não pode ser desfeita.`)) {
        return;
    }

    mostrarCarregando(true);

    try {
        const resp = await fetch(`${urlApi}/${id}?definitivo=true`, { method: 'DELETE' });
        const resultado = await resp.json();

        if (!resp.ok) {
            avisar(resultado.error || 'Erro ao excluir usuário.', 'erro');
            return;
        }

        avisar('Usuário excluído permanentemente!', 'sucesso');
        carregarLista();
    } catch (erro) {
        avisar('Erro ao conectar com o servidor.', 'erro');
    } finally {
        mostrarCarregando(false);
    }
}

// Reseta o formulário
function limpar() {
    document.getElementById('form-usuario').reset();
    document.getElementById('id-usuario').value = '';
    document.getElementById('ativo').checked = true;
    document.getElementById('titulo-form').textContent = 'Novo Usuário';
    document.getElementById('dica-senha').textContent = '(mín. 8 caracteres)';
    document.getElementById('btn-cancelar').classList.add('escondido');
}