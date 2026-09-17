<?php

use App\Service\AuthService;

dumper($data);

if (
    !AuthService::hasPermission('lideres rh') &&
    !AuthService::hasPermission('gestao de processos') &&
    !AuthService::hasPermission('ti')
) {
    echo 'Acesso não permitido!';
} else {
    echo 'Acesso permitido!';
}
