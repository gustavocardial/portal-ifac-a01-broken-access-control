<?php
/**
 * Página de diagnóstico "esquecida" (CWE-497 / CWE-200).
 * Vulnerável: expõe phpinfo() para qualquer um — caminhos, versões, variáveis
 *   de ambiente, módulos... um prato cheio para o atacante mapear o servidor.
 * Corrigido: bloqueada.
 */
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/flags.php';

if (SECURE) {
    http_response_code(403);
    exit('403 — Página de diagnóstico desativada em produção.');
}

echo '<div style="font-family:system-ui;background:#05281a;color:#7bffb0;'
   . 'padding:10px;border:1px dashed #2ecc71">🚩 phpinfo exposto. Flag: '
   . flags_catalogo()['PHPINFO']['flag'] . '</div>';
phpinfo();
