<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache vide';
} else {
    echo 'OPcache non actif';
}
