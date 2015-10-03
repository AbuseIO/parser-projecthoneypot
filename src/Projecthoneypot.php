<?php

namespace AbuseIO\Parsers;

class Projecthoneypot extends Parser
{
    /**
     * Create a new Projecthoneypot instance
     */
    public function __construct($parsedMail, $arfMail)
    {
        parent::__construct($parsedMail, $arfMail, $this);
    }

    /**
     * Parse attachments
     * @return Array    Returns array with failed or success data
     *                  (See parser-common/src/Parser.php) for more info.
     */
    public function parse()
    {
        if (preg_match_all(
            '/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}) \(([HSDCR])\)\r?\n  \- ([\w, :]+)\r?\n/',
            $this->parsedMail->getMessageBody(),
            $match
        )) {
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
                if (!empty($report['Report-Type'])) {
                    $this->feedName = $report['Report-Type'];

                    // If feed is known and enabled, validate data and save report
                    if ($this->isKnownFeed() && $this->isEnabledFeed()) {
                        // Sanity check
                        if ($this->hasRequiredFields($report) === true) {
                            // Event has all requirements met, filter and add!
                            $report = $this->applyFilters($report);

                            $this->events[] = [
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
                    }
                } else {
                    $this->warningCount++;
                }
            }
        } else {
            $this->warningCount++;
        }

        return $this->success();
    }
}
