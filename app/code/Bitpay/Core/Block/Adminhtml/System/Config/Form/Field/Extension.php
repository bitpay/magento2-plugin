<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

/**
 * This is used to display php extensions and if they are installed or not
 */
namespace Bitpay\Core\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Extension extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render element html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        if (false === isset($element) || true === empty($element)) {
            $helper = \Magento\Framework\App\ObjectManager::getInstance()->get('Bitpay\Core\Helper\Data');
            $helper->debugData('[ERROR] In Bitpay_Core_Block_Adminhtml_System_Config_Form_Field_Extension::_getElementHtml(): Missing or invalid $element parameter passed to function.');
            throw new \Exception('In Bitpay_Core_Block_Adminhtml_System_Config_Form_Field_Extension::_getElementHtml(): Missing or invalid $element parameter passed to function.');
        }

        $config = $element->getFieldConfig();
        $phpExtension = isset($config['php_extension']) ? $config['php_extension'] : 'null';
       
        if (true === in_array($phpExtension, get_loaded_extensions())) {
            return 'Installed';
        }

        return 'Not Installed';
    }
}
