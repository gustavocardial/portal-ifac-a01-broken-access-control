<?php
/**
 * Definição das flags (estilo CTF) e registro de pontos no placar.
 *
 * Cada vulnerabilidade, quando explorada com sucesso, revela uma flag no
 * formato FLAG{...}. O aluno envia a flag em placar.php para pontuar.
 */
require_once __DIR__ . '/db.php';

/**
 * Catálogo de flags. A chave é o "código" da missão; o valor traz a flag,
 * os pontos, o título e os CWEs associados (usado no placar e no roteiro).
 */
function flags_catalogo(): array {
    return [
        'IDOR' => [
            'flag'   => 'FLAG{idor_boletim_de_outro_aluno}',
            'pontos' => 100,
            'titulo' => 'IDOR no boletim',
            'cwe'    => 'CWE-639, CWE-566, CWE-284',
        ],
        'ADMIN' => [
            'flag'   => 'FLAG{forced_browsing_painel_admin}',
            'pontos' => 150,
            'titulo' => 'Painel admin por força bruta de URL',
            'cwe'    => 'CWE-425, CWE-862, CWE-863, CWE-285, CWE-424, CWE-749',
        ],
        'PRIVESC' => [
            'flag'   => 'FLAG{virei_admin_sozinho}',
            'pontos' => 150,
            'titulo' => 'Escalada de privilégio pelo perfil',
            'cwe'    => 'CWE-285, CWE-639',
        ],
        'CSRF' => [
            'flag'   => 'FLAG{csrf_trocou_meu_email}',
            'pontos' => 120,
            'titulo' => 'CSRF na troca de e-mail',
            'cwe'    => 'CWE-352, CWE-1275',
        ],
        'TRAVERSAL' => [
            'flag'   => 'FLAG{path_traversal_leu_o_env}',
            'pontos' => 150,
            'titulo' => 'Path traversal no download',
            'cwe'    => 'CWE-22, CWE-23, CWE-36',
        ],
        'REDIRECT' => [
            'flag'   => 'FLAG{open_redirect_phishing}',
            'pontos' => 80,
            'titulo' => 'Open redirect pós-login',
            'cwe'    => 'CWE-601',
        ],
        'SSRF' => [
            'flag'   => 'FLAG{ssrf_bateu_no_localhost}',
            'pontos' => 150,
            'titulo' => 'SSRF no importador de avatar',
            'cwe'    => 'CWE-918, CWE-441',
        ],
        'APIEXPOSE' => [
            'flag'   => 'FLAG{api_vazou_cpf_e_hash}',
            'pontos' => 120,
            'titulo' => 'API expõe dados demais',
            'cwe'    => 'CWE-200, CWE-201, CWE-359',
        ],
        'DIRLIST' => [
            'flag'   => 'FLAG{listagem_de_diretorio_aberta}',
            'pontos' => 80,
            'titulo' => 'Listagem de diretório em /uploads',
            'cwe'    => 'CWE-548, CWE-552',
        ],
        'BACKUP' => [
            'flag'   => 'FLAG{backup_sql_sob_o_webroot}',
            'pontos' => 120,
            'titulo' => 'Backup e .env sob o web root',
            'cwe'    => 'CWE-219, CWE-538, CWE-552, CWE-540',
        ],
        'PHPINFO' => [
            'flag'   => 'FLAG{phpinfo_exposto}',
            'pontos' => 60,
            'titulo' => 'phpinfo() exposto',
            'cwe'    => 'CWE-497, CWE-200',
        ],
        'FRONTSECRET' => [
            'flag'   => 'FLAG{segredo_no_javascript}',
            'pontos' => 80,
            'titulo' => 'Segredo no código do front',
            'cwe'    => 'CWE-540, CWE-615, CWE-922',
        ],
    ];
}

/** Resolve uma flag enviada -> código da missão (ou null). */
function flag_lookup(string $flag): ?string {
    foreach (flags_catalogo() as $codigo => $info) {
        if (hash_equals($info['flag'], trim($flag))) {
            return $codigo;
        }
    }
    return null;
}

/**
 * Registra a flag para uma equipe/aluno no placar.
 * @return array{ok:bool,msg:string,pontos:int}
 */
function registrar_flag(string $equipe, string $flag): array {
    $equipe = trim($equipe);
    if ($equipe === '') {
        return ['ok' => false, 'msg' => 'Informe seu nome ou o da equipe.', 'pontos' => 0];
    }
    $codigo = flag_lookup($flag);
    if ($codigo === null) {
        return ['ok' => false, 'msg' => 'Flag inválida. Continue explorando!', 'pontos' => 0];
    }
    $info = flags_catalogo()[$codigo];
    try {
        $st = db()->prepare(
            'INSERT INTO placar (equipe, codigo, pontos) VALUES (?, ?, ?)'
        );
        $st->execute([$equipe, $codigo, $info['pontos']]);
    } catch (PDOException $e) {
        // UNIQUE(equipe, codigo) -> já capturou essa flag.
        return ['ok' => false, 'msg' => 'Você já capturou esta flag: ' . $info['titulo'], 'pontos' => 0];
    }
    return ['ok' => true, 'msg' => 'Flag capturada: ' . $info['titulo'], 'pontos' => $info['pontos']];
}
