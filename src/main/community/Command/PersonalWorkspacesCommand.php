<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CommunityBundle\Command;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Manager\Workspace\PersonalWorkspaceProvisioner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cree l'espace personnel des comptes actifs qui n'en ont pas.
 *
 * Sans --apply, la commande ne fait que compter ce qu'elle ferait : rien n'est ecrit.
 * Les comptes dont le code est pris par un espace qu'on ne doit pas toucher sont listes
 * par id : ils relevent de docs/80-support/claroline_personal_workspace_repair_runbook.md.
 */
class PersonalWorkspacesCommand extends Command
{
    /** @var ObjectManager */
    private $om;
    /** @var PersonalWorkspaceProvisioner */
    private $provisioner;

    public function __construct(ObjectManager $om, PersonalWorkspaceProvisioner $provisioner)
    {
        $this->om = $om;
        $this->provisioner = $provisioner;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Creates the missing personal workspace of active users (dry run unless --apply).');
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Really create the workspaces. Without it, nothing is written.');
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Process at most this number of users.');
        $this->addOption('user', null, InputOption::VALUE_REQUIRED, 'Process only this user id.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = (bool) $input->getOption('apply');
        $limit = $input->getOption('limit') ? (int) $input->getOption('limit') : null;

        $query = $this->om->createQueryBuilder()
            ->select('u.id')
            ->from(User::class, 'u')
            ->where('u.personalWorkspace IS NULL')
            ->andWhere('u.isRemoved = false')
            ->andWhere('u.isEnabled = true')
            ->orderBy('u.id', 'ASC');
        if ($input->getOption('user')) {
            $query->andWhere('u.id = :id')->setParameter('id', (int) $input->getOption('user'));
        }
        if ($limit) {
            $query->setMaxResults($limit);
        }
        $ids = array_column($query->getQuery()->getScalarResult(), 'id');

        $output->writeln(sprintf(
            '%d active user(s) without personal workspace. Mode: %s.',
            count($ids),
            $apply ? 'APPLY' : 'dry run, nothing is written'
        ));

        $counts = [];
        $codeTaken = [];
        $failed = [];
        foreach ($ids as $index => $id) {
            /** @var User $user */
            $user = $this->om->getRepository(User::class)->find($id);

            try {
                $result = $apply ? $this->provisioner->ensure($user) : $this->provisioner->diagnose($user);
            } catch (\Throwable $e) {
                $failed[] = sprintf('%d (%s)', $id, $e->getMessage());

                // une erreur Doctrine peut fermer le gestionnaire d'entites : on s'arrete plutot
                // que d'echouer en chaine. Relancer la commande reprend ou elle s'est arretee.
                $output->writeln('<error>Stopping on first error.</error>');
                break;
            }

            $counts[$result] = ($counts[$result] ?? 0) + 1;
            if (PersonalWorkspaceProvisioner::CODE_TAKEN === $result) {
                $codeTaken[] = $id;
            }

            // chaque creation charge un espace complet : liberer la memoire regulierement
            if (0 === ($index + 1) % 20) {
                $this->om->clear();
                $output->writeln(sprintf('%d / %d', $index + 1, count($ids)));
            }
        }

        foreach ($counts as $result => $count) {
            $output->writeln(sprintf('%s: %d', $result, $count));
        }
        if (!empty($codeTaken)) {
            $output->writeln(sprintf('code_taken, manual repair needed, user ids: %s', implode(', ', $codeTaken)));
        }
        if (!empty($failed)) {
            $output->writeln(sprintf('<error>failed: %s</error>', implode('; ', $failed)));

            return 1;
        }

        return 0;
    }
}
