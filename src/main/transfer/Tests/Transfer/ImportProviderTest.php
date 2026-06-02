<?php

namespace Claroline\TransferBundle\Tests\Transfer;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\TransferBundle\Transfer\ImportProvider;
use Claroline\CoreBundle\Library\Testing\TransactionalTestCase;

class ImportProviderTest extends TransactionalTestCase
{
    /** @var ImportProvider */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = $this->client->getContainer()->get(ImportProvider::class);
    }

    /**
     * @dataProvider formatProvider
     *
     * @param string $format
     *
     * If json il malformed, a syntax error will be thrown
     */
    public function testActions($format)
    {
        $availableActions = $this->provider->getAvailableActions($format, [], []);

        foreach ($availableActions as $class) {
            foreach ($class as $action) {
                //we just check here the return is not null
                //(so json_decode worked and nothing crashed due to a bad schema)
                $this->assertTrue(null !== $action);
            }
        }
    }

    public function testOrganizationImportExposesParent()
    {
        $availableActions = $this->provider->getAvailableActions('csv', [], []);

        $this->assertArrayHasKey('organization', $availableActions);
        $this->assertArrayHasKey('create', $availableActions['organization']);

        $schema = $availableActions['organization']['create'];

        $this->assertTrue($this->hasProperty($schema->properties, 'parent.id'));
        $this->assertTrue($this->hasProperty($schema->properties, 'parent.code'));
        $this->assertTrue($this->hasProperty($schema->properties, 'parent.name'));
    }

    public function testObjectManagerResolvesUnflushedOrganizationByCode()
    {
        /** @var ObjectManager $om */
        $om = $this->client->getContainer()->get(ObjectManager::class);

        $organization = new Organization();
        $organization->setName('Communes');
        $organization->setCode('COMMUNES');

        $om->persist($organization);

        $found = $om->getObject(['code' => 'COMMUNES'], Organization::class, ['id', 'code', 'name']);

        $this->assertSame($organization, $found);
    }

    /**
     * @return string[]
     */
    public function formatProvider()
    {
        return [
          ['csv'],
          ['json'],
        ];
    }

    private function hasProperty(array $properties, string $name): bool
    {
        foreach ($properties as $property) {
            if ($property instanceof \Claroline\TransferBundle\Transfer\Adapter\Explain\Csv\Property && $property->getName() === $name) {
                return true;
            }

            if ($property instanceof \Claroline\TransferBundle\Transfer\Adapter\Explain\Csv\OneOf) {
                foreach ($property->getExplanations() as $explanation) {
                    if ($this->hasProperty($explanation->getProperties(), $name)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
