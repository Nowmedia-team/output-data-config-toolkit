<?php

namespace OutputDataConfigToolkitBundle\ConfigElement\Value;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;

//TODO: convert to Operators
class LinkValue extends DefaultValue
{
    /**
     * @var IConfigElement[]
     */
    protected $childs;

    protected $params;

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

    public function getLabeledValue($object)
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
                return $this->$typedMethod($child, $object);
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

    protected function getLabeledValueRelation($child, $object)
    {
        $value = $child->getLabeledValue($object);

        if ($value && $value->value) {
            $relation = AbstractObject::getById($value->value);

            if (method_exists($relation, 'getXmlId')) {
                $value->value = $relation->getXmlId();
            } else {
                $value->value = '';
            }

            return $value;
        }
        
        return null;
    }

    protected function getLabeledValueText($child, $object)
    {
        return $this->getLabeledValueString($child, $object);
    }

    protected function getLabeledValueString($child, $object)
    {        
        $value = $child->getLabeledValue($object);
        if ($value->value) {
            if (is_array($value->value)) {
                $value->value = $this->arrayToValue($value->value);
            }

            return $value;
        }
        
        return null;
    }

    protected function getLabeledValueList($child, $object)
    {
        $value = $child->getLabeledValue($object);
        if ($value->value) {
            $translator = \Pimcore::getContainer()->get('translator');
            if (is_array($value->value)) {
                $oldValue = $value->value;
                $value->value = [];
                foreach ($value->def->options as $sortKey => $option) {
                    if (in_array($option['value'], $oldValue)) {
                        $value->value[] = [
                            'value' => $translator->trans($option['key'], [], 'selects'),
                            'xml_id' => $option['value'],
                            'sort' => ($sortKey + 1) * 100
                        ];
                    }
                }

            } else {
                foreach ($value->def->options as $sortKey => $option) {
                    if ($option['value'] === $value->value) {
                        $value->value = [
                            'value' => $translator->trans($option['key'], [], 'selects'),
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

    protected function getLabeledValueFile($child, $object)
    {
        $value = $child->getLabeledValue($object);
        if ($value->value) {
            $value->value = \Pimcore\Tool::getHostUrl() . $value->value->getFrontendFullPath();

            return $value;
        }
        
        return null;
    }

    protected function getLabeledValueBoolean($child, $object)
    {
        $value = $child->getLabeledValue($object);
        if ($value->value) {
            $value->value = (bool) $value->value;

            return $value;
        }
        
        return null;
    }

    protected function getLabeledValueInteger($child, $object)
    {
        $value = $child->getLabeledValue($object);
        if ($value && $value->value) {
            $value->value = (int) $value->value;

            return $value;
        }
        
        return null;
    }
}