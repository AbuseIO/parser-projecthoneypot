<?php

namespace AbuseIO\Parsers;

use ReflectionClass;
use Log;

class Projecthoneypot extends Parser
{
    public $parsedMail;
    public $arfMail;

    /**
     * Create a new Projecthoneypot instance
     */
    public function __construct($parsedMail, $arfMail)
    {
        $this->parsedMail = $parsedMail;
        $this->arfMail = $arfMail;
    }

    /**
     * Parse attachments
     * @return Array    Returns array with failed or success data
     *                  (See parser-common/src/Parser.php) for more info.
     */
    public function parse()
    {
        // Generalize the local config based on the parser class name.
        $reflect = new ReflectionClass($this);
        $this->configBase = 'parsers.' . $reflect->getShortName();

        Log::info(
            get_class($this). ': Received message from: '.
            $this->parsedMail->getHeader('from') . " with subject: '" .
            $this->parsedMail->getHeader('subject') . "' arrived at parser: " .
            config("{$this->configBase}.parser.name")
        );

        // Define array where all events are going to be saved in.
        $events = [ ];

        // Didn't find an ARF report, go scrape the email body!
        $body = $this->parsedMail->getMessageBody();
        preg_match_all(
            '/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}) \(([HSDCR])\)\r?\n  \- ([\w, :]+)\r?\n/',
            $body,
            $match
        );
        array_shift($match);
        $reports = [ ];
        while (sizeof($match[0]) > 0) {
            $reports[] = [
                'Source' => array_shift($match[0]),
                'Type' => array_shift($match[1]),
                'Date' => array_shift($match[2])
            ];
        }

        foreach ($reports as $report) {
            $this->feedName = $report['Type'];

            if (!$this->isKnownFeed()) {
                return $this->failed(
                    "Detected feed {$this->feedName} is unknown."
                );
            }

            if (!$this->isEnabledFeed()) {
                continue;
            }

            if (!$this->hasRequiredFields($report)) {
                return $this->failed(
                    "Required field {$this->requiredField} is missing or the config is incorrect."
                );
            }

            $report = $this->applyFilters($report);

            $events[] = [
                'source'        => config("{$this->configBase}.parser.name"),
                'ip'            => $report['Source'],
                'domain'        => false,
                'uri'           => false,
                'class'         => config("{$this->configBase}.feeds.{$this->feedName}.class"),
                'type'          => config("{$this->configBase}.feeds.{$this->feedName}.type"),
                'timestamp'     => strtotime($report['Date']),
                'information'   => json_encode($report),
            ];
        }

        return $this->success($events);
    }
}
