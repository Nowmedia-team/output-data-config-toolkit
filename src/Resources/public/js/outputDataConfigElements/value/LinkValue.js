/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */


pimcore.registerNS("pimcore.bundle.outputDataConfigToolkit.outputDataConfigElements.value.LinkValue");

pimcore.bundle.outputDataConfigToolkit.outputDataConfigElements.value.LinkValue = Class.create(pimcore.bundle.outputDataConfigToolkit.outputDataConfigElements.Abstract, {

    type: "value",
    class: "LinkValue",

    getConfigTreeNode: function(configAttributes) {
        var node = {
            draggable: false,
            iconCls: "pimcore_icon_" + configAttributes.dataType,
            text: configAttributes.text,
            qtip: configAttributes.attribute,
            configAttributes: configAttributes,
            isTarget: false,
            leaf: false,
            expanded: false,
            childs: null
        };

        return node;
    },

    getCopyNode: function(source) {

        var copy = source.createNode({
            iconCls: source.data.iconCls,
            text: source.data.text,
            isTarget: true,
            leaf: true,
            dataType: source.data.dataType,
            qtip: source.data.key,
            configAttributes: {
                label: null,
                type: this.type,
                class: this.class,
                attribute: source.data.key,
                dataType: source.data.dataType
            }
        });
        return copy;
    },

    getConfigDialog: function(node) {
        return false;
    },

    commitData: function() {
        if(this.radiogroup.getValue().rb == "custom") {
            this.node.data.configAttributes.label = this.textfield.getValue();
            this.node.set('text', this.textfield.getValue());
        } else {
            this.node.data.configAttributes.label = null;
        }

        var iconValue = this.icon ? this.icon.getValue() : null;
        if (iconValue != null && iconValue.length == 0) {
            iconValue = null;
            this.node.data.configAttributes.icon = iconValue;
            var restoredIconClass = "pimcore_icon_" + this.node.data.configAttributes.dataType;
            this.node.set('iconCls', restoredIconClass);
        } else if (iconValue != null) {
            this.node.set('iconCls', null);
        }
        this.node.data.configAttributes.icon = iconValue;
        this.node.set('icon', iconValue);

        this.window.close();
    }
});