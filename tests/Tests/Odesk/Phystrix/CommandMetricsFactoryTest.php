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
use Odesk\Phystrix\CommandMetricsFactory;
use ReflectionClass;

class CommandMetricsFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $config = new \Laminas\Config\Config(array(
            'metrics' => array(
                'rollingStatisticalWindowInMilliseconds' => 10000,
                'rollingStatisticalWindowBuckets' => 10,
                'healthSnapshotIntervalInMilliseconds' => 2000,
            )
        ));
        $factory = new CommandMetricsFactory(new ArrayStateStorage());
        $metrics = $factory->get('TestCommand', $config);
        $this->assertAttributeEquals(2000, 'healthSnapshotIntervalInMilliseconds', $metrics);

        $reflection = new ReflectionClass('Odesk\Phystrix\CommandMetrics');
        $property = $reflection->getProperty('counter');
        $property->setAccessible(true);
        $counter = $property->getValue($metrics);
        $this->assertAttributeEquals('TestCommand', 'commandKey', $counter);
        $this->assertAttributeEquals(10000, 'rollingStatisticalWindowInMilliseconds', $counter);
        $this->assertAttributeEquals(10, 'rollingStatisticalWindowBuckets', $counter);
        $this->assertAttributeEquals(1000, 'bucketInMilliseconds', $counter); // 10000 / 10 = 1000
    }
}
