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

    /**
     * Resolve placeholders by matching the exact FieldFacet label or name.
     * Provide a map of placeholder => [exact labels]. The comparison is strict
     * on the string value as stored in the FieldFacet label or name.
     *
     * Example input:
     *   [ 'civility' => ['civilité/grade', 'civilite/grade', 'civility/grade'] ]
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
     * Centralized civility resolution used by mailing and templates.
     * Order:
     * 1) Configured facet mapping
     * 2) Exact label/name match (eg. "civilité/grade")
     * 3) Heuristic substring match (backward compatibility)
     */
    public function resolveCivility(User $user): ?string
    {
        // 1) Config mapping
        $mapped = $this->resolve($user, ['civility']);
        if (!empty($mapped['civility'])) {
            return $mapped['civility'];
        }

        // 2) Exact match on label or name
        $exact = $this->resolveByExactFieldLabel($user, [
            'civility' => ['civilité/grade', 'civilite/grade', 'civility/grade'],
        ]);
        if (!empty($exact['civility'])) {
            return $exact['civility'];
        }

        // 3) Heuristic: look for fields containing civility patterns
        $valueRepo = $this->om->getRepository(FieldFacetValue::class);
        /** @var FieldFacetValue[] $values */
        $values = $valueRepo->findPlatformValuesByUser($user);

        foreach ($values as $value) {
            $field = $value->getFieldFacet();
            if (!$field) {
                continue;
            }

            $label = mb_strtolower($field->getLabel() ?? '');
            $name  = mb_strtolower($field->getName() ?? '');

            if (false !== strpos($label, 'civility') || false !== strpos($label, 'civilité')
                || false !== strpos($name, 'civility') || false !== strpos($name, 'civilite')) {
                return $this->formatFacetValue($user, $value, 'civility');
            }
        }

        return null;
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
