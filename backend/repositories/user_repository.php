<?php

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

    foreach ($usuarios as $i => $user) {
        $usuarios[$i]['id'] = (int) $user['id'];
        $usuarios[$i]['active'] = (bool) $user['active'];
    }

    return $usuarios;
}

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

function excluirUsuario($pdo, $id) {
    $sql = "UPDATE users SET active = 0 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->rowCount() > 0;
}