<?php

namespace Icap\WikiBundle\Manager;

use Claroline\AppBundle\Persistence\ObjectManager;
use Icap\HtmlDiff\HtmlDiff;
use Icap\WikiBundle\Entity\Contribution;
use Icap\WikiBundle\Entity\Section;
use Icap\WikiBundle\Repository\ContributionRepository;
use Icap\WikiBundle\Serializer\ContributionSerializer;

class ContributionManager
{
    /** @var ObjectManager */
    private $om;

    /** @var ContributionRepository */
    private $contributionRepository;

    /** @var ContributionSerializer */
    private $contributionSerializer;

    public function __construct(
        ObjectManager $om,
        ContributionSerializer $contributionSerializer
    ) {
        $this->om = $om;
        $this->contributionRepository = $om->getRepository(Contribution::class);
        $this->contributionSerializer = $contributionSerializer;
    }

    public function serializeContribution(Contribution $contribution)
    {
        return $this->contributionSerializer->serialize($contribution);
    }

    public function serializeContributions($contributions)
    {
        $serialized = [];
        foreach ($contributions as $contribution) {
            $serialized[] = $this->serializeContribution($contribution);
        }

        return $serialized;
    }

    /**
     * @param array $uuids
     *
     * @return array $contributions
     */
    public function compareContributions(Section $section, $uuids)
    {
        $contributions = $this->contributionRepository->findyBySectionAndUuids($section, $uuids);
        $titleDiff = new HtmlDiff($contributions[0]->getTitle(), $contributions[1]->getTitle(), false);
        $textDiff = new HtmlDiff($contributions[0]->getText(), $contributions[1]->getText(), true);
        $contribution = new Contribution();
        $contribution->setText($textDiff->outputDiff()->toString());
        $contribution->setTitle($titleDiff->outputDiff()->toString());
        $contribution->setContributor($contributions[1]->getContributor());
        $contribution->setCreationDate($contributions[1]->getCreationDate());
        $contribution->refreshUuid();
        $contributions[1] = $contribution;

        return $contributions;
    }
}
