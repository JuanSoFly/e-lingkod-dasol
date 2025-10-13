<?php

namespace App\Services;

class FilipinoCharacterService
{
    /**
     * Normalize Filipino characters for Excel export
     *
     * @param string $text
     * @return string
     */
    public function normalizeCharacters(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Convert to UTF-8 normalization form C (canonical composition)
        $normalized = normalizer_normalize($text, \Normalizer::FORM_C);

        // Ensure proper encoding for Excel compatibility
        if (!mb_check_encoding($normalized, 'UTF-8')) {
            $normalized = mb_convert_encoding($normalized, 'UTF-8', 'UTF-8');
        }

        return $normalized;
    }

    /**
     * Validate if text contains valid Filipino characters
     *
     * @param string $text
     * @return bool
     */
    public function validateFilipinoCharacters(string $text): bool
    {
        if (empty($text)) {
            return true;
        }

        // Check if text is valid UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            return false;
        }

        // Common Filipino characters using Unicode code points in hex format
        $filipinoPattern = '/[\x{00C0}-\x{00FF}\x{0100}-\x{017F}\x{1E00}-\x{1EFF}]/u';

        return preg_match($filipinoPattern, $text) !== false;
    }

    /**
     * Clean and prepare text for Excel export
     *
     * @param string $text
     * @return string
     */
    public function cleanForExcel(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Normalize characters first
        $text = $this->normalizeCharacters($text);

        // Remove control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Ensure Excel-friendly line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Trim whitespace while preserving internal spacing
        $text = trim($text);

        return $text;
    }

    /**
     * Prepare array of data for Excel export with Filipino character support
     *
     * @param array $data
     * @return array
     */
    public function prepareDataForExcel(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->cleanForExcel($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->prepareDataForExcel($value);
            }
        }

        return $data;
    }

    /**
     * Check if Excel BOM (Byte Order Mark) is needed for the data
     *
     * @param string|array $data
     * @return bool
     */
    public function needsBOM($data): bool
    {
        if (is_string($data)) {
            return $this->containsFilipinoCharacters($data);
        } elseif (is_array($data)) {
            foreach ($data as $value) {
                if (is_string($value) && $this->containsFilipinoCharacters($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if text contains Filipino characters that need special handling
     *
     * @param string $text
     * @return bool
     */
    private function containsFilipinoCharacters(string $text): bool
    {
        // Check for common Filipino characters and diacritics
        return preg_match('/[ñÑáÁéÉíÍóÓúÚüÜ]/', $text) > 0;
    }

    /**
     * Generate Excel-compatible filename with Filipino character support
     *
     * @param string $filename
     * @return string
     */
    public function generateExcelFilename(string $filename): string
    {
        // Remove characters that are problematic in filenames
        $filename = preg_replace('/[<>:"\/\\\\|?*]/', '_', $filename);

        // Normalize characters
        $filename = $this->normalizeCharacters($filename);

        // Ensure .xlsx extension
        if (!preg_match('/\.xlsx$/i', $filename)) {
            $filename .= '.xlsx';
        }

        return $filename;
    }

    /**
     * Check if text has invalid Filipino characters (T046)
     *
     * @param string $text
     * @return bool
     */
    public function hasInvalidCharacters(string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        // Check for invalid UTF-8 sequences
        if (!mb_check_encoding($text, 'UTF-8')) {
            return true;
        }

        // Check for replacement characters (indicating encoding issues)
        if (strpos($text, '�') !== false) {
            return true;
        }

        // Check for malformed Unicode sequences
        $normalized = normalizer_normalize($text, \Normalizer::FORM_C);
        if ($normalized === false) {
            return true;
        }

        return false;
    }

    /**
     * Validate and fix text encoding issues (T046)
     *
     * @param string $text
     * @return string
     * @throws FilipinoCharacterEncodingException
     */
    public function validateAndFixEncoding(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Check for invalid characters
        if ($this->hasInvalidCharacters($text)) {
            // Attempt to fix encoding issues
            $fixed = $this->attemptEncodingFix($text);

            if ($this->hasInvalidCharacters($fixed)) {
                throw new FilipinoCharacterEncodingException(
                    'Unable to fix character encoding issues',
                    $text
                );
            }

            return $fixed;
        }

        return $text;
    }

    /**
     * Attempt to fix common encoding issues (T046)
     *
     * @param string $text
     * @return string
     */
    protected function attemptEncodingFix(string $text): string
    {
        // Remove replacement characters
        $text = str_replace('�', '', $text);

        // Try common encodings that might be misinterpreted as UTF-8
        $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252'];

        foreach ($encodings as $encoding) {
            if (mb_check_encoding($text, $encoding)) {
                $converted = mb_convert_encoding($text, 'UTF-8', $encoding);
                if (mb_check_encoding($converted, 'UTF-8')) {
                    $text = $converted;
                    break;
                }
            }
        }

        // Normalize Unicode
        $normalized = normalizer_normalize($text, \Normalizer::FORM_C);
        if ($normalized !== false) {
            $text = $normalized;
        }

        return $text;
    }

    /**
     * Get character encoding diagnostic information (T046)
     *
     * @param string $text
     * @return array
     */
    public function getEncodingDiagnostics(string $text): array
    {
        if (empty($text)) {
            return ['status' => 'empty', 'issues' => []];
        }

        $issues = [];

        // Check UTF-8 validity
        if (!mb_check_encoding($text, 'UTF-8')) {
            $issues[] = 'Invalid UTF-8 encoding';
        }

        // Check for replacement characters
        if (strpos($text, '�') !== false) {
            $issues[] = 'Contains replacement characters (�)';
        }

        // Check Unicode normalization
        $normalized = normalizer_normalize($text, \Normalizer::FORM_C);
        if ($normalized === false) {
            $issues[] = 'Unicode normalization failed';
        }

        // Check for Filipino characters
        $hasFilipinoChars = $this->containsFilipinoCharacters($text);

        return [
            'status' => empty($issues) ? 'valid' : 'has_issues',
            'issues' => $issues,
            'has_filipino_characters' => $hasFilipinoChars,
            'byte_length' => strlen($text),
            'character_length' => mb_strlen($text, 'UTF-8'),
            'encoding' => mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true),
        ];
    }

    /**
     * Sanitize text for safe export while preserving Filipino characters (T046)
     *
     * @param string $text
     * @return string
     */
    public function sanitizeForExport(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Validate and fix encoding
        try {
            $text = $this->validateAndFixEncoding($text);
        } catch (FilipinoCharacterEncodingException $e) {
            // If we can't fix the encoding, remove problematic characters
            // but log the issue for debugging
            \Log::warning('Filipino character encoding issue detected and sanitized', [
                'original_text_length' => strlen($text),
                'error' => $e->getMessage(),
            ]);

            $text = $this->removeProblematicCharacters($text);
        }

        // Apply existing cleaning methods
        return $this->cleanForExcel($text);
    }

    /**
     * Remove problematic characters while preserving basic structure (T046)
     *
     * @param string $text
     * @return string
     */
    protected function removeProblematicCharacters(string $text): string
    {
        // Keep basic ASCII, common Filipino characters, and punctuation
        return preg_replace('/[^\x{20}-\x{7E}\x{00A0}-\x{00FF}\x{0100}-\x{017F}\n\r\t]/u', '', $text);
    }
}