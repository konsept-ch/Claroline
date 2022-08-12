<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Manager;

use Claroline\CoreBundle\Manager\LocaleManager;
use Claroline\CoreBundle\Manager\MailManager;
use Claroline\CoreBundle\Manager\Template\TemplateManager;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class QuotaManager
{
    /** @var TemplateManager */
    private $templateManager;

    /** @var LocaleManager */
    private $localeManager;

    /** @var MailManager */
    private $mailManager;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(
        TemplateManager $templateManager,
        LocaleManager $localeManager,
        MailManager $mailManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->templateManager = $templateManager;
        $this->localeManager = $localeManager;
        $this->mailManager = $mailManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function sendValidatedStatusMail(SessionUser $sessionUser): void
    {
        $this->sendMail('training_quota_status_validated', $sessionUser);
    }

    public function sendManagedStatusMail(SessionUser $sessionUser): void
    {
        $this->sendMail('training_quota_status_managed', $sessionUser);
    }

    public function sendRefusedStatusMail(SessionUser $sessionUser): void
    {
        $this->sendMail('training_quota_status_refused', $sessionUser);
    }

    public function sendCancelledStatusMail(SessionUser $sessionUser): void
    {
        $this->sendMail('training_quota_status_cancelled', $sessionUser);
    }

    private function sendMail(string $template, SessionUser $sessionUser): void
    {
        $manager = $this->tokenStorage->getToken()->getUser();

        $user = $sessionUser->getUser();
        $locale = $this->localeManager->getLocale($user);

        $placeholders = [
            'session_name' => $sessionUser->getSession()->getName(),
            'user_first_name' => $user->getFirstName(),
            'user_last_name' => $user->getLastName(),
            'session_start' => $sessionUser->getSession()->getStartDate()->format('d/m/Y'),
            'session_end' => $sessionUser->getSession()->getEndDate()->format('d/m/Y'),
            'remark' => $sessionUser->getRemark(),
        ];
        $subject = $this->templateManager->getTemplate($template, $placeholders, $locale, 'title');
        $body = $this->templateManager->getTemplate($template, $placeholders, $locale);

        $this->mailManager->send($subject, $body, [$user], $manager, [], true);
    }
}
