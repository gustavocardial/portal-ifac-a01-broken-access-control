<?php
require_once __DIR__ . '/lib/render.php';

$erro = null; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome    = trim($_POST['nome'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = $_POST['senha'] ?? '';
    $email   = trim($_POST['email'] ?? '');
    if ($nome && $usuario && $senha) {
        try {
            $st = db()->prepare('INSERT INTO usuarios
                (nome,usuario,senha,senha_hash,email,cpf,role,matricula,avatar)
                VALUES (?,?,?,?,?,?,?,?,?)');
            $st->execute([$nome, $usuario, $senha,
                password_hash($senha, PASSWORD_BCRYPT),
                $email ?: $usuario . '@ifac.edu', '000.000.000-00',
                'aluno', 'NOVA', 'default.png']);
            $ok = true;
        } catch (PDOException $e) {
            $erro = 'Não foi possível cadastrar (usuário já existe?).';
        }
    } else {
        $erro = 'Preencha nome, usuário e senha.';
    }
}

page_header('Cadastro');
?>
<div class="card" style="max-width:420px;margin:30px auto">
  <h2>Criar conta de aluno</h2>
  <?php if ($ok): ?>
    <div class="alert ok">Conta criada! <a href="<?= e(base_url()) ?>/login.php">Entrar</a>.</div>
  <?php endif; ?>
  <?php if ($erro): ?><div class="alert err"><?= e($erro) ?></div><?php endif; ?>
  <form method="post">
    <label>Nome</label><input name="nome">
    <label>Usuário</label><input name="usuario">
    <label>E-mail</label><input name="email">
    <label>Senha</label><input type="password" name="senha">
    <div style="margin-top:14px"><button class="btn">Cadastrar</button></div>
  </form>
</div>
<?php page_footer();
