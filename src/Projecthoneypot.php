<?php

namespace AbuseIO\Parsers;

/**
 * Class Projecthoneypot
 * @package AbuseIO\Parsers
 */
class Projecthoneypot extends Parser
{
    /**
     * Create a new Projecthoneypot instance
     *
     * @param \PhpMimeMailParser\Parser $parsedMail phpMimeParser object
     * @param array $arfMail array with ARF detected results
     */
    public function __construct($parsedMail, $arfMail)
    {
        parent::__construct($parsedMail, $arfMail, $this);
    }

    /**
     * Parse attachments
     * @return array    Returns array with failed or success data
     *                  (See parser-common/src/Parser.php) for more info.
     */
    public function parse()
    {
        $reports = [];

        /*
         * First we attempt to parse the 'free' format which most people should have. This regex will not return
         * data if the 'paid' format is used.
         */
        $body = $this->parsedMail->getMessageBody();
        $body = str_replace('\r', '', $body);
        $body = str_replace(
            'Other Potentially Suspicious IPs On Your Network:',
            'IPs Engaged In Potentially Suspicious On Your Network:',
            $body
        );

        if (preg_match_all(
            '/Engaged\sIn\s(.+?)\s.+?Network:[\n]+(.+?)^\n/ms',
            $body,
            $matches
        )) {
            if (!empty($matches[1]) &&
                !empty($matches[2]) &&
                is_array($matches[1]) &&
                is_array($matches[2]) &&
                count($matches[1]) == 5 &&
                count($matches[2]) == 5
            ) {
                /*
                 * Free reports hold hold a timestamp, so need to figure this out ourselves so we can still ignore
                 * duplicates when saving the events.
                 */
                if (!empty($this->parsedMail->getHeader('date')) &&
                    ctype_digit(strtotime($this->parsedMail->getHeader('date'))) &&
                    strtotime(
                        date(
                            'Y-m-d H:i:s',
                            strtotime($this->parsedMail->getHeader('date'))
                        )
                    ) === (int)strtotime($this->parsedMail->getHeader('date'))
                ) {
                    $timestamp = strtotime($this->parsedMail->getHeader('date'));
                } else {
                    $timestamp = strtotime(date('Y-m-d 00:00:00'));
                }

                foreach ($matches[2] as $key => $ipSet) {
                    foreach (array_filter(explode("\n", $ipSet)) as $ip) {
                        $reports[] = $report = [
                            'feed' => $matches[1][$key],
                            'ip' => $ip,
                            'timestamp' => $timestamp,
                        ];
                    }
                }
            }

        } elseif (preg_match_all(
            '/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}) \(([HSDCR])\)\r?\n  \- ([\w, :]+)\r?\n/',
            $this->parsedMail->getMessageBody(),
            $matches
        )) {
            array_shift($matches);

            while (sizeof($matches[0]) > 0) {
                $reports[] = [
                    'ip' => array_shift($matches[0]),
                    'feed' => config("{$this->configBase}.parser.aliases")[array_shift($matches[1])],
                    'timestamp' => strtotime(array_shift($matches[2])),
                ];
            }
        } elseif (preg_match_all(
            // Match ipv6
            '/^(?>(?>([a-f0-9]{1,4})(?>:(?1)){7}|(?!(?:.*[a-f0-9](?>:|$)){8,})((?1)(?>:(?1)){0,6})?::(?2)?)'.
            '|(?>(?>(?1)(?>:(?1)){5}:|(?!(?:.*[a-f0-9]:){6,})(?3)?::(?>((?1)(?>:(?1)){0,4}):)?)'.
            '?(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(?>\.(?4)){3}))/im',
            $this->parsedMail->getMessageBody(),
            $ipv6matches
        )) {
            // Get the ClassType and Date/Time
            $ipv6matches = array_shift($ipv6matches);
            preg_match_all(
                '/^(?>(?>([a-f0-9]{1,4})(?>:(?1)){7}|(?!(?:.*[a-f0-9](?>:|$)){8,})((?1)(?>:(?1)){0,6})?::(?2)?)'.
                '|(?>(?>(?1)(?>:(?1)){5}:|(?!(?:.*[a-f0-9]:){6,})(?3)?::(?>((?1)(?>:(?1)){0,4}):)?)'.
                '?(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(?>\.(?4)){3})) \(([HSDCR])\)\r?\n  \- ([\w, :]+)\r?\n/im',
                $this->parsedMail->getMessageBody(),
                $matches
            );
            array_shift($matches);

            // Combine the data from both regexp's and construct a ready to use array to create a report.
            $report_data = array_merge([$ipv6matches], [$matches[4]], [$matches[5]]);
            while (sizeof($report_data[0]) > 0) {
                $reports[] = [
                    'ip' => array_shift($report_data[0]),
                    'feed' => config("{$this->configBase}.parser.aliases")[array_shift($report_data[1])],
                    'timestamp' => strtotime(array_shift($report_data[2])),
                ];
            }
        } else {
            /*
             * Finally we just give up raising a warning.
             */
            $this->warningCount++;
        }

        if (!empty($reports) && is_array($reports)) {
            foreach ($reports as $report) {
                if ($report['ip'] != 'none') {
                    $this->feedName = $report['feed'];

                    // If feed is known and enabled, validate data and save report
                    if ($this->isKnownFeed() && $this->isEnabledFeed()) {
                        // Sanity check
                        if ($this->hasRequiredFields($report) === true) {
                            // Event has all requirements met, filter and add!
                            $report = $this->applyFilters($report);

                            $this->events[] = [
                                'source' => config("{$this->configBase}.parser.name"),
                                'ip' => $report['ip'],
                                'domain' => false,
                                'uri' => false,
                                'class' => config("{$this->configBase}.feeds.{$this->feedName}.class"),
                                'type' => config("{$this->configBase}.feeds.{$this->feedName}.type"),
                                'timestamp' => $report['timestamp'],
                                'information' => json_encode($report),
                            ];
                        }
                    }
                }
            }
        }

        return $this->success();
    }
}
