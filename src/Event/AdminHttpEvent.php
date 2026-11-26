<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Event;

use Austral\HttpBundle\Event\HttpEvent;

/**
 * Austral Admin Http Event.
 *
 * HTTP event of every route of the admin (attribute _austral_http_event set by AdminRouteLoader): the HttpListener
 * of http-bundle dispatches it as austral.event.http.*, and HttpAdminEventSubscriber prepares the AdminHandler and
 * the template parameters of the page.
 *
 * @extends HttpEvent<object>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminHttpEvent extends HttpEvent
{
}
