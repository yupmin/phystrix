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

use Odesk\Phystrix\CircuitBreakerFactory;
use Odesk\Phystrix\CommandMetrics;

class CircuitBreakerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CircuitBreakerFactory
     */
    protected $factory;

    /**
     * @var CommandMetrics
     */
    protected $metrics;

    protected static $baseConfig = array(
        'circuitBreaker' => array(
            'enabled' => true,
        )
    );

    protected function setUp()
    {
        $this->factory = new CircuitBreakerFactory($this->createMock('Odesk\Phystrix\StateStorageInterface'));
        $this->metrics = $this->createMock('Odesk\Phystrix\CommandMetrics', array(), array(), '', false);
    }

    public function testGetNoOpCircuitBreaker()
    {
        $config = self::$baseConfig;
        $config['circuitBreaker']['enabled'] = false;
        $config = new \Zend\Config\Config($config);
        $circuitBreaker = $this->factory->get('TestCommand', $config, $this->metrics);
        $this->assertInstanceOf('Odesk\Phystrix\NoOpCircuitBreaker', $circuitBreaker);
    }

    public function testGetInstantiatesOnce()
    {
        $config = self::$baseConfig;
        $config['circuitBreaker']['enabled'] = false;
        $config = new \Zend\Config\Config($config);
        // this will be a NoOpCircuitBreaker
        $circuitBreaker = $this->factory->get('TestCommand', $config, $this->metrics);
        // now trying to get the same circuit breaker with a different config
        $circuitBreakerB
            = $this->factory->get('TestCommand', new \Zend\Config\Config(self::$baseConfig), $this->metrics);
        $this->assertEquals($circuitBreaker, $circuitBreakerB);
    }

    public function testGetInjectsParameters()
    {
        $config = new \Zend\Config\Config(self::$baseConfig);
        $circuitBreaker = $this->factory->get('TestCommand', $config, $this->metrics);
        $this->assertAttributeEquals('TestCommand', 'commandKey', $circuitBreaker);
        $this->assertAttributeEquals($config, 'config', $circuitBreaker);
        $this->assertAttributeInstanceOf('Odesk\Phystrix\CommandMetrics', 'metrics', $circuitBreaker);
        $this->assertAttributeInstanceOf('Odesk\Phystrix\StateStorageInterface', 'stateStorage', $circuitBreaker);
    }
}
