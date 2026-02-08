<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class UserImportService
{
    public function __construct(protected UserService $userService) {}

    /**
     * Import users from a CSV or Excel file.
     *
     * @throws ValidationException
     */
    public function import(UploadedFile $file, string $role): int
    {
        $extension = $file->getClientOriginalExtension();

        $reader = match ($extension) {
            'csv' => new CsvReader,
            'xlsx' => new XlsxReader,
            default => throw ValidationException::withMessages(['file' => ['Unsupported file format. Please upload a .csv or .xlsx file.']]),
        };

        $reader->open($file->getRealPath());

        $importedCount = 0;
        $rowNumber = 0;

        // Cache classes for lookups
        $classes = SchoolClass::all()->pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);

        DB::beginTransaction();
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;

                    // Skip header row
                    if ($rowNumber === 1) {
                        continue;
                    }

                    $data = $row->toArray();

                    // Expected columns: Name, Email, Username, School_ID, Class_Name (for students)
                    if (count($data) < 4) {
                        continue;
                    }

                    [$name, $email, $username, $schoolId] = $data;
                    $className = $data[4] ?? null;

                    $classId = null;
                    if ($role === 'student' && $className) {
                        $classId = $classes[strtolower(trim((string) $className))] ?? null;
                    }

                    $dto = new UserDTO(
                        name: trim((string) $name),
                        email: trim((string) $email),
                        username: trim((string) $username),
                        school_id: trim((string) $schoolId),
                        school_class_id: $classId
                    );

                    // Check if user already exists by school_id or email
                    if (User::where('school_id', $dto->school_id)->orWhere('email', $dto->email)->exists()) {
                        continue;
                    }

                    $this->userService->createUser($dto, $role);
                    $importedCount++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'file' => ["Error at row $rowNumber: ".$e->getMessage()],
            ]);
        } finally {
            $reader->close();
        }

        return $importedCount;
    }
}
