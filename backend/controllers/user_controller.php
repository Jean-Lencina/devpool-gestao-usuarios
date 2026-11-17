<?php

function enviarJson($statusCode, $dados) {
    http_response_code($statusCode);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
}

function validarDadosUsuario($pdo, $dados, $isEdicao = false, $idUsuario = null) {
    $erros = [];

    $name = trim($dados['name'] ?? '');
    $email = trim($dados['email'] ?? '');
    $login = trim($dados['login'] ?? '');
    $password = $dados['password'] ?? '';

    if (mb_strlen($name) < 3) {
        $erros[] = 'O nome é obrigatório e deve ter no mínimo 3 caracteres.';
    }

    if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    } elseif (emailJaExiste($pdo, $email, $idUsuario)) {
        $erros[] = 'Este e-mail já está cadastrado.';
    }

    if (mb_strlen($login) < 3) {
        $erros[] = 'O login é obrigatório e deve ter no mínimo 3 caracteres.';
    } elseif (loginJaExiste($pdo, $login, $idUsuario)) {
        $erros[] = 'Este login já está em uso.';
    }

    if ($isEdicao == false) {
        if (strlen($password) < 8) {
            $erros[] = 'A senha é obrigatória e deve ter no mínimo 8 caracteres.';
        }
    } else {
        if ($password != '' && strlen($password) < 8) {
            $erros[] = 'A nova senha deve ter no mínimo 8 caracteres.';
        }
    }

    return $erros;
}

function controllerListarUsuarios($pdo, $termoBusca = null) {
    $usuarios = listarUsuarios($pdo, $termoBusca);
    enviarJson(200, $usuarios);
}

function controllerBuscarUsuario($pdo, $id) {
    $usuario = buscarUsuarioPorId($pdo, $id);

    if ($usuario == null) {
        enviarJson(404, ['error' => 'Usuário não encontrado.']);
        return;
    }

    enviarJson(200, $usuario);
}

function controllerCriarUsuario($pdo, $dados) {
    $erros = validarDadosUsuario($pdo, $dados, false, null);

    if (count($erros) > 0) {
        enviarJson(400, ['error' => implode(' ', $erros)]);
        return;
    }

    $name = trim($dados['name']);
    $email = trim($dados['email']);
    $login = trim($dados['login']);
    $senhaHash = password_hash($dados['password'], PASSWORD_BCRYPT);

    $active = isset($dados['active']) ? (bool) $dados['active'] : true;

    $novoUsuario = criarUsuario($pdo, $name, $email, $login, $senhaHash, $active);
    enviarJson(201, $novoUsuario);
}

function controllerAtualizarUsuario($pdo, $id, $dados) {
    $usuarioAtual = buscarUsuarioPorId($pdo, $id);

    if ($usuarioAtual == null) {
        enviarJson(404, ['error' => 'Usuário não encontrado.']);
        return;
    }

    $erros = validarDadosUsuario($pdo, $dados, true, $id);

    if (count($erros) > 0) {
        enviarJson(400, ['error' => implode(' ', $erros)]);
        return;
    }

    $name = trim($dados['name']);
    $email = trim($dados['email']);
    $login = trim($dados['login']);
    $active = isset($dados['active']) ? (bool) $dados['active'] : $usuarioAtual['active'];

    $senhaHash = null;
    if (!empty($dados['password'])) {
        $senhaHash = password_hash($dados['password'], PASSWORD_BCRYPT);
    }

    $usuarioAtualizado = atualizarUsuario($pdo, $id, $name, $email, $login, $senhaHash, $active);
    enviarJson(200, $usuarioAtualizado);
}

function controllerExcluirUsuario($pdo, $id) {
    $usuarioAtual = buscarUsuarioPorId($pdo, $id);

    if ($usuarioAtual == null) {
        enviarJson(404, ['error' => 'Usuário não encontrado.']);
        return;
    }

    excluirUsuario($pdo, $id);
    enviarJson(200, ['message' => 'Usuário desativado com sucesso.']);
}