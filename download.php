<?php
require_once __DIR__ . '/lib/render.php';
$eu = require_login();
$b  = base_url();

$file = $_GET['file'] ?? '';

if ($file !== '') {
    /*
     * Path traversal (CWE-22/23/36).
     * Vulnerável: concatena o nome recebido direto na pasta, então
     *   ?file=../.env  ou  ?file=../backup.sql  escapam do diretório.
     * Corrigido: usa basename() + realpath() e confere que o caminho final
     *   continua DENTRO de DECLARACOES_DIR.
     */
    if (SECURE) {
        $alvo = DECLARACOES_DIR . DIRECTORY_SEPARATOR . basename($file);
        $real = realpath($alvo);
        $baseReal = realpath(DECLARACOES_DIR);
        if ($real === false || !str_starts_with($real, $baseReal . DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }
    } else {
        $alvo = DECLARACOES_DIR . DIRECTORY_SEPARATOR . $file;
    }

    if (is_file($alvo)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($alvo) . '"');
        readfile($alvo);
        exit;
    }
    // Em modo vulnerável mostramos o caminho tentado (ajuda o aluno e vaza info).
    http_response_code(404);
    page_header('Download');
    echo '<div class="card"><div class="alert err">Arquivo não encontrado: '
       . e(SECURE ? basename($file) : $alvo) . '</div></div>';
    page_footer();
    exit;
}

page_header('Download de declarações');
?>
<div class="card">
  <h2>Minhas declarações</h2>
  <p class="muted">Baixe suas declarações de matrícula. O arquivo é escolhido pelo
     parâmetro <code>?file=</code> — será que dá pra pedir outros arquivos?</p>
  <ul>
    <li><a href="?file=declaracao_aluno1.txt">declaracao_aluno1.txt</a></li>
    <li><a href="?file=declaracao_aluno2.txt">declaracao_aluno2.txt</a></li>
    <li><a href="?file=declaracao_aluno3.txt">declaracao_aluno3.txt</a></li>
  </ul>
  <p class="muted">Dica: a aplicação fica em <code>htdocs/seguranca</code> e as
     declarações em <code>seguranca/storage</code>. E o <code>.env</code>, onde está?</p>
</div>
<?php page_footer();
