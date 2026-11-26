<?php

namespace Claroline\CursusBundle\Tests\Unit\Controller;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\FinderProvider;
use Claroline\AppBundle\Manager\PdfManager;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use Claroline\CursusBundle\Controller\EventController;
use Claroline\CursusBundle\Entity\Event;
use Claroline\CursusBundle\Manager\EventManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CEP : `GET /apiv2/cursus_event/{workspace}` répondait sans connexion, et un identifiant qui
 * n'est pas celui d'un espace listait toutes les séances, sans limite (constaté le 25.11.2026).
 */
class EventControllerTest extends MockeryTestCase
{
    private $authorization;
    private $finder;

    public function testTheListRefusesAnonymousVisitors()
    {
        $this->authorization->shouldReceive('isGranted')->with('IS_AUTHENTICATED_FULLY')->andReturn(false);
        $this->finder->shouldNotReceive('search');

        $this->expectException(AccessDeniedException::class);

        $this->controller()->listAction(new Request(), Event::class, $this->workspace());
    }

    public function testTheListRefusesAnUnknownWorkspaceInsteadOfListingEverything()
    {
        $this->authorization->shouldReceive('isGranted')->with('IS_AUTHENTICATED_FULLY')->andReturn(true);
        $this->finder->shouldNotReceive('search');

        $this->expectException(NotFoundHttpException::class);

        $this->controller()->listAction(new Request(), Event::class, null);
    }

    public function testTheListIsFilteredOnTheWorkspace()
    {
        $this->authorization->shouldReceive('isGranted')->with('IS_AUTHENTICATED_FULLY')->andReturn(true);
        $this->authorization->shouldReceive('isGranted')->with('ROLE_ADMIN')->andReturn(true);
        $this->finder->shouldReceive('search')
            ->once()
            ->withArgs(function ($class, $query) {
                return Event::class === $class && 'ws-uuid' === $query['hiddenFilters']['workspace'];
            })
            ->andReturn(['data' => [], 'totalResults' => 0]);

        $response = $this->controller()->listAction(new Request(), Event::class, $this->workspace());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testThePublicListRefusesAnUnknownWorkspace()
    {
        $this->finder->shouldNotReceive('search');

        $this->expectException(NotFoundHttpException::class);

        $this->controller()->listPublicAction(new Request(), null);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorization = $this->mock(AuthorizationCheckerInterface::class);
        $this->finder = $this->mock(FinderProvider::class);
    }

    private function controller(): EventController
    {
        $controller = new EventController(
            $this->authorization,
            $this->mock(TokenStorageInterface::class),
            $this->mock(TranslatorInterface::class),
            $this->mock(EventManager::class),
            $this->mock(PdfManager::class)
        );
        $controller->setFinder($this->finder);
        $controller->setCrud($this->mock(Crud::class));

        return $controller;
    }

    private function workspace(): Workspace
    {
        $workspace = new Workspace();
        $workspace->setUuid('ws-uuid');

        return $workspace;
    }
}
