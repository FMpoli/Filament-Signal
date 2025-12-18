<?php

namespace Voodflow\Voodflow\Exceptions;

class CredentialSerializationException extends \Exception
{
    public function __construct(string $message = 'Credentials cannot be serialized for security reasons')
    {
        parent::__construct($message);
    }
}
