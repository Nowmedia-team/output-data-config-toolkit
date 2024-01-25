<?php

namespace OutputDataConfigToolkitBundle\OutputDefinition\Query;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;

interface IQueryOutputDefinition
{
    public function loadConfiguration(ClassDefinition $classDefinition, Concrete $object, array $configuration): array;
}