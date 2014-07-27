<?php

namespace Addr;

interface Resolver
{
    /**
     * Resolve a name
     *
     * Callback has the following signature:
     *  void callback ( string $address, int $type )
     *
     * Called with the following values:
     *  [$ipAddress, $addressFamily] - if the name was resolved
     *  [null, $errorCode] - if the name was not resolved
     *
     * @param string $name
     * @param int $mode
     * @param callable $callback
     * @return
     */
    public function resolve($name, $mode, callable $callback);
}
