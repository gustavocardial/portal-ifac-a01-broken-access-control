/*
 * app.js — Portal Acadêmico IFAC
 *
 * ATENÇÃO (lição CWE-540/615): NUNCA deixe segredos no código do front-end.
 * Tudo que vai para o navegador é visível para o usuário (Ctrl+U / DevTools).
 *
 * TODO: remover antes de publicar  <-- este comentário "esquecido" vaza segredo:
 *   API_KEY = "sk_live_ifac_8f3a1c90d7"   // chave da integração de notas
 *   FLAG{segredo_no_javascript}
 */

// CWE-922: guardar "token de sessão" em localStorage expõe o valor a qualquer
// script da página (incluindo XSS) e ele persiste no navegador. Dado sensível
// deveria ficar em cookie HttpOnly, não aqui.
(function () {
  try {
    if (!localStorage.getItem('auth_token')) {
      localStorage.setItem('auth_token', 'eyJhbGciOiJIUzI1NiJ9.ZmFrZS1kZW1v.ifac');
    }
  } catch (e) { /* ignora navegadores sem storage */ }
})();
