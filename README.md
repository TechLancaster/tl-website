# TechLancaster
_The Official TechLancaster Website... Now with 20% more TechLancaster!_

## Objective
The goal of this site is to be the one stop shop for the tech community in the Lancaster area. The old TL site was just a home for the TL meetup. The new site can be an umbrella for all of them. This isn't a corporate take-over of all the meetups, though. We just think the community could benefit from a central location to dive off from.

The central aspect of the new site will be a mirror of the existing community calendar to get an overview of the upcoming meet-ups and events. There will be some small amount of detail for each item, but we'll link out to the actual homes set up by that meetup's maintainer. We'll pull the data from the Google calendar so maintainers only have to update their info in one place.

We'll also try to create a compendium of resources for all of us. Want to start up a new meetup? We'll have info for that. New to the area and looking to plug in? We've got you covered. And if you have any other ideas for the site, let us know. We're building this together.

## Contribute
Mi casa es su casa. Everyone is welcome to submit issues and/or pull requests. Or jam in person at  [hacknight](http://www.hacklancaster.net/). Here's what you need to get started:

1. Make sure you've got these installed:
    - Composer
    - Rbenv
    - Bundler
2. Clone/Fork the repo
3. Run `composer install` to grab PHP dependencies
4. Run `bundle` to grab other dependencies. Note that gems *must* be installed to `.gems` in the project root.
5. Run `php app/console assetic:dump` to compile sass files to css.
6. _Optional:_ You can also run `php app/console assetic:watch` to work on sass files and auto-compile them
7. Make a copy of `web/events-example.json` called `web/events.json` so the calendar has content.
8. _Note:_ The live site pulls data from the community calendar and writes to `web/events.json`, but you can just use our dummy file of old data instead of bothering with this step.
9. Finally, run `php app/console server:start` to serve up the project and view it in a browser at `http://localhost:8000`!

## Details
Our initial thoughts are as follows:

- A PHP framework called [Symfony](http://symfony.com/what-is-symfony)
- [Bootstrap Sass](https://github.com/twbs/bootstrap-sass) on the front
- Scraping the [community calendar](https://www.google.com/calendar/embed?src=6l7e832ee9bemt1i9c42vltrug%40group.calendar.google.com)
- Mocks and assets are in the /assets directory, using the following fonts:
    - Lato Regular
    - Lato Light
    - Lato Light Italic
    - Oswald Regular
