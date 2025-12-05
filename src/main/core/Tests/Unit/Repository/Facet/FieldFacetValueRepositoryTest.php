<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Repository\Facet;

use Claroline\CoreBundle\Entity\Facet\FieldFacetValue;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mockery as m;

class FieldFacetValueRepositoryTest extends MockeryTestCase
{
    public function testReturnsEmptyArrayWhenUserHasNoId(): void
    {
        $em = $this->mock(EntityManagerInterface::class);
        $em->shouldReceive('createQuery')->never();

        $repository = new FieldFacetValueRepository($em, $this->getMetadata());

        $this->assertSame([], $repository->findPlatformValuesByUser(new User()));
    }

    public function testQueriesWhenUserHasId(): void
    {
        $user = new User();
        $user->setId(123);

        $query = $this->mock('Doctrine\ORM\AbstractQuery');
        $query->shouldReceive('setParameter')->once()->with('user', $user)->andReturn($query);
        $query->shouldReceive('getResult')->once()->andReturn(['fv']);

        $em = $this->mock(EntityManagerInterface::class);
        $em->shouldReceive('createQuery')
            ->once()
            ->with(m::on(function ($dql) {
                // Ignore formatting differences by normalizing whitespace
                $normalized = preg_replace('/\s+/', ' ', trim($dql));

                return $normalized === 'SELECT fv FROM Claroline\CoreBundle\Entity\Facet\FieldFacetValue fv LEFT JOIN fv.fieldFacet AS f WHERE fv.user = :user AND f.panelFacet IS NOT NULL';
            }))
            ->andReturn($query);

        $repository = new FieldFacetValueRepository($em, $this->getMetadata());

        $this->assertSame(['fv'], $repository->findPlatformValuesByUser($user));
    }

    private function getMetadata(): ClassMetadata
    {
        return new ClassMetadata(FieldFacetValue::class);
    }
}
