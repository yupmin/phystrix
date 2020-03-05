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

use Odesk\Phystrix\CommandMetrics;
use Odesk\Phystrix\HealthCountsSnapshot;
use Odesk\Phystrix\MetricsCounter;
use ReflectionClass;

class CommandMetricsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CommandMetrics
     */
    protected $metrics;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $counter;

    protected function setUp()
    {
        $this->counter = $this->createMock('Odesk\Phystrix\MetricsCounter', array(), array(), '', false);
        $this->metrics = new CommandMetrics($this->counter, 1000);
        // microtime is fixed
        global $globalUnitTestPhystrixMicroTime;
        $globalUnitTestPhystrixMicroTime = 1369861562.1266;
    }

    protected function tearDown()
    {
        // making microtime to fallback to the default behavior
        global $globalUnitTestPhystrixMicroTime;
        $globalUnitTestPhystrixMicroTime = null;
    }

    public function testMarkSuccess()
    {
        $this->counter->expects($this->once())->method('add')->with(MetricsCounter::SUCCESS);
        $this->metrics->markSuccess();
    }

    public function testMarkFailure()
    {
        $this->counter->expects($this->once())->method('add')->with(MetricsCounter::FAILURE);
        $this->metrics->markFailure();
    }

    public function testMarkShortCircuited()
    {
        $this->counter->expects($this->once())->method('add')->with(MetricsCounter::SHORT_CIRCUITED);
        $this->metrics->markShortCircuited();
    }

    public function testMarkExceptionThrown()
    {
        $this->counter->expects($this->once())->method('add')->with(MetricsCounter::EXCEPTION_THROWN);
        $this->metrics->markExceptionThrown();
    }

    public function testMarkFallbackSuccess()
    {
        $this->counter->expects($this->once())->method('add')->with(MetricsCounter::FALLBACK_SUCCESS);
        $this->metrics->markFallbackSuccess();
    }

    public function testMarkFallbackFailure()
    {
        $this->counter->expects($this->once())->method('add')->with(MetricsCounter::FALLBACK_FAILURE);
        $this->metrics->markFallbackFailure();
    }

    public function testMarkResponseFromCache()
    {
        $this->counter->expects($this->once())->method('add')->with(MetricsCounter::RESPONSE_FROM_CACHE);
        $this->metrics->markResponseFromCache();
    }

    public function testResetCounter()
    {
        $this->counter->expects($this->once())->method('reset');
        $this->metrics->resetCounter();
    }

    public function testGetRollingCount()
    {
        $this->counter->expects($this->once())->method('get')->with(1);
        $this->metrics->getRollingCount(1);
    }

    public function testGetHealthCountsInitialSnapshot()
    {
        $this->counter->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap(array(
                array(MetricsCounter::FAILURE, 22),
                array(MetricsCounter::SUCCESS, 33),
            )));

        $snapshot = $this->metrics->getHealthCounts();
        $this->assertEquals(22, $snapshot->getFailure());
        $this->assertEquals(33, $snapshot->getSuccessful());
    }

    public function testGetHealthCountsReusingSnapshot()
    {
        $now = \Odesk\Phystrix\microtime() * 1000; // current time in milliseconds

        // a snapshot created half a second ago, still valid at the moment
        $snapshot = new HealthCountsSnapshot($now - 500, 11, 22);

        // setting it as the last snapshot into metrics
        $reflection = new ReflectionClass('Odesk\Phystrix\CommandMetrics');
        $property = $reflection->getProperty('lastSnapshot');
        $property->setAccessible(true);
        $property->setValue($this->metrics, $snapshot);

        $this->counter->expects($this->never())->method('get');

        $this->assertEquals($snapshot, $this->metrics->getHealthCounts());
    }

    public function testGetHealthCountsExpiringSnapshot()
    {
        $now = \Odesk\Phystrix\microtime() * 1000; // current time in milliseconds

        // a snapshot created two seconds ago is no longer valid and we expect a new one
        $snapshot = new HealthCountsSnapshot($now - 2000, 11, 22);

        // setting it as the last snapshot into metrics
        $reflection = new ReflectionClass('Odesk\Phystrix\CommandMetrics');
        $property = $reflection->getProperty('lastSnapshot');
        $property->setAccessible(true);
        $property->setValue($this->metrics, $snapshot);

        $this->counter->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap(array(
                array(MetricsCounter::FAILURE, 22),
                array(MetricsCounter::SUCCESS, 33),
            )));

        $newSnapshot = $this->metrics->getHealthCounts();
        $this->assertNotEquals($snapshot, $newSnapshot);
        $this->assertEquals(33, $newSnapshot->getSuccessful());
    }
}
