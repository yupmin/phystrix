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

use Odesk\Phystrix\NoOpCircuitBreaker;
use PHPUnit_Framework_TestCase;

class NoOpCircuitBreakerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var NoOpCircuitBreaker
     */
    protected $circuitBreaker;

    protected function setUp()
    {
        $this->circuitBreaker = new NoOpCircuitBreaker();
    }

    public function testAllowSingleTest()
    {
        $this->assertTrue($this->circuitBreaker->allowSingleTest());
    }

    public function testAllowRequest()
    {
        $this->assertTrue($this->circuitBreaker->allowRequest());
    }

    public function testIsOpen()
    {
        $this->assertFalse($this->circuitBreaker->isOpen());
    }
}
