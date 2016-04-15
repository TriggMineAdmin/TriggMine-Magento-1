<?php
class Triggmine_IntegrationModule_Adminhtml_IntegrationmodulebackendController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
       $this->loadLayout();
	   $this->_title($this->__("Triggmine Settings"));
	   $this->renderLayout();
    }
}