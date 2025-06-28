<?php
function getAvailableStatuses(array $allStatuses, string $actualStatus): array
{
    $transitions = [
        'PENDING'          => ['PROCESSING', 'CANCELLED'],
        'PROCESSING'       => ['CONFIRMED', 'CANCELLED'],
        'READY-FOR-PICKUP' => ['COLLECTED', 'CANCELLED'],
        'IN-TRANSIT'       => ['INSTALLED', 'CANCELLED'],
        'INSTALLED'        => ['COMPLETED'],
        'COLLECTED'        => ['COMPLETED'],
        'CONFIRMED'        => ['COMPLETED', 'CANCELLED'],
        'ACTIVE'           => ['INACTIVE'],
        'INACTIVE'         => ['ACTIVE'],
        'COMPLETED'        => [],
        'CANCEL'           => [],
    ];

    $allowed = $transitions[strtoupper($actualStatus)] ?? [];
    return array_values(array_filter($allStatuses, function ($status) use ($allowed) {
        return in_array(strtoupper($status['Name']), $allowed);
    }));
}
