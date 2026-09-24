<?php

use App\Service\AuthService;

dumper($data);

echo "Acesso: " . AuthService::canManageCarousel();
