<?php

namespace App\Services;

class CategorizationService
{
    /**
     * Priority-ordered rules: more specific categories come first
     */
    protected array $rules = [
        'Personal Data Sheet' => ['cs form no. 212', 'personal data sheet'],
        'Work Experience Sheet' => ['work experience sheet'],
        'Oath of Office' => ['oath of office'],
        'Certification of Assumption to Duty' => ['assumption to duty'],

        // School Property
        'ICS' => ['inventory custodian slip', 'ics'],
        'RIS' => ['requisition and issue slip', 'ris'],
    ];

    /**
     * Categorize document using OCR text and filename fallback
     */
    public function categorize(string $text, string $filename = null): ?string
    {
        $text = strtolower($text ?? '');
        $filename = strtolower($filename ?? '');

        // ✅ First: strict text matching with priority
        foreach ($this->rules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->matchStrict($text, $keyword)) {
                    return $category;
                }
            }
        }

        // ✅ Fallback: use filename if text is unclear
        foreach ($this->rules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->matchLoose($filename, $keyword)) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * Whole word/phrase match using regex for stricter checking
     */
    protected function matchStrict(string $text, string $keyword): bool
    {
        return preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $text);
    }

    /**
     * Loose matching for filename (underscores, dashes, etc.)
     */
    protected function matchLoose(string $filename, string $keyword): bool
    {
        return str_contains(str_replace(['_', '-', '.', ' '], '', $filename), str_replace(' ', '', $keyword));
    }

    /**
     * Only teacher-related categories
     */
    public function getTeacherDocumentTypes(): array
    {
        return [
            'Personal Data Sheet',
            'Work Experience Sheet',
            'Oath of Office',
            'Certification of Assumption to Duty',
        ];
    }
}
