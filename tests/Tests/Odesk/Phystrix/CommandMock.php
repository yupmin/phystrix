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
namespace Tests\Odesk\Phystrix;

use DomainException;
use Odesk\Phystrix\AbstractCommand;
use Odesk\Phystrix\Exception\BadRequestException;

class CommandMock extends AbstractCommand
{
    public $throwBadRequestException = false;

    public $throwException = false;

    public $throwExceptionInFallback = false;

    public $cacheKey = null;

    public $simulateDelay = false;

    protected function run()
    {
        if ($this->simulateDelay) {
            // simulates that command execution took 555 milliseconds
            global $globalUnitTestPhystrixMicroTime;
            $globalUnitTestPhystrixMicroTime += 0.555;
        }
        if ($this->throwBadRequestException) {
            throw new BadRequestException('special treatment');
        } elseif ($this->throwException) {
            throw new DomainException('could not run');
        } else {
            return 'run result';
        }
    }

    protected function getFallback(\Exception $e = null)
    {
        if ($this->throwExceptionInFallback) {
            throw new DomainException('error falling back');
        } else {
            return 'fallback result';
        }
    }

    protected function getCacheKey()
    {
        return $this->cacheKey;
    }
}
