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

use Odesk\Phystrix\CircuitBreaker;

class CircuitBreakerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metrics;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stateStorage;

    protected function setUp()
    {
        $this->metrics = $this->createMock('Odesk\Phystrix\CommandMetrics', array(), array(), '', false);
        $this->stateStorage = $this->createMock('Odesk\Phystrix\StateStorageInterface');
    }

    protected function getCircuitBreaker($config = array())
    {
        $commandConfig = new \Zend\Config\Config(array(
            'circuitBreaker' => array(
                'enabled' => true,
                'errorThresholdPercentage' => 50,
                'forceOpen' => false,
                'forceClosed' => false,
                'requestVolumeThreshold' => 50,
                'sleepWindowInMilliseconds' => 5000,
                'metrics' => array(
                    'healthSnapshotIntervalInMilliseconds' => 1000,
                    'rollingStatisticalWindowInMilliseconds' => 10000,
                    'rollingStatisticalWindowBuckets' => 10,
                )
            ),
        ), true);
        $commandConfig->merge(new \Zend\Config\Config($config, true));

        return new CircuitBreaker('TestCommand', $this->metrics, $commandConfig, $this->stateStorage);
    }


    public function testIsOpenReturnsTrueImmediately()
    {
        $this->stateStorage->expects($this->once())
            ->method('isCircuitOpen')
            ->with($this->equalTo('TestCommand'))
            ->will($this->returnValue(true));

        $this->metrics->expects($this->never())
            ->method('getHealthCounts');

        $this->assertTrue($this->getCircuitBreaker()->isOpen());
    }

    public function testIsOpenNotPastTheThreshold()
    {
        $this->stateStorage->expects($this->once())
            ->method('isCircuitOpen')
            ->with($this->equalTo('TestCommand'))
            ->will($this->returnValue(false));

        $healthCounts = $this->createMock('Odesk\Phystrix\HealthCountsSnapshot', array(), array(), '', false);
        $healthCounts->expects($this->once())
            ->method('getTotal')
            ->will($this->returnValue(47)); // total is 47, threshold is set to 50.
        $healthCounts->expects($this->never())
            ->method('getErrorPercentage'); // making sure it doesn't make it to error percentage checking logic

        $this->metrics->expects($this->once())
            ->method('getHealthCounts')
            ->will($this->returnValue($healthCounts));

        $this->assertFalse($this->getCircuitBreaker()->isOpen());
    }

    public function testIsOpenErrorPercentageNotBigEnough()
    {
        $healthCounts = $this->createMock('Odesk\Phystrix\HealthCountsSnapshot', array(), array(), '', false);
        $healthCounts->expects($this->once())
            ->method('getTotal')
            ->will($this->returnValue(60)); // total is 60, threshold is set to 50.
        $healthCounts->expects($this->once())
            ->method('getErrorPercentage')
            ->will($this->returnValue(49)); // error percentage threshold is set to 50. 49 should not open the circuit
        $this->metrics->expects($this->once())
            ->method('getHealthCounts')
            ->will($this->returnValue($healthCounts));

        $this->assertFalse($this->getCircuitBreaker()->isOpen());
    }

    public function testIsOpenOpensCircuit()
    {
        $healthCounts = $this->createMock('Odesk\Phystrix\HealthCountsSnapshot', array(), array(), '', false);
        $healthCounts->expects($this->once())
            ->method('getTotal')
            ->will($this->returnValue(60)); // total is 60, threshold is set to 50.
        $healthCounts->expects($this->once())
            ->method('getErrorPercentage')
            ->will($this->returnValue(51)); // error percentage threshold is set to 50. 51 should open the circuit
        $this->metrics->expects($this->once())
            ->method('getHealthCounts')
            ->will($this->returnValue($healthCounts));

        $this->stateStorage->expects($this->once())
            ->method('openCircuit')
            ->with($this->equalTo('TestCommand'), $this->equalTo(5000)); // 5000 is sleeping window

        $this->assertTrue($this->getCircuitBreaker()->isOpen());
    }

    public function testAllowSingleTest()
    {
        $this->stateStorage->expects($this->at(0))
            ->method('allowSingleTest')
            ->with($this->equalTo('TestCommand'), $this->equalTo(5000))
            ->will($this->returnValue(false));

        $this->stateStorage->expects($this->at(1))
            ->method('allowSingleTest')
            ->with($this->equalTo('TestCommand'), $this->equalTo(5000))
            ->will($this->returnValue(true));

        $this->assertFalse($this->getCircuitBreaker()->allowSingleTest());
        $this->assertTrue($this->getCircuitBreaker()->allowSingleTest());
    }

    public function testAllowRequestForceOpen()
    {
        $this->stateStorage->expects($this->never())
            ->method('isCircuitOpen'); // making sure it doesn't get to checking if the circuit is open
        $circuitBreaker = $this->getCircuitBreaker(array('circuitBreaker' => array('forceOpen' => true)));
        $this->assertFalse($circuitBreaker->allowRequest());
    }

    public function testAllowRequestForceClose()
    {
        $this->stateStorage->expects($this->never())
            ->method('isCircuitOpen'); // making sure it doesn't get to checking if the circuit is open
        $circuitBreaker = $this->getCircuitBreaker(array('circuitBreaker' => array('forceClosed' => true)));
        $this->assertTrue($circuitBreaker->allowRequest());
    }

    public function testMarkSuccessClosesCircuitIfOpenAndResetCounter()
    {
        $this->stateStorage->expects($this->once())
            ->method('isCircuitOpen')
            ->with($this->equalTo('TestCommand'))
            ->will($this->returnValue(true));
        $this->stateStorage->expects($this->once())
            ->method('closeCircuit')
            ->with($this->equalTo('TestCommand'));
        $this->metrics->expects($this->once())
            ->method('resetCounter');
         $this->getCircuitBreaker()->markSuccess();
    }
}
