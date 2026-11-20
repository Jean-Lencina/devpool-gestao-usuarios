<?php

// Lista todos os usuários ou filtra por nome, e-mail ou login
function listarUsuarios($pdo, $termoBusca = null) {
    if ($termoBusca != null && trim($termoBusca) != '') {
        $sql = "SELECT id, name, email, login, active 
                FROM users 
                WHERE LOWER(name) LIKE LOWER(:termo) 
                   OR LOWER(email) LIKE LOWER(:termo) 
                   OR LOWER(login) LIKE LOWER(:termo) 
                ORDER BY id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':termo' => '%' . trim($termoBusca) . '%'
        ]);
    } else {
        $sql = "SELECT id, name, email, login, active FROM users ORDER BY id DESC";
        $stmt = $pdo->query($sql);
    }

    $usuarios = $stmt->fetchAll();

    // Converte o ID para inteiro e o active (0 ou 1 no SQLite) para booleano
    foreach ($usuarios as $i => $user) {
        $usuarios[$i]['id'] = (int) $user['id'];
        $usuarios[$i]['active'] = (bool) $user['active'];
    }

    return $usuarios;
}

// Busca um usuário específico pelo ID
function buscarUsuarioPorId($pdo, $id) {
    $sql = "SELECT id, name, email, login, active FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }

    $user['id'] = (int) $user['id'];
    $user['active'] = (bool) $user['active'];
    return $user;
}

// Verifica se o e-mail já está em uso (ignorando o próprio ID quando for edição)
function emailJaExiste($pdo, $email, $ignorarId = null) {
    if ($ignorarId == null) {
        $sql = "SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:email)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
    } else {
        $sql = "SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:email) AND id != :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email, ':id' => $ignorarId]);
    }

    $total = $stmt->fetchColumn();
    return $total > 0;
}

// Verifica se o login já está em uso (ignorando o próprio ID quando for edição)
function loginJaExiste($pdo, $login, $ignorarId = null) {
    if ($ignorarId == null) {
        $sql = "SELECT COUNT(*) FROM users WHERE LOWER(login) = LOWER(:login)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':login' => $login]);
    } else {
        $sql = "SELECT COUNT(*) FROM users WHERE LOWER(login) = LOWER(:login) AND id != :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':login' => $login, ':id' => $ignorarId]);
    }

    $total = $stmt->fetchColumn();
    return $total > 0;
}

// Insere um novo usuário no banco e retorna o registro criado
function criarUsuario($pdo, $name, $email, $login, $senhaHash, $active) {
    $sql = "INSERT INTO users (name, email, login, password_hash, active) 
            VALUES (:name, :email, :login, :password_hash, :active)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':login' => $login,
        ':password_hash' => $senhaHash,
        ':active' => $active ? 1 : 0
    ]);

    $novoId = $pdo->lastInsertId();
    return buscarUsuarioPorId($pdo, $novoId);
}

// Atualiza os dados do usuário (só mexe na senha se vier uma nova)
function atualizarUsuario($pdo, $id, $name, $email, $login, $senhaHash, $active) {
    if ($senhaHash != null) {
        $sql = "UPDATE users 
                SET name = :name, email = :email, login = :login, password_hash = :password_hash, active = :active 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':email' => $email,
            ':login' => $login,
            ':password_hash' => $senhaHash,
            ':active' => $active ? 1 : 0
        ]);
    } else {
        $sql = "UPDATE users 
                SET name = :name, email = :email, login = :login, active = :active 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':email' => $email,
            ':login' => $login,
            ':active' => $active ? 1 : 0
        ]);
    }

    return buscarUsuarioPorId($pdo, $id);
}

// Desativa o usuário no banco sem apagar o registro (exclusão lógica)
function excluirUsuario($pdo, $id) {
    $sql = "UPDATE users SET active = 0 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->rowCount() > 0;
}

// Exclui o registro do usuário permanentemente do banco
function excluirUsuarioDefinitivo($pdo, $id) {
    $sql = "DELETE FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->rowCount() > 0;
}