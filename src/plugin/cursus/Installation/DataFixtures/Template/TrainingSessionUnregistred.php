<?php

namespace Claroline\CursusBundle\Installation\DataFixtures\Template;

use Claroline\CoreBundle\Installation\DataFixtures\AbstractTemplateFixture;

class TrainingSessionUnregistred extends AbstractTemplateFixture
{
    protected static function getTemplateType(): string
    {
        return 'training_session_unregistred';
    }

    protected function getSystemTemplates(): array
    {
        return [
            'Claroline Connect' => [
                'en' => [
                    'title' => 'Unregistration from a training session',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_session_unregistred.en.html.twig'),
                ],
                'fr' => [
                    'title' => 'Désinscription à une session de formation',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_session_unregistred.fr.html.twig'),
                ],
            ],
        ];
    }
}
