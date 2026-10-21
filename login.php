<?php
require_once __DIR__ . '/lib/render.php';

$erro = null;
$next = $_GET['next'] ?? $_POST['next'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = $_POST['senha'] ?? '';
    $st = db()->prepare('SELECT * FROM usuarios WHERE usuario = ?');
    $st->execute([$usuario]);
    $u = $st->fetch();
    if ($u && verificar_senha($u, $senha)) {
        login_user($u);
        // Open redirect (CWE-601): o destino pós-login vem do ?next= sem validação.
        redirect_next($next, base_url() . '/dashboard.php');
    }
    $erro = 'Usuário ou senha inválidos.';
}

page_header('Entrar');
?>
<div class="card" style="max-width:420px;margin:30px auto">
  <h2>Entrar no portal</h2>
  <?php if ($erro): ?><div class="alert err"><?= e($erro) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label>Usuário</label>
    <input name="usuario" autofocus>
    <label>Senha</label>
    <input type="password" name="senha">
    <div style="margin-top:14px"><button class="btn">Entrar</button></div>
  </form>
  <p class="muted" style="margin-top:14px">Não tem conta?
     <a href="<?= e(base_url()) ?>/register.php">Cadastre-se</a>.</p>
</div>
<?php page_footer();
