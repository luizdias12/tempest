<?php

$a1 = [18, 1, 14, 16, 25];
$a2 = [2, 24, 8, 16, 1];
$a3 = [7, 18, 25, 16, 23];
$a4 = [14, 4, 3, 7, 8];

// Combina todos os arrays
$valores = array_merge($a1, $a2, $a3, $a4);

// Conta as ocorrências de cada número
$ocorrencias = array_count_values($valores);

// Filtra os números que aparecem mais de uma vez
$repetidos = array_filter(
    $ocorrencias,
    fn($quantidade) => $quantidade > 1
);

print_r($repetidos);