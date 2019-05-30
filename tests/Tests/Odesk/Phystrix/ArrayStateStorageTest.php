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

use Odesk\Phystrix\ArrayStateStorage;
use PHPUnit_Framework_TestCase;

class ArrayStateStorageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayStateStorage
     */
    protected $storage;

    protected function setUp()
    {
        $this->storage = new ArrayStateStorage();
    }

    public function testIncrementAndGetBucket()
    {
        $this->assertNull($this->storage->getBucket('TestCommand', 'success', 2));
        $this->storage->incrementBucket('TestCommand', 'success', 2);
        $this->assertEquals(1, $this->storage->getBucket('TestCommand', 'success', 2));
        $this->storage->incrementBucket('TestCommand', 'success', 2);
        $this->assertEquals(2, $this->storage->getBucket('TestCommand', 'success', 2));
        $this->storage->incrementBucket('TestCommand', 'success', 2);
        $this->storage->incrementBucket('TestCommand', 'failure', 2);
        $this->storage->incrementBucket('TestCommand', 'success', 1);
        $this->storage->incrementBucket('OtherTestCommand', 'success', 1);
        $this->assertEquals(1, $this->storage->getBucket('TestCommand', 'failure', 2));
        $this->assertEquals(3, $this->storage->getBucket('TestCommand', 'success', 2));
        $this->assertEquals(1, $this->storage->getBucket('TestCommand', 'success', 1));
        $this->assertEquals(1, $this->storage->getBucket('OtherTestCommand', 'success', 1));
    }

    public function testResetBucket()
    {
        $this->storage->incrementBucket('TestCommand', 'success', 2);
        $this->storage->incrementBucket('TestCommand', 'success', 2);
        $this->storage->incrementBucket('TestCommand', 'success', 2);
        $this->storage->incrementBucket('TestCommand', 'success', 2);
        $this->assertEquals(4, $this->storage->getBucket('TestCommand', 'success', 2));
        $this->storage->resetBucket('TestCommand', 'success', 2);
        $this->assertEquals(0, $this->storage->getBucket('TestCommand', 'success', 2));
    }

    public function testOpenCircuit()
    {
        $this->assertFalse($this->storage->isCircuitOpen('TestCommand'));
        $this->storage->openCircuit('TestCommand', 1000);
        $this->assertTrue($this->storage->isCircuitOpen('TestCommand'));
    }

    public function testIsCircuitOpen()
    {
        $this->storage->openCircuit('TestCommand', 1000);
        $this->assertTrue($this->storage->isCircuitOpen('TestCommand'));
        $this->storage->closeCircuit('TestCommand');
        $this->assertFalse($this->storage->isCircuitOpen('TestCommand'));
    }

    public function testCloseCircuit()
    {
        $this->storage->openCircuit('TestCommand', 1000);
        $this->assertTrue($this->storage->isCircuitOpen('TestCommand'));
        $this->storage->closeCircuit('TestCommand');
        $this->assertFalse($this->storage->isCircuitOpen('TestCommand'));
    }

    public function testAllowSingleTest()
    {
        // there is no point in checking, but when circuit is closed, the test is allowed also
        $this->assertTrue($this->storage->allowSingleTest('TestCommand', 900));
        // after the circuit is open, the test is not allowed for the period of time we specify
        $this->storage->openCircuit('TestCommand', 900); // 900 milliseconds
        $this->assertFalse($this->storage->allowSingleTest('TestCommand', 900));
        // no matter how many times we check
        $this->assertFalse($this->storage->allowSingleTest('TestCommand', 900));
        // but after the period passes...
        sleep(1);
        // we can check again
        $this->assertTrue($this->storage->allowSingleTest('TestCommand', 900));
        // but only once
        $this->assertFalse($this->storage->allowSingleTest('TestCommand', 900));
        // because each check sets new time
        sleep(1);
        $this->assertTrue($this->storage->allowSingleTest('TestCommand', 900));
    }
}
