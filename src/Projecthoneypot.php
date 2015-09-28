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
        while (sizeof($match[0]) > 0) {
            $reports[] = [
                'Source' => array_shift($match[0]),
                'Type' => array_shift($match[1]),
                'Date' => array_shift($match[2])
            ];
        }

        // Loop through all reported issues
        foreach ($reports as $report) {
            $feedName = $report['Type'];

            // If feed is known and enabled, validate data and save report
            if ($this->isKnownFeed($feedName) && $this->isEnabledFeed($feedName)) {
                // Sanity checks (skip if required fields are unset)
                if ($this->hasRequiredFields($feedName, $report) === true) {
                    $events[] = [
                        'source'        => config("{$this->configBase}.parser.name"),
                        'ip'            => $report['Source'],
                        'domain'        => false,
                        'uri'           => false,
                        'class'         => config("{$this->configBase}.feeds.{$feedName}.class"),
                        'type'          => config("{$this->configBase}.feeds.{$feedName}.type"),
                        'timestamp'     => strtotime($report['Date']),
                        'information'   => json_encode($report),
                    ];
                } else {
                    return $this->failed(
                        "Required field {$this->requiredField} is missing in the report or config is incorrect."
                    );
                }
            } else {
                return $this->failed(
                    "Detected feed '{$feedName}' is unknown or disabled."
                );
            }
        }

        return $this->success($events);
    }
}
