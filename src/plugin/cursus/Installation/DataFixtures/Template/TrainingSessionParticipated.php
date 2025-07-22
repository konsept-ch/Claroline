<?php

namespace Claroline\CursusBundle\Installation\DataFixtures\Template;

use Claroline\CoreBundle\Installation\DataFixtures\AbstractTemplateFixture;

class TrainingSessionParticipated extends AbstractTemplateFixture
{
    protected static function getTemplateType(): string
    {
        return 'training_session_participated';
    }

    protected function getSystemTemplates(): array
    {
        return [
            'Claroline Connect' => [
                'en' => [
                    'title' => 'Participation to the session',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_session_participated.en.html.twig'),
                ],
                'fr' => [
                    'title' => 'Participation à la session',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_session_participated.fr.html.twig'),
                ],
            ],
        ];
    }
}
