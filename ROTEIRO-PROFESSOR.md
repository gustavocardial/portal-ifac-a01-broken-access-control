# Roteiro do Professor — A01:2025 Broken Access Control

Guia de condução da aula prática. Para cada missão: **onde encontrar**, **como
explorar**, **impacto**, **CWEs**, **a flag** e **como corrigir** (o que o MODO
SEGURO faz). Troque de modo pelo botão no topo do site para mostrar o antes/depois.

> **Material do professor — contém o gabarito das flags.** Não entregue aos alunos.

Substitua `<IP>` pelo IP da máquina do professor (ex.: `192.168.0.42`). Todos os
ataques pressupõem o **MODO VULNERÁVEL** (padrão), salvo quando indicado.

Ferramentas sugeridas para os alunos: o próprio navegador, o **DevTools** (F12),
"ver código-fonte" (Ctrl+U) e, se quiser aprofundar, um proxy como o **Burp Suite**
ou a extensão de edição de requisições.

---

## Visão geral das 12 missões

| # | Missão | CWEs | Pts | Flag |
|---|--------|------|-----|------|
| 1 | IDOR no boletim | 639, 566, 284 | 100 | `FLAG{idor_boletim_de_outro_aluno}` |
| 2 | Forced browsing no painel admin | 425, 862, 863, 285, 424, 749 | 150 | `FLAG{forced_browsing_painel_admin}` |
| 3 | Escalada de privilégio (role) | 285, 639 | 150 | `FLAG{virei_admin_sozinho}` |
| 4 | CSRF na troca de e-mail | 352, 1275 | 120 | `FLAG{csrf_trocou_meu_email}` |
| 5 | Path traversal no download | 22, 23, 36 | 150 | `FLAG{path_traversal_leu_o_env}` |
| 6 | Open redirect pós-login | 601 | 80 | `FLAG{open_redirect_phishing}` |
| 7 | SSRF no importador de avatar | 918, 441 | 150 | `FLAG{ssrf_bateu_no_localhost}` |
| 8 | API expõe dados demais | 200, 201, 359 | 120 | `FLAG{api_vazou_cpf_e_hash}` |
| 9 | Listagem de diretório | 548, 552 | 80 | `FLAG{listagem_de_diretorio_aberta}` |
| 10 | Backup e .env sob o web root | 219, 538, 552, 540 | 120 | `FLAG{backup_sql_sob_o_webroot}` |
| 11 | phpinfo exposto | 497, 200 | 60 | `FLAG{phpinfo_exposto}` |
| 12 | Segredo no front-end | 540, 615, 922 | 80 | `FLAG{segredo_no_javascript}` |

Sugestão de condução: faça **1, 2 e 3 juntas** (são o coração do A01: controle de
acesso quebrado por objeto, por função e por papel). Depois **5, 7, 6** (entradas
controladas pelo usuário). Depois a "caça ao tesouro" de vazamento: **8–12**. E a
**4** (CSRF) como peça à parte, que mostra bem o papel do cookie `SameSite`.

---

## 1. IDOR no boletim — CWE-639 / 566 / 284

**Onde:** logado como `aluno1`, acesse seu boletim em
`http://<IP>/seguranca/boletim.php?id=1`.

**Explorar:** troque o `id` na URL — `?id=2`, `?id=3`... Você lê o boletim de
outros alunos. O identificador do objeto vem do cliente e o servidor não confere
se o objeto pertence a você (*Insecure Direct Object Reference*).

**Impacto:** acesso não autorizado a notas e dados de qualquer aluno por força
bruta de um número sequencial.

**Flag:** aparece ao abrir o boletim de outro aluno → `FLAG{idor_boletim_de_outro_aluno}`.

**Correção (MODO SEGURO):** o servidor só devolve o boletim se `id` for o do
próprio usuário logado, ou se o papel for `professor`/`admin`. Veja em
`boletim.php` o bloco `if (SECURE)`. Lição: **sempre** valide a propriedade do
objeto no servidor; não confie no identificador recebido.

---

## 2. Forced browsing no painel admin — CWE-425 / 862 / 863 / 285 / 424 / 749

**Onde:** logado como `aluno1` (um aluno comum), note que **não existe** link para
a administração no menu. Mesmo assim, digite a URL:
`http://<IP>/seguranca/admin/`

**Explorar:** o painel abre normalmente, embora você não seja admin. Siga para
`admin/usuarios.php` e veja a lista completa de usuários com CPF. "Esconder o
link" do menu **não é** controle de acesso (*security through obscurity*). A
função administrativa está sem verificação de autorização (*Missing Authorization*).

**Impacto:** qualquer usuário (às vezes até sem login) alcança funções e dados
administrativos só adivinhando o caminho.

**Flag:** exibida ao abrir `admin/` sem ser admin → `FLAG{forced_browsing_painel_admin}`.

**Correção (MODO SEGURO):** `admin/index.php` e `admin/usuarios.php` chamam
`require_role('admin')`. A autorização é verificada **no servidor, em cada
recurso**, não pela presença do link. Lição: negue por padrão e cheque o papel
em todo endpoint sensível (inclusive APIs e páginas "internas").

---

## 3. Escalada de privilégio pelo perfil — CWE-285 / 639

**Onde:** `http://<IP>/seguranca/perfil.php`, logado como `aluno1`.

**Explorar:** o formulário traz um campo **"Papel (role)"**. Escolha `admin` e
salve. No DevTools dá para ver que o valor vai no POST (`role=admin`). O servidor
grava esse papel sem questionar (*mass assignment*). Volte ao painel admin: agora
você é admin "de verdade".

**Impacto:** o próprio usuário promove a si mesmo a administrador.

**Flag:** ao salvar com `role=admin` → `FLAG{virei_admin_sozinho}`.

**Correção (MODO SEGURO):** o servidor **ignora** qualquer `role` vindo do
cliente neste formulário; o papel é imutável aqui. Veja o bloco `if (SECURE)` em
`perfil.php`. Lição: campos sensíveis (papel, dono, preço, saldo) nunca devem ser
atribuídos a partir de dados do cliente — use uma allowlist de campos editáveis.

---

## 4. CSRF na troca de e-mail — CWE-352 / 1275

**Onde:** o alvo é o POST de `perfil.php` (troca de e-mail). A isca é o arquivo
`csrf_poc.html` (uma página de "prêmio" que simula o site do atacante).

**Explorar (demonstração do professor):**
1. Faça login no portal como `aluno1` em uma aba.
2. **Abra o arquivo `csrf_poc.html` direto do disco** (dê duplo-clique; a URL
   fica `file:///.../csrf_poc.html`). Abrir de disco é importante: assim o POST é
   mesmo **cross-site**, e o cookie `SameSite` passa a importar.
3. Ajuste o campo "URL do portal" para `http://<IP>/seguranca` e clique em
   **RESGATAR PRÊMIO**. A página envia, escondido, um POST para `perfil.php`
   trocando seu e-mail para `csrf@evil.com` — sem você pedir.

**Impacto:** um site malicioso realiza ações em nome da vítima autenticada
(trocar e-mail/senha, transferir, apagar) aproveitando o cookie de sessão que o
navegador envia automaticamente.

**Flag:** ao e-mail virar `...@evil.com` por essa requisição → `FLAG{csrf_trocou_meu_email}`.

**Correção (MODO SEGURO):** duas defesas entram juntas:
- **Token CSRF** (`csrf_field()`/`csrf_check()` em `lib/auth.php`): o POST só é
  aceito com um token imprevisível ligado à sessão, que o site atacante não tem.
- **Cookie de sessão `SameSite=Lax`** (em `config.php`): o navegador deixa de
  enviar o cookie em POSTs cross-site (CWE-1275). Por isso é importante abrir a
  isca de `file://`: em MODO SEGURO o ataque falha (vai parar na tela de login).

---

## 5. Path traversal no download — CWE-22 / 23 / 36

**Onde:** `http://<IP>/seguranca/download.php?file=declaracao_aluno1.txt`.

**Explorar:** o parâmetro `file` é colado direto no caminho da pasta de
declarações (`seguranca/storage`). Suba de diretório com `../`:
```
download.php?file=../.env
download.php?file=../backup.sql
download.php?file=../config.php
```
Você baixa arquivos fora da pasta prevista — inclusive o `.env` com segredos.

**Impacto:** leitura de arquivos arbitrários do servidor (configurações, código,
credenciais e, dependendo das permissões, arquivos do sistema).

**Flag:** dentro do `.env` baixado → `FLAG{path_traversal_leu_o_env}`
(o `../backup.sql` também revela a flag da missão 10).

**Correção (MODO SEGURO):** `download.php` aplica `basename()` para descartar
qualquer caminho e confirma com `realpath()` que o arquivo final continua
**dentro** da pasta permitida. Lição: valide com allowlist/`realpath`, nunca
concatene entrada do usuário em caminhos; guarde segredos fora do web root.

---

## 6. Open redirect pós-login — CWE-601

**Onde:** o parâmetro `next` do login:
`http://<IP>/seguranca/login.php?next=/dashboard.php`.

**Explorar:** troque o destino por um endereço **absoluto/externo**:
```
http://<IP>/seguranca/login.php?next=http://<IP>/seguranca/externo.php
```
Após autenticar, o portal te joga para o endereço arbitrário. A página
`externo.php` representa o "site do atacante" (num ataque real, uma cópia da tela
de login para roubar credenciais). O mesmo vale para `go.php?next=...`.

**Impacto:** links que começam no seu domínio confiável terminam num site
malicioso — base para phishing convincente.

**Flag:** ao cair em `externo.php` via `next` → `FLAG{open_redirect_phishing}`.

**Correção (MODO SEGURO):** `redirect_next()` (em `lib/auth.php`) só aceita
**caminhos internos relativos** (começam com `/`, sem esquema e sem `//`);
qualquer URL externa cai no destino padrão (`/dashboard.php`). Lição: valide o
destino contra uma allowlist; não redirecione para valores crus do usuário.

---

## 7. SSRF no importador de avatar — CWE-918 / 441

**Onde:** `http://<IP>/seguranca/perfil.php` → "Foto de perfil por URL", ou direto
em `http://<IP>/seguranca/avatar.php?url=...`.

**Explorar:** o servidor **busca** a URL que você informar. Aponte para um
recurso que só o servidor alcança:
```
avatar.php?url=http://127.0.0.1/seguranca/internal.php
```
A página `internal.php` recusa (403) quem a acessa pela rede, mas responde ao
**próprio servidor** (loopback). Via avatar, o servidor a busca por você e devolve
o conteúdo — credenciais internas e a flag. (Em nuvem, o alvo clássico seria o
endpoint de metadados `169.254.169.254`.)

**Impacto:** o servidor é usado como "procurador confuso" (*confused deputy*) para
alcançar serviços internos, a rede interna e metadados de nuvem.

**Flag:** no conteúdo trazido de `internal.php` → `FLAG{ssrf_bateu_no_localhost}`.

**Correção (MODO SEGURO):** `lib/ssrf.php` valida a URL antes de buscar: aceita só
`http/https` e **bloqueia endereços privados/loopback/reservados**, resolvendo o
host e rejeitando IPs internos (inclusive IP literal). Lição: allowlist de
destinos, bloqueio de faixas internas e atenção a DNS rebinding.

> Observação de laboratório: se você testar tudo na **própria** máquina do
> servidor, `internal.php` vai te responder (você também é loopback). O efeito
> "só o servidor alcança" aparece quando o aluno acessa de **outra** máquina da
> rede: aí `internal.php` direto dá 403, mas via SSRF funciona.

---

## 8. API expõe dados demais — CWE-200 / 201 / 359

**Onde:** `http://<IP>/seguranca/api/alunos.php`.

**Explorar:** a API devolve um JSON com **todos** os usuários e **todas** as
colunas — inclusive `senha` (texto puro), `senha_hash` e `cpf`. Não há
autenticação nem filtro de campos.

**Impacto:** vazamento massivo de dados pessoais (LGPD) e de credenciais.

**Flag:** no campo `_flag` do JSON → `FLAG{api_vazou_cpf_e_hash}`.

**Correção (MODO SEGURO):** o endpoint passa a exigir login + papel
`professor`/`admin` e devolve **apenas** campos não sensíveis (`id`, `nome`,
`matricula`). Lição: serialize só o necessário (DTO/allowlist de campos), nunca
`SELECT *` direto para a resposta; proteja a API com a mesma autorização das telas.

---

## 9. Listagem de diretório — CWE-548 / 552

**Onde:** `http://<IP>/seguranca/uploads/`.

**Explorar:** o Apache mostra o índice de arquivos da pasta (habilitado pelo
`uploads/.htaccess` com `Options +Indexes`). Você navega e baixa coisas que não
deveriam ser públicas, como `comprovante_pagamento_aluno2.pdf` e
`notas_internas.txt`.

**Impacto:** descoberta e download de arquivos sensíveis sem nenhum link para eles.

**Flag:** dentro de `uploads/notas_internas.txt` → `FLAG{listagem_de_diretorio_aberta}`.

**Correção:** desligue a listagem — `Options -Indexes` (no `.htaccess` ou na
config do Apache) — e sirva uploads por um script que valide autorização. Lição:
não confie que "ninguém sabe o caminho".

---

## 10. Backup e .env sob o web root — CWE-219 / 538 / 552 / 540

**Onde:** direto na raiz do app:
```
http://<IP>/seguranca/backup.sql
http://<IP>/seguranca/.env
```

**Explorar:** arquivos de backup e de configuração "esquecidos" dentro da pasta
pública são servidos como texto. O `.env` traz credenciais do banco e segredos; o
`backup.sql` traz dump com dados. (Dá para chegar neles também pelo path traversal
da missão 5.)

**Impacto:** exposição direta de credenciais e dados — comprometimento total.

**Flag:** dentro de `backup.sql` → `FLAG{backup_sql_sob_o_webroot}`
(e o `.env` traz a flag da missão 5).

**Correção:** **nunca** guarde segredos, backups ou dumps dentro do web root.
Coloque-os fora de `htdocs` e/ou negue o acesso no servidor. Lição: o que está sob
o web root é potencialmente público.

---

## 11. phpinfo exposto — CWE-497 / 200

**Onde:** `http://<IP>/seguranca/phpinfo.php`.

**Explorar:** a página de diagnóstico revela versões, caminhos absolutos,
extensões, variáveis de ambiente e configuração do PHP — um mapa do servidor para
o atacante. (Note também que, em modo vulnerável, erros do banco aparecem na tela
com detalhes — outro vazamento, em `lib/db.php`.)

**Impacto:** reconhecimento facilitado; detalhes que ajudam a encadear outros
ataques.

**Flag:** no topo da página → `FLAG{phpinfo_exposto}`.

**Correção (MODO SEGURO):** `phpinfo.php` responde 403 e os erros de banco viram
mensagem genérica. Lição: remova páginas de diagnóstico de produção; não exiba
stack traces ao usuário.

---

## 12. Segredo no front-end — CWE-540 / 615 / 922

**Onde:** `http://<IP>/seguranca/assets/js/app.js` (ou Ctrl+U em qualquer página
e abra o JS).

**Explorar:** o arquivo tem um **comentário "TODO"** com uma `API_KEY` e a flag, e
ainda guarda um "token de sessão" no **`localStorage`** (visível no DevTools →
Application → Local Storage).

**Impacto:** tudo que vai ao navegador é visível ao usuário. Segredos em JS/HTML
vazam; dados sensíveis em `localStorage` ficam expostos a qualquer script
(inclusive XSS) e persistem no dispositivo.

**Flag:** no comentário do `app.js` → `FLAG{segredo_no_javascript}`.

**Correção:** segredos ficam **só no servidor**; o front recebe apenas o que
precisa. Dados de sessão vão em cookie `HttpOnly`, não em `localStorage`. Remova
comentários com credenciais antes de publicar. Lição: não há segredo no cliente.

---

## Encerramento sugerido

Fechando a aula, amarre o conceito do A01: **todo controle de acesso precisa ser
decidido e aplicado no servidor, negando por padrão**, sobre o objeto (missão 1),
a função (missão 2) e o papel (missão 3) — e que "esconder" (link oculto, caminho
difícil, segredo no front) **não é** controle de acesso (missões 9–12). Mostre o
mesmo fluxo em MODO SEGURO para fixar cada correção.
