<?php
require_once __DIR__ . '/../lib/render.php';   // traz config/sessão/SECURE
require_once __DIR__ . '/../lib/flags.php';

header('Content-Type: application/json; charset=utf-8');

/*
 * Exposição de dados sensíveis (CWE-200/201/359).
 * Vulnerável: endpoint sem autenticação que devolve TODAS as colunas —
 *   inclusive senha em texto puro, hash e CPF de todos os usuários.
 * Corrigido: exige login + papel adequado e devolve só campos não sensíveis.
 */
if (SECURE) {
    $u = current_user();
    if (!$u || !in_array($u['role'], ['professor', 'admin'], true)) {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado']);
        exit;
    }
    $rows = db()->query('SELECT id, nome, matricula FROM usuarios WHERE role = "aluno"')->fetchAll();
    echo json_encode(['alunos' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Vulnerável: SELECT * e sem filtro de papel.
$rows = db()->query('SELECT * FROM usuarios')->fetchAll();
echo json_encode([
    '_flag'   => flags_catalogo()['APIEXPOSE']['flag'],
    '_aviso'  => 'Esta API devolve campos demais (senha, hash, CPF). Nunca faça isto.',
    'usuarios' => $rows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
