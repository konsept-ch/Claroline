<?php

namespace Claroline\CursusBundle\Installation\DataFixtures\Template;

use Claroline\CoreBundle\Installation\DataFixtures\AbstractTemplateFixture;

class TrainingSessionValidatedData extends AbstractTemplateFixture
{
    protected static function getTemplateType(): string
    {
        return 'training_session_validated';
    }

    protected function getSystemTemplates(): array
    {
        return [
            'Claroline Connect' => [
                'en' => [
                    'title' => 'Validation of registration for a training session',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_session_validated.en.html.twig'),
                ],
                'fr' => [
                    'title' => 'Validation de l\'inscription à une session de formation',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_session_validated.fr.html.twig'),
                ],
            ],
        ];
    }
}
