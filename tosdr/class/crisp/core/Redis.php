<?php

/*
 * Copyright (C) 2021 Justin René Back <justin@tosdr.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace crisp\core;

use crisp\exceptions\BitmaskException;
use Exception;

/**
 * Interact with the database yourself. Please use this interface only when you REALLY need it for custom tables.
 * We offer a variety of functions to interact with users or the system itself in a safe way :-)
 */
class Redis {

    private \Redis $Database_Connection;

    /**
     * Constructs the Database_Connectio
     * @throws BitmaskException
     * @see getDBConnector
     */
    public function __construct() {
        try {
            $EnvFile = parse_ini_file(__DIR__ . "/../../../../.env");
            $redis = new \Redis();
            $redis->connect($EnvFile["REDIS_HOST"], $EnvFile["REDIS_PORT"]);
            $redis->auth($EnvFile["REDIS_AUTH"]);
            $this->Database_Connection = $redis;
        } catch (Exception $ex) {
            throw new BitmaskException($ex, Bitmask::REDIS_CONN_ERROR);
        }
    }

    /**
     * Get the database connector
     * @return \Redis
     */
    public function getDBConnector(): \Redis
    {
        return $this->Database_Connection;
    }

}
