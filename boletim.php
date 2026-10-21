<?php
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/flags.php';
$eu = require_login();

// O id do "dono" do boletim vem direto da URL (chave controlada pelo usuário).
$alvo = (int) ($_GET['id'] ?? $eu['id']);

/*
 * -------- Controle de acesso --------
 * Vulnerável (CWE-639/566/284): qualquer usuário logado lê o boletim de QUALQUER
 *   id só trocando ?id=. Não há verificação de dono.
 * Corrigido: só o próprio dono (ou professor/admin) pode ver.
 */
if (SECURE) {
    $podeVer = ($alvo === (int) $eu['id'])
        || in_array($eu['role'], ['professor', 'admin'], true);
    if (!$podeVer) {
        http_response_code(403);
        page_header('Boletim');
        echo '<div class="card"><div class="alert err">403 — Você só pode ver o seu próprio boletim.</div></div>';
        page_footer();
        exit;
    }
}

$st = db()->prepare('SELECT * FROM usuarios WHERE id = ?');
$st->execute([$alvo]);
$dono = $st->fetch();

$sb = db()->prepare('SELECT * FROM boletins WHERE usuario_id = ?');
$sb->execute([$alvo]);
$boletins = $sb->fetchAll();

page_header('Boletim');

// Flag: visualizou boletim de outro aluno (IDOR efetivada).
if (!SECURE && $dono && (int) $dono['id'] !== (int) $eu['id']) {
    $f = flags_catalogo()['IDOR']['flag'];
    echo '<div class="alert ok">🚩 Você acessou o boletim de <strong>' . e($dono['nome'])
       . '</strong>, que não é você. Isso é um <strong>IDOR</strong>.<br>'
       . 'Flag: <span class="flag">' . e($f) . '</span></div>';
}
?>
<div class="card">
  <h2>Boletim de <?= e($dono['nome'] ?? 'desconhecido') ?></h2>
  <p class="muted">id=<?= (int) $alvo ?> · tente trocar o número na URL…</p>
  <?php if ($boletins): ?>
  <table>
    <tr><th>Disciplina</th><th>Nota</th><th>Situação</th><th>Observação</th></tr>
    <?php foreach ($boletins as $bo): ?>
      <tr><td><?= e($bo['disciplina']) ?></td><td><?= e($bo['nota']) ?></td>
          <td><?= e($bo['situacao']) ?></td><td><?= e($bo['observacao']) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?><p class="muted">Nenhum boletim para este id.</p><?php endif; ?>
</div>
<?php page_footer();
