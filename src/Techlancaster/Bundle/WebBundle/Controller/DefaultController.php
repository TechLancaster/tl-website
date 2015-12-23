<?php

namespace Techlancaster\Bundle\WebBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Google_Service_Calendar_Event;
use Symfony\Component\Config\FileLocator;
use Techlancaster\Bundle\WebBundle\Entity\Event;

class DefaultController extends Controller
{
    public function indexAction()
    {
        if (file_exists("events.json") &&
            ($file = file_get_contents("events.json")) !== false
        ) {
            $json = json_decode($file, true);
        } else {
            $json = array();
        }

        // Modify the data to fit into the template
        foreach ($json as &$event) {
            /* the title should handle a max of ~50 characters to accomodate 3 lines
                at it's smallest width (browser width of 992px).
                truncate summary at last full word */
            if (strlen($event['summary']) > 47) {
                $event['summary'] = wordwrap($event['summary'], 47);
                $event['summary'] = substr($event['summary'], 0, strpos($event['summary'], "\n")) . '...';
            }

            // make links in description clickable
            $pattern_url = '/((?:(?:http|ftp)s?:\/\/)?[a-z0-9\-\.]+\.[a-z]{2,5}(?:\/\S*)?)/i';
            $url_replacement = '<a href="$1">$1</a> ';
            $event['description'] = preg_replace($pattern_url, $url_replacement, $event['description']);
            // replace newlines with <br> tag
            $event['description'] = nl2br($event['description']);

            /* location needs link and truncation
               (for one line, cap at ~30 characters) */
            // init short_location
            $short_location = $event['location'];
            // truncate short_location at the first comma
            if (strpos($short_location, ',') !== false) {
                $short_location = substr($event['location'], 0, strpos($event['location'], ','));
            }
            // truncate short_location after the 30th character respecting whole words
            if (strlen($short_location) > 30) {
                $short_location = wordwrap($short_location, 30);
                $short_location = substr($short_location, 0, strpos($short_location, "\n"));
                // trim non-alphanumeric characters off the end of string
                $short_location = preg_replace('/[^a-z\d]+$/i', '', $short_location);
            }
            // create location as link with short text
            $event['location'] = '<a href="https://www.google.com/maps/?q=' . $event['location'] . '" >' . $short_location . '</a>';
        }

        return $this->render('TechlancasterWebBundle:Default:index.html.twig', array('events' => $json));
    }

    public function fetchAction()
    {
        // TODO move to a cron job
        // TODO move google credentials to symfony parameters file
        $configDirectories = array(__DIR__.'/../Resources/config');
        $locator = new FileLocator($configDirectories);
        $clientSecret = $locator->locate('client_secret.json');
        $credentialsPath = $locator->locate('calendar-api-quickstart.json');

        // Get the API client and construct the service object.
        $client = new \Google_Client();
        $client->setApplicationName('Google Calendar API Quickstart');
        $client->setScopes(
            implode(' ', array(\Google_Service_Calendar::CALENDAR_READONLY))
        );
        $client->setAuthConfigFile($clientSecret);
        $client->setAccessType('offline');
        // Load previously authorized credentials from a file.
        if (file_exists($credentialsPath)) {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            //TODO this should be accomplished with an error status
            return $this->render('TechlancasterWebBundle:Default:calendar.html.twig', array(
                'message' => 'Invalid Credentials',
            ));
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, $client->getAccessToken());
        }
        $service = new \Google_Service_Calendar($client);

        $calendarId = '6l7e832ee9bemt1i9c42vltrug@group.calendar.google.com';
        $optParams = array(
            'maxResults' => 29,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c'),
        );
        $results = $service->events->listEvents($calendarId, $optParams);
        $events = array();

        /**
         * @var $googleEvent Google_Service_Calendar_Event
         */
        foreach ($results->getItems() as $googleEvent) {
            $event = new Event();
            $event->setId($googleEvent->getId());
            $event->setDescription($googleEvent->getDescription());
            $event->setLocation($googleEvent->getLocation());
            $event->setStart($googleEvent->getStart());
            $event->setEnd($googleEvent->getEnd());
            $event->setSummary($googleEvent->getSummary());
            $events[] = $event;
        }

        // Write the events to a file
        $json = json_encode($events);
        $handler = fopen("events.json", 'w')
        or die("Error opening output file");
        fwrite($handler, $json);
        fclose($handler);

        return $this->render('TechlancasterWebBundle:Default:calendar.html.twig', array('message'=>$json));
    }

    public function meetupAction()
    {
        return $this->render('TechlancasterWebBundle:Default:meetup.html.twig');
    }

    public function resourcesAction()
    {
        return $this->render('TechlancasterWebBundle:Default:resources.html.twig');
    }
}