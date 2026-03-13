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
     *     totalExams: int,
     *     activeExams: int,
     *     totalBranches: int,
     *     totalClasses: int,
     *     totalSubjects: int,
     *     systemStatus: string,
     *     subjectBreakdown: array<int, array{name: string, count: int}>
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
