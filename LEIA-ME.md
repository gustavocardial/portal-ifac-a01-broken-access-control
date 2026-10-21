# Portal Acadêmico IFAC — Laboratório de A01 (Broken Access Control)

Aplicação **deliberadamente vulnerável** para a aula prática sobre o
**OWASP Top 10 2025 — A01 Broken Access Control**. PHP + MySQL/MariaDB,
pensada para rodar no **XAMPP (Windows)** e ser acessada pelos alunos via rede local.

> ⚠️ **AVISO.** Este software tem falhas de propósito. Rode **apenas** em rede
> isolada de laboratório, em máquina descartável. **Nunca** exponha à internet
> nem instale em máquina com dados reais. Path traversal e SSRF alcançam o
> sistema de arquivos e a rede do servidor inteiro. Ao terminar a aula, derrube
> o Apache/MySQL e apague o banco `portal_ifac`.

---

## 1. Instalar no XAMPP (Windows) — passo a passo

1. Copie a pasta **`seguranca`** inteira (a que contém este arquivo) para dentro de:
   ```
   C:\xampp\htdocs\
   ```
   Fica assim: `C:\xampp\htdocs\seguranca\`

2. Abra o **XAMPP Control Panel** e dê **Start** em **Apache** e em **MySQL**.

3. No navegador da própria máquina, rode o **instalador de 1 clique**:
   ```
   http://localhost/seguranca/install.php
   ```
   Ele cria o banco `portal_ifac`, as tabelas, os usuários de exemplo e os
   arquivos do laboratório. Deve aparecer **"✔ Instalado com sucesso!"**.

4. Pronto. Acesse o portal:
   ```
   http://localhost/seguranca/
   ```

### Se o instalador der erro de conexão
O padrão do XAMPP é usuário `root` com **senha vazia**. Se o seu XAMPP tiver
senha no MySQL, edite `config.php` (constantes `DB_USER` / `DB_PASS`) — ou
defina as variáveis de ambiente `IFAC_DB_USER` / `IFAC_DB_PASS`.

---

## 2. Liberar o acesso pela rede local (alunos)

1. Descubra o IP da máquina do professor. No **Prompt de Comando**:
   ```
   ipconfig
   ```
   Anote o "Endereço IPv4" da rede (ex.: `192.168.0.42`).

2. **Firewall do Windows:** na primeira execução o Windows pode perguntar se
   permite o Apache (`httpd.exe`) na rede — marque **Redes privadas** e permita.
   Se não perguntou, libere manualmente a **porta 80 (TCP)** em
   *Firewall do Windows Defender → Configurações avançadas → Regras de entrada*.

3. Os alunos acessam, no navegador deles:
   ```
   http://192.168.0.42/seguranca/
   ```
   (troque pelo IP real da máquina do professor).

> Dica: todos precisam estar na **mesma rede Wi-Fi/cabo**. Se a sala tiver
> isolamento de clientes no Wi-Fi (AP isolation), use um switch/cabo ou um
> roteador sem esse recurso.

---

## 3. Contas de demonstração

| Usuário | Senha | Papel |
|--------|-------|-------|
| `aluno1` | `123` | aluno |
| `aluno2` | `bruno` | aluno |
| `aluno3` | `senha` | aluno |
| `prof1` | `123` | professor |
| `admin` | `admin` | admin |

Alunos também podem se cadastrar em `register.php` (cria conta de aluno).

---

## 4. O interruptor MODO VULNERÁVEL / MODO SEGURO

No topo de todas as páginas há um selo e um botão:

- **MODO VULNERÁVEL** (padrão): os ataques funcionam. É o modo da exploração.
- **MODO SEGURO**: as correções entram e os ataques passam a ser bloqueados.

O botão **"Ligar/Desligar correções"** alterna o modo **apenas no navegador do
professor** (grava um cookie). Ótimo para mostrar, ao vivo, a **mesma URL**
primeiro cedendo ao ataque e depois resistindo.

Para mudar o padrão de **todo mundo**, edite em `config.php`:
```php
define('SECURE_DEFAULT', false);  // true = seguro para todos
```

---

## 5. Como funciona a pontuação (CTF)

Cada falha explorada revela uma **flag** no formato `FLAG{...}`. O aluno envia a
flag em `http://<ip>/seguranca/placar.php` informando o nome da equipe. O placar
soma os pontos e mostra o ranking da turma. São **12 flags** no total.

O **gabarito** (todas as flags, onde achar, como explorar e como corrigir) está em
**`ROTEIRO-PROFESSOR.md`** — material do professor, não distribua aos alunos antes.

---

## 6. Testar localmente sem XAMPP (opcional, para o professor)

Com PHP e um MariaDB/MySQL na máquina:
```bash
# na pasta seguranca/
php -S 0.0.0.0:8000
# abra http://localhost:8000/install.php e depois http://localhost:8000/
```
Para apontar a credenciais diferentes das do XAMPP, use variáveis de ambiente:
`IFAC_DB_HOST, IFAC_DB_PORT, IFAC_DB_USER, IFAC_DB_PASS, IFAC_DB_NAME`.

---

## 7. Limpeza pós-aula

- XAMPP: **Stop** no Apache e no MySQL.
- Para apagar os dados: no phpMyAdmin (`http://localhost/phpmyadmin`) remova o
  banco `portal_ifac`, ou rode de novo o `install.php` (ele recria do zero).
- Apague a pasta `C:\xampp\htdocs\seguranca`.
