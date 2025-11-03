<?php

namespace HeVinci\CompetencyBundle\Entity;

use Claroline\AppBundle\Entity\Identifier\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * Links a learning objective to a competency for a given framework level.
 *
 * @ORM\Entity(repositoryClass="HeVinci\CompetencyBundle\Repository\ObjectiveCompetencyRepository")
 * @ORM\Table(name="hevinci_objective_competency")
 */
class ObjectiveCompetency
{
    use Uuid;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Objective",
     *     inversedBy="objectiveCompetencies"
     * )
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $objective;

    /**
     * @ORM\ManyToOne(targetEntity="Competency")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $competency;

    /**
     * @ORM\ManyToOne(targetEntity="Level")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $level;

    /**
     * @ORM\ManyToOne(targetEntity="Competency")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $framework;

    public function __construct()
    {
        $this->refreshUuid();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getObjective()
    {
        return $this->objective;
    }

    public function setObjective(Objective $objective)
    {
        $this->objective = $objective;
    }

    public function getCompetency()
    {
        return $this->competency;
    }

    public function setCompetency(Competency $competency)
    {
        $this->competency = $competency;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel(Level $level)
    {
        $this->level = $level;
    }

    public function getFramework()
    {
        return $this->framework;
    }

    public function setFramework(Competency $framework)
    {
        $this->framework = $framework;
    }
}

