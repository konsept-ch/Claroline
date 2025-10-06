<?php

namespace Claroline\AppBundle\Service;

class YearScope
{
    private bool $archiveMode;

    public function __construct(bool $archiveMode)
    {
        $this->archiveMode = $archiveMode;
    }

    public function isArchiveMode(): bool
    {
        return $this->archiveMode;
    }

    public function currentYear(): int
    {
        return (int) (new \DateTimeImmutable())->format('Y');
    }

    public function normalYearsWindow(): array
    {
        $y = $this->currentYear();
        return [$y, $y - 1];
    }

    public function archiveMaxYear(): int
    {
        // Everything older than previous year (<= currentYear - 2)
        return $this->currentYear() - 2;
    }
}

