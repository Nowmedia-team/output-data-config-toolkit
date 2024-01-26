<?php

namespace OutputDataConfigToolkitBundle\ConfigElement\Value;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ObjectMetadata;

//TODO: convert to Operators
class LinkValue extends DefaultValue
{
    /**
     * @var IConfigElement[]
     */
    protected $childs;

    protected object $params;

    /**
     * @return IConfigElement[]
     */
    public function getChilds()
    {
        return $this->childs;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);
        $this->childs = $config->childs ?? [];
        $this->params = $config->params ?? [];
    }

    public function getLabeledValue($object, $lang = 'default')
    {
        if ($child = $this->getFirstChild()) {
            $type = 'string';
            if ($this->params && $this->params->type) {
                $type = $this->params->type;
            }

            if (substr($child->attribute, 0, 4) == '#cs#') {
                foreach ($object->getClass()->getFieldDefinitions() as $fieldCode => $definition) {
                    if ($definition::class === Classificationstore::class) {
                        $child->classificationstore = $fieldCode;
                        $store = $object->get($child->classificationstore);
                        if (!empty($store->getActiveGroups())) {
                            $groupIds = array_keys($store->getActiveGroups());
                            $child->classificationstore_group_id = reset($groupIds);
                        }
                    }
                }
            }

            $typedMethod = 'getLabeledValue' . ucfirst($type);
            if (method_exists($this, $typedMethod)) {
                return $this->$typedMethod($child, $object, $lang);
            }

            return null;
        }

        return null;
    }

    protected function getFirstChild()
    {
        $childs = $this->getChilds();
        if ($childs) {
            return reset($childs);
        }

        return null;
    }

    protected function arrayToValue($values)
    {
        $newValue = [];
        foreach ($values as $valueKey => $value) {
            foreach ($value as $elementKey => $element) {
                $newValue[$valueKey][$elementKey] = $element->getData();
            }
        }

        return $newValue;
    }

    protected function getLabeledValueRelation($child, $object, $lang = 'default')
    {
        $value = $child->getLabeledValue($object, $lang);

        if ($value && $value->value) {
            if (is_array($value->value)) {
                $byKey = $this->getOption('byKey');
                $newValue = $this->params->multiple ? [] : null;
                foreach ($value->value as $relationValue) {
                    if ($relationValue instanceof ObjectMetadata) {
                        $relation = $relationValue->getObject();
                    } else if (is_int($relationValue)) {
                        $relation = AbstractObject::getById($relationValue);
                    } else {
                        $relation = $relationValue;
                    }
                    if ($relation && method_exists($relation, 'getXml_id')) { //TODO: в конфиг метод
                        if ($byKey) {
                            if ($relationValue->getData()[$byKey] ?? '') {
                                $newValue = $relation->getXml_id();
                                break;
                            }
                        } else {
                            if ($this->params->multiple) {
                                $newValue[] = $relation->getXml_id();
                            } else {
                                $newValue = $relation->getXml_id();
                                break;
                            }
                        }
                    }
                }
                $value->value = $newValue;
            } else {
                if ($value->value instanceof ObjectMetadata) {
                    $relation = $value->value->getObject();
                } else if (is_int($value->value)) {
                    $relation = AbstractObject::getById($value->value);
                } else {
                    $relation = $value->value;
                }

                if (method_exists($relation, 'getXml_id')) {//TODO: в конфиг метод
                    if ($this->params->multiple) {
                        $value->value[] = $relation->getXml_id();
                    } else {
                        $value->value = $relation->getXml_id();
                    }
                } else {
                    $value->value = null;
                }
            }

            return $value;
        }
        
        return null;
    }

    protected function getLabeledValueText($child, $object, $lang = 'default')
    {
        return $this->getLabeledValueString($child, $object, $lang);
    }

    protected function getLabeledValueString($child, $object, $lang = 'default')
    {
        $value = $child->getLabeledValue($object, $lang);
        if ($value->value) {
            if (is_array($value->value)) {
                $value->value = $this->arrayToValue($value->value);
            }

            return $value;
        }
        
        return null;
    }

    protected function getLabeledValueList($child, $object, $lang = 'default')
    {
        $value = $child->getLabeledValue($object, $lang);
        if ($value->value) {
            $translator = \Pimcore::getContainer()->get('translator');
            if (is_array($value->value)) {
                $oldValue = $value->value;
                $value->value = [];
                foreach ($value->def->getDataForGrid([])['options'] as $sortKey => $option) {
                    if (in_array($option['value'], $oldValue)) {
                        $value->value[] = [
                            'value' => $translator->trans($option['key'], [], 'selects', $lang),
                            'xml_id' => $option['value'],
                            'sort' => ($sortKey + 1) * 100
                        ];
                    }
                }
            } else {
                foreach ($value->def->getDataForGrid([])['options'] as $sortKey => $option) {
                    if ($option['value'] === $value->value) {
                        $value->value = [
                            'value' => $translator->trans($option['key'], [], 'selects', $lang),
                            'xml_id' => $option['value'],
                            'sort' => ($sortKey + 1) * 100
                        ];
                    }
                }
            }

            return $value;
        }

        return null;
    }

    protected function getLabeledValueFile($child, $object, $lang = 'default')
    {
        $value = $child->getLabeledValue($object, $lang);
        if ($value->value) {
            if (Hotspotimage::class === $value->value::class) {
                $path = $value->value->getImage()->getFrontendFullPath();
            } else {
                $path = $value->value->getFrontendFullPath();
            }
            $value->value = \Pimcore\Tool::getHostUrl('https') . $path;

            return $value;
        }
        
        return null;
    }

    protected function getLabeledValueBoolean($child, $object, $lang = 'default')
    {
        $value = $child->getLabeledValue($object, $lang);
        if ($value->value !== null) {
            $value->value = (bool) $value->value;

            return $value;
        }
        
        return null;
    }

    protected function getLabeledValueInteger($child, $object, $lang = 'default')
    {
        $value = $child->getLabeledValue($object, $lang);
        if ($value && $value->value) {
            $value->value = (int) $value->value;

            return $value;
        }
        
        return null;
    }
}
