<?php

class Triggmine_IntegrationModule_Block_Adminhtml_System_Config_Productexport extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_productExportStep;
    
    public function __construct()
    {
        $this->_productExportStep = 20;
        $this->_websiteCode = Mage::getSingleton('adminhtml/config_data')->getWebsite();
        $this->_websiteId   = Mage::getModel('core/website')->load($this->_websiteCode)->getId();
        $this->_storeId     = Mage::app()->getWebsite($this->_websiteId)->getDefaultStore()->getId();
    }
    
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $pageSize      = $this->_productExportStep;
        $websiteId     = $this->_websiteId;
        $storeId       = $this->_storeId;
        
        $pluginSetUp   = Mage::app()->getWebsite($websiteId)->getConfig('triggmine/settings/plugin_set_up');
        $productExport = Mage::getStoreConfig('triggmine/triggmine_product_export/export', $storeId);
        $pluginEnabled = Mage::helper('integrationmodule/data')->isEnabled();

        // manual product export
        if ($productExport)
        {
            $pluginSetUp = 0;
        }
        
        $apiURL   = Mage::app()->getWebsite($websiteId)->getConfig('triggmine/settings/url_api');
        $apiToken = Mage::app()->getWebsite($websiteId)->getConfig('triggmine/settings/token');

        $html = "";
        
        if ($pluginEnabled && !$pluginSetUp) // TO DO more complex integration check
        {
            $productPool = Mage::getModel('catalog/product')->getCollection()
                            ->addStoreFilter($storeId);
            
            $productPool->setPageSize($pageSize);
            $pages = $productPool->getLastPageNumber();
            $currentPage = $productPool->setCurPage(1);

            $html .= "<div id=\"message-popup-window-mask\" style=\"display:none\"></div>
                        <div id=\"message-popup-window\" class=\"message-popup\">
                            <div class=\"message-popup-head\">
                                <h2>Export in progress</h2>
                            </div>
                            <div class=\"message-popup-content\">
                                <div class=\"message\">
                                    <p class=\"message-text\">Please wait while your products data is being exported to TriggMine. This popup will close automatically when the export is done.</p>
                                </div>
                                <div id=\"triggmine-export-status\"></div>
                            </div>
                        </div>
            
                    <script type=\"text/javascript\">
                            // get cookie value
                            function getCookie(name) {
                              var matches = document.cookie.match(new RegExp(
                                \"(?:^|; )\" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\\$1') + \"=([^;]*)\"
                              ));
                              return matches ? decodeURIComponent(matches[1]) : undefined;
                            }
                            
                            // popup functions
                            var messagePopupClosed = true;
                            function openMessagePopup() {
                                var height = $('html-body').getHeight()
                                $('message-popup-window-mask').setStyle({'height':height+'px'});
                                toggleSelectsUnderBlock($('message-popup-window-mask'), false);
                                Element.show('message-popup-window-mask');
                                $('message-popup-window').addClassName('show');
                            }
                            
                            function closeMessagePopup() {
                                toggleSelectsUnderBlock($('message-popup-window-mask'), true);
                                Element.hide('message-popup-window-mask');
                                $('message-popup-window').removeClassName('show');
                                messagePopupClosed = true;
                            }
                                
                            // prevent window being closed while export in progress
                            window.onbeforeunload = function (e) {
                                if (getCookie('exportInProgress') == 1) {
                                    e = e || window.event;
                                    // For IE and Firefox prior to version 4
                                    if (e) {
                                        e.returnValue = 'Sure?';
                                    }
                                    // For Safari
                                    return 'Sure?';
                                }
                            };
                            
                            document.addEventListener('DOMContentLoaded', function() {
                                var pages = $pages;

                                var xhttp    = new XMLHttpRequest();
                                var bodyBase = 'apiURL=$apiURL&apiToken=$apiToken&pagesTotal=$pages&pageSize=$pageSize&website=$websiteId&store=$storeId&page=';
                                
                                var exportStatus = document.getElementById('triggmine-export-status');
                                
                                document.cookie = \"exportInProgress=0\";
                                
                                // show message
                                openMessagePopup();
                                
                                function sendRequest(currentPage) {
                                    body = bodyBase+currentPage;
                                    xhttp.open(\"POST\", \"" . Mage::getUrl('triggmine/ajax/export') . "\", true);
                                    xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                                    
                                    xhttp.onreadystatechange = function() {
                                        if (xhttp.readyState == XMLHttpRequest.DONE ) {
                                            if (xhttp.status == 200) {
                                            
                                                if (currentPage < pages) {
                                                    document.cookie = \"exportInProgress=1\";
                                                    exportStatus.innerHTML = 'Exporting page '+currentPage+'/'+pages;
                                                    sendRequest(currentPage + 1);
                                                } else {
                                                    document.cookie = \"exportInProgress=0\";
                                                    closeMessagePopup();
                                                }
                                                
                                            } else {
                                                exportStatus.innerHTML = '<span style=\"color:red\">An error occured. Please check your browser console for more info.</span>';
                                                console.error('TriggMine could not send an Ajax request');
                                            }
                                        }
                                    };
                                    
                                    xhttp.send(body);
                                }
                                
                                sendRequest(1);
                            });
                    </script>";
        }

        return $html;
    }
}