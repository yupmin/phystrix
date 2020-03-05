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
 * Serves Phystrix metrics in text/event-stream format
 */
class MetricsServer
{
    public const DEFAULT_DELAY = 500; // in milliseconds

    /**
     * @var integer
     */
    protected $delay;

    /**
     * @var MetricsPollerInterface
     */
    protected $metricsPoller;

    /**
     * Constructor
     *
     * @param MetricsPollerInterface $metricsPoller
     * @param int $delay
     */
    public function __construct(MetricsPollerInterface $metricsPoller, $delay = self::DEFAULT_DELAY)
    {
        $this->metricsPoller = $metricsPoller;
        $this->delay = $delay;
    }

    /**
     * Serves text/event-stream format
     */
    public function run()
    {
        header('HTTP/1.1 200 OK');
        header('Content-Type: text/event-stream;charset=UTF-8');
        header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        header('Pragma: no-cache');

        while (1) {
            $this->runStream();
        }
    }

    protected function runStream()
    {
        $stats = $this->metricsPoller->getStatsForCommandsRunning();

        if (empty($stats)) {
            echo "ping: \n\n";
        } else {
            foreach ($stats as $commandStats) {
                echo "data: " . json_encode($commandStats) . "\n\n";
            }
        }

        ob_flush();
        flush();
        usleep($this->delay * 1000);
    }
}
