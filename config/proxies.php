<?php

declare(strict_types=1);

return [
    /*
     * Adresy odwrotnych proxy (np. Nginx Proxy Manager), którym wolno ustawiać
     * nagłówki X-Forwarded-*. Domyślnie sieci prywatne RFC 1918, bo aplikacja
     * jest wystawiona wyłącznie w LAN.
     *
     * Ustaw dokładny adres proxy w produkcji: każdy host, który może połączyć
     * się bezpośrednio z portem aplikacji, może podszyć się pod cudze IP
     * i obejść limity zapytań.
     */
    'trusted' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('TRUSTED_PROXIES', '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16')),
    ))),
];
