<?php
/**
 * Pulisce la cache di opcache
 */

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ Opcache pulita con successo!";
} else {
    echo "✗ Opcache non disponibile";
}

if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "\n✓ APCu cache pulita!";
}

echo "\n\nProva ora: https://www.blocksy.it/api/vote/fetch?apiKey=be8be85935f4c824b392cb91849764c5201eb6835bd0d6690c500669bf2b1385";
