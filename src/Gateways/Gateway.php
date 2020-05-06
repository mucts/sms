<?php
/**
 *
 * @version 1.0
 * @author herry<yuandeng@aliyun.com>
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 */

namespace MuCTS\SMS\Gateways;


use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Gateway as GatewayInterface;

abstract class Gateway implements GatewayInterface
{
    /** @var float 默认超时时间 */
    public const DEFAULT_TIMEOUT = 5.0;
    /** @var Config */
    protected Config $config;
    /** @var array */
    protected array $options;
    /** @var float */
    protected float $timeout;

    /**
     * Gateway constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * Return timeout.
     * @return int|mixed
     */
    public function getTimeout()
    {
        return $this->timeout ?: $this->config->get('timeout', self::DEFAULT_TIMEOUT);
    }

    /**
     * Set timeout.
     * @param int $timeout
     * @return self
     */
    public function setTimeout($timeout)
    {
        $this->timeout = floatval($timeout);
        return $this;
    }

    public function getConfig(): ?Config
    {
        return $this->config ?? new Config();
    }

    public function setConfig(Config $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param $options
     * @return $this
     */
    public function setGuzzleOptions($options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getGuzzleOptions(): array
    {
        return $this->options ?: $this->config->get('options', []);
    }

    public function getName(): string
    {
        return strtolower(str_replace([__NAMESPACE__ . '\\', 'Gateway'], '', get_class($this)));
    }
}