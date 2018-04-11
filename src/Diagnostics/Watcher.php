<?php

/**
 * This file is part of Nepttune (https://www.peldax.com)
 *
 * Copyright (c) 2018 Václav Pelíšek (info@peldax.com)
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <https://www.peldax.com>.
 */

declare(strict_types = 1);

namespace Nepttune\Mongo\Diagnostics;

class Watcher implements \MongoDB\Driver\Monitoring\CommandSubscriber
{
    use \Nette\SmartObject;

    /** @var  Panel */
    protected $panel;

    public function setPanel(Panel $panel)
    {
        \MongoDB\Driver\Monitoring\addSubscriber($this);

        $this->panel = $panel;
    }

    public function commandStarted(\MongoDB\Driver\Monitoring\CommandStartedEvent $event) : void
    {
        $coll = '';
        $params = '';

        $cmd = $event->getCommand();
        $db = property_exists($cmd, '$db') ? $cmd->{'$db'} : '';

        switch ((string) $event->getCommandName())
        {
            case 'find':
            {
                $coll = property_exists($cmd, 'find') ? $cmd->find : '';
                $params = property_exists($cmd, 'filter') ? json_encode($cmd->filter) : '';
                break;
            }
            case 'insert':
            {
                $coll = property_exists($cmd, 'insert') ? $cmd->insert : '';
                $params = property_exists($cmd, 'documents') ? json_encode($cmd->documents) : '';
                break;
            }
            case 'delete':
            {
                $coll = property_exists($cmd, 'delete') ? $cmd->delete : '';
                $params = property_exists($cmd, 'deletes') ? json_encode($cmd->deletes) : '';
                break;
            }
            case 'update':
            {
                $coll = property_exists($cmd, 'update') ? $cmd->update : '';
                $params = property_exists($cmd, 'updates') ? json_encode($cmd->updates) : '';
                break;
            }
            case 'listCollections': { break; }
        }

        $this->panel->begin($db, $coll, $event->getCommandName(), $params);
    }

    public function commandSucceeded(\MongoDB\Driver\Monitoring\CommandSucceededEvent $event) : void
    {
        $result = 0;

        switch ((string) $event->getCommandName())
        {
            case 'find':
            case 'listCollections':
            {
                $result = count($event->getReply()->cursor->firstBatch);
                break;
            }
            case 'insert':
            case 'delete':
            case 'update':
            {
                $result = $event->getReply()->n;
                break;
            }
        }

        $this->panel->end($event->getDurationMicros(), $result);
    }

    public function commandFailed(\MongoDB\Driver\Monitoring\CommandFailedEvent $event) : void
    {
        $this->panel->end($event->getDurationMicros(), 'ERROR');
    }
}
