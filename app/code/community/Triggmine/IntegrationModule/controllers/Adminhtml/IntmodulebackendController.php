<?php
class Triggmine_IntegrationModule_Adminhtml_IntmodulebackendController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
        $session = Mage::getSingleton('admin/session');
        $session->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());
        $this->_redirect('adminhtml/system_config/edit/section/triggmine');
    }
}