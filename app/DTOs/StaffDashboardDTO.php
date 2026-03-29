<?php

namespace App\DTOs;

readonly class StaffDashboardDTO
{
    /**
     * @param array{
     *     assignedClasses: int,
     *     pendingResults: int,
     *     questionBankCount: int
     * } $stats
     * @param  array<int, array{
     *     id: string,
     *     subject: string,
     *     class: string
     * }>  $assignments
     * @param  array<int, array{
     *     id: string,
     *     title: string,
     *     time: string,
     *     location: string,
     *     type: string,
     *     color: string
     * }>  $schedule
     */
    public function __construct(
        public array $stats,
        public array $assignments,
        public array $schedule,
    ) {}
}
