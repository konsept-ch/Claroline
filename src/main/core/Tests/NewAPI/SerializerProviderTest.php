<?php

namespace Claroline\CoreBundle\Tests\NewAPI;

use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\SchemaProvider;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\API\ValidatorProvider;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Library\Testing\TransactionalTestCase;
use Claroline\CoreBundle\Security\PlatformRoles;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class SerializerProviderTest extends TransactionalTestCase
{
    /** @var SerializerProvider */
    private $provider;
    /** @var ValidatorProvider */
    private $validator;
    /** @var SchemaProvider */
    private $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = $this->client->getContainer()->get(SerializerProvider::class);
        $this->validator = $this->client->getContainer()->get(ValidatorProvider::class);
        $this->schema = $this->client->getContainer()->get(SchemaProvider::class);

        // this is hacky
        // I need to allow anonymous to see users email otherwise the serialized data can not pass the validation
        // the feature needs to be written in another way to avoid this
        $config = $this->client->getContainer()->get(PlatformConfigurationHandler::class);
        $config->setParameter('profile.show_email', [PlatformRoles::ANONYMOUS]);

        $tokenStorage = $this->client->getContainer()->get('security.token_storage');
        $token = new AnonymousToken('key', 'anon.', [PlatformRoles::ANONYMOUS]);
        $tokenStorage->setToken($token);
    }

    /**
     * @dataProvider getHandledClassesProvider
     *
     * @param string $class
     *
     * If json is malformed, a syntax error will be thrown
     */
    public function testSchema($class)
    {
        if ($this->schema->has($class)) {
            $schema = $this->schema->getSchema($class);
            $this->assertTrue(is_object($schema));
        } else {
            $this->markTestSkipped('No schema defined for class '.$class);
        }
    }

    /**
     * @dataProvider getHandledClassesProvider
     *
     * @param string $class
     */
    public function testSerializer($class)
    {
        $iterator = new \DirectoryIterator($this->schema->getSampleDirectory($class).'/json/valid/create');

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $originalData = \file_get_contents($file->getPathName());
                //let's test the deserializer
                $object = new $class();
                $object = $this->provider->deserialize(json_decode($originalData, true), $object);
                //can we serialize it ?
                $data = $this->provider->serialize($object);

                if ('Claroline\CoreBundle\Entity\User' === $class) {
                    $data['plainPassword'] = '123';
                }
                //is the result... valid ?
                $errors = $this->validator->validate($class, $data, ValidatorProvider::UPDATE);
                $this->assertTrue(0 === count($errors));
            }
        }
    }

    public function testUserRegistrationPayloadWithMainOrganization(): void
    {
        $data = [
            'preferences' => [
                'locale' => 'fr',
            ],
            'lastName' => 'Test',
            'firstName' => 'Debug',
            'email' => 'debug.repro@example.com',
            'plainPassword' => 'Debug123!',
            'phone' => '079 123 45 67',
            'profile' => [
                '8e478bb6-4796-4700-b9c6-968636fe94c7' => 'Monsieur',
                '79a0a4f9-1b7b-4ca2-8e09-f3b10c011df2' => 'Compte professionnel',
                '3b9164ac-4ba7-417b-b585-321631199c6a' => ['Cadre de direction'],
                '2f0cf951-d30f-4555-adba-ce0ac0ed25ca' => [
                    'id' => '346e0f18-5fed-4d2e-8228-ddd36edb8caf',
                    'name' => 'ACI - Administration cantonale des impôts (hors OID)',
                    'code' => 'VD-DGF-ACI',
                    'email' => null,
                    'type' => 'internal',
                    'meta' => ['default' => false, 'position' => null],
                    'restrictions' => ['public' => false, 'users' => -1],
                    'parent' => [
                        'id' => '0d3b362b-f4c0-4970-9981-98675ab9703f',
                        'name' => 'DGF - Direction générale de la fiscalité',
                        'code' => 'VD-DGF',
                        'meta' => ['default' => false],
                    ],
                    'locations' => [],
                    'children' => [],
                ],
                'b5c3ea76-60a1-4f5d-a852-bbdada6c540b' => '1990-01-01T00:00:00',
            ],
            'mainOrganization' => [
                'id' => '346e0f18-5fed-4d2e-8228-ddd36edb8caf',
                'name' => 'ACI - Administration cantonale des impôts (hors OID)',
                'code' => 'VD-DGF-ACI',
            ],
        ];

        $errors = $this->validator->validate(
            \Claroline\CoreBundle\Entity\User::class,
            $data,
            ValidatorProvider::CREATE,
            true,
            [Options::REGISTRATION, Options::ADD_NOTIFICATIONS, Options::WORKSPACE_VALIDATE_ROLES, Options::VALIDATE_FACET]
        );

        $this->assertSame([], $errors);
    }

    public function testUserRegistrationPayloadWithProfileOrganizationOnly(): void
    {
        $data = [
            'preferences' => [
                'locale' => 'fr',
            ],
            'lastName' => 'Test',
            'firstName' => 'Debug',
            'email' => 'debug.repro.profile@example.com',
            'plainPassword' => 'Debug123!',
            'phone' => '079 123 45 67',
            'profile' => [
                '8e478bb6-4796-4700-b9c6-968636fe94c7' => 'Monsieur',
                '79a0a4f9-1b7b-4ca2-8e09-f3b10c011df2' => 'Compte professionnel',
                '3b9164ac-4ba7-417b-b585-321631199c6a' => ['Cadre de direction'],
                '2f0cf951-d30f-4555-adba-ce0ac0ed25ca' => [
                    'id' => '346e0f18-5fed-4d2e-8228-ddd36edb8caf',
                    'name' => 'ACI - Administration cantonale des impÃ´ts (hors OID)',
                    'code' => 'VD-DGF-ACI',
                    'email' => null,
                    'type' => 'internal',
                    'meta' => ['default' => false, 'position' => null],
                    'restrictions' => ['public' => false, 'users' => -1],
                    'parent' => [
                        'id' => '0d3b362b-f4c0-4970-9981-98675ab9703f',
                        'name' => 'DGF - Direction gÃ©nÃ©rale de la fiscalitÃ©',
                        'code' => 'VD-DGF',
                        'meta' => ['default' => false],
                    ],
                    'locations' => [],
                    'children' => [],
                ],
                'b5c3ea76-60a1-4f5d-a852-bbdada6c540b' => '1990-01-01T00:00:00',
            ],
        ];

        $errors = $this->validator->validate(
            \Claroline\CoreBundle\Entity\User::class,
            $data,
            ValidatorProvider::CREATE,
            true,
            [Options::REGISTRATION, Options::ADD_NOTIFICATIONS, Options::WORKSPACE_VALIDATE_ROLES, Options::VALIDATE_FACET]
        );

        $this->assertSame([], $errors);
    }

    /**
     * @return [][]
     */
    public function getHandledClassesProvider()
    {
        parent::setUp();
        $provider = $this->client->getContainer()->get('Claroline\AppBundle\API\SerializerProvider');
        $schemaProvider = $this->client->getContainer()->get('Claroline\AppBundle\API\SchemaProvider');

        $classes = array_map(function ($serializer) use ($provider) {
            return [$provider->getSerializerHandledClass($serializer)];
        }, $provider->all());

        $classes = array_filter($classes, function ($class) use ($schemaProvider) {
            return $schemaProvider->has($class[0]) && $schemaProvider->getSampleDirectory($class[0]) && class_exists($class[0]);
        });

        return $classes;
    }
}
