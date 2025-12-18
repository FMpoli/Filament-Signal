<?php

namespace Voodflow\Voodflow\Exceptions;

class UnauthorizedCredentialAccessException extends \Exception
{
    public function __construct(string $credentialName, string $nodeName, string $reason = '')
    {
        $message = "Node '{$nodeName}' is not authorized to access credential '{$credentialName}'";
        
        if ($reason) {
            $message .= ": {$reason}";
        }
        
        parent::__construct($message);
    }
}
