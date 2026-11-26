<?php

namespace Claroline\MessageBundle\Tests\Unit\Controller;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\FinderProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use Claroline\MessageBundle\Controller\MessageController;
use Claroline\MessageBundle\Entity\UserMessage;
use Claroline\MessageBundle\Manager\MessageManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * CEP : la messagerie répondait sans connexion (100 951 messages lisibles sur VAL), et les
 * actions d'écriture agissaient sur les messages de n'importe qui (constaté le 26.11.2026).
 */
class MessageControllerTest extends MockeryTestCase
{
    private $tokenStorage;
    private $finder;
    private $om;
    private $crud;
    private $messageManager;

    public function testTheReceivedListRefusesAnonymousVisitors()
    {
        $this->tokenStorage->setToken(new AnonymousToken('secret', 'anon.'));
        $this->finder->shouldNotReceive('search');

        $this->expectException(AccessDeniedException::class);

        $this->controller()->getReceivedAction(new Request());
    }

    public function testTheSentListRefusesAVisitorWithoutToken()
    {
        $this->finder->shouldNotReceive('search');

        $this->expectException(AccessDeniedException::class);

        $this->controller()->getSentAction(new Request());
    }

    public function testAMessageOfSomeoneElseCannotBeDeleted()
    {
        $this->connect($this->user(1));
        $this->om->shouldReceive('findList')->andReturn([$this->userMessage($this->user(2))]);
        $this->messageManager->shouldNotReceive('remove');

        $this->expectException(AccessDeniedException::class);

        $this->controller()->hardDeleteAction(new Request(['ids' => [10]]));
    }

    public function testOwnMessagesAreDeleted()
    {
        $me = $this->user(1);
        $this->connect($me);
        $this->om->shouldReceive('findList')->andReturn([$this->userMessage($me)]);
        $this->om->shouldReceive('startFlushSuite');
        $this->om->shouldReceive('endFlushSuite');
        $this->messageManager->shouldReceive('remove')->once();

        $response = $this->controller()->hardDeleteAction(new Request(['ids' => [10]]));

        $this->assertSame(204, $response->getStatusCode());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenStorage = new TokenStorage();
        $this->finder = $this->mock(FinderProvider::class);
        $this->om = $this->mock(ObjectManager::class);
        $this->crud = $this->mock(Crud::class);
        $this->messageManager = $this->mock(MessageManager::class);
    }

    private function controller(): MessageController
    {
        $controller = new MessageController($this->tokenStorage, $this->messageManager);
        $controller->setFinder($this->finder);
        $controller->setObjectManager($this->om);
        $controller->setCrud($this->crud);

        return $controller;
    }

    private function connect(User $user): void
    {
        $this->tokenStorage->setToken(new UsernamePasswordToken($user, null, 'main', ['ROLE_USER']));
    }

    private function user(int $id): User
    {
        $user = new User();
        $property = new \ReflectionProperty(User::class, 'id');
        $property->setAccessible(true);
        $property->setValue($user, $id);

        return $user;
    }

    private function userMessage(User $owner): UserMessage
    {
        $message = new UserMessage();
        $message->setUser($owner);

        return $message;
    }
}
