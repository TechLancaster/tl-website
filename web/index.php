<?php

namespace techlancaster;

use Silex;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../calendar/Event.php';

$app = new Silex\Application();

$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
    $twig->addExtension(new \Twig_Extensions_Extension_Text($app));

    return $twig;
}));

$app->get('/fetchevents', function () use ($app) {
    // Get the API client and construct the service object.
    $client = new Google_Client();
    $client->setApplicationName('Google Calendar API Quickstart');
    $client->setScopes(
        implode(' ', array(Google_Service_Calendar::CALENDAR_READONLY))
    );
    $client->setAuthConfigFile(__DIR__.'/../conf/client_secret.json');
    $client->setAccessType('offline');
    // Load previously authorized credentials from a file.
    $credentialsPath = __DIR__.'/../conf/calendar-api-quickstart.json';
    if (file_exists($credentialsPath)) {
        $accessToken = file_get_contents($credentialsPath);
    } else {
        //TODO this should be accomplished with an error status
        return $app['twig']->render('calendar.twig', array(
            'message' => 'Invalid Credentials',
        ));
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->refreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, $client->getAccessToken());
    }
    $service = new Google_Service_Calendar($client);

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
        $event = new calendar\Event();
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

    return $app['twig']->render('calendar.twig', array('message'=>$json));
});

$app->get('/meetup', function () use ($app) {
    return $app['twig']->render('meetup.twig', array(

    ));
});

$app->get('/', function () use ($app) {
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
        // truncate short_location after the 30th charater respecting whole words
        if (strlen($short_location) > 30) {
            $short_location = wordwrap($short_location, 30);
            $short_location = substr($short_location, 0, strpos($short_location, "\n"));
        }
        // create location as link with short text
        $event['location'] = '<a href="https://www.google.com/maps/?q=' . $event['location'] . '" >' . $short_location . '</a>';
    }

    return $app['twig']->render('index.twig', array(
        'events'=>$json
    ));
});

$app->run();
