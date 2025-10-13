<?php

namespace App\Services;

use Exception;

class FilipinoCharacterEncodingException extends Exception
{
    /**
     * The problematic text that caused the encoding issue
     */
    protected $problematicText;

    /**
     * Create a new Filipino character encoding exception.
     *
     * @param string $message
     * @param string|null $problematicText
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(string $message = "Filipino character encoding error", ?string $problematicText = null, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->problematicText = $problematicText;
    }

    /**
     * Get the problematic text that caused the encoding issue
     */
    public function getProblematicText(): ?string
    {
        return $this->problematicText;
    }

    /**
     * Get a user-friendly error message
     */
    public function getUserFriendlyMessage(): string
    {
        return 'Character encoding issue detected with Filipino characters (ñ, é, ó, etc.). Please contact the system administrator.';
    }

    /**
     * Get the error context for logging
     */
    public function getContext(): array
    {
        return [
            'error_type' => 'character_encoding',
            'component' => 'filipino_character_service',
            'problematic_text' => $this->problematicText,
            'recovery_action' => 'check_utf8_encoding_and_filipino_characters',
        ];
    }

    /**
     * Check if the error is related to specific Filipino characters
     */
    public function isFilipinoCharacterIssue(): bool
    {
        if (!$this->problematicText) {
            return false;
        }

        // Check for common Filipino characters that might cause encoding issues
        $filipinoCharacters = ['ñ', 'Ñ', 'é', 'É', 'í', 'Í', 'ó', 'Ó', 'ú', 'Ú', 'á', 'Á', 'ü', 'Ü'];

        foreach ($filipinoCharacters as $char) {
            if (strpos($this->problematicText, $char) !== false) {
                return true;
            }
        }

        return false;
    }
}