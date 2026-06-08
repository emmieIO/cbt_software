# Change Map

Use this as the first navigation point when you want to modify a feature.

## Quick Rule

- Portal routing starts in:
  - `routes/admin.php`
  - `routes/staff.php`
  - `routes/student.php`
- Backend behavior usually lives in:
  - `app/Http/Controllers/*`
  - `app/Services/*`
  - `app/Repositories/*`
- Frontend pages live in:
  - `resources/js/Pages/*`
- Feature tests live in:
  - `tests/Feature/*`

## Where To Change What

### Authentication + Portal Redirects

- Routes:
  - `routes/admin.php`
  - `routes/staff.php`
  - `routes/student.php`
- Logic:
  - `app/Services/AuthService.php`
  - `app/Services/NotificationService.php`
  - `app/Services/ProfileService.php`
  - `bootstrap/app.php`
- UI:
  - `resources/js/Pages/Admin/Login.vue`
  - `resources/js/Pages/Staff/Login.vue`
  - `resources/js/Pages/Student/Login.vue`
- Tests:
  - `tests/Feature/SecurityTest.php`

### RBAC / Permissions / Roles

- Routes:
  - `routes/admin.php` (RBAC group)
- Controllers:
  - `app/Http/Controllers/Admin/RoleController.php`
  - `app/Http/Controllers/Admin/PermissionController.php`
  - `app/Http/Controllers/Admin/PermissionOverviewController.php`
- Services:
  - `app/Services/Rbac/PermissionOverviewService.php`
- Config:
  - `config/permission.php`
- Seeder:
  - `database/seeders/RevampPermissionsSeeder.php`
- UI:
  - `resources/js/Pages/Admin/RBAC/*`

### Question Bank (Staff)

- Routes:
  - `routes/staff.php` (`questions.*`)
- Controller:
  - `app/Http/Controllers/Staff/StaffQuestionController.php`
- Services:
  - `app/Services/QuestionService.php`
  - `app/Services/Question/QuestionPayloadService.php`
  - `app/Services/Question/QuestionDtoFactory.php`
  - `app/Services/Question/QuestionMediaService.php`
  - `app/Services/BulkImportService.php`
  - `app/Services/BulkExportService.php`
- Models:
  - `app/Models/Question.php`
  - `app/Models/Option.php`
  - `app/Models/Topic.php`
  - `app/Models/Subject.php`
- UI:
  - `resources/js/Pages/QuestionBank/*`
- Tests:
  - `tests/Unit/Repositories/QuestionRepositoryTest.php`

### Exam Creation + Allocation (Staff)

- Routes:
  - `routes/staff.php` (`exams.create`, `exams.store`, `exams.questions*`)
- Controller:
  - `app/Http/Controllers/Staff/ExamController.php`
- Requests:
  - `app/Http/Requests/Exam/StoreExamRequest.php`
  - `app/Http/Requests/Exam/UpdateExamRequest.php`
  - `app/Http/Requests/Exam/UpdateExamQuestionsRequest.php`
  - `app/Http/Requests/Exam/AutoSelectQuestionsRequest.php`
- Services:
  - `app/Services/ExamService.php`
  - `app/Services/Exam/ExamReadService.php`
  - `app/Services/Exam/ExamManagementService.php`
  - `app/Services/Exam/ExamPayloadService.php`
  - `app/Services/Exam/QuestionSelectionService.php`
- Models:
  - `app/Models/Exam.php`
  - `app/Models/ExamComposition.php`
  - `app/Models/ExamQuestion.php`
- UI:
  - `resources/js/Pages/Staff/Exams/Create.vue`
  - `resources/js/Pages/Staff/Exams/Edit.vue`
  - `resources/js/Pages/Staff/Exams/Questions.vue`

### Exam Attempt Lifecycle (Student)

- Routes:
  - `routes/student.php` (`student.exams.start/show/save-answer/submit/result`)
- Controller:
  - `app/Http/Controllers/Student/StudentController.php`
- Services:
  - `app/Services/Exam/AttemptLifecycleService.php`
  - `app/Services/Exam/AttemptSubmissionService.php`
  - `app/Services/ExamService.php`
  - `app/Services/Student/StudentPortalService.php`
- Models:
  - `app/Models/ExamAttempt.php`
  - `app/Models/ExamAnswer.php`
- UI:
  - `resources/js/Pages/Student/Exams/Show.vue`
  - `resources/js/Pages/Student/Exams/Result.vue`
- Tests:
  - `tests/Feature/ExamLogicTest.php`
  - `tests/Feature/StudentExamAuthorizationTest.php`

### Results (Staff + Student)

- Staff routes:
  - `routes/staff.php` (`exams.results*`)
- Student routes:
  - `routes/student.php` (`student.results.index`, `student.exams.result`)
- Controllers:
  - `app/Http/Controllers/Staff/ExamController.php`
  - `app/Http/Controllers/Student/StudentController.php`
- Services:
  - `app/Services/Exam/ExamResultService.php`
  - `app/Services/Exam/ExamPrintService.php`
- UI:
  - `resources/js/Pages/Staff/Results/*`
  - `resources/js/Pages/Student/Results/*`
  - `resources/views/staff/exams/*print*.blade.php`

### Profile + Notifications

- Routes:
  - `routes/web.php` (`profile.*`, `notifications.*`)
- Controllers:
  - `app/Http/Controllers/ProfileController.php`
  - `app/Http/Controllers/NotificationController.php`
- Services:
  - `app/Services/ProfileService.php`
  - `app/Services/NotificationService.php`
- UI:
  - `resources/js/Pages/Profile/Show.vue`

### Admin Master Data (Sessions, Classes, Subjects, Topics, Schools)

- Routes:
  - `routes/admin.php` (`school-setup`, `curriculum`, `super-admin`)
- Controllers:
  - `app/Http/Controllers/Admin/AcademicSessionController.php`
  - `app/Http/Controllers/Admin/SchoolClassController.php`
  - `app/Http/Controllers/Admin/SubjectController.php`
  - `app/Http/Controllers/Admin/TopicController.php`
  - `app/Http/Controllers/Admin/SchoolController.php`
- Services:
  - `app/Services/AcademicSessionService.php`
  - `app/Services/SchoolClassService.php`
  - `app/Services/SubjectService.php`
  - `app/Services/TopicService.php`
  - `app/Services/SchoolService.php`
- Requests:
  - `app/Http/Requests/Admin/AcademicSessionRequest.php`
  - `app/Http/Requests/Admin/SchoolClassRequest.php`
  - `app/Http/Requests/Admin/SubjectRequest.php`
  - `app/Http/Requests/Admin/TopicRequest.php`
- UI:
  - `resources/js/Pages/Admin/Classes/*`
  - `resources/js/Pages/Admin/Subjects/*`
  - `resources/js/Pages/Admin/Topics/*`
  - `resources/js/Pages/Admin/Schools/*`

### User Management (Admin Staff/Students)

- Routes:
  - `routes/admin.php` (`admin.staff.*`, `admin.students.*`)
- Controllers:
  - `app/Http/Controllers/Admin/StaffController.php`
  - `app/Http/Controllers/Admin/StudentController.php`
- Services:
  - `app/Services/Admin/StaffManagementService.php`
  - `app/Services/Admin/StudentManagementService.php`
  - `app/Services/UserService.php`
  - `app/Services/UserImportService.php`
- Requests:
  - `app/Http/Requests/Admin/StaffRequest.php`
  - `app/Http/Requests/Admin/StaffImportRequest.php`
  - `app/Http/Requests/Admin/StudentRequest.php`
  - `app/Http/Requests/Admin/StudentImportRequest.php`
- UI:
  - `resources/js/Pages/Admin/Users/Staff*`
  - `resources/js/Pages/Admin/Users/Students*`
- Tests:
  - `tests/Unit/Repositories/UserRepositoryTest.php`

## Fast Lookup Commands

- Find route by feature term:
  - `rg -n "results|questions|exams|students" routes`
- Find backend handler:
  - `rg -n "function submitExam|function saveAnswer|function aiSelectQuestions" app/Http/Controllers app/Services`
- Find page component:
  - `rg --files resources/js/Pages | rg "Staff/Exams|Student/Exams|Admin"`

## Controller Refactor Status

### Completed In This Refactor Wave

- `app/Http/Controllers/Staff/ExamController.php`
- `app/Http/Controllers/Staff/StaffQuestionController.php`
- `app/Http/Controllers/Student/StudentController.php`
- `app/Http/Controllers/Admin/StaffController.php`
- `app/Http/Controllers/Admin/StudentController.php`
- `app/Http/Controllers/Admin/AcademicSessionController.php`
- `app/Http/Controllers/Admin/SchoolClassController.php`
- `app/Http/Controllers/Admin/SubjectController.php`
- `app/Http/Controllers/Admin/TopicController.php`
- `app/Http/Controllers/Admin/PermissionOverviewController.php`
- `app/Http/Controllers/Staff/StudentController.php`

### Next Targets (Lower Priority)

- `app/Http/Controllers/Admin/AdminController.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/NotificationController.php`
- Any new controller methods should follow:
  - FormRequest validation
  - service/action orchestration
  - thin controller response handling only

## Refactor Pattern (Use This Every Time)

1. Move validation into `FormRequest`.
2. Move query/build/side effects into a service or action class.
3. Keep controller methods short (ideally <= 25 lines).
4. Add/adjust feature test before and after extraction.
5. Refactor one endpoint group at a time, not entire controller at once.
