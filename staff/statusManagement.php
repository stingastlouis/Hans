<?php
function getAvailableStatuses(array $allStatuses, string $actualStatus, bool $needsInstallation = false, bool $installation = false): array
{
    $status = strtoupper($actualStatus);

    $transitions = [
        'PROCESSING'           => ['CONFIRMED', 'CANCELLED'],
        'READY-FOR-PICKUP'     => ['COLLECTED', 'CANCELLED'],
        'IN-TRANSIT'           => ['INSTALLED', 'CANCELLED'],
        'INSTALLED'            => ['COMPLETED'],
        'COLLECTED'            => ['COMPLETED'],
        'CONFIRMED'            => [],
        'READY-FOR-INSTALLATION' => ['COMPLETED'],
        'ACTIVE'               => ['INACTIVE'],
        'INACTIVE'             => ['ACTIVE'],
        'COMPLETED'            => [],
        'CANCEL'               => [],
        'PENDING'              => []
    ];


    if ($status === 'CONFIRMED') {
        $transitions['CONFIRMED'] = $needsInstallation
            ? ['READY-FOR-INSTALLATION', 'CANCELLED']
            : ['READY-FOR-PICKUP', 'CANCELLED'];
    }



    if ($installation) {
        if ($status === 'PENDING' && $needsInstallation) {
            $transitions['PENDING'] = ['IN-TRANSIT', 'INSTALLED'];
        }

        if ($status === 'PROCESSING' && $needsInstallation) {
            $transitions['PROCESSING'] = ['IN-TRANSIT', 'INSTALLED'];
        }
    }


    $allowed = $transitions[$status] ?? [];

    return array_values(array_filter($allStatuses, function ($status) use ($allowed) {
        return in_array(strtoupper($status['Name']), $allowed);
    }));
}
