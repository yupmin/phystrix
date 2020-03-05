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

namespace Odesk\Phystrix;

/**
 * Circuit breaker interface
 */
interface CircuitBreakerInterface
{
    /**
     * Whether the circuit is open
     *
     * @return boolean
     */
    public function isOpen();

    /**
     * Whether the request is allowed
     *
     * @return boolean
     */
    public function allowRequest();

    /**
     * Whether a single test is allowed now
     *
     * @return boolean
     */
    public function allowSingleTest();

    /**
     * Marks a successful request
     *
     * @return void
     */
    public function markSuccess();
}
