<?php
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/flags.php';
$b = base_url();

$resultado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipe = $_POST['equipe'] ?? '';
    $flag   = $_POST['flag'] ?? '';
    $resultado = registrar_flag($equipe, $flag);
    // Lembra o nome da equipe para o próximo envio.
    if ($equipe !== '') { setcookie('equipe', $equipe, time() + 86400, '/'); }
}
$equipeAtual = $_POST['equipe'] ?? ($_COOKIE['equipe'] ?? '');

// Ranking: soma de pontos por equipe.
$ranking = db()->query(
    'SELECT equipe, SUM(pontos) AS total, COUNT(*) AS flags
     FROM placar GROUP BY equipe ORDER BY total DESC, flags DESC'
)->fetchAll();

$totalFlags = count(flags_catalogo());

page_header('Placar');
?>
<div class="card">
  <h2>Enviar flag</h2>
  <?php if ($resultado): ?>
    <div class="alert <?= $resultado['ok'] ? 'ok' : 'err' ?>">
      <?= e($resultado['msg']) ?>
      <?= $resultado['ok'] ? '(+' . (int)$resultado['pontos'] . ' pts)' : '' ?>
    </div>
  <?php endif; ?>
  <form method="post">
    <label>Equipe / seu nome</label>
    <input name="equipe" value="<?= e($equipeAtual) ?>" required>
    <label>Flag</label>
    <input name="flag" placeholder="FLAG{...}" class="mono" required>
    <div style="margin-top:14px"><button class="btn">Pontuar</button></div>
  </form>
  <p class="muted" style="margin-top:10px">São <?= $totalFlags ?> flags no total.</p>
</div>

<div class="card">
  <h2>Ranking</h2>
  <?php if ($ranking): ?>
  <table>
    <tr><th>#</th><th>Equipe</th><th>Flags</th><th>Pontos</th></tr>
    <?php $pos = 1; foreach ($ranking as $r): ?>
      <tr><td><?= $pos++ ?></td><td><?= e($r['equipe']) ?></td>
          <td><?= (int)$r['flags'] ?>/<?= $totalFlags ?></td>
          <td><strong><?= (int)$r['total'] ?></strong></td></tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?><p class="muted">Ninguém pontuou ainda. Seja o primeiro!</p><?php endif; ?>
</div>
<?php page_footer();
