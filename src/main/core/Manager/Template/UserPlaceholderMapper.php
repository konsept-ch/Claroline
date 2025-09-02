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
     * Resolve user placeholders based on facet UUID mapping configuration.
     * Mapping parameter key: 'template.placeholder_facet_map'
     * Example: { civility: '<facet-uuid>', function: '<facet-uuid>' }
     *
     * Kept for backward compatibility (ID-based). Prefer label dictionary.
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

    /**
     * Resolve placeholders by matching the exact FieldFacet label or name.
     * Provide a map of placeholder => [exact labels]. The comparison is strict
     * on the string value as stored in the FieldFacet label or name.
     *
     * Example input:
     *   [ 'civility' => ['Civilité/Grade'] ]
     */
    public function resolveByExactFieldLabel(User $user, array $labelMap): array
    {
        $resolved = [];

        $valueRepo = $this->om->getRepository(FieldFacetValue::class);
        /** @var FieldFacetValue[] $values */
        $values = $valueRepo->findPlatformValuesByUser($user);

        // Pre-index by exact label and name for O(1) exact lookups
        $byLabel = [];
        $byName = [];
        foreach ($values as $v) {
            $field = $v->getFieldFacet();
            if (!$field) {
                continue;
            }
            if (null !== $field->getLabel()) {
                $byLabel[(string) $field->getLabel()] = $v;
            }
            if (null !== $field->getName()) {
                $byName[(string) $field->getName()] = $v;
            }
        }

        foreach ($labelMap as $placeholder => $labels) {
            foreach ((array) $labels as $label) {
                if (isset($byLabel[$label])) {
                    $resolved[$placeholder] = $this->formatFacetValue($user, $byLabel[$label], $placeholder);
                    break;
                }
                if (isset($byName[$label])) {
                    $resolved[$placeholder] = $this->formatFacetValue($user, $byName[$label], $placeholder);
                    break;
                }
            }
        }

        return $resolved;
    }

    /**
     * Resolve placeholders using a label dictionary.
     * The dictionary is a map of placeholder => [exact FieldFacet labels].
     * It strictly matches the stored labels (including accents and case).
     *
     * Default labels are provided for common placeholders but can be
     * overridden or extended via config parameter 'template.placeholder_label_map'.
     */
    public function resolveByLabelDictionary(User $user, array $placeholders): array
    {
        // Default dictionary (exact labels as stored in DB)
        $defaultDict = [
            'civility'        => ['Civilité/Grade'],
            'civility_other'  => ['Autre Civilité/Grade'],
            'function'        => ['Fonction'],
            'partner'         => ['Partenaire'],
        ];

        $configDict = (array) $this->config->getParameter('template.placeholder_label_map');

        // Merge defaults with config (config can override or add labels)
        $dict = $defaultDict;
        foreach ($configDict as $ph => $labels) {
            $dict[$ph] = (array) $labels;
        }

        // Build the map limited to requested placeholders
        $labelMap = [];
        foreach ($placeholders as $ph) {
            if (isset($dict[$ph])) {
                $labelMap[$ph] = $dict[$ph];
            }
        }

        if (empty($labelMap)) {
            return [];
        }

        return $this->resolveByExactFieldLabel($user, $labelMap);
    }

    private function formatFacetValue(User $user, FieldFacetValue $value, string $placeholder)
    {
        // Normalize through FacetManager (applies eventual subscribers, eg. organizations)
        $raw = $this->facetManager->serializeFieldValue($user, $value->getType(), $value->getValue());

        // Special case: organization facet returns an object {id, name, ...}
        if ($value->getType() === 'organization' && is_array($raw)) {
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
