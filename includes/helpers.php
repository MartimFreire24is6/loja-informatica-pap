<?php
function obterEstadoStockProduto(int $stock): array
{
    if ($stock <= 0) {
        return [
            'classe' => 'bg-danger-subtle text-danger border border-danger-subtle',
            'texto' => 'Esgotado',
        ];
    }

    if ($stock <= 2) {
        return [
            'classe' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'texto' => "Últimas {$stock} un.",
        ];
    }

    return [
        'classe' => 'bg-success-subtle text-success border border-success-subtle',
        'texto' => "Em stock ({$stock})",
    ];
}
