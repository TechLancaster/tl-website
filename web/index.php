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

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addExtension(new \Twig_Extensions_Extension_Text($app));

    return $twig;
}));

$app->get('/hello/{name}', function ($name) use ($app) {
    return $app['twig']->render('hello.twig', array(
        'name' => $name,
    ));
});

$app->get('/fetchevents', function () use ($app) {
    // Get the API client and construct the service object.
    $client = new Google_Client();
    $client->setApplicationName('Google Calendar API Quickstart');
    $client->setScopes(implode(' ', array(
            Google_Service_Calendar::CALENDAR_READONLY)
    ));
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
        'maxResults' => 30,
        'orderBy' => 'startTime',
        'singleEvents' => TRUE,
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
    $json = json_encode($events);
    return $app['twig']->render('calendar.twig', array('message'=>$json));
    //TODO store these in a json file for use in the application
});

$app->get('/meetup', function () use ($app) {
    return $app['twig']->render('meetup.twig', array(

    ));
});

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.twig', array(
    ));
});

$app->run();
