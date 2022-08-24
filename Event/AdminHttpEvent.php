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
 * Austral Http Event.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AdminHttpEvent extends HttpEvent
{

  const EVENT_AUSTRAL_HTTP_REQUEST = "austral.event.http.admin.request";
  const EVENT_AUSTRAL_HTTP_CONTROLLER = "austral.event.http.admin.controller";
  const EVENT_AUSTRAL_HTTP_RESPONSE = "austral.event.http.admin.response";
  const EVENT_AUSTRAL_HTTP_EXCEPTION = "austral.event.http.admin.exception";

}