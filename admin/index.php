<?php
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/flags.php';
$b = base_url();

/*
 * Controle de acesso do painel admin.
 * Vulnerável (CWE-425/862/863/285/424/749): NÃO há verificação de papel. O link
 *   só não aparece no menu para quem não é admin — mas a URL /admin/ funciona
 *   para qualquer um (inclusive sem login). "Esconder o link" não é autorização.
 * Corrigido: exige papel admin de verdade (require_role).
 */
if (SECURE) {
    require_role('admin');
}

page_header('Administração');

if (!SECURE) {
    echo '<div class="alert ok">🚩 Você entrou no <strong>painel administrativo</strong> '
       . 'apenas adivinhando a URL (<em>forced browsing</em>), sem ser admin.<br>Flag: '
       . '<span class="flag">' . e(flags_catalogo()['ADMIN']['flag']) . '</span></div>';
}
?>
<div class="card">
  <h2>Painel administrativo</h2>
  <p class="muted">Recursos restritos — em tese, só para administradores.</p>
  <ul>
    <li><a href="<?= e($b) ?>/admin/usuarios.php">Gerenciar usuários</a></li>
    <li><a href="<?= e($b) ?>/api/alunos.php">Exportar alunos (API JSON)</a></li>
    <li><a href="<?= e($b) ?>/phpinfo.php">Diagnóstico do servidor (phpinfo)</a></li>
  </ul>
</div>
<?php page_footer();
