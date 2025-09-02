<?php

namespace Claroline\CoreBundle\Manager\Template;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Facet\FieldFacetChoice;
use Claroline\CoreBundle\Entity\Facet\FieldFacetValue;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Manager\FacetManager;

class UserPlaceholderMapper
{
    /** @var ObjectManager */
    private $om;
    /** @var PlatformConfigurationHandler */
    private $config;
    /** @var FacetManager */
    private $facetManager;

    public function __construct(
        ObjectManager $om,
        PlatformConfigurationHandler $config,
        FacetManager $facetManager
    ) {
        $this->om = $om;
        $this->config = $config;
        $this->facetManager = $facetManager;
    }

    /**
     * Resolve user placeholders based on facet mapping configuration.
     * Mapping parameter key: 'template.placeholder_facet_map'
     * Example: { civility: '<facet-uuid>', function: '<facet-uuid>' }
     */
    public function resolve(User $user, array $placeholders): array
    {
        $resolved = [];

        $mapping = (array) $this->config->getParameter('template.placeholder_facet_map');
        if (empty($mapping)) {
            return $resolved;
        }

        // Load all user's facet values once
        $valueRepo = $this->om->getRepository(FieldFacetValue::class);
        /** @var FieldFacetValue[] $values */
        $values = $valueRepo->findPlatformValuesByUser($user);
        $byFacet = [];
        foreach ($values as $v) {
            $field = $v->getFieldFacet();
            if ($field) {
                $byFacet[$field->getUuid()] = $v;
            }
        }

        foreach ($placeholders as $ph) {
            $facetUuid = isset($mapping[$ph]) ? $mapping[$ph] : null;
            if (!$facetUuid || !isset($byFacet[$facetUuid])) {
                continue;
            }

            $value = $byFacet[$facetUuid];
            $resolved[$ph] = $this->formatFacetValue($user, $value, $ph);
        }

        return $resolved;
    }

    private function formatFacetValue(User $user, FieldFacetValue $value, string $placeholder)
    {
        // Normalize through FacetManager (applies eventual subscribers, eg. organizations)
        $raw = $this->facetManager->serializeFieldValue($user, $value->getType(), $value->getValue());

        // Special case: organization facet returns an object {id, name, ...}
        if ($value->getType() === 'organization' && is_array($raw)) {
            // If placeholder asks for all levels (eg. partner), return parent chain
            if ($placeholder === 'partner') {
                // Load full organization to traverse parents
                $orgRepo = $this->om->getRepository(\Claroline\CoreBundle\Entity\Organization\Organization::class);
                $org = $orgRepo->findOneBy(['uuid' => $raw['id'] ?? null]);
                $segments = [];
                while ($org) {
                    $segments[] = $org->getName();
                    $org = $org->getParent();
                }
                return implode(' > ', array_filter(array_reverse($segments)));
            }

            if (isset($raw['name'])) {
                return $raw['name'];
            }
        }

        // If a choice UUID (or list), resolve labels to user-facing text
        $choiceRepo = $this->om->getRepository(FieldFacetChoice::class);
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
            // For cascade fields, preserve hierarchy with a separator
            $sep = ($value->getType() === 'cascade') ? ' > ' : ', ';
            return implode($sep, $labels);
        }

        return $resolveChoice($raw);
    }
}
