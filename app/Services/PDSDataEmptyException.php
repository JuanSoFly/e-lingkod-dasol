<?php

namespace App\Services;

use Exception;

class PDSDataEmptyException extends Exception
{
    /**
     * Create a new PDS data empty exception.
     *
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(string $message = "PDS data is empty or unavailable", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get a user-friendly error message
     */
    public function getUserFriendlyMessage(): string
    {
        return 'No PDS data is available for export. The employee record may be incomplete or not yet set up.';
    }

    /**
     * Get the error context for logging
     */
    public function getContext(): array
    {
        return [
            'error_type' => 'empty_data',
            'component' => 'pds_export',
            'recovery_action' => 'verify_employee_data_completeness',
        ];
    }
}