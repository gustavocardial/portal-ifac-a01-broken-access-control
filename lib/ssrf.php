<?php
/**
 * Validação anti-SSRF usada no MODO SEGURO do avatar.php.
 * Em modo vulnerável nada disso é chamado.
 */

function ip_e_privado(string $ip): bool {
    // Bloqueia loopback, link-local, privados e reservados (IPv4/IPv6).
    return filter_var($ip, FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

/**
 * @return array{0:bool,1:string}  [permitida, motivo]
 */
function ssrf_url_permitida(string $url): array {
    $p = parse_url($url);
    if ($p === false || empty($p['scheme'])) {
        return [false, 'URL malformada.'];
    }
    if (!in_array(strtolower($p['scheme']), ['http', 'https'], true)) {
        return [false, 'esquema não permitido (use http/https).'];
    }
    if (empty($p['host'])) {
        return [false, 'URL sem host.'];
    }
    $host = $p['host'];

    // Caso o host já seja um IP literal (ex.: http://127.0.0.1/), valida direto.
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        if (ip_e_privado($host)) {
            return [false, "endereço interno/privado ($host)."];
        }
        return [true, 'ok'];
    }

    // Resolve TODOS os IPs do host e rejeita se qualquer um for interno
    // (defesa contra DNS rebinding simples).
    $ips = [];
    foreach (['A' => DNS_A, 'AAAA' => DNS_AAAA] as $rec) {
        foreach (@dns_get_record($host, $rec) ?: [] as $r) {
            $ips[] = $r['ip'] ?? ($r['ipv6'] ?? null);
        }
    }
    if (!$ips) {
        $resolved = @gethostbyname($host);
        if ($resolved && $resolved !== $host) { $ips[] = $resolved; }
    }
    if (!$ips) {
        return [false, 'Não foi possível resolver o host.'];
    }
    foreach ($ips as $ip) {
        if ($ip && ip_e_privado($ip)) {
            return [false, "host aponta para endereço interno/privado ($ip)."];
        }
    }
    return [true, 'ok'];
}
