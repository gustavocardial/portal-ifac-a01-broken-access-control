<?php
/**
 * ============================================================================
 *  Portal Acadêmico IFAC  —  Aplicação DELIBERADAMENTE VULNERÁVEL
 *  Material didático para a aula de OWASP Top 10 2025 — A01 Broken Access Control
 * ============================================================================
 *
 *  >>> NÃO USE EM PRODUÇÃO. NÃO EXPONHA NA INTERNET. <<<
 *  Rode apenas em rede isolada de laboratório, em máquina descartável.
 *
 * ----------------------------------------------------------------------------
 *  O TOGGLE SECURE
 * ----------------------------------------------------------------------------
 *  Toda falha deste app tem dois caminhos no código:
 *
 *      if (SECURE) {  // versão corrigida
 *          ...
 *      } else {       // versão vulnerável (padrão)
 *          ...
 *      }
 *
 *  Com SECURE = false  -> o app está vulnerável (modo da aula, exploração).
 *  Com SECURE = true   -> as mitigações entram e os ataques são bloqueados.
 *
 *  O professor pode alternar de duas formas:
 *   1) Editando a constante abaixo (vale para todos os acessos), ou
 *   2) Ao vivo, pelo botão no topo do site, que grava um override em sessão
 *      (afeta só o navegador do professor — bom para demonstrar lado a lado).
 */

// ---- Toggle padrão (global) -------------------------------------------------
define('SECURE_DEFAULT', false);

// ---- Credenciais do banco (padrão do XAMPP: root sem senha) -----------------
// Os valores podem ser sobrescritos por variáveis de ambiente (útil para testes
// e portabilidade); se nada for definido, usa o padrão do XAMPP.
define('DB_HOST', getenv('IFAC_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('IFAC_DB_PORT') ?: '3306');
define('DB_USER', getenv('IFAC_DB_USER') ?: 'root');
define('DB_PASS', getenv('IFAC_DB_PASS') !== false ? getenv('IFAC_DB_PASS') : '');
define('DB_NAME', getenv('IFAC_DB_NAME') ?: 'portal_ifac');

// ---- Caminhos ---------------------------------------------------------------
// Pasta pública (web root desta app). Arquivos sensíveis "de verdade" deveriam
// ficar FORA daqui — parte da lição é mostrar o que acontece quando não ficam.
define('APP_ROOT', __DIR__);
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');
// Pasta que o download.php DEVERIA restringir (as declarações dos alunos):
define('DECLARACOES_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'storage');

// ---- Base URL (ajuste automático) -------------------------------------------
// Monta o prefixo da URL (ex.: /seguranca) a partir do script atual.
function base_url(): string {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    // Se estamos dentro de /admin ou /api, sobe um nível para a raiz do app.
    foreach (['/admin', '/api'] as $sub) {
        if (str_ends_with($dir, $sub)) {
            $dir = substr($dir, 0, -strlen($sub));
        }
    }
    return rtrim($dir, '/');
}

// ---- Sessão -----------------------------------------------------------------
// Observação didática: aqui NÃO configuramos SameSite no cookie de sessão de
// propósito (CWE-1275). A correção aparece quando SECURE está ligado.
require_once __DIR__ . '/lib/secure_flag.php';

if (session_status() === PHP_SESSION_NONE) {
    if (SECURE) {
        // Versão corrigida: cookie de sessão endurecido.
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',   // mitiga CSRF via navegação de terceiros
        ]);
    }
    // Versão vulnerável: sem SameSite, sem HttpOnly explícito.
    session_start();
}
