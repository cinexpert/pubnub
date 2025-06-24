<?php

/**
 * @date       20.05.2018
 * @author     Pascal Paulis <pascal.paulis@cinexpert.net>
 * @file       PubNubAdapter.php
 * @copyright  Copyright (c) CineXpert - All rights reserved
 * @license    Unauthorized copying of this source code, via any medium is strictly
 *             prohibited, proprietary and confidential.
 */

namespace Cinexpert\PubNub;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PubNub\Exceptions\PubNubConfigurationException;
use PubNub\PNConfiguration;
use PubNub\PubNub;

/**
 * @author      Pascal Paulis <pascal.paulis@cinexpert.net>
 * @copyright   Copyright (c) CineXpert - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */
class PubNubService
{
    protected string $publisherKey;
    protected string $subscriberKey;
    protected string $userId;
    protected ?PubNub $pubNubClient = null;

    /**
     * @param string $publisherKey
     * @param string $subscriberKey
     * @param string $userId
     */
    public function __construct(string $publisherKey, string $subscriberKey, string $userId)
    {
        $this->publisherKey  = $publisherKey;
        $this->subscriberKey = $subscriberKey;
        $this->userId        = $userId;
    }

    /**
     * @return PubNub
     * @throws PubNubConfigurationException
     */
    public function getPubNubClient(): PubNub
    {
        if (is_null($this->pubNubClient)) {
            $pnConfiguration = new PNConfiguration();
            $pnConfiguration
                ->setPublishKey($this->publisherKey)
                ->setSubscribeKey($this->subscriberKey)
                ->setUserId($this->userId)
                ->setSecure(true);

            $this->pubNubClient = new PubNub($pnConfiguration);

            $logger = new Logger('pubnub');
            $logger->pushHandler(new StreamHandler('php://stdout', Logger::ERROR));
            $this->pubNubClient->setLogger($logger);
            $this->pubNubClient->getLogger()->pushHandler(new ErrorLogHandler());
        }

        return $this->pubNubClient;
    }

    /**
     * @param string $channel
     * @param mixed $message
     * @return void
     * @throws PubNubConfigurationException
     */
    public function publish(string $channel, $message): void
    {
        $this->getPubNubClient()
            ->publish()
            ->channel($channel)
            ->message($message)
            ->usePost(true)
            ->sync();
    }

    /**
     * @return array<string, array<string,mixed>>
     * @throws PubNubConfigurationException
     */
    public function hereNow(): array
    {
        $result = $this->getPubNubClient()
            ->hereNow()
            ->includeState(true)
            ->includeUuids(true)
            ->sync();

        $hereNow = [];

        foreach ($result->getChannels() as $channel) {
            $uuids = [];

            foreach ($channel->getOccupants() as $occupant) {
                $uuids[] = $occupant->getUuid();
            }

            $hereNow[$channel->getChannelName()] =
                [
                    'uuids'     => $uuids,
                    'occupancy' => $channel->getOccupancy(),
                ];
        }

        return $hereNow;
    }

    /**
     * @param string[] $channels
     * @return void
     * @throws PubNubConfigurationException
     */
    public function subscribe(array $channels): void
    {
        $this
            ->getPubNubClient()
            ->subscribe()
            ->channels($channels);
    }
}
