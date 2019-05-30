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

use Odesk\Phystrix\HealthCountsSnapshot;
use PHPUnit_Framework_TestCase;

class HealthCountsSnapshotTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var HealthCountsSnapshot
     */
    protected $snapshot;

    protected function setUp()
    {
        $this->snapshot = new HealthCountsSnapshot(1369760400, 12, 24);
    }

    public function testConstruct()
    {
        $this->assertAttributeEquals(12, 'successful', $this->snapshot);
        $this->assertAttributeEquals(24, 'failure', $this->snapshot);
        $this->assertAttributeEquals(1369760400, 'time', $this->snapshot);
    }

    public function testGetTime()
    {
        $this->assertEquals(1369760400, $this->snapshot->getTime());
    }

    public function testGetFailure()
    {
        $this->assertEquals(24, $this->snapshot->getFailure());
    }

    public function testGetSuccessful()
    {
        $this->assertEquals(12, $this->snapshot->getSuccessful());
    }

    public function testGetTotal()
    {
        $this->assertEquals(36, $this->snapshot->getTotal());
    }

    public function testGetErrorPercentage()
    {
        $this->assertEquals(66, (integer) $this->snapshot->getErrorPercentage());
    }
}
