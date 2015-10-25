<?php

namespace Techlancaster\Bundle\WebBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $menu->addChild('Home', array('route' => 'techlancaster_web_homepage'));
        $menu->addChild('Resources for Meetups', array('route' => 'techlancaster_resources'));
        $menu->addChild('Calendar', array('uri' => '/#calendar'));

        return $menu;
    }

    public function rightMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $menu->addChild('Next TL -  Nov. 17', array('route' => 'techlancaster_meetup'));

        return $menu;
    }
}