<?php
/**
 * This file is a part of the phystrix-dashboard package
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
namespace Odesk\Phystrix\MetricsEventStream;

/**
 * Obtains and returns metrics for each Phystrix command in-use
 */
interface MetricsPollerInterface
{
    /**
     * Finds all commands currently running (having any metrics recorded within the statistical rolling window).
     * For each command key prepares a set of statistic. Returns all sets.
     *
     * @return array
     */
    public function getStatsForCommandsRunning();
}
