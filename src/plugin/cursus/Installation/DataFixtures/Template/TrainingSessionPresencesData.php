<?php

namespace Claroline\CursusBundle\Installation\DataFixtures\Template;

use Claroline\CoreBundle\Installation\DataFixtures\AbstractTemplateFixture;

class TrainingSessionPresencesData extends AbstractTemplateFixture
{
    protected static function getTemplateType(): string
    {
        return 'training_session_presences';
    }

    protected function getSystemTemplates(): array
    {
        return [
            'Claroline Connect' => [
                'en' => [
                    'title' => 'Attendance grid for a course session',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_session_presences.en.pdf.twig'),
                ],
                'fr' => [
                    'title' => 'Grille de présence d\'une session de formation',
                    'content' => $this->twig->render('@ClarolineCursus/template/training_session_presences.fr.pdf.twig'),
                ],
            ],
        ];
    }
}
