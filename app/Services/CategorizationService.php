<?php

namespace App\Services;

class CategorizationService
{
    /**
     * Priority-ordered rules (most specific first).
     * Each entry maps a Category Name (as stored in DB) to an array of keywords/phrases.
     * Matching is done first against OCR text (strict phrase/word), then the filename (looser).
     *
     * NOTE:
     * - Keep highly specific/rare phrases earlier to avoid early generic matches.
     * - Use lowercase keywords only; we lowercase inputs before matching.
     */
    protected array $rules = [
        // ----- Teacher Records (existing) -----
        'Personal Data Sheet' => [
            'personal data sheet',
            'pds',
            'cs form no. 212',
            'cs form 212'
        ],
        'Work Experience Sheet' => [
            'work experience sheet',
            'work experience',
            'wes'
        ],
        'Oath of Office' => [
            'oath of office'
        ],
        'Certification of Assumption to Duty' => [
            'assumption to duty',
            'certification of assumption to duty'
        ],
        'Transcript of Records' => [
            'transcript of records',
            'tor'
        ],
        'Appointment Form' => [
            'appointment form',
            'appointment paper'
        ],
        'Daily Time Record' => [
            'daily time record',
            'dtr',
            'civil service form no. 48',
            'cs form 48'
        ],

        // ----- Teacher Records (NEW) -----
        'SAL-N' => [
            'sal-n',
            'saln',
            'statement of assets, liabilities and net worth',
            'statement of assets and liabilities',
            'assets liabilities and net worth'
        ],
        'Service credit ledgers' => [
            'service credit',
            'service credits',
            'credit ledger',
            'ledger of credits',
            'leave credits',
            'sl/vl',
            'vacation leave',
            'sick leave'
        ],
        'IPCRF' => [
            'ipcrf',
            'individual performance commitment',
            'individual performance review',
            'individual performance commitment and review'
        ],
        'NOSI' => [
            'nosi',
            'notice of salary increase'
        ],
        'NOSA' => [
            'nosa',
            'notice of salary adjustment',
            'step increment'
        ],
        'Travel order' => [
            'travel order',
            'authority to travel',
            'official business travel',
            ' ato '
        ],

        // ----- School Property -----
        // Put ICS/RIS after teacher docs to reduce early matches from files with short names.
        'ICS' => [
            'inventory custodian slip',
            'ics'
        ],
        'RIS' => [
            'requisition and issue slip',
            'ris'
        ],
    ];

    /**
     * Categorize a document based on OCR text and (optionally) filename.
     * - First pass: OCR text (strict whole-word/phrase match).
     * - Second pass: filename (loose match; ignores _, -, ., and spaces).
     *
     * @param  string|null $text     OCR-extracted text (may be empty)
     * @param  string|null $filename Original filename (with extension)
     * @return string|null           Category name (as in DB) or null if unknown
     */
    public function categorize(?string $text, ?string $filename = null): ?string
    {
        $text = $this->normalize($text ?? '');
        $filename = $this->normalize($filename ?? '');

        // 1) Strict OCR text match (highest confidence)
        foreach ($this->rules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->matchStrict($text, $keyword)) {
                    return $category;
                }
            }
        }

        // 2) Filename loose match (common fallback)
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
     * Return the list of categories that require selecting a Teacher.
     * Make sure names here exactly match the `categories.name` rows in your DB.
     */
    public function getTeacherDocumentTypes(): array
    {
        return [
            // Existing
            'Personal Data Sheet',
            'Work Experience Sheet',
            'Oath of Office',
            'Certification of Assumption to Duty',
            'Transcript of Records',
            'Appointment Form',
            'Daily Time Record',
            // New
            'SAL-N',
            'Service credit ledgers',
            'IPCRF',
            'NOSI',
            'NOSA',
            'Travel order',
        ];
    }

    /* ======================== Helpers ======================== */

    /**
     * Normalize by lowercasing and trimming.
     */
    protected function normalize(string $s): string
    {
        // using mb_* for safety with Unicode; adjust if your environment lacks mbstring
        return mb_strtolower(trim($s));
    }

    /**
     * Strict whole word/phrase match against OCR text:
     *   - Uses word boundaries around the keyword.
     *   - Case-insensitive.
     */
    protected function matchStrict(string $text, string $keyword): bool
    {
        $pattern = '/\b' . preg_quote(mb_strtolower($keyword), '/') . '\b/i';
        return (bool) preg_match($pattern, $text);
    }

    /**
     * Loose filename matching:
     *   - Remove _, -, ., and spaces from both haystack and needle.
     *   - Case-insensitive contains().
     */
    protected function matchLoose(string $filename, string $keyword): bool
    {
        $cleanHaystack = str_replace(['_', '-', '.', ' '], '', $filename);
        $cleanNeedle = str_replace(['_', '-', '.', ' '], '', mb_strtolower($keyword));
        return str_contains($cleanHaystack, $cleanNeedle);
    }
}
