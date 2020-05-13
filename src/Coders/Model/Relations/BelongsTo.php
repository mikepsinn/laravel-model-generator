<?php

/**
 * Created by Cristian.
 * Date: 05/09/16 11:41 PM.
 */

namespace Reliese\Coders\Model\Relations;

use Illuminate\Support\Str;
use Reliese\Support\Dumper;
use Illuminate\Support\Fluent;
use Reliese\Coders\Model\Model;
use Reliese\Coders\Model\Relation;

class BelongsTo implements Relation
{
    /**
     * @var \Illuminate\Support\Fluent
     */
    protected $command;

    /**
     * @var \Reliese\Coders\Model\Model
     */
    protected $parent;

    /**
     * @var \Reliese\Coders\Model\Model
     */
    protected $related;

    /**
     * @var \Reliese\Coders\Model\Model
     */
    protected $relationNameStrategy;

    /**
     * BelongsToWriter constructor.
     *
     * @param \Illuminate\Support\Fluent $command
     * @param \Reliese\Coders\Model\Model $parent
     * @param \Reliese\Coders\Model\Model $related
     */
    public function __construct(Fluent $command, Model $parent, Model $related, string $relationNameStrategy)
    {
        $this->command = $command;
        $this->parent = $parent;
        $this->related = $related;
        $this->relationNameStrategy = $relationNameStrategy;
    }

    /**
     * @return string
     */
    public function getRelationNameStrategy()
    {
        return $this->relationNameStrategy;
    }

    /**
     * @return string
     */
    public function name()
    {
        $strategy = $this->getRelationNameStrategy();

        switch ($strategy) {
            case 'foreign_key':
                $relationName = $this->getForeignKeyBasedRelationName();
                break;
            default:
            case 'related':
                $relationName = $this->related->getClassName();
                $columns = $this->command->get('columns');
                if(count($columns) === 1){
                    $col = $columns[0];
                    if(stripos($col, '_id') !== false){
                        $col = str_replace('_id', '', $col);
                        if(strlen($col) > strlen($relationName)){
                            $relationName = $col;
                        }
                    }
                }
                break;
        }

        if(empty($relationName)){
            throw new \LogicException("Could not determine relation name!");
        }

        if ($this->parent->usesSnakeAttributes()) {
            return Str::snake($relationName);
        }

        return Str::camel($relationName);
    }

    /**
     * @return string
     */
    public function body()
    {
        $related = $this->related;

        $parent = $this->parent;

        $body = 'return $this->belongsTo(';

        $body .= $related->getQualifiedUserClassName().'::class';

        $constantNamePrefix = $parent->constantNamePrefix();

        $foreignKeyColumnName = $this->foreignKey();
        $foreignKey = $parent->usesPropertyConstants()
            ? $parent->getQualifiedUserClassName().'::'.$constantNamePrefix.strtoupper($foreignKeyColumnName)
            : $foreignKeyColumnName;
        $body .= ', '.Dumper::export($foreignKey);

        $otherKeyColumnName = $this->otherKey();
        $otherKey = $related->usesPropertyConstants()
            ? $related->getQualifiedUserClassName().'::'.$constantNamePrefix.strtoupper($otherKeyColumnName)
            : $otherKeyColumnName;
        $body .= ', '.Dumper::export($otherKey);

        $ownerKeyColumnName = $this->ownerKey();
        $ownerKey = $parent->usesPropertyConstants()
            ? $parent->getQualifiedUserClassName().'::'.$constantNamePrefix.strtoupper($ownerKeyColumnName)
            : $foreignKeyColumnName;
        $body .= ', '.Dumper::export($ownerKey);

        $body .= ')';

        if ($this->hasCompositeOtherKey()) {
            // We will assume that when this happens the referenced columns are a composite primary key
            // or a composite unique key. Otherwise it should be a has-many relationship which is not
            // supported at the moment. @todo: Improve relationship resolution.
            foreach ($this->command->references as $index => $column) {
                $body .= "\n\t\t\t\t\t->where(".
                    Dumper::export($this->qualifiedOtherKey($index)).
                    ", '=', ".
                    Dumper::export($this->qualifiedForeignKey($index)).
                    ')';
            }
        }

        $body .= ';';

        return $body;
    }

    /**
     * @return string
     */
    public function hint()
    {
        return $this->related->getQualifiedUserClassName();
    }

    /**
     * @return bool
     */
    protected function needsForeignKey()
    {
        $defaultForeignKey = $this->related->getRecordName().'_id';

        $foreignKey = $this->foreignKey();

        $needsOtherKey = $this->needsOtherKey();

        return $defaultForeignKey != $foreignKey || $needsOtherKey;
    }

    /**
     * @param int $index
     *
     * @return string
     */
    protected function foreignKey($index = 0)
    {
        return $this->command->columns[$index];
    }

    /**
     * @param int $index
     *
     * @return string
     */
    protected function qualifiedForeignKey($index = 0)
    {
        return $this->parent->getTable().'.'.$this->foreignKey($index);
    }

    /**
     * @return bool
     */
    protected function needsOtherKey()
    {
        $defaultOtherKey = $this->related->getPrimaryKey();
        $otherKey = $this->otherKey();
        return $defaultOtherKey != $otherKey;
    }

    /**
     * @param int $index
     *
     * @return string
     */
    protected function otherKey($index = 0)
    {
        $references = $this->command->references;
        return $references[$index];
    }

    /**
     * @param int $index
     *
     * @return string
     */
    protected function ownerKey($index = 0)
    {
        return $this->foreignKey($index);
    }

    /**
     * @param int $index
     *
     * @return string
     */
    protected function qualifiedOtherKey($index = 0)
    {
        return $this->related->getTable().'.'.$this->otherKey($index);
    }

    /**
     * Whether the "other key" is a composite foreign key.
     *
     * @return bool
     */
    protected function hasCompositeOtherKey()
    {
        return count($this->command->references) > 1;
    }
    /**
     * @return string|string[]|null
     */
    public function getForeignKeyBasedRelationName(){
        $relationName = preg_replace("/[^a-zA-Z0-9]?{$this->otherKey()}$/", '', $this->foreignKey());
        if(empty($relationName)){
            $relationName = $this->related->getClassName();
        }
        $relationName = rtrim($relationName, '_');
        $relationName = str_replace('_id', '', $relationName);
        return $relationName;
    }
}
