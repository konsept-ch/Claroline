<?php

namespace Claroline\CursusBundle\Installation\DataFixtures\Template;

use Claroline\CoreBundle\Installation\DataFixtures\AbstractTemplateFixture;

class TrainingSessionRefusedData extends AbstractTemplateFixture
{
    protected static function getTemplateType(): string
    {
        return 'training_session_refused';
    }

    protected function getSystemTemplates(): array
    {
        return [
            'Claroline Connect' => [
                'en' => [
                    'title' => 'Refusal of registration for a training session',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_session_refused.en.html.twig'),
                ],
                'fr' => [
                    'title' => 'Refus de l\'inscription à une session de formation',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_session_refused.fr.html.twig'),
                ],
            ],
        ];
    }
}
