<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Http\Listener;

use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class FirewallStrategyListener implements EventSubscriberInterface
{
    public function __construct(private Firewall $firewall)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $strategy = null;

        /** @var SecurityStrategy[] $attributes */
        $attributes = $event->getAttributes(SecurityStrategy::class);
        $number_of_attributes = \count($attributes);
        if ($number_of_attributes > 1) {
            throw new \RuntimeException(\sprintf(
                'You can apply only one security strategy per HTTP request. You actually used the "%s" attribute %d times.',
                SecurityStrategy::class,
                $number_of_attributes,
            ));
        } elseif ($number_of_attributes === 1) {
            $strategy = current($attributes)->strategy;
        } elseif ($event->isMainRequest()) {
            $strategy = $this->firewall->computeFallbackStrategy($event->getRequest());
        }

        if ($strategy !== null) {
            $this->firewall->applyStrategy($strategy);
        }
    }
}