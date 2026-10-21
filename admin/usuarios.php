<?php
require_once __DIR__ . '/../lib/render.php';
$b = base_url();

// Mesmo problema do index: sem checagem de papel em modo vulnerável.
if (SECURE) {
    require_role('admin');
}

$usuarios = db()->query('SELECT id,nome,usuario,email,cpf,role,matricula FROM usuarios')->fetchAll();

page_header('Usuários');
?>
<div class="card">
  <h2>Usuários do portal</h2>
  <p class="muted">Lista completa — dados pessoais de todos (note CPF e e-mail).</p>
  <table>
    <tr><th>id</th><th>Nome</th><th>Usuário</th><th>E-mail</th><th>CPF</th><th>Papel</th></tr>
    <?php foreach ($usuarios as $u): ?>
      <tr><td><?= (int)$u['id'] ?></td><td><?= e($u['nome']) ?></td>
          <td><?= e($u['usuario']) ?></td><td><?= e($u['email']) ?></td>
          <td><?= e($u['cpf']) ?></td><td><?= e($u['role']) ?></td></tr>
    <?php endforeach; ?>
  </table>
</div>
<?php page_footer();
