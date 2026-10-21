<?php
/**
 * Página que SIMULA um site externo controlado por um atacante.
 * No laboratório (sem internet) ela faz o papel do "destino malicioso" para o
 * qual o open redirect leva a vítima. Chegar aqui via login.php?next=<URL absoluta>
 * prova o CWE-601.
 */
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/flags.php';
page_header('⚠ Site externo');
?>
<div class="card">
  <h2>🚩 Você saiu do Portal IFAC sem perceber</h2>
  <p>Esta página representa um <strong>site externo controlado por um atacante</strong>.
     Você chegou aqui porque o portal te redirecionou para um endereço arbitrário
     passado em <code>?next=</code> — um <strong>Open Redirect (CWE-601)</strong>.
     Num ataque real, aqui estaria uma página de phishing idêntica à de login.</p>
  <p>Flag: <span class="flag"><?= e(flags_catalogo()['REDIRECT']['flag']) ?></span></p>
</div>
<?php page_footer();
