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
        $menu->addChild('Meetup -  June 21st', array('route' => 'techlancaster_meetup'));
        $menu->addChild('Members', array('route' => 'fos_user_security_login'));

        if ($this->container->get('security.context')->isGranted('ROLE_USER')) {
            $menu['Members']->addChild('Logout', array('route' => 'fos_user_security_logout'));
        } else {
            $menu['Members']->addChild('Login', array('route' => 'fos_user_security_login'));
            $menu['Members']->addChild('Register', array('route' => 'fos_user_registration_register'));
        }

        return $menu;
    }
}
