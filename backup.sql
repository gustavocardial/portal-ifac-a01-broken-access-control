-- Backup do Portal Acadêmico (NÃO deveria estar sob o web root!)
-- CWE-219 / CWE-538 / CWE-552
INSERT INTO usuarios (usuario,senha,role) VALUES ('admin','admin','admin');
-- FLAG{backup_sql_sob_o_webroot}
