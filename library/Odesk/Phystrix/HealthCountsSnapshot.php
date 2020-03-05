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
 * Represent a snapshot of current statistical metrics for a Circuit Breaker
 */
class HealthCountsSnapshot
{
    /**
     * @var integer
     */
    private $successful;

    /**
     * @var integer
     */
    private $failure;

    /**
     * Time the snapshot was made, in milliseconds
     *
     * @var integer
     */
    private $time;

    /**
     * Constructor
     *
     * @param integer $time
     * @param integer $successful
     * @param integer $failure
     */
    public function __construct($time, $successful, $failure)
    {
        $this->time = $time;
        $this->failure = $failure;
        $this->successful = $successful;
    }

    /**
     * Returns the time the snapshot was taken
     *
     * @return integer
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Returns the number of failures
     *
     * @return integer
     */
    public function getFailure()
    {
        return $this->failure;
    }

    /**
     * Returns the number of failures
     *
     * @return integer
     */
    public function getSuccessful()
    {
        return $this->successful;
    }

    /**
     * Returns the total sum of requests made
     *
     * @return integer
     */
    public function getTotal()
    {
        return $this->successful + $this->failure;
    }

    /**
     * Returns error percentage
     *
     * @return float
     */
    public function getErrorPercentage()
    {
        $total = $this->getTotal();
        if (!$total) {
            return 0;
        } else {
            return $this->getFailure() / $total * 100;
        }
    }
}
