<?php
/**
 * Autenticação e autorização.
 *
 * Vários pontos aqui são FRACOS DE PROPÓSITO — é o material da aula sobre
 * Broken Access Control. Os helpers require_login()/require_role() existem,
 * mas repare que muitas páginas "esquecem" de chamá-los (CWE-862 Missing
 * Authorization, CWE-425 Forced Browsing).
 */
require_once __DIR__ . '/db.php';

function current_user(): ?array {
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $st = db()->prepare('SELECT * FROM usuarios WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();
    return $u ?: null;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

/** Exige login; redireciona para a tela de login se não houver sessão. */
function require_login(): array {
    $u = current_user();
    if (!$u) {
        header('Location: ' . base_url() . '/login.php');
        exit;
    }
    return $u;
}

/** Exige um papel específico (ex.: 'admin'). */
function require_role(string $role): array {
    $u = require_login();
    if ($u['role'] !== $role) {
        http_response_code(403);
        exit('403 — Acesso negado. Este recurso exige o papel: ' . htmlspecialchars($role));
    }
    return $u;
}

/**
 * Verifica a senha.
 * Vulnerável: senhas em texto puro no banco (parte da lição de exposição).
 * Corrigido: usa password_verify() contra hash.
 */
function verificar_senha(array $usuario, string $senha): bool {
    if (SECURE) {
        // Em modo seguro usamos o hash bcrypt (coluna senha_hash).
        return password_verify($senha, (string) ($usuario['senha_hash'] ?? ''));
    }
    // Modo vulnerável: comparação direta contra a senha em texto puro.
    return hash_equals((string) $usuario['senha'], $senha);
}

function login_user(array $usuario): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = $usuario['id'];
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}

/**
 * Redireciona para um destino vindo do parâmetro ?next=.
 * Vulnerável: aceita qualquer URL, inclusive domínios externos -> CWE-601.
 * Corrigido: só permite caminhos internos relativos (allowlist).
 */
function redirect_next(string $next, string $fallback): void {
    if (SECURE) {
        // Aceita apenas caminho relativo do próprio site: começa com "/",
        // não tem "//" (que o navegador trata como host) e não tem esquema.
        $ok = $next !== ''
            && $next[0] === '/'
            && !str_starts_with($next, '//')
            && !preg_match('#^/\\\\#', $next)
            && !preg_match('#^[a-z][a-z0-9+.-]*:#i', $next);
        header('Location: ' . ($ok ? $next : $fallback));
        exit;
    }
    // Vulnerável: redireciona para onde mandarem.
    header('Location: ' . ($next !== '' ? $next : $fallback));
    exit;
}

// ---- CSRF (usado pelas páginas que escrevem dados) --------------------------

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Campo <input> pronto para colar nos formulários (só faz efeito em modo seguro). */
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

/**
 * Valida o token CSRF.
 * Vulnerável: não valida nada (retorna sempre true) -> CWE-352.
 * Corrigido: exige token igual ao da sessão.
 */
function csrf_check(): bool {
    if (!SECURE) {
        return true; // formulário aceito venha de onde vier
    }
    $enviado = $_POST['csrf'] ?? '';
    return is_string($enviado) && hash_equals($_SESSION['csrf'] ?? '', $enviado);
}
