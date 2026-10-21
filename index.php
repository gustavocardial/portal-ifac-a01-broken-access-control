<?php
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/flags.php';

$b = base_url();
page_header('Início');
?>
<div class="card">
  <h1>Portal Acadêmico IFAC</h1>
  <p class="muted">Laboratório prático — <strong>OWASP Top 10 2025 · A01 Broken Access Control</strong>.
     Encontre, explore e corrija as falhas. Cada missão cumprida revela uma
     <span class="flag">FLAG{...}</span> que você envia no
     <a href="<?= e($b) ?>/placar.php">placar</a>.</p>
  <p>
    <?php if (SECURE): ?>
      O site está em <span class="pill pill-secure">MODO SEGURO</span>: as correções
      estão ativas e os ataques devem falhar. Use o botão no topo para voltar ao modo vulnerável.
    <?php else: ?>
      O site está em <span class="pill pill-vuln">MODO VULNERÁVEL</span>: mãos à obra.
    <?php endif; ?>
  </p>
</div>

<div class="card">
  <h2>Missões</h2>
  <table>
    <tr><th>#</th><th>Missão</th><th>Onde começar</th><th>Pontos</th><th class="cwe">CWE</th></tr>
    <?php
    $pistas = [
      'IDOR'        => 'boletim.php?id=1',
      'ADMIN'       => 'admin/',
      'PRIVESC'     => 'perfil.php',
      'CSRF'        => 'perfil.php',
      'TRAVERSAL'   => 'download.php?file=declaracao_aluno1.txt',
      'REDIRECT'    => 'login.php?next=/dashboard.php',
      'SSRF'        => 'avatar.php',
      'APIEXPOSE'   => 'api/alunos.php',
      'DIRLIST'     => 'uploads/',
      'BACKUP'      => '(fuce a raiz do site)',
      'PHPINFO'     => '(procure uma página de diagnóstico)',
      'FRONTSECRET' => '(veja o código-fonte do front)',
    ];
    // Dicas curtas para as missões que, sozinhas, não se explicam.
    $dicas = [
      'IDOR'        => 'troque o número do id na URL.',
      'ADMIN'       => 'existe esse caminho mesmo sem link no menu?',
      'PRIVESC'     => 'o formulário deixa você escolher o seu papel…',
      'CSRF'        => 'dispare a troca de e-mail a partir de OUTRA origem; há um exemplo pronto em csrf_poc.html.',
      'TRAVERSAL'   => 'e se o file apontar para fora da pasta? (../)',
      'REDIRECT'    => 'troque o next por uma URL externa (http://…).',
      'SSRF'        => 'faça o servidor buscar um serviço interno (127.0.0.1 / internal.php).',
      'APIEXPOSE'   => 'que campos a API devolve?',
      'DIRLIST'     => 'o diretório lista os arquivos?',
      'BACKUP'      => 'nomes clássicos de arquivos esquecidos.',
      'PHPINFO'     => 'nome clássico de página de diagnóstico.',
      'FRONTSECRET' => 'leia os comentários e o localStorage.',
    ];
    $i = 1;
    foreach (flags_catalogo() as $cod => $info):
      $pista = $pistas[$cod] ?? '';
      $linkable = $pista && !str_starts_with($pista, '(');
    ?>
    <tr>
      <td><?= $i++ ?></td>
      <td><?= e($info['titulo']) ?></td>
      <td class="mono">
        <?php if ($linkable): ?>
          <a href="<?= e($b . '/' . explode(' ', $pista)[0]) ?>"><?= e($pista) ?></a>
        <?php else: ?>
          <?= e($pista) ?>
        <?php endif; ?>
        <?php if (!empty($dicas[$cod])): ?>
          <div class="muted" style="font-size:12px;margin-top:3px">💡 <?= e($dicas[$cod]) ?></div>
        <?php endif; ?>
      </td>
      <td><?= (int) $info['pontos'] ?></td>
      <td class="cwe"><?= e($info['cwe']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php page_footer();
