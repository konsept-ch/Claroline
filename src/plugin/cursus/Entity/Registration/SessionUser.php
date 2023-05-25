<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Entity\Registration;

use Claroline\CoreBundle\Entity\Facet\FieldFacetValue;
use Claroline\CursusBundle\Entity\Session;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Claroline\CursusBundle\Repository\SessionUserRepository")
 * @ORM\Table(
 *     name="claro_cursusbundle_course_session_user",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="training_session_unique_user", columns={"session_id", "user_id"})
 *     }
 * )
 */
class SessionUser extends AbstractUserRegistration
{
    const STATUS_PENDING = 0;
    const STATUS_REFUSED = 1;
    const STATUS_VALIDATED = 2;
    const STATUS_MANAGED = 3;

    /**
     * @ORM\ManyToOne(targetEntity="Claroline\CursusBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", nullable=false, onDelete="CASCADE")
     */
    private ?Session $session = null;

    /**
     * @ORM\ManyToMany(targetEntity="Claroline\CoreBundle\Entity\Facet\FieldFacetValue", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(name="claro_cursusbundle_session_user_values",
     *      joinColumns={@ORM\JoinColumn(name="registration_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="value_id", referencedColumnName="id", unique=true, onDelete="CASCADE")}
     * )
     */
    private Collection $facetValues;

    public function __construct()
    {
        parent::__construct();

        $this->facetValues = new ArrayCollection();
    }

    /**
     * The registration has to be managed by another service.
     *
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $status = self::STATUS_PENDING;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $remark = '';

    public function getSession(): Session
    {
        return $this->session;
    }

    public function setSession(Session $session): void
    {
        $this->session = $session;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    public function setRemark(string $remark): void
    {
        $this->remark = $remark;
    }

    public function getFacetValue(string $fieldId): ?FieldFacetValue
    {
        $found = null;
        foreach ($this->facetValues as $facetValue) {
            if ($facetValue->getFieldFacet()->getUuid() === $fieldId) {
                $found = $facetValue;
                break;
            }
        }

        return $found;
    }

    public function getFacetValues(): Collection
    {
        return $this->facetValues;
    }

    public function addFacetValue(FieldFacetValue $fieldFacetValue): void
    {
        if (!$this->facetValues->contains($fieldFacetValue)) {
            $this->facetValues->add($fieldFacetValue);
        }
    }

    public function removeFacetValue(FieldFacetValue $fieldFacetValue): void
    {
        if ($this->facetValues->contains($fieldFacetValue)) {
            $this->facetValues->removeElement($fieldFacetValue);
        }
    }
}
