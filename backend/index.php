<?php

// Configurações de CORS e formato de resposta JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Responde rápido às requisições de verificação do navegador (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Importa a conexão com o banco, o repository e o controller
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/repositories/user_repository.php';
require_once __DIR__ . '/controllers/user_controller.php';

try {
    // Abre conexão com o banco SQLite
    $pdo = getConnection();

    // Captura o método HTTP (GET, POST, etc.) e limpa a URL
    $metodo = $_SERVER['REQUEST_METHOD'];
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $url = trim($url, '/');

    // Lê o corpo JSON da requisição quando for envio ou alteração de dados
    $dados = [];
    if ($metodo === 'POST' || $metodo === 'PUT' || $metodo === 'PATCH') {
        $json = file_get_contents('php://input');
        
        if ($json) {
            $dados = json_decode($json, true);
            
            if (!is_array($dados)) {
                enviarJson(400, ['erro' => 'JSON inválido.']);
                exit;
            }
        }
    }

    // Divide a URL em partes para identificar a rota
    $partes = explode('/', $url);

    // Rota: /users (listar todos/filtrar ou cadastrar novo)
    if (count($partes) === 1 && $partes[0] === 'users') {
        switch ($metodo) {
            case 'GET':
                $busca = isset($_GET['busca']) ? trim($_GET['busca']) : null;
                controllerListarUsuarios($pdo, $busca);
                break;
            case 'POST':
                controllerCriarUsuario($pdo, $dados);
                break;
            default:
                enviarJson(405, ['erro' => 'Método não permitido.']);
                break;
        }
    } 
    // Rota: /users/{id} (buscar um, atualizar, desativar ou excluir definitivamente)
    elseif (count($partes) === 2 && $partes[0] === 'users' && is_numeric($partes[1])) {
        $id = (int) $partes[1];

        if ($id <= 0) {
            enviarJson(400, ['erro' => 'ID inválido.']);
            exit;
        }

        switch ($metodo) {
            case 'GET':
                controllerBuscarUsuario($pdo, $id);
                break;
            case 'PUT':
            case 'PATCH':
                controllerAtualizarUsuario($pdo, $id, $dados);
                break;
            case 'DELETE':
                if (isset($_GET['definitivo']) && $_GET['definitivo'] === 'true') {
                    controllerExcluirUsuarioDefinitivo($pdo, $id);
                } else {
                    controllerExcluirUsuario($pdo, $id);
                }
                break;
            default:
                enviarJson(405, ['erro' => 'Método não permitido.']);
                break;
        }
    } else {
        // Retorna 404 se a URL não bater com nenhuma rota esperada
        enviarJson(404, ['erro' => 'Rota não encontrada.']);
    }

} catch (Exception $e) {
    // Captura qualquer falha inesperada e evita expor detalhes internos
    enviarJson(500, ['erro' => 'Erro interno no servidor.']);
}