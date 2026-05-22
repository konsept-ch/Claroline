<?php

namespace Claroline\CoreBundle\Repository\Tool;

use Claroline\CoreBundle\Entity\Tool\ToolRights;
use Claroline\CoreBundle\Library\Testing\RepositoryTestCase;
use Claroline\CoreBundle\Security\PlatformRoles;

class ToolRightsRepositoryTest extends RepositoryTestCase
{
    private static $repo;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$repo = self::getRepository(ToolRights::class);

        self::createWorkspace('ws_1');
        self::createRole('ROLE_ANONYMOUS');
        self::createRole('ROLE_1', self::get('ws_1'));
        self::createTool('tool_public');
        self::createWorkspaceTool(self::get('tool_public'), self::get('ws_1'), [self::get('ROLE_ANONYMOUS')], 1);
    }

    public function testFindMaximumRights()
    {
        $mask = self::$repo->findMaximumRights(['ROLE_1'], self::get('tool_public'), self::get('ws_1'));
        $this->assertSame(63, $mask);
    }

    public function testFindMaximumRightsDoesNotWidenScopedWorkspaceAnonymousAccess()
    {
        $mask = self::$repo->findMaximumRights(
            [PlatformRoles::WORKSPACE_ACCESS, 'ROLE_1'],
            self::get('tool_public'),
            self::get('ws_1')
        );

        $this->assertSame(0, $mask);
    }
}
