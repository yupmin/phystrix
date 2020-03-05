<?php

/**
 * This file is a part of the Phystrix library
 *
 * Copyright 2013-2014 oDesk Corporation. All Rights Reserved.
 *
 * This file is licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Odesk\Phystrix\Exception;

use Exception;

/**
 * General Phystrix exception
 */
class RuntimeException extends \RuntimeException
{
    /**
     * Exception while retrieving the fallback, if enabled
     *
     * @var Exception
     */
    private $fallbackException;

    /**
     * Class name of the command
     *
     * @var string
     */
    private $commandClass;

    /**
     * Constructor
     *
     * @param string $message
     * @param int $commandClass
     * @param Exception $originalException (Optional) Original exception. May be null if short-circuited
     * @param Exception $fallbackException (Optional) Exception thrown while retrieving fallback
     */
    public function __construct(
        $message,
        $commandClass,
        Exception $originalException = null,
        Exception $fallbackException = null
    ) {
        parent::__construct($message, 0, $originalException);
        $this->fallbackException = $fallbackException;
        $this->commandClass = $commandClass;
    }

    /**
     * Returns class name of the command the exception was thrown from
     *
     * @return string
     */
    public function getCommandClass()
    {
        return $this->commandClass;
    }

    /**
     * Returns fallback exception if available
     *
     * @return Exception
     */
    public function getFallbackException()
    {
        return $this->fallbackException;
    }
}
