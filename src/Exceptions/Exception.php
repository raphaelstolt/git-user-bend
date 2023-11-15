<?php

namespace Stolt\GitUserBend\Exceptions;

class Exception extends \Exception
{
    /**
     * @return string
     */
    public function getInforizedMessage(): string
    {
        return (string) preg_replace(
            "~\'(.+)\'~U",
            '<info>$1</info>',
            $this->getMessage()
        );
    }
}
