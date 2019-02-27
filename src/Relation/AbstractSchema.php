<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Relation;

use Cycle\ORM\Relation;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\BuilderException;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\Util\OptionSchema;
use Cycle\Schema\RelationInterface;

abstract class AbstractSchema implements RelationInterface
{
    // exported relation type
    protected const RELATION_TYPE = null;

    // name of all required relation options
    protected const OPTION_SCHEMA = [];

    // options to be excluded from generated schema
    protected const EXCLUDE = [];

    /** @var string */
    protected $source;

    /** @var string */
    protected $target;

    /** @var OptionSchema */
    protected $options;

    /**
     * @inheritdoc
     */
    public function withContext(string $name, string $source, string $target, OptionSchema $options): RelationInterface
    {
        $relation = clone $this;
        $relation->source = $source;
        $relation->target = $target;

        $relation->options = $options->withTemplate(static::OPTION_SCHEMA)->withContext([
            'relation'    => $name,
            'source:role' => $source,
            'target:role' => $target
        ]);

        return $relation;
    }

    /**
     * @param Registry $registry
     */
    public function compute(Registry $registry)
    {
        $this->options = $this->options->withContext([
            'source:primaryKey' => $this->getPrimary($registry->getEntity($this->source))
        ]);

        if ($registry->hasRole($this->target)) {
            $this->options = $this->options->withContext([
                'target:primaryKey' => $this->getPrimary($registry->getEntity($this->target))
            ]);
        }
    }

    /**
     * @return array
     */
    public function packSchema(): array
    {
        $schema = [];

        foreach (static::OPTION_SCHEMA as $option => $template) {
            if (in_array($option, static::EXCLUDE)) {
                continue;
            }

            $schema[$option] = $this->options->get($option);
        }

        return [
            Relation::TYPE   => static::RELATION_TYPE,
            Relation::TARGET => $this->target,
            Relation::SCHEMA => $schema
        ];
    }

    /**
     * @return OptionSchema
     */
    protected function getOptions(): OptionSchema
    {
        return $this->options;
    }

    /**
     * @param Entity $entity
     * @return string
     *
     * @throws BuilderException
     */
    protected function getPrimary(Entity $entity): string
    {
        foreach ($entity->getFields() as $name => $field) {
            if ($field->isPrimary()) {
                return $name;
            }
        }

        throw new BuilderException("Entity `{$entity->getRole()}` must have defined primary key");
    }
}