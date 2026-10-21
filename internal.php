<?php
/**
 * "Serviço interno" — simula um endpoint de metadados/admin que só deveria ser
 * acessível a partir do próprio servidor (loopback), nunca pela rede.
 *
 * É o alvo do exercício de SSRF: o aluno não consegue abrir esta página a
 * partir da máquina dele (recebe 403), mas o servidor consegue — e o avatar.php
 * pode ser abusado para fazer o servidor buscá-la.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/flags.php';

$remoto = $_SERVER['REMOTE_ADDR'] ?? '';
$loopback = in_array($remoto, ['127.0.0.1', '::1', ''], true);

header('Content-Type: text/plain; charset=utf-8');

if (SECURE || !$loopback) {
    // Em modo seguro OU se a requisição não vier do próprio servidor: negado.
    http_response_code(403);
    echo "403 - Serviço interno. Acesso permitido apenas a partir do servidor (loopback).\n";
    echo "Seu IP: $remoto\n";
    exit;
}

echo "=== PAINEL INTERNO DE METADADOS (apenas loopback) ===\n";
echo "instance-id: ifac-portal-01\n";
echo "db-credentials: root:(vazio)@127.0.0.1/portal_ifac\n";
echo "secret: " . flags_catalogo()['SSRF']['flag'] . "\n";
