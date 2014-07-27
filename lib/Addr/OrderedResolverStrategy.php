<?php

namespace Addr;

use Alert\Reactor;

class OrderedResolverStrategy implements Resolver
{
    /**
     * @var Reactor
     */
    private $reactor;

    /**
     * @var NameValidator
     */
    private $nameValidator;

    /**
     * @var Resolver[]
     */
    private $resolvers = [];

    /**
     * Constructor
     *
     * @param Reactor $reactor
     * @param NameValidator $nameValidator
     * @param Resolver ...$resolvers
     * @throws \LogicException
     */
    public function __construct(
        Reactor $reactor,
        NameValidator $nameValidator
        /*, Resolver ...$resolvers */
    ) {
        if (func_num_args() < 3) {
            throw new \LogicException(__CLASS__ . ' requires at least one resolver');
        }

        $this->reactor = $reactor;
        $this->nameValidator = $nameValidator;

        foreach (array_slice(func_get_args(), 2) as $resolver) {
            if (!($resolver instanceof Resolver)) {
                throw new \LogicException(__CLASS__ . ' resolvers must be instances or Resolver');
            }

            $this->resolvers[] = $resolver;
        }
    }

    /**
     * Verify the supplied domain name is in a valid format
     *
     * @param $name
     * @param $callback
     * @return bool
     */
    private function validateName($name, $callback)
    {
        if (!$this->nameValidator->validate($name)) {
            $this->reactor->immediately(function() use($callback) {
                call_user_func($callback, null, ResolutionErrors::ERR_INVALID_NAME);
            });

            return false;
        }

        return true;
    }

    /**
     * Check if a supplied name is an IP address and resolve immediately
     *
     * @param string $name
     * @param callable $callback
     * @return bool
     */
    private function resolveAsIPAddress($name, $callback)
    {
        if (filter_var($name, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->reactor->immediately(function() use($callback, $name) {
                call_user_func($callback, $name, AddressModes::INET4_ADDR);
            });

            return true;
        } else if (filter_var($name, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->reactor->immediately(function() use($callback, $name) {
                call_user_func($callback, $name, AddressModes::INET6_ADDR);
            });

            return true;
        }

        return false;
    }

    /**
     * Walk the resolver stack until one gets a hit
     *
     * @param int $id
     * @param string $name
     * @param int $mode
     * @param callable $callback
     */
    private function callResolver($id, $name, $mode, callable $callback)
    {
        $this->resolvers[$id]->resolve($name, $mode, function($address, $type) use($id, $name, $mode, $callback) {
            if ($address !== null) {
                $callback($address, $type);
                return;
            }

            if (isset($this->resolvers[++$id])) {
                $this->callResolver($id, $name, $mode, $callback);
                return;
            }

            $callback(null, $type);
        });
    }

    /**
     * Resolve a name
     *
     * @param string $name
     * @param int $mode
     * @param callable $callback
     */
    public function resolve($name, $mode, callable $callback)
    {
        if ($this->resolveAsIPAddress($name, $callback)) {
            return;
        }

        $name = strtolower($name);
        if (!$this->validateName($name, $callback)) {
            return;
        }

        $this->callResolver(0, $name, $mode, $callback);
    }
}
