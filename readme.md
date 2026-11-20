# Gestão de Usuários (CRUD + API REST)

Sistema web para cadastro e gerenciamento de usuários desenvolvido em PHP puro, SQLite e JavaScript (Vanilla JS).

## Estrutura do Projeto

```text
├── backend/
│   ├── config/
│   │   └── database.php        # Conexão PDO com SQLite e criação da tabela
│   ├── controllers/
│   │   └── user_controller.php # Validações, regras de negócio e respostas JSON
│   ├── repositories/
│   │   └── user_repository.php # Consultas SQL (CRUD)
│   ├── index.php               # Roteamento da API e configurações de CORS
│   └── database.sqlite         # Banco de dados (gerado automaticamente)
├── frontend/
│   ├── index.html              # Estrutura da página e formulário
│   ├── style.css               # Estilização
│   └── script.js               # Consumo da API via fetch e atualização da tabela
└── README.md
```

## Requisitos

- PHP 8.0+ com a extensão `pdo_sqlite` habilitada.
- Navegador web.

## Como rodar o projeto

1. Clone o repositório:
```bash
git clone [https://github.com/Jean-Lencina/devpool-gestao-usuarios.git](https://github.com/Jean-Lencina/devpool-gestao-usuarios.git)
cd devpool-gestao-usuarios
```

2. Inicie o servidor PHP na pasta `backend`:
```bash
cd backend
php -S localhost:8000
```
*O arquivo `database.sqlite` e a tabela `users` são criados automaticamente na primeira requisição.*

3. Abra o arquivo `frontend/index.html` no navegador (ou via Live Server) com a API rodando na porta `8000`.

## Rotas da API

URL base: `http://localhost:8000`

| Método | Rota | O que faz |
| :--- | :--- | :--- |
| `GET` | `/users` | Lista todos os usuários (aceita `?busca=termo`) |
| `GET` | `/users/{id}` | Busca um usuário pelo ID |
| `POST` | `/users` | Cadastra um novo usuário |
| `PUT` / `PATCH` | `/users/{id}` | Atualiza os dados de um usuário |
| `DELETE` | `/users/{id}` | Desativa o usuário (exclusão lógica) |

## Exemplo de Payload (JSON)

Para testar `POST` ou `PUT` via Postman/Insomnia (`Body` -> `raw` -> `JSON`):

```json
{
    "name": "Maria Silva",
    "email": "maria@exemplo.com",
    "login": "mariasilva",
    "password": "senha123",
    "active": true
}
```
*(Na edição via `PUT`, o campo `password` é opcional: se vier vazio ou não for enviado, a senha atual é mantida).*

## Regras implementadas

- **Validações:** `name` e `login` exigem no mínimo 3 caracteres; `email` deve ser válido; `password` exige no mínimo 8 caracteres.
- **Unicidade:** Não permite cadastrar ou editar com `email` ou `login` já existentes.
- **Segurança:** Senhas salvas com `password_hash` (`PASSWORD_BCRYPT`) e consultas protegidas com *Prepared Statements* (PDO).
- **Exclusão lógica:** O `DELETE` marca o usuário como inativo (`active = 0`) sem apagar o registro do banco.