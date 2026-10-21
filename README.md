# Portal Acadêmico IFAC — Laboratório de A01 (Broken Access Control)

Aplicação **deliberadamente vulnerável** (PHP + MySQL/MariaDB) para a aula prática
sobre a **OWASP Top 10 2025 — A01 Broken Access Control**. Os alunos encontram,
exploram e corrigem 12 vulnerabilidades no estilo CTF (cada falha revela uma flag).

> ⚠️ **AVISO DE SEGURANÇA.** Este software tem falhas de propósito. Rode **apenas**
> em rede isolada de laboratório, em máquina descartável. **Nunca** exponha à
> internet nem instale em máquina com dados reais. Path traversal e SSRF alcançam
> o sistema de arquivos e a rede do servidor.

## Começo rápido (XAMPP / Windows)

1. Baixe/clone este repositório e copie a pasta para `C:\xampp\htdocs\seguranca\`.
2. No XAMPP, inicie **Apache** e **MySQL**.
3. Abra `http://localhost/seguranca/install.php` (cria banco, tabelas e dados).
4. Acesse `http://localhost/seguranca/` — ou `http://<IP-do-professor>/seguranca/`
   pelos alunos na rede local.

Passo a passo completo (firewall, IP, limpeza): **[LEIA-ME.md](LEIA-ME.md)**.

## Documentação

- **[LEIA-ME.md](LEIA-ME.md)** — instalação, acesso em rede, contas, o toggle
  MODO VULNERÁVEL/MODO SEGURO e limpeza pós-aula.
- **[ROTEIRO-PROFESSOR.md](ROTEIRO-PROFESSOR.md)** — guia por missão (onde achar,
  como explorar, impacto, CWEs, flag e correção). **Contém o gabarito.**
- **[GABARITO-impressao.html](GABARITO-impressao.html)** — tabela das 12 flags
  para imprimir (abra no navegador → Imprimir / salvar PDF). **Material do professor.**

## Contas de demonstração

| Usuário | Senha | Papel |
|--------|-------|-------|
| `aluno1` | `123` | aluno |
| `aluno2` | `bruno` | aluno |
| `aluno3` | `senha` | aluno |
| `prof1` | `123` | professor |
| `admin` | `admin` | admin |

## As 12 missões (CWEs do A01:2025)

IDOR (639/566/284) · Forced browsing no admin (425/862/863/285/424/749) · Escalada
de privilégio (285/639) · CSRF (352/1275) · Path traversal (22/23/36) · Open
redirect (601) · SSRF (918/441) · Exposição de dados na API (200/201/359) ·
Listagem de diretório (548/552) · Backup/.env sob o web root (219/538/552/540) ·
phpinfo exposto (497/200) · Segredo no front-end (540/615/922).
