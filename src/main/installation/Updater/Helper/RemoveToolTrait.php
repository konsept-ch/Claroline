<?php

namespace Claroline\InstallationBundle\Updater\Helper;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Tool\Tool;
use Doctrine\DBAL\Connection;

trait RemoveToolTrait
{
    private function removeTool(string $toolName): void
    {
        if (!$this->om instanceof ObjectManager) {
            throw new \RuntimeException(sprintf('RemoveToolTrait requires the ObjectManager (@%s to be injected in your service.', ObjectManager::class));
        }

        if (!$this->connection instanceof Connection) {
            throw new \RuntimeException(sprintf('RemoveToolTrait requires the Connection (@%s to be injected in your service.', Connection::class));
        }

        // Avoid flushing unrelated transient entities collected earlier in the update lifecycle.
        $this->om->clear();

        $tool = $this->om->getRepository(Tool::class)->findOneBy([
            'name' => $toolName,
        ]);

        if ($tool) {
            $this->om->remove($tool);
            $this->om->flush();
        }

        $orderedTools = $this->connection->prepare(
            'DELETE FROM claro_ordered_tool WHERE tool_name = ?'
        );
        $orderedTools->bindValue(1, $toolName);
        $orderedTools->executeQuery();

        $maskDecoders = $this->connection->prepare(
            'DELETE FROM claro_tool_mask_decoder WHERE tool_name = ?'
        );
        $maskDecoders->bindValue(1, $toolName);
        $maskDecoders->executeQuery();
    }
}
