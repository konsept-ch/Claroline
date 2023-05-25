<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\AgendaBundle\Security\Voter;

use Claroline\AgendaBundle\Entity\Event;

class EventVoter extends AbstractEventVoter
{
    public function getClass(): string
    {
        return Event::class;
    }
}
