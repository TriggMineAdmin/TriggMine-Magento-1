<?php

class Triggmine_IntegrationModule_Block_Adminhtml_System_Config_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
    

       // $action = Mage::helper('integrationmodule')->exportInit();
        
        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('Run Now !')
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }

}