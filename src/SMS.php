<?php
/**
 * This file is part of the mucts/sms.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * @version 1.0
 * @author herry<yuandeng@aliyun.com>
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 */

namespace MuCTS\SMS;

use Closure;
use Exception;
use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Gateways\Gateway;
use MuCTS\SMS\Interfaces\Message as MessageInterface;
use MuCTS\SMS\Interfaces\Mobile as MobileInterface;
use MuCTS\SMS\Interfaces\Strategy;
use MuCTS\SMS\Strategies\Order;
use RuntimeException;

class SMS
{
    /** @var Config */
    protected $config;
    /** @var string */
    protected $defaultGateway;
    /** @var array */
    protected $customCreators = [];
    /** @var array */
    protected $gateways = [];
    /** @var Messenger */
    protected $messenger;
    /** @var array */
    protected $strategies = [];

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);
        if (!empty($config['default'])) {
            $this->setDefaultGateway($config['default']);
        }
    }

    /**
     * Send a message.
     *
     * @param MobileInterface|string|array $mobile
     * @param MessageInterface|array $message
     * @param array $gateways
     * @return array
     * @throws Exception
     */
    public function send($mobile, $message, array $gateways = [])
    {
        $mobile = $this->formatMobile($mobile);
        $message = $this->formatMessage($message);
        $gateways = empty($gateways) ? $message->getGateways() : $gateways;

        if (empty($gateways)) {
            $gateways = $this->config->get('default.gateways', []);
        }

        return $this->getMessenger()->send($message, $mobile, $this->formatGateways($gateways));
    }

    /**
     * Create a gateway.
     *
     * @param string|null $name
     * @return Gateway
     * @throws Exception
     */
    public function gateway(?string $name = null)
    {
        $name = $name ?: $this->getDefaultGateway();
        if (!isset($this->gateways[$name])) {
            $this->gateways[$name] = $this->createGateway($name);
        }
        return $this->gateways[$name];
    }

    /**
     * Get a strategy instance.
     *
     * @param string|null $strategy
     * @return Strategy
     * @throws Exception
     */
    public function strategy($strategy = null)
    {
        if (is_null($strategy)) {
            $strategy = $this->config->get('default.strategy', Order::class);
        }

        if (!class_exists($strategy)) {
            $strategy = __NAMESPACE__ . '\Strategies\\' . ucfirst($strategy);
        }

        if (!class_exists($strategy)) {
            throw new Exception("Unsupported strategy {$strategy}");
        }

        if (empty($this->strategies[$strategy]) || !($this->strategies[$strategy] instanceof Strategy)) {
            $this->strategies[$strategy] = new $strategy($this);
        }

        return $this->strategies[$strategy];
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string $name
     * @param Closure $callback
     * @return SMS
     */
    public function extend($name, Closure $callback)
    {
        $this->customCreators[$name] = $callback;

        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get default gateway name.
     *
     * @return string
     * @throws RuntimeException if no default gateway configured
     */
    public function getDefaultGateway(): string
    {
        if (empty($this->defaultGateway)) {
            throw new RuntimeException('No default gateway configured.');
        }

        return $this->defaultGateway;
    }

    /**
     * Set default gateway name.
     *
     * @param string $name
     * @return $this
     */
    public function setDefaultGateway(string $name): self
    {
        $this->defaultGateway = $name;
        return $this;
    }

    /**
     * @return Messenger
     */
    public function getMessenger(): Messenger
    {
        return $this->messenger ?: $this->messenger = new Messenger($this);
    }

    /**
     * Create a new driver instance.
     *
     * @param string $name
     * @return Gateway
     * @throws Exception
     */
    protected function createGateway($name): Gateway
    {
        if (isset($this->customCreators[$name])) {
            $gateway = $this->callCustomCreator($name);
        } else {
            $className = $this->formatGatewayClassName($name);
            $config = $this->config->get("gateways.{$name}", []);
            if (!isset($config['timeout'])) {
                $config['timeout'] = $this->config->get('timeout', Gateway::DEFAULT_TIMEOUT);
            }
            $gateway = $this->makeGateway($className, $config);
        }

        if (!($gateway instanceof Gateway)) {
            throw new Exception(sprintf('Gateway "%s" must implement interface %s.', $name, Gateway::class));
        }

        return $gateway;
    }

    /**
     * Make gateway instance.
     *
     * @param string $gateway
     * @param array $config
     * @return Gateway
     * @throws Exception
     */
    protected function makeGateway(string $gateway, array $config)
    {
        if (!class_exists($gateway) || !in_array(Gateway::class, class_implements($gateway))) {
            throw new Exception(sprintf('Class "%s" is a invalid easy-sms gateway.', $gateway));
        }
        return new $gateway($config);
    }

    /**
     * Format gateway name.
     *
     * @param string $name
     * @return string
     */
    protected function formatGatewayClassName(string $name): string
    {
        if (class_exists($name) && in_array(Gateway::class, class_implements($name))) {
            return $name;
        }
        $name = ucfirst(str_replace(['-', '_', ''], '', $name));
        return __NAMESPACE__ . "\\Gateways\\{$name}Gateway";
    }

    /**
     * Call a custom gateway creator.
     *
     * @param string $gateway
     * @return mixed
     */
    protected function callCustomCreator($gateway)
    {
        return call_user_func($this->customCreators[$gateway], $this->config->get("gateways.{$gateway}", []));
    }

    protected function formatMobile($number): MobileInterface
    {
        if ($number instanceof MobileInterface) {
            return $number;
        }
        return new Mobile(trim($number));
    }

    /**
     * @param array|string|MessageInterface $message
     * @return MessageInterface
     */
    protected function formatMessage($message): MessageInterface
    {
        if (!($message instanceof MessageInterface)) {
            if (!is_array($message)) {
                $message = [
                    'content' => $message,
                    'template' => $message,
                ];
            }
            $message = new Message($message);
        }
        return $message;
    }

    /**
     * @param array $gateways
     * @return array
     * @throws Exception
     */
    protected function formatGateways(array $gateways): array
    {
        $formatted = [];
        foreach ($gateways as $gateway => $setting) {
            if (is_int($gateway) && is_string($setting)) {
                $gateway = $setting;
                $setting = [];
            }
            $formatted[$gateway] = $setting;
            $globalSettings = $this->config->get("gateways.{$gateway}", []);
            if (is_string($gateway) && !empty($globalSettings) && is_array($setting)) {
                $formatted[$gateway] = new Config(array_merge($globalSettings, $setting));
            }
        }
        $result = [];
        foreach ($this->strategy()->apply($formatted) as $name) {
            $result[$name] = $formatted[$name];
        }
        return $result;
    }
}