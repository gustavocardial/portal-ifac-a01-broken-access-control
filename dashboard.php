<?php
require_once __DIR__ . '/lib/render.php';
$u = require_login();
$b = base_url();

// Boletins do próprio usuário.
$st = db()->prepare('SELECT * FROM boletins WHERE usuario_id = ?');
$st->execute([$u['id']]);
$boletins = $st->fetchAll();

page_header('Painel');
?>
<div class="card">
  <h2>Olá, <?= e($u['nome']) ?> <span class="muted">(<?= e($u['role']) ?>)</span></h2>
  <p class="muted">Matrícula <?= e($u['matricula']) ?> · <?= e($u['email']) ?></p>
  <p>
    <a class="btn" href="<?= e($b) ?>/perfil.php">Meu perfil</a>
    <a class="btn ghost" href="<?= e($b) ?>/boletim.php?id=<?= (int)$u['id'] ?>">Meu boletim</a>
    <?php if ($u['role'] === 'admin'): ?>
      <a class="btn ghost" href="<?= e($b) ?>/admin/">Área administrativa</a>
    <?php endif; ?>
  </p>
</div>

<div class="card">
  <h3>Resumo das disciplinas</h3>
  <?php if ($boletins): ?>
  <table>
    <tr><th>Disciplina</th><th>Nota</th><th>Situação</th></tr>
    <?php foreach ($boletins as $bo): ?>
      <tr><td><?= e($bo['disciplina']) ?></td><td><?= e($bo['nota']) ?></td><td><?= e($bo['situacao']) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?><p class="muted">Sem lançamentos.</p><?php endif; ?>
</div>
<?php page_footer();
