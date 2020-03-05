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

use Exception;
use Psr\SimpleCache\CacheInterface;

/**
 * Object for request caching, one instance shared between all commands
 */
class RequestCache implements CacheInterface
{
    /** @var array */
    protected $storage = array();

    public function clear()
    {
        unset($this->storage);
        $this->storage = array();
    }

    public function delete($key)
    {
        if ($this->has($key)) {
            unset($this->storage[$key]);
        }
    }

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->storage[$key];
        }

        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->storage[$key] = $value;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->storage);
    }

    public function getMultiple($keys, $default = null)
    {
        throw new Exception('Not supported');
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new Exception('Not supported');
    }

    public function deleteMultiple($keys)
    {
        throw new Exception('Not supported');
    }
}
