<?php
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/flags.php';
$eu = require_login();
$b = base_url();

$msgs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF: só é validado em modo seguro (em modo vulnerável csrf_check()==true).
    if (!csrf_check()) {
        http_response_code(403);
        page_header('Perfil');
        echo '<div class="card"><div class="alert err">403 — Token CSRF inválido.</div></div>';
        page_footer();
        exit;
    }

    $novoEmail = trim($_POST['email'] ?? $eu['email']);

    /*
     * Escalada de privilégio (CWE-285/639): em modo vulnerável o campo "role"
     * vem do cliente e é gravado sem checagem (mass assignment). Em modo seguro
     * o papel NUNCA é alterável pelo próprio usuário por este formulário.
     */
    if (SECURE) {
        $novoRole = $eu['role']; // imutável aqui
    } else {
        $novoRole = $_POST['role'] ?? $eu['role'];
    }

    $up = db()->prepare('UPDATE usuarios SET email = ?, role = ? WHERE id = ?');
    $up->execute([$novoEmail, $novoRole, $eu['id']]);
    $eu['email'] = $novoEmail;
    $eu['role']  = $novoRole;

    // Flag CSRF: e-mail trocado por um POST forjado de outro site (PoC usa @evil.com).
    if (!SECURE && str_ends_with(strtolower($novoEmail), '@evil.com')) {
        $msgs[] = ['ok', '🚩 Seu e-mail foi trocado por uma requisição forjada de outro site (CSRF). '
            . 'Flag: <span class="flag">' . e(flags_catalogo()['CSRF']['flag']) . '</span>'];
    }
    // Flag privesc: virou admin sozinho.
    if (!SECURE && $novoRole === 'admin') {
        $msgs[] = ['ok', '🚩 Você alterou seu próprio papel para <strong>admin</strong>! '
            . 'Flag: <span class="flag">' . e(flags_catalogo()['PRIVESC']['flag']) . '</span>'];
    }
    if (!$msgs) {
        $msgs[] = ['ok', 'Perfil atualizado.'];
    }
}

page_header('Perfil');
foreach ($msgs as [$tipo, $txt]) {
    echo '<div class="alert ' . $tipo . '">' . $txt . '</div>';
}
?>
<div class="card">
  <h2>Meu perfil</h2>
  <p class="muted"><?= e($eu['nome']) ?> · papel atual:
     <strong><?= e($eu['role']) ?></strong> · matrícula <?= e($eu['matricula']) ?></p>

  <form method="post">
    <?= csrf_field() /* só tem efeito em modo seguro */ ?>
    <label>E-mail</label>
    <input name="email" value="<?= e($eu['email']) ?>">

    <!-- Campo "role" exposto ao cliente de propósito: mass assignment / privesc.
         Em modo seguro o servidor IGNORA este valor. -->
    <label>Papel (role) — repare que isto NÃO deveria estar editável aqui</label>
    <select name="role">
      <?php foreach (['aluno','professor','admin'] as $r): ?>
        <option value="<?= $r ?>" <?= $eu['role']===$r?'selected':'' ?>><?= $r ?></option>
      <?php endforeach; ?>
    </select>

    <div style="margin-top:14px"><button class="btn">Salvar</button></div>
  </form>
</div>

<div class="card">
  <h3>Foto de perfil por URL</h3>
  <p class="muted">Importe seu avatar a partir de um endereço (o servidor baixa a imagem).</p>
  <form method="get" action="<?= e($b) ?>/avatar.php">
    <label>URL da imagem</label>
    <input name="url" placeholder="https://exemplo.com/foto.png">
    <div style="margin-top:14px"><button class="btn">Importar</button></div>
  </form>
</div>
<?php page_footer();
