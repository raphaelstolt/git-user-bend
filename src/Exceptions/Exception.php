<?php

namespace Stolt\GitUserBend\Exceptions;

class Exception extends \Exception
{
    /**
     * @return string
     */
    public function getInforizedMessage()
    {
        return preg_replace(
            "~\'(.+)\'~U",
            '<info>$1</info>',
            $this->getMessage()
        );
    }
}
