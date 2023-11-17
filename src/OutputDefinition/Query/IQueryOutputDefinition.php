<?php

namespace OutputDataConfigToolkitBundle\OutputDefinition\Query;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\Element\ElementInterface;

interface IQueryOutputDefinition
{
    public function loadConfiguration(ClassDefinition $classDefinition, ElementInterface $object, array $configuration): array;
}