<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Manager;

use Claroline\AppBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Event\CatalogEvents\SecurityEvents;
use Claroline\CoreBundle\Event\Security\ForgotPasswordEvent;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Library\Mailing\Mailer;
use Claroline\CoreBundle\Library\Mailing\Message;
use Claroline\CoreBundle\Manager\Template\TemplateManager;
use Claroline\CoreBundle\Manager\Template\UserPlaceholderMapper;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Manager\FacetManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailManager
{
    /** @var Mailer */
    private $mailer;
    /** @var UrlGeneratorInterface */
    private $router;
    /** @var PlatformConfigurationHandler */
    private $config;
    /** @var TemplateManager */
    private $templateManager;
    /** @var LocaleManager */
    private $localeManager;
    /** @var UserManager */
    private $userManager;
    /** @var StrictDispatcher */
    private $dispatcher;
    /** @var ObjectManager */
    private $om;
    /** @var FacetManager */
    private $facetManager;
    /** @var UserPlaceholderMapper */
    private $userPlaceholderMapper;

    public function __construct(
        Mailer $mailer,
        UrlGeneratorInterface $router,
        PlatformConfigurationHandler $config,
        TemplateManager $templateManager,
        LocaleManager $localeManager,
        UserManager $userManager,
        StrictDispatcher $dispatcher,
        ObjectManager $om,
        FacetManager $facetManager,
        UserPlaceholderMapper $userPlaceholderMapper
    ) {
        $this->mailer = $mailer;
        $this->router = $router;
        $this->config = $config;
        $this->templateManager = $templateManager;
        $this->localeManager = $localeManager;
        $this->userManager = $userManager;
        $this->dispatcher = $dispatcher;
        $this->om = $om;
        $this->facetManager = $facetManager;
        $this->userPlaceholderMapper = $userPlaceholderMapper;
    }

    public function isMailerAvailable(): bool
    {
        return $this->config->getParameter('mailer.enabled');
    }

    public function sendForgotPassword(User $user): bool
    {
        $this->dispatcher->dispatch(SecurityEvents::FORGOT_PASSWORD, ForgotPasswordEvent::class, [$user]);

        $locale = $this->localeManager->getLocale($user);
        $placeholders = [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'username' => $user->getUsername(),
            // civility coming from profile facets if configured
            'civility' => $this->resolveCivility($user),
        ];
        $placeholders = array_merge($placeholders, $this->mapUserPlaceholders($user));

        if (!$user->isEnabled()) {
            $subject = $this->templateManager->getTemplate('user_disabled', $placeholders, $locale, 'title');
            $body = $this->templateManager->getTemplate('user_disabled', $placeholders, $locale);

            return $this->send($subject, $body, [$user], null, [], true);
        }

        $this->userManager->initializePassword($user);

        $placeholders['password_reset_link'] = $this->router->generate(
            'claro_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        )."#/newpassword/{$user->getResetPasswordHash()}";

        $subject = $this->templateManager->getTemplate('forgotten_password', $placeholders, $locale, 'title');
        $body = $this->templateManager->getTemplate('forgotten_password', $placeholders, $locale);

        return $this->send($subject, $body, [$user], null, [], true);
    }

    public function sendInitPassword(User $user)
    {
        $this->userManager->initializePassword($user);
        $hash = $user->getResetPasswordHash();
        $link = $this->router->generate(
            'claro_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        )."#/newpassword/{$hash}";
        $locale = $this->localeManager->getLocale($user);
        $placeholders = [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'username' => $user->getUsername(),
            'password_initialization_link' => $link,
            'civility' => $this->resolveCivility($user),
        ];
        $placeholders = array_merge($placeholders, $this->mapUserPlaceholders($user));
        $subject = $this->templateManager->getTemplate('password_initialization', $placeholders, $locale, 'title');
        $body = $this->templateManager->getTemplate('password_initialization', $placeholders, $locale);

        return $this->send($subject, $body, [$user], null, [], true);
    }

    public function sendEnableAccountMessage(User $user)
    {
        $hash = $user->getResetPasswordHash();
        $link = $this->router->generate(
            'claro_security_activate_user',
            ['hash' => $hash],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $locale = $this->localeManager->getLocale($user);
        $placeholders = [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'username' => $user->getUsername(),
            'user_activation_link' => $link,
            'civility' => $this->resolveCivility($user),
        ];
        $placeholders = array_merge($placeholders, $this->mapUserPlaceholders($user));
        $subject = $this->templateManager->getTemplate('user_activation', $placeholders, $locale, 'title');
        $body = $this->templateManager->getTemplate('user_activation', $placeholders, $locale);

        return $this->send($subject, $body, [$user], null, [], true);
    }

    public function sendValidateEmail(User $user)
    {
        $url = $this->router->generate(
            'claro_security_validate_email',
            ['hash' => $user->getEmailValidationHash()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $locale = $this->localeManager->getLocale($user);
        $placeholders = [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'username' => $user->getUsername(),
            'validation_mail' => $url,
            'civility' => $this->resolveCivility($user),
        ];
        $placeholders = array_merge($placeholders, $this->mapUserPlaceholders($user));
        $subject = $this->templateManager->getTemplate('user_email_validation', $placeholders, $locale, 'title');
        $body = $this->templateManager->getTemplate('user_email_validation', $placeholders, $locale);

        $this->send($subject, $body, [$user], null, [], true);
    }

    /**
     * @return bool
     */
    public function sendCreationMessage(User $user)
    {
        $locale = $this->localeManager->getLocale($user);
        $url = $this->router->generate(
            'claro_security_validate_email',
            ['hash' => $user->getEmailValidationHash()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $placeholders = [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'username' => $user->getUsername(),
            'password' => $user->getPlainPassword(),
            'validation_mail' => $url,
            'civility' => $this->resolveCivility($user),
        ];
        $placeholders = array_merge($placeholders, $this->mapUserPlaceholders($user));
        $subject = $this->templateManager->getTemplate('user_registration', $placeholders, $locale, 'title');
        $body = $this->templateManager->getTemplate('user_registration', $placeholders, $locale);

        return $this->send($subject, $body, [$user], null, [], true);
    }

    private function resolveCivility(User $user): ?string
    {
        // 1) Try configuration-driven mapping via UserPlaceholderMapper
        $mapped = $this->userPlaceholderMapper->resolve($user, ['civility']);
        if (!empty($mapped['civility'])) {
            return $mapped['civility'];
        }

        // 2) Fallback: heuristic search on civility-like facet
        $mapped = $this->userPlaceholderMapper->resolve($user, []); // will load caches inside
        // Re-run a quick heuristic using the previous implementation for backward compat
        $repo = $this->om->getRepository(\Claroline\CoreBundle\Entity\Facet\FieldFacetValue::class);
        $choiceRepo = $this->om->getRepository(\Claroline\CoreBundle\Entity\Facet\FieldFacetChoice::class);
        $values = $repo->findPlatformValuesByUser($user);

        foreach ($values as $value) {
            $field = $value->getFieldFacet();
            if (!$field) {
                continue;
            }

            $label = mb_strtolower($field->getLabel() ?? '');
            $name  = mb_strtolower($field->getName() ?? '');

            if (false !== strpos($label, 'civility') || false !== strpos($label, 'civilité')
                || false !== strpos($name, 'civility') || false !== strpos($name, 'civilite')) {
                $raw = $this->facetManager->serializeFieldValue($user, $value->getType(), $value->getValue());

                $resolveChoice = function ($v) use ($choiceRepo) {
                    if (is_string($v)) {
                        $choice = $choiceRepo->findOneBy(['uuid' => $v]);
                        return $choice ? $choice->getLabel() : $v;
                    }
                    return $v;
                };

                if (is_array($raw)) {
                    $labels = array_map($resolveChoice, $raw);
                    $labels = array_filter($labels, function ($x) { return null !== $x && '' !== $x; });
                    return implode(', ', $labels);
                }

                return $resolveChoice($raw);
            }
        }

        return null;
    }

    private function mapUserPlaceholders(User $user): array
    {
        // Map additional placeholders via configured facet UUIDs
        $wanted = ['civility', 'function', 'partner'];
        $mapped = $this->userPlaceholderMapper->resolve($user, $wanted);

        // Fallback civility if not configured
        if (empty($mapped['civility'])) {
            $mapped['civility'] = $this->resolveCivility($user);
        }

        // Keep only non-empty values
        return array_filter($mapped, function ($v) { return null !== $v && $v !== ''; });
    }

    public function send($subject, $body, array $users, $from = null, array $extra = [], $force = true, $replyToMail = null)
    {
        if (0 === count($users) && (!isset($extra['to']) || 0 === count($extra['to']))) {
            //obviously, if we're not going to send anything to anyone, it's better to stop
            return false;
        }

        if ($this->isMailerAvailable()) {
            $to = [];

            $fromEmail = $this->config->getParameter('mailer.from');
            $locale = 1 === count($users) ? $users[0]->getLocale() : $this->localeManager->getDefault();

            if (!$locale) {
                $locale = $this->localeManager->getDefault();
            }

            $body = $this->templateManager->getTemplate('email_layout', ['content' => $body], $locale);

            if ($from) {
                $body = str_replace('%first_name%', $from->getFirstName(), $body);
                $body = str_replace('%last_name%', $from->getLastName(), $body);
            } else {
                $body = str_replace('%first_name%', $this->config->getParameter('display.name'), $body);
                $body = str_replace('%last_name%', '', $body);
            }

            foreach ($users as $user) {
                $email = $user->getEmail();

                if ($user->isMailValidated() || $force) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $to[] = $email;
                    }
                }
            }

            if (isset($extra['to'])) {
                foreach ($extra['to'] as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $to[] = $email;
                    }
                }
            }

            $message = new Message();
            $message->subject($subject);
            $message->from($fromEmail);
            $message->body($body);

            (null !== $from && filter_var($from->getEmail(), FILTER_VALIDATE_EMAIL)) ?
                $message->replyTo($from->getEmail()) :
                $message->replyTo($replyToMail);

            if (count($to) > 1) {
                $message->bcc($to);
            } else {
                $message->to($to);
            }

            if (isset($extra['attachments'])) {
                foreach ($extra['attachments'] as $attachment) {
                    $message->attach($attachment['name'], $attachment['url'], $attachment['type']);
                }
            }

            return $this->mailer->send($message);
        }

        return false;
    }

    public function getMailerFrom()
    {
        $from = $this->config->getParameter('mailer.from');
        if ($from) {
            if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
                return $from;
            }
        }

        if ($this->config->getParameter('internet.domain_name') && '' !== trim($this->config->getParameter('internet.domain_name'))) {
            return 'noreply@'.$this->config->getParameter('internet.domain_name');
        }

        return $this->config->getParameter('help.support_email');
    }
}
