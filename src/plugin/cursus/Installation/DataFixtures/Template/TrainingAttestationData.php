<?php

namespace Claroline\CursusBundle\Installation\DataFixtures\Template;

use Claroline\CoreBundle\Installation\DataFixtures\AbstractTemplateFixture;

class TrainingAttestationData extends AbstractTemplateFixture
{
    protected static function getTemplateType(): string
    {
        return 'training_attestation';
    }

    protected function getSystemTemplates(): array
    {
        return [
            'Claroline Connect' => [
                'en' => [
                    'title' => 'Attestation de formation',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_attestation.en.pdf.twig'),
                ],
                'fr' => [
                    'title' => 'Attestation de formation',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_attestation.fr.pdf.twig'),
                ],
            ],
        ];
    }
}
