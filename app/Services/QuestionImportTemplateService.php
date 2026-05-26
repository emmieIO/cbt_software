<?php

namespace App\Services;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class QuestionImportTemplateService
{
    public function downloadTemplate(string $filename = 'import-template.xlsx'): never
    {
        $writer = new Writer;
        $writer->openToBrowser($filename);

        foreach ($this->templateRows() as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        exit;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function templateRows(): array
    {
        return [
            [
                'subject', 'topic', 'type', 'content', 'image_url', 'option_a', 'option_b',
                'option_c', 'option_d', 'correct_answer', 'explanation',
                'marking_points', 'marking_weights', 'level',
            ],
            [
                'Mathematics', 'Algebra', 'mcq', 'What is 2 + 2?', 'https://example.com/questions/addition.png', '3', '4', '5', '6', 'b', '',
                '', '', 'js',
            ],
            [
                'English', 'Composition', 'theory', 'Write a paragraph about your school.', '', '', '', '', '', '', '',
                'Proper structure|Good grammar|Relevant content', '3|2|2', 'js',
            ],
        ];
    }
}
