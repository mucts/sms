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

use MuCTS\SMS\Config\Config;
use MuCTS\SMS\Interfaces\Gateway;
use MuCTS\SMS\Interfaces\Message as MessageInterface;

/**
 *
 * @version 1.0
 * @author herry<yuandeng@aliyun.com>
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 */
class Message implements MessageInterface
{
    /** @var array */
    protected $gateways = [];
    /** @var string */
    protected $type;
    /** @var string */
    protected $content;
    /** @var string|null */
    protected $template;
    /** @var array */
    protected $data = [];

    /**
     * Message constructor.
     *
     * @param array|string $attributes
     * @param string $type
     */
    public function __construct($attributes = [], $type = self::TEXT_MESSAGE)
    {
        $this->type = $type;
        if (is_string($attributes)) {
            $this->setContent($attributes);
        }
        foreach ($attributes as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Return the message type.
     *
     * @return string
     */
    public function getMessageType(): string
    {
        return $this->type;
    }

    /**
     * Return message content.
     *
     * @param Gateway|null $gateway
     *
     * @return string
     */
    public function getContent(?Gateway $gateway = null): string
    {
        return is_callable($this->content) ? call_user_func($this->content, $gateway) : $this->content;
    }

    /**
     * Return the template id of message.
     *
     * @param Gateway|null $gateway
     *
     * @return string
     */
    public function getTemplate(?Gateway $gateway = null): ?string
    {
        return is_callable($this->template) ? call_user_func($this->template, $gateway) : $this->template;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param mixed $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param mixed $template
     *
     * @return $this
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @param Gateway|null $gateway
     *
     * @return Config
     */
    public function getData(?Gateway $gateway = null): Config
    {
        return new Config(is_callable($this->data) ? call_user_func($this->data, $gateway) : $this->data);
    }

    /**
     * @param array|callable $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getGateways(): array
    {
        return $this->gateways;
    }

    /**
     * @param array $gateways
     *
     * @return $this
     */
    public function setGateways(array $gateways)
    {
        $this->gateways = $gateways;

        return $this;
    }

    /**
     * @param $property
     *
     * @return string
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
}