<?php

namespace Claroline\CursusBundle\Entity\Registration;

use Claroline\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractUserRegistration extends AbstractRegistration
{
    const STATE_PENDING = 0;
    const STATE_VALIDATED = 1;
    const STATE_REFUSED = 2;
    const STATE_CANCELLED = 3;
    const STATE_PARTICIPATED = 4;

    /**
     * The registration request has been confirmed by the user.
     *
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $confirmed = false;

    /**
     * The registration request has been validated by a manager.
     * It is false when the registration requires manual validation or if their is no more seats to validate the registration.
     *
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $validated = false;

    /**
     * The registration has to be managed by another service.
     *
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $state = self::STATE_PENDING;

    /**
     * @ORM\ManyToOne(targetEntity="Claroline\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", nullable=false, onDelete="CASCADE")
     *
     * @var User
     */
    protected $user;

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed)
    {
        $this->confirmed = $confirmed;
    }

    public function isValidated(): bool
    {
        return $this->state == self::STATE_VALIDATED;
    }

    public function isCancelled(): bool
    {
        return $this->state == self::STATE_CANCELLED;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state)
    {
        $this->state = $state;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
