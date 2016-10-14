<?php
/**
 * Interface CommandExecutionService
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 */

namespace DclPictureExport;


interface CommandExecutionService
{
    /**
     * Part of the ILIAS control flow.
     * Needed in every GUI class.
     *
     * @return void
     */
    public function executeCommand();
}