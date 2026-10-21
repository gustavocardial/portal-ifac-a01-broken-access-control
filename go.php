<?php
/**
 * Redirecionador genérico ("continuar para..."). Mesma falha do ?next= do login,
 * isolada para facilitar a demonstração do Open Redirect (CWE-601).
 *   Vulnerável: go.php?next=http://qualquer-lugar  -> redireciona sem validar.
 *   Corrigido:  só aceita caminhos internos relativos.
 */
require_once __DIR__ . '/lib/auth.php';
$next = $_GET['next'] ?? '';
redirect_next($next, base_url() . '/dashboard.php');
