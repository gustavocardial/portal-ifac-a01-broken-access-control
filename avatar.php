<?php
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/flags.php';
require_once __DIR__ . '/lib/ssrf.php';
$eu = require_login();
$b  = base_url();

$url = trim($_GET['url'] ?? '');
$conteudo = null; $erro = null;

if ($url !== '') {
    if (SECURE) {
        // Corrigido: valida esquema e bloqueia destinos internos/privados.
        [$okUrl, $motivo] = ssrf_url_permitida($url);
        if (!$okUrl) {
            $erro = 'Bloqueado pela validação SSRF: ' . $motivo;
        }
    }
    if ($erro === null) {
        // Busca do lado do servidor. Em modo vulnerável aceita qualquer coisa
        // (inclusive http://127.0.0.1/..., file://, metadados internos) -> SSRF.
        $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
        $conteudo = @file_get_contents($url, false, $ctx, 0, 4096);
        if ($conteudo === false) {
            $erro = 'Não foi possível buscar a URL.';
        }
    }
}

page_header('Importar avatar');

// Flag SSRF: a resposta trazida do servidor contém o segredo do serviço interno.
if (!SECURE && is_string($conteudo) && str_contains($conteudo, flags_catalogo()['SSRF']['flag'])) {
    echo '<div class="alert ok">🚩 O servidor buscou um recurso <strong>interno</strong> '
       . 'que você não deveria alcançar. Isso é <strong>SSRF</strong>.<br>Flag: '
       . '<span class="flag">' . e(flags_catalogo()['SSRF']['flag']) . '</span></div>';
}
?>
<div class="card">
  <h2>Importar avatar por URL</h2>
  <form method="get">
    <label>URL</label>
    <input name="url" value="<?= e($url) ?>" placeholder="https://exemplo.com/foto.png">
    <div style="margin-top:14px"><button class="btn">Buscar</button>
      <a class="btn ghost" href="<?= e($b) ?>/perfil.php">Voltar</a></div>
  </form>
  <?php if ($erro): ?><div class="alert err"><?= e($erro) ?></div><?php endif; ?>
  <?php if (is_string($conteudo)): ?>
    <h3>Resposta recebida pelo servidor</h3>
    <pre class="mono" style="white-space:pre-wrap"><?= e(substr($conteudo, 0, 2000)) ?></pre>
  <?php endif; ?>
  <p class="muted">Dica: o servidor também alcança serviços que só respondem
     <strong>localmente</strong> (em <code>127.0.0.1</code>), fora do alcance da
     sua máquina na rede — por exemplo um painel interno de diagnóstico em
     <code>/internal.php</code>. Será que dá para fazer o servidor buscá-lo
     por você?</p>
</div>
<?php page_footer();
