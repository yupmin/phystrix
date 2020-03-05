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
 * Counts successful and failed requests for a given circuit/command group
 */
class MetricsCounter
{
    /**
     * Types of metrics we can track
     */
    public const
        SUCCESS             = 1,
        FAILURE             = 2,
        TIMEOUT             = 3,
        SHORT_CIRCUITED     = 4,
        FALLBACK_SUCCESS    = 5,
        FALLBACK_FAILURE    = 6,
        EXCEPTION_THROWN    = 8,
        RESPONSE_FROM_CACHE = 9;

    /**
     * @var string
     */
    private $commandKey;

    /**
     * @var StateStorageInterface
     */
    private $stateStorage;

    /**
     * Time span to track counters, in milliseconds
     *
     * @var integer
     */
    private $rollingStatisticalWindowInMilliseconds;

    /**
     * Number of buckets within the statistical window
     *
     * @var integer
     */
    private $rollingStatisticalWindowBuckets;

    /**
     * Size of a bucket in milliseconds
     *
     * @var float
     */
    private $bucketInMilliseconds;

    /**
     * Constructor
     *
     * @param string $commandKey
     * @param StateStorageInterface $stateStorage
     * @param integer $rollingStatisticalWindowInMilliseconds Time span in milliseconds
     * @param integer $rollingStatisticalWindowBuckets The number of buckets in the statistical window
     */
    public function __construct(
        $commandKey,
        StateStorageInterface $stateStorage,
        $rollingStatisticalWindowInMilliseconds,
        $rollingStatisticalWindowBuckets
    ) {
        $this->commandKey = $commandKey;
        $this->stateStorage = $stateStorage;
        $this->rollingStatisticalWindowInMilliseconds = $rollingStatisticalWindowInMilliseconds;
        $this->rollingStatisticalWindowBuckets = $rollingStatisticalWindowBuckets;
        $this->bucketInMilliseconds =
            $this->rollingStatisticalWindowInMilliseconds / $this->rollingStatisticalWindowBuckets;
    }

    /**
     * Increase counter for given metric type
     *
     * @param integer $type
     */
    public function add($type)
    {
        $this->stateStorage->incrementBucket($this->commandKey, $type, $this->getCurrentBucketIndex());
    }

    /**
     * Calculates sum for given metric type within the statistical window
     *
     * @param integer $type
     * @return integer
     */
    public function get($type)
    {
        $sum = 0;
        $now = $this->getTimeInMilliseconds();

        for ($i = 0; $i < $this->rollingStatisticalWindowBuckets; $i++) {
            $bucketIndex = $this->getBucketIndex($i, $now);
            $sum += $this->stateStorage->getBucket($this->commandKey, $type, $bucketIndex);
        }

        return $sum;
    }

    /**
     * Returns current time on the server in milliseconds
     *
     * @return float
     */
    private function getTimeInMilliseconds()
    {
        return floor(microtime(true) * 1000);
    }

    /**
     * Returns unique index for the current bucket
     *
     * @return integer
     */
    private function getCurrentBucketIndex()
    {
        return $this->getBucketIndex(0, $this->getTimeInMilliseconds());
    }

    /**
     * Gets unique bucket index by current time and bucket sequential number in the statistical window
     *
     * @param integer $bucketNumber
     * @param integer $time Current time in milliseconds
     * @return float
     */
    private function getBucketIndex($bucketNumber, $time)
    {
        // Getting unique bucket index
        return floor(($time - $bucketNumber * $this->bucketInMilliseconds) / $this->bucketInMilliseconds);
    }

    /**
     * Resets buckets for all metrics, within the statistical window.
     *
     * This is needed for when the statistical window is larger than the sleep window (for allowSingleTest).
     * In such case, if we did not reset the buckets, we could possibly have bad statistics effective for the
     * following request, causing the circuit to open right after it was just closed.
     *
     * May cause short-circuited stats to be removed from reporting, see http://goo.gl/dtHN34
     */
    public function reset()
    {
        // For each type of metric, we attempt to set the counter to 0
        foreach (
            [
                self::SUCCESS,
                self::FAILURE,
                self::TIMEOUT,
                self::FALLBACK_SUCCESS,
                self::FALLBACK_FAILURE,
                self::EXCEPTION_THROWN,
                self::RESPONSE_FROM_CACHE
            ] as $type
        ) {
            $now = $this->getTimeInMilliseconds();
            for ($i = 0; $i < $this->rollingStatisticalWindowBuckets; $i++) {
                $bucketIndex = $this->getBucketIndex($i, $now);
                $this->stateStorage->resetBucket($this->commandKey, $type, $bucketIndex);
            }
        }
    }
}
