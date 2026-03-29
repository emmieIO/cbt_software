<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionOverviewController extends Controller
{
    /**
     * Display the system permission architecture for board review.
     */
    public function __invoke(Request $request): Response
    {
        $groups = [
            'System Governance' => [
                [
                    'name' => 'sys:manage_schools',
                    'description' => 'Global authority to create and configure Chrisland branches/campuses.',
                    'usage' => 'Super Admin Only',
                    'impact' => 'Critical: Multi-tenancy control.',
                ],
                [
                    'name' => 'sys:manage_settings',
                    'description' => 'Control over system-wide RBAC, roles, and master toggle configurations.',
                    'usage' => 'Admin > RBAC',
                    'impact' => 'High: Access governance.',
                ],
            ],
            'Administration' => [
                [
                    'name' => 'staff:* (view, create, edit, delete)',
                    'description' => 'Granular CRUD operations for Staff accounts.',
                    'usage' => 'Admin > Staff',
                    'impact' => 'High: Personnel data control.',
                ],
                [
                    'name' => 'student:* (view, create, edit, delete)',
                    'description' => 'Granular CRUD operations for Student accounts.',
                    'usage' => 'Admin > Students',
                    'impact' => 'High: Candidate data control.',
                ],
                [
                    'name' => 'admin:manage_setup',
                    'description' => 'Configuration of Academic Sessions, Terms, and Classes.',
                    'usage' => 'Admin > School Setup',
                    'impact' => 'Medium: Operational structure.',
                ],
                [
                    'name' => 'admin:manage_enrollment',
                    'description' => 'Authority to assign students to classes and manage promotions.',
                    'usage' => 'Admin > Enrollment',
                    'impact' => 'Medium: Student mapping.',
                ],
                [
                    'name' => 'admin:manage_admissions',
                    'description' => 'Management of entrance exam candidates and admission status.',
                    'usage' => 'Admin > Admissions',
                    'impact' => 'Medium: New student entry.',
                ],
                [
                    'name' => 'admin:manage_batches',
                    'description' => 'Control over entrance exam groups and batch settings.',
                    'usage' => 'Admin > Entrance Batches',
                    'impact' => 'Medium: Admission grouping.',
                ],
            ],
            'Curriculum & Question Bank' => [
                [
                    'name' => 'admin:manage_curriculum',
                    'description' => 'Definition of the Subject syllabus and Topic tree.',
                    'usage' => 'Admin > Curriculum',
                    'impact' => 'Medium: Content structure.',
                ],
                [
                    'name' => 'bank:view',
                    'description' => 'Read-only access to the Universal Question Repository.',
                    'usage' => 'Staff > Question Bank',
                    'impact' => 'Low: Visibility only.',
                ],
                [
                    'name' => 'bank:create',
                    'description' => 'Permission to contribute new questions to the repository.',
                    'usage' => 'Staff > Add New Question',
                    'impact' => 'Low: Repository growth.',
                ],
                [
                    'name' => 'bank:edit',
                    'description' => 'Modify existing repository items.',
                    'usage' => 'Staff > Edit Question',
                    'impact' => 'Medium: Data integrity.',
                ],
                [
                    'name' => 'bank:delete',
                    'description' => 'Permanent removal of items from the bank.',
                    'usage' => 'Staff > Question Bank',
                    'impact' => 'Medium: Content retention.',
                ],
                [
                    'name' => 'bank:manage',
                    'description' => 'Advanced repository management including bulk actions.',
                    'usage' => 'Staff > Bulk Operations',
                    'impact' => 'High: Mass data management.',
                ],
                [
                    'name' => 'bank:use_ai',
                    'description' => 'Access to the AI Question Generation engine.',
                    'usage' => 'Staff > AI Lab',
                    'impact' => 'Efficiency: Automated content.',
                ],
                [
                    'name' => 'bank:export',
                    'description' => 'Download repository data into Excel/CSV formats.',
                    'usage' => 'Staff > Export',
                    'impact' => 'High: Data exfiltration/Backup.',
                ],
            ],
            'Examination Control' => [
                [
                    'name' => 'exam:create',
                    'description' => 'Initialization of new assessment configurations.',
                    'usage' => 'Staff > New Exam',
                    'impact' => 'High: Exam lifecycle start.',
                ],
                [
                    'name' => 'exam:edit',
                    'description' => 'Modify timing, duration, and instructions of active exams.',
                    'usage' => 'Staff > Edit Exam',
                    'impact' => 'High: Integrity of testing.',
                ],
                [
                    'name' => 'exam:delete',
                    'description' => 'Removal of assessments from the vault.',
                    'usage' => 'Staff > Manage Exams',
                    'impact' => 'High: Schedule disruption.',
                ],
                [
                    'name' => 'exam:view',
                    'description' => 'Ability to see the status and details of upcoming assessments.',
                    'usage' => 'Staff/Student Portals',
                    'impact' => 'Low: General visibility.',
                ],
                [
                    'name' => 'exam:take',
                    'description' => 'Authority to enter the CBT environment and submit responses.',
                    'usage' => 'Student > Active Exam',
                    'impact' => 'Critical: Core student function.',
                ],
                [
                    'name' => 'exam:manage_entrance',
                    'description' => 'Specialized control over multi-subject admission testing.',
                    'usage' => 'Admin > Admissions',
                    'impact' => 'High: Admission integrity.',
                ],
            ],
            'Results & Analytics' => [
                [
                    'name' => 'results:view',
                    'description' => 'Access to score sheets, grades, and performance reports.',
                    'usage' => 'Staff > Results',
                    'impact' => 'Reporting: Performance tracking.',
                ],
                [
                    'name' => 'results:grade',
                    'description' => 'Manual review and correction of automated scores if needed.',
                    'usage' => 'Staff > Grading',
                    'impact' => 'Medium: Scoring accuracy.',
                ],
            ],
        ];

        return Inertia::render('Admin/RBAC/PermissionsOverview', [
            'groups' => $groups,
        ]);
    }
}
