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
        $em = $this->get('doctrine.orm.entity_manager');
        $events = $em->getRepository('TechlancasterWebBundle:Event')->findBy([],['start'=>'ASC']);

        // Modify the data to fit into the template
        foreach ($events as &$event) {
            /* the title should handle a max of ~50 characters to accommodate 3 lines
                at it's smallest width (browser width of 992px).
                truncate summary at last full word */
            if (strlen($event->getSummary()) > 47) {
                $event->setSummary(wordwrap($event->getSummary(), 47));
                $event->setSummary(substr($event->getSummary(), 0, strpos($event->getSummary(), "\n")) . '...');
            }

            // make links in description clickable
            $pattern_url = '/((?:(?:http|ftp)s?:\/\/)?[a-z0-9\-\.]+\.[a-z]{2,5}(?:\/\S*)?)/i';
            $url_replacement = '<a href="$1">$1</a> ';
            $event->setDescription(preg_replace($pattern_url, $url_replacement, $event->getDescription()));
            // replace newlines with <br> tag
            $event->setDescription(nl2br($event->getDescription()));

            /* location needs link and truncation
               (for one line, cap at ~30 characters) */
            // init short_location
            $short_location = $event->getLocation();
            // truncate short_location at the first comma
            if (strpos($short_location, ',') !== false) {
                $short_location = substr($event->getLocation(), 0, strpos($event->getLocation(), ','));
            }
            // truncate short_location after the 30th character respecting whole words
            if (strlen($short_location) > 30) {
                $short_location = wordwrap($short_location, 30);
                $short_location = substr($short_location, 0, strpos($short_location, "\n"));
                // trim non-alphanumeric characters off the end of string
                $short_location = preg_replace('/[^a-z\d]+$/i', '', $short_location);
            }
            // create location as link with short text
            $event->setLocation(
                '<a href="https://www.google.com/maps/?q=' . $event->getLocation() . '" >' . $short_location . '</a>'
            );
        }
        $em->clear();

        return $this->render('TechlancasterWebBundle:Default:index.html.twig', array('events' => $events));
    }

    public function fetchAction()
    {
        $em = $this->getDoctrine()->getEntityManager();

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

        $connection = $em->getConnection();
        $platform   = $connection->getDatabasePlatform();

        $connection->executeUpdate($platform->getTruncateTableSQL('event', false));

        /**
         * @var $googleEvent Google_Service_Calendar_Event
         */
        foreach ($results->getItems() as $googleEvent) {
            $event = new Event();
            $event->setId($googleEvent->getId());
            $event->setDescription($googleEvent->getDescription());
            $event->setLocation($googleEvent->getLocation());
            $event->setStart(new \DateTime($googleEvent->getStart()->dateTime));
            $event->setEnd(new \DateTime($googleEvent->getEnd()->dateTime));
            $event->setSummary($googleEvent->getSummary());
            $em->persist($event);
            $events[] = $event;
        }
        $em->flush();

        return $this->render('TechlancasterWebBundle:Default:calendar.html.twig', array(
            'message'=>json_encode($events))
        );
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
