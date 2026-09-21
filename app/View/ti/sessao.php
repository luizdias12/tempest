<?php

use App\Service\AuthService;

dumper($data);

echo '<pre>';
echo 'permitido: ' . (!AuthService::isExterno() && AuthService::hasPermission('ti'));
echo '</pre>';
echo '<br>';


if (!AuthService::isExterno() && AuthService::hasPermission('ti')) {
    echo 'Acesso permitido!';
} else {
    echo 'Acesso não permitido!';
}
