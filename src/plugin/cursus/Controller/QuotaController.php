<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Controller;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\FinderProvider;
use Claroline\AppBundle\Controller\AbstractCrudController;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Manager\LocaleManager;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceManager;
use Claroline\CoreBundle\Security\PermissionCheckerTrait;
use Claroline\CursusBundle\Entity\Quota;
use Claroline\CursusBundle\Entity\Registration\AbstractRegistration;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Claroline\CursusBundle\Event\Log\LogSubscriptionSetStatusEvent;
use Claroline\CursusBundle\Manager\QuotaManager;
use Claroline\CursusBundle\Manager\SessionManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/cursus_quota")
 */
class QuotaController extends AbstractCrudController
{
    use PermissionCheckerTrait;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var TranslatorInterface */
    private $translator;
    /** @var LocaleManager */
    private $localeManager;
    /** @var PlatformConfigurationHandler */
    private $config;
    /** @var QuotaManager */
    private $quotaManager;
    /** @var SessionManager */
    private $sessionManager;
    /** @var WorkspaceManager */
    private $workspaceManager;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        AuthorizationCheckerInterface $authorization,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        LocaleManager $localeManager,
        PlatformConfigurationHandler $config,
        ObjectManager $om,
        QuotaManager $quotaManager,
        SessionManager $sessionManager,
        WorkspaceManager $workspaceManager
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->authorization = $authorization;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->localeManager = $localeManager;
        $this->config = $config;
        $this->om = $om;
        $this->quotaManager = $quotaManager;
        $this->sessionManager = $sessionManager;
        $this->workspaceManager = $workspaceManager;
    }

    public function getName(): string
    {
        return 'cursus_quota';
    }

    public function getClass(): ?string
    {
        return Quota::class;
    }

    public function getIgnore(): array
    {
        return ['copyBulk', 'doc', 'exist'];
    }

    protected function getDefaultHiddenFilters(): array
    {
        $filters = [];
        if (!$this->authorization->isGranted('ROLE_ADMIN')) {
            /** @var User */
            $user = $this->tokenStorage->getToken()->getUser();

            // filter by organization
            if ($user instanceof User) {
                $userOrganizations = $user->getOrganizations();
            } else {
                $userOrganizations = $this->om->getRepository(Organization::class)->findBy(['default' => true]);
            }

            $organizations = [];
            $this->getOrganizationIds($userOrganizations, $organizations);
            $filters['organizations'] = $organizations;
        }

        return $filters;
    }

    /**
     * @Route("/organizations", name="apiv2_cursus_quota_organizations", methods={"GET"})
     * @EXT\ParamConverter("user", converter="current_user", options={"allowAnonymous"=false})
     */
    public function getOrganizationsAction(User $user, Request $request): JsonResponse
    {
        $params = $request->query->all();

        if (!$this->authorization->isGranted('ROLE_ADMIN')) {
            if (!isset($params['hiddenFilters'])) {
                $params['hiddenFilters'] = [];
            }
            $organizations = [];
            $this->getOrganizationIds($user->getOrganizations(), $organizations);
            $params['hiddenFilters']['uuid'] = $organizations;
        }

        return new JsonResponse(
            $this->finder->search(Organization::class, $params)
        );
    }

    /**
     * @Route("/{id}/{year}/statistics", name="apiv2_cursus_quota_statistics", methods={"GET"})
     * @EXT\ParamConverter("quota", class="Claroline\CursusBundle\Entity\Quota", options={"mapping": {"id": "uuid"}})
     */
    public function getStatisticsAction(Quota $quota, string $year): JsonResponse
    {
        $this->checkPermission('VALIDATE_SUBSCRIPTIONS', $quota, [], true);

        /** @var SessionUserRepository */
        $repo = $this->om->getRepository(SessionUser::class);
        $sessionUsers = $repo->findByOrganization($quota->getOrganization(), $year);
        $statistics = [
            'total' => count($sessionUsers),
            'pending' => array_reduce($sessionUsers, function ($accum, $subscription) {
                return $accum + (SessionUser::STATUS_PENDING == $subscription->getStatus() ? 1 : 0);
            }, 0),
            'refused' => array_reduce($sessionUsers, function ($accum, $subscription) {
                return $accum + (SessionUser::STATUS_REFUSED == $subscription->getStatus() ? 1 : 0);
            }, 0),
            'validated' => array_reduce($sessionUsers, function ($accum, $subscription) {
                return $accum + (SessionUser::STATUS_VALIDATED == $subscription->getStatus() ? 1 : 0);
            }, 0),
        ];
        if ($quota->getQuotaByYear($year)->enabled) {
            $statistics['managed'] = array_reduce($sessionUsers, function ($accum, $subscription) {
                return $accum + (SessionUser::STATUS_MANAGED == $subscription->getStatus() ? 1 : 0);
            }, 0);
            $statistics['calculated'] = array_reduce($sessionUsers, function ($accum, $subscription) {
                return SessionUser::STATUS_MANAGED == $subscription->getStatus() ? $accum + $subscription->getSession()->getQuotaDays() : $accum;
            }, 0);
        } else {
            $statistics['total'] = array_reduce($sessionUsers, function ($accum, $subscription) {
                return $accum + (SessionUser::STATUS_MANAGED != $subscription->getStatus() ? 1 : 0);
            }, 0);
        }

        return new JsonResponse($statistics);
    }

    /**
     * @Route("/{id}/{year}/csv", name="apiv2_cursus_quota_export", methods={"GET"})
     * @EXT\ParamConverter("quota", class="Claroline\CursusBundle\Entity\Quota", options={"mapping": {"id": "uuid"}})
     */
    public function exportAction(Quota $quota, string $year, Request $request): BinaryFileResponse
    {
        $this->checkPermission('VALIDATE_SUBSCRIPTIONS', $quota, [], true);

        $STATUS_STRINGS = [
            $this->translator->trans('subscription_pending', [], 'cursus'),
            $this->translator->trans('subscription_refused', [], 'cursus'),
            $this->translator->trans('subscription_validated', [], 'cursus'),
            $this->translator->trans('subscription_managed', [], 'cursus'),
        ];

        $filters = $request->query->get('filters', []);
        $filters['organization'] = $quota->getOrganization();
        $filters['type'] = AbstractRegistration::LEARNER;
        $filters['year'] = $year;

        if (!$quota->getQuotaByYear($year)->enabled) {
            $filters['ignored_status'] = SessionUser::STATUS_MANAGED;
        }

        $locale = $this->localeManager->getLocale($this->tokenStorage->getToken()->getUser());

        $subscriptions = $this->finder->fetch(SessionUser::class, $filters);
        $this->sortSubscriptions($subscriptions);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', ucfirst($this->translator->trans('user', [], 'platform', $locale)));
        $sheet->setCellValue('B1', ucfirst($this->translator->trans('session', [], 'cursus', $locale)));
        $sheet->setCellValue('C1', ucfirst($this->translator->trans('days', [], 'platform', $locale)));
        $sheet->setCellValue('D1', ucfirst($this->translator->trans('price', [], 'platform', $locale)));
        $sheet->setCellValue('E1', ucfirst($this->translator->trans('start_date', [], 'platform', $locale)));
        $sheet->setCellValue('F1', ucfirst($this->translator->trans('status', [], 'platform', $locale)));

        $row = 1;
        foreach ($subscriptions as $subscription) {
            $sheet->setCellValueByColumnAndRow(1, ++$row, sprintf('%s %s', $subscription->getUser()->getFirstName(), $subscription->getUser()->getLastName()));
            $sheet->setCellValueByColumnAndRow(2, $row, $subscription->getSession()->getName());
            $sheet->setCellValueByColumnAndRow(3, $row, $subscription->getSession()->getQuotaDays());
            $sheet->setCellValueByColumnAndRow(4, $row, $subscription->getSession()->getPrice());
            $sheet->setCellValueByColumnAndRow(5, $row, $subscription->getSession()->getStartDate()->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow(6, $row, $STATUS_STRINGS[$subscription->getStatus()]);
            $sheet->setCellValueByColumnAndRow(7, $row, $subscription->getRemark());
        }

        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'XLSXCLARO').'.xlsx';
        $writer->save($tmpFile);

        return new BinaryFileResponse($tmpFile, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename={$this->getName()}.xlsx",
        ]);
    }

    /**
     * @Route("/year/{year}", name="apiv2_cursus_quota_list_by_year", methods={"GET"})
     */
    public function listByYearAction(string $year, Request $request)
    {
        $query = $request->query->all();

        $options = ['year' => $year];
        $query['hiddenFilters'] = $this->getDefaultHiddenFilters();

        return new JsonResponse(
            $this->crud->list(Quota::class, $query, $options)
        );
    }

    /**
     * @Route("/{id}/{year}/subscriptions", name="apiv2_cursus_quota_list_subscriptions", methods={"GET"})
     * @EXT\ParamConverter("quota", class="Claroline\CursusBundle\Entity\Quota", options={"mapping": {"id": "uuid"}})
     */
    public function listSubscriptionsAction(Quota $quota, string $year, Request $request): JsonResponse
    {
        $this->checkPermission('VALIDATE_SUBSCRIPTIONS', $quota, [], true);

        $organization = $quota->getOrganization();

        $query = $request->query->all();
        $query['hiddenFilters'] = [
            'organization' => $organization,
            'type' => AbstractRegistration::LEARNER,
            'year' => $year,
        ];

        if (!$quota->getQuotaByYear($year)->enabled) {
            $query['hiddenFilters']['ignored_status'] = SessionUser::STATUS_MANAGED;
        }

        $options = isset($query['options']) ? $query['options'] : [];

        $queryParams = $this->finder->parseQueryParams($query);
        $page = $queryParams['page'];
        $limit = $queryParams['limit'];
        $filters = $queryParams['filters'];
        $allFilters = $queryParams['allFilters'];
        $sortBy = $queryParams['sortBy'];

        $count = $this->finder->fetch(SessionUser::class, $allFilters, $sortBy, $page, $limit, true);
        $data = $this->finder->fetch(SessionUser::class, $allFilters, $sortBy, $page, $limit);

        if (0 < $count && empty($data)) {
            $page = 0 !== $limit ? ceil($count / $limit) - 1 : 1;
            $data = $this->finder->fetch(SessionUser::class, $allFilters, $sortBy, $page, $limit);
        }

        $this->sortSubscriptions($data);

        $results = FinderProvider::formatPaginatedData($data, $count, $page, $limit, $filters, $sortBy);

        return new JsonResponse(array_merge($results, [
            'data' => array_map(function ($result) use ($options) {
                return $this->serializer->serialize($result, $options);
            }, $results['data']),
        ]));
    }

    /**
     * @Route("/{id}/subscriptions/{sid}/{year}", name="apiv2_cursus_subscription_status", methods={"PATCH"})
     * @EXT\ParamConverter("quota", class="Claroline\CursusBundle\Entity\Quota", options={"mapping": {"id": "uuid"}})
     * @EXT\ParamConverter("sessionUser", class="Claroline\CursusBundle\Entity\Registration\SessionUser", options={"mapping": {"sid": "uuid"}})
     */
    public function setSubscriptionStatusAction(Quota $quota, SessionUser $sessionUser, string $year, Request $request): JsonResponse
    {
        $this->checkPermission('VALIDATE_SUBSCRIPTIONS', $quota, [], true);

        $remark = $request->query->get('remark', '');

        $status = $request->query->get('status', null);
        if (null == $status) {
            return new JsonResponse('The status is missing.', 500);
        }

        if ($status < SessionUser::STATUS_PENDING || $status > SessionUser::STATUS_MANAGED) {
            return new JsonResponse('The status don\'t have been updated.', 500);
        }

        $sessionUser->setRemark($remark);

        $oldStatus = $sessionUser->getStatus();
        if ($oldStatus != $status) {
            $this->eventDispatcher->dispatch(new LogSubscriptionSetStatusEvent($sessionUser), 'log');

            // Execute action, dispatch event, send mail, etc
            switch ($status) {
                case SessionUser::STATUS_VALIDATED:
                    $this->sessionManager->addUsers($sessionUser->getSession(), [$sessionUser->getUser()]);
                    $this->quotaManager->sendValidatedStatusMail($sessionUser);
                    break;
                case SessionUser::STATUS_MANAGED:
                    if (null == $quota || !$quota->getQuotaByYear($year)->enabled) {
                        return new JsonResponse('The status don\'t can be changed to managed.', 500);
                    }
                    $this->sessionManager->addUsers($sessionUser->getSession(), [$sessionUser->getUser()]);
                    $this->quotaManager->sendManagedStatusMail($sessionUser);
                    break;
                case SessionUser::STATUS_PENDING:
                    break;
                case SessionUser::STATUS_REFUSED:
                    if (SessionUser::STATUS_VALIDATED == $oldStatus || SessionUser::STATUS_MANAGED == $oldStatus) {
                        $this->quotaManager->sendCancelledStatusMail($sessionUser);
                    } else {
                        $this->quotaManager->sendRefusedStatusMail($sessionUser);
                    }
                    $sessionUser->setValidated(false);
                    $sessionUser->setConfirmed(false);

                    $session = $sessionUser->getSession();
                    if ($session->getWorkspace()) $this->workspaceManager->unregister($sessionUser->getUser(), $session->getWorkspace(), [Crud::NO_PERMISSIONS]);
                    $this->sessionManager->checkUsersRegistration($sessionUser->getSession(), [$sessionUser]);
                    break;
            }

            $sessionUser->setStatus($status);
            $this->om->persist($sessionUser);
            $this->om->flush();
        }

        return new JsonResponse([
            'subscription' => $this->serializer->serialize($sessionUser),
        ]);
    }

    /**
     * @Route("/{id}/open", name="apiv2_cursus_quota_open", methods={"GET"})
     * @EXT\ParamConverter("quota", class="Claroline\CursusBundle\Entity\Quota", options={"mapping": {"id": "uuid"}})
     */
    public function openAction(Quota $quota): JsonResponse
    {
        return new JsonResponse([
            'quota' => $this->serializer->serialize($quota),
        ]);
    }

    private function sortSubscriptions(array &$subscriptions)
    {
        //usort($subscriptions, fn(SessionUser $a, SessionUser $b) => $a->getSession()->getStartDate()->getTimestamp() - $b->getSession()->getStartDate()->getTimestamp());
    }

    private function getOrganizationIds(array $organizations, array &$output): void
    {
        foreach ($organizations as $organization) {
            $this->getOrganizationIds($organization->getChildren()->toArray(), $output);
            if (!in_array($organization, $output)) {
                $output[] = $organization->getUuid();
            }
        }
    }
}
