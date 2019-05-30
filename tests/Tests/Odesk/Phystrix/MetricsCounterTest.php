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

use Odesk\Phystrix\MetricsCounter;

class MetricsCounterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MetricsCounter
     */
    protected $counter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stateStorageMock;

    protected function setUp()
    {
        $this->stateStorageMock = $this->createMock('Odesk\Phystrix\StateStorageInterface');
        // 10 seconds statistical window, 10 buckets.
        $this->counter = new MetricsCounter('TestCommand', $this->stateStorageMock, 10000, 10);
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

    protected function getExpectedBucketIndex($bucketNumber)
    {
        $timeInMilliseconds = \Odesk\Phystrix\microtime() * 1000;
        return floor(($timeInMilliseconds - $bucketNumber * 1000) / 1000);
    }

    public function testAdd()
    {
        // current bucket (0) index is calculated as follows, using the value from microtime:
        // floor((1369861562.126 - 0 * 1000) / 10000) = 1369861562
        $bucketIndex = $this->getExpectedBucketIndex(0);
        $this->stateStorageMock
            ->expects($this->once())
            ->method('incrementBucket')
            ->with(
                $this->equalTo('TestCommand'),
                $this->equalTo(MetricsCounter::SUCCESS),
                $this->equalTo($bucketIndex)
            );
        $this->counter->add(MetricsCounter::SUCCESS);
    }

    public function testGet()
    {
        // going through each bucket, making sure the value for it is requested from the storage
        for ($bucketNumber = 0; $bucketNumber < 10; $bucketNumber++) {
            $this->stateStorageMock
                ->expects($this->at($bucketNumber))
                ->method('getBucket')
                ->with(
                    $this->equalTo('TestCommand'),
                    $this->equalTo(MetricsCounter::SUCCESS),
                    $this->equalTo($this->getExpectedBucketIndex($bucketNumber))
                );
        }
        $this->counter->get(MetricsCounter::SUCCESS);
    }
}
