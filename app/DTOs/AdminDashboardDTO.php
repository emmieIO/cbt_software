<?php

namespace App\DTOs;

readonly class AdminDashboardDTO
{
    /**
     * @param array{
     *     totalStudents: int,
     *     totalStaff: int,
     *     totalCandidates: int,
     *     totalQuestions: int,
     *     activeExams: int,
     *     totalBranches: int,
     *     systemStatus: string
     * } $stats
     * @param  array<int, array<string, mixed>>  $recentExams
     * @param  array<int, array<string, mixed>>  $recentUsers
     */
    public function __construct(
        public array $stats,
        public array $recentExams,
        public array $recentUsers,
    ) {}
}
