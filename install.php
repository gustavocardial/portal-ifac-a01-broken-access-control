<?php
/**
 * Instalador de 1 clique.
 * Cria o banco, as tabelas e o seed. Também (re)gera os arquivos "esquecidos"
 * sob o web root (.env, backup.sql) e os arquivos de exemplo em /storage e /uploads.
 *
 * Como usar: abra  http://localhost/seguranca/install.php  no navegador.
 * Não depende do cliente de linha de comando `mysql` estar no PATH.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

$log = [];
$erro = null;

try {
    // 1) Cria o banco (conexão sem schema selecionado).
    $srv = db_server();
    $srv->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` '
        . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $log[] = 'Banco `' . DB_NAME . '` pronto.';

    $pdo = db();

    // 2) Tabelas (drop + create para permitir reinstalar do zero).
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['placar', 'boletins', 'usuarios'] as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    $pdo->exec("
        CREATE TABLE usuarios (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            nome       VARCHAR(120) NOT NULL,
            usuario    VARCHAR(60)  NOT NULL UNIQUE,
            senha      VARCHAR(255) NOT NULL,   -- texto puro (modo vulnerável)
            senha_hash VARCHAR(255) NOT NULL,   -- bcrypt (modo seguro)
            email      VARCHAR(160) NOT NULL,
            cpf        VARCHAR(14)  NOT NULL,
            role       VARCHAR(20)  NOT NULL DEFAULT 'aluno',
            matricula  VARCHAR(20)  NOT NULL,
            avatar     VARCHAR(255) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $log[] = 'Tabela usuarios criada.';

    $pdo->exec("
        CREATE TABLE boletins (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id  INT NOT NULL,
            disciplina  VARCHAR(120) NOT NULL,
            nota        DECIMAL(4,2) NOT NULL,
            situacao    VARCHAR(20)  NOT NULL,
            observacao  VARCHAR(255) NULL,
            CONSTRAINT fk_bol_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $log[] = 'Tabela boletins criada.';

    $pdo->exec("
        CREATE TABLE placar (
            id       INT AUTO_INCREMENT PRIMARY KEY,
            equipe   VARCHAR(80) NOT NULL,
            codigo   VARCHAR(40) NOT NULL,
            pontos   INT NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_equipe_codigo (equipe, codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $log[] = 'Tabela placar criada.';

    // 3) Seed de usuários. senha = texto puro; senha_hash = bcrypt do mesmo valor.
    $usuarios = [
        // nome, usuario, senha, email, cpf, role, matricula
        ['Ana Aluna',      'aluno1', '123',    'ana.aluna@ifac.edu',    '111.111.111-11', 'aluno',    '2025A001'],
        ['Bruno Aluno',    'aluno2', 'bruno',  'bruno@ifac.edu',        '222.222.222-22', 'aluno',    '2025A002'],
        ['Clara Aluna',    'aluno3', 'senha',  'clara@ifac.edu',        '333.333.333-33', 'aluno',    '2025A003'],
        ['Prof. Pereira',  'prof1',  '123',    'pereira@ifac.edu',      '444.444.444-44', 'professor','PROF010'],
        ['Admin do Portal','admin',  'admin',  'admin@ifac.edu',        '000.000.000-00', 'admin',    'ADM001'],
    ];
    $ins = $pdo->prepare('INSERT INTO usuarios
        (nome,usuario,senha,senha_hash,email,cpf,role,matricula,avatar)
        VALUES (?,?,?,?,?,?,?,?,?)');
    foreach ($usuarios as $u) {
        $ins->execute([
            $u[0], $u[1], $u[2], password_hash($u[2], PASSWORD_BCRYPT),
            $u[3], $u[4], $u[5], $u[6], 'default.png',
        ]);
    }
    $log[] = count($usuarios) . ' usuários inseridos.';

    // 4) Seed de boletins (alunos 1,2,3 = ids 1,2,3).
    $boletins = [
        [1, 'Algoritmos',           9.2, 'Aprovado', 'Ótimo desempenho'],
        [1, 'Banco de Dados',       7.5, 'Aprovado', ''],
        [2, 'Redes de Computadores',5.0, 'Recuperação', 'Faltou às provas'],
        [2, 'Algoritmos',           8.1, 'Aprovado', ''],
        [3, 'Segurança da Informação',10.0,'Aprovado', 'Achou todas as flags :)'],
    ];
    $insb = $pdo->prepare('INSERT INTO boletins
        (usuario_id,disciplina,nota,situacao,observacao) VALUES (?,?,?,?,?)');
    foreach ($boletins as $b) { $insb->execute($b); }
    $log[] = count($boletins) . ' boletins inseridos.';

    // 5) Arquivos "esquecidos" sob o web root (CWE-219/538/540/552).
    //    Parte da lição: segredos NUNCA deveriam estar aqui.
    @file_put_contents(APP_ROOT . '/.env',
        "APP_ENV=producao\n" .
        "DB_HOST=127.0.0.1\nDB_USER=root\nDB_PASS=\nDB_NAME=portal_ifac\n" .
        "APP_SECRET=sup3r_s3cr3t_do_portal\n" .
        "# Parabéns por achar o .env!\n" .
        "FLAG=FLAG{path_traversal_leu_o_env}\n");

    @file_put_contents(APP_ROOT . '/backup.sql',
        "-- Backup do Portal Acadêmico (NÃO deveria estar sob o web root!)\n" .
        "-- CWE-219 / CWE-538 / CWE-552\n" .
        "INSERT INTO usuarios (usuario,senha,role) VALUES ('admin','admin','admin');\n" .
        "-- FLAG{backup_sql_sob_o_webroot}\n");

    // 6) Declarações dos alunos (o que o download.php DEVERIA servir).
    @mkdir(DECLARACOES_DIR, 0777, true);
    @file_put_contents(DECLARACOES_DIR . '/declaracao_aluno1.txt',
        "Declaração de matrícula — Ana Aluna (2025A001).\n");
    @file_put_contents(DECLARACOES_DIR . '/declaracao_aluno2.txt',
        "Declaração de matrícula — Bruno Aluno (2025A002).\n");
    @file_put_contents(DECLARACOES_DIR . '/declaracao_aluno3.txt',
        "Declaração de matrícula — Clara Aluna (2025A003).\n");

    // 7) Uploads de exemplo (para a listagem de diretório - CWE-548).
    @mkdir(UPLOAD_DIR, 0777, true);
    @file_put_contents(UPLOAD_DIR . '/default.png', "PNG falso (placeholder)\n");
    @file_put_contents(UPLOAD_DIR . '/comprovante_pagamento_aluno2.pdf',
        "Comprovante de pagamento (PDF falso) — dados sensíveis de Bruno.\n");
    @file_put_contents(UPLOAD_DIR . '/notas_internas.txt',
        "Rascunho interno da coordenação — não deveria estar público.\n" .
        "FLAG{listagem_de_diretorio_aberta}\n");

    $log[] = 'Arquivos de exemplo (.env, backup.sql, storage/, uploads/) gravados.';

} catch (Throwable $e) {
    $erro = $e->getMessage();
}

$b = base_url();
?>
<!doctype html>
<html lang="pt-br"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalador — Portal Acadêmico IFAC</title>
<style>
 body{font:15px/1.6 system-ui,Segoe UI,Arial;background:#0f1420;color:#e8edf6;max-width:760px;margin:40px auto;padding:0 16px}
 .card{background:#1a2233;border:1px solid #2b3850;border-radius:12px;padding:20px;margin:16px 0}
 code,pre{background:#0b1220;border:1px solid #2b3850;border-radius:6px;padding:2px 6px}
 .ok{color:#2ecc71}.err{color:#ff5c7a}
 a.btn{display:inline-block;background:#4f8cff;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;margin-top:8px}
 li{margin:2px 0}
</style></head><body>
<h1>🎓 Instalador — Portal Acadêmico IFAC</h1>
<?php if ($erro): ?>
  <div class="card">
    <h2 class="err">Falha na instalação</h2>
    <p>Mensagem do banco:</p>
    <pre><?= htmlspecialchars($erro) ?></pre>
    <p>Verifique no XAMPP se o <strong>MySQL</strong> está iniciado e se as
       credenciais em <code>config.php</code> conferem (padrão: usuário
       <code>root</code>, senha vazia).</p>
  </div>
<?php else: ?>
  <div class="card">
    <h2 class="ok">✔ Instalado com sucesso!</h2>
    <ul>
      <?php foreach ($log as $l): ?><li class="ok">✔ <?= htmlspecialchars($l) ?></li><?php endforeach; ?>
    </ul>
    <a class="btn" href="<?= htmlspecialchars($b) ?>/index.php">Ir para o portal →</a>
  </div>
  <div class="card">
    <h3>Contas de demonstração</h3>
    <table>
      <tr><td><code>aluno1</code></td><td><code>123</code></td><td>aluno</td></tr>
      <tr><td><code>prof1</code></td><td><code>123</code></td><td>professor</td></tr>
      <tr><td><code>admin</code></td><td><code>admin</code></td><td>admin</td></tr>
    </table>
    <p class="err"><strong>Lembrete:</strong> app vulnerável de propósito. Só em
       rede de laboratório. Depois da aula, derrube o site e/ou apague o banco
       <code><?= DB_NAME ?></code>.</p>
  </div>
<?php endif; ?>
</body></html>
