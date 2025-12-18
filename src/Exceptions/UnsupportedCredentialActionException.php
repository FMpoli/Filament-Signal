<?php

namespace Voodflow\Voodflow\Exceptions;

class UnsupportedCredentialActionException extends \Exception
{
    public function __construct(string $action, string $credentialType)
    {
        $message = "Action '{$action}' is not supported for credential type '{$credentialType}'";
        
        parent::__construct($message);
    }
}
