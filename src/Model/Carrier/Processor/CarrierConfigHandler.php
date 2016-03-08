<?php
/**
 *
 * ShipperHQ Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * Shipper HQ Shipping
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2015 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */

namespace ShipperHQ\Shipper\Model\Carrier\Processor;

class CarrierConfigHandler
{
    /**
     * @var \ShipperHQ\Shipper\Helper\Data
     */
    private $shipperDataHelper;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    private $resourceConfig;

    /**
     * @param Config $configHelper
     *
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Backend\Block\Template\Context $context,
        \ShipperHQ\Shipper\Helper\Data $shipperDataHelper)
    {

        $this->shipperDataHelper = $shipperDataHelper;
        $this->storeManager = $context->getStoreManager();
        $this->resourceConfig = $resourceConfig;
    }

    public function saveCarrierResponseDetails($carrierRate, $carrierGroupDetail = null)
    {
        $carrierCode = $carrierRate->carrierCode;
        $sort = isset($carrierRate->sortOrder) ? $carrierRate->sortOrder : false;
        $this->dynamicCarrierConfig($carrierCode, $carrierRate->carrierTitle, $sort);

        $this->populateCarrierLevelDetails((array)$carrierRate, $carrierGroupDetail);
    }


    public function populateCarrierLevelDetails($carrierRate, &$carrierGroupDetail)
    {
        $carrierGroupDetail['carrierType'] = $carrierRate['carrierType'];
        $carrierGroupDetail['carrierTitle'] = $carrierRate['carrierTitle'];
        $carrierGroupDetail['carrier_code'] = $carrierRate['carrierCode'];
        $carrierGroupDetail['carrierName'] = $carrierRate['carrierName'];
    }


    protected function dynamicCarrierConfig($carrierCode, $carrierTitle, $sortOrder = false)
    {
        $modelPath = 'carriers/' . $carrierCode . '/model';
        if (!$this->shipperDataHelper->getConfigValue($modelPath)) {
            $model = 'ShipperHQ\Shipper\Model\Carrier\Shipper';
            $this->saveConfig($modelPath, $model);
            $this->saveConfig('carriers/' . $carrierCode . '/active', 0);
        }
        $this->saveCarrierTitle($carrierCode, $carrierTitle);

        if ($sortOrder) {
            $this->saveConfig('carriers/' . $carrierCode . '/sort_order', $sortOrder);
        }
    }


    /**
     * Saves the carrier title to core_config_data
     * Need to do this as doesnt read from the shipping rate quote table!
     * @param $carrierCode
     * @param $carrierTitle
     */
    public function saveCarrierTitle($carrierCode, $carrierTitle)
    {
        $this->saveConfig('carriers/' . $carrierCode . '/title', $carrierTitle);
    }


    /**
     * Save config value to db
     * @param $path
     * @param $value
     * @param string $scope
     * @param int $scopeId
     * @return $this
     */
    public function saveConfig($path, $value, $scope = 'default', $scopeId = 0)
    {
        if ($this->shipperDataHelper->getConfigValue($path) != $value) {
            $this->resourceConfig->saveConfig(rtrim($path, '/'), $value, $scope, $scopeId);
            $this->shipperDataHelper->getQuote()->setConfigUpdated(true);
        }
    }


    public function refreshConfig()
    {
        if ($this->shipperDataHelper->getQuote()->getConfigUpdated()) {
            $this->storeManager->getStore()->resetConfig();
            $this->shipperDataHelper->getQuote()->setConfigUpdated(false);
        }
    }


    /*
     * This dynamically updates the carrier titles from ShipperHQ
     * Is required as don't want to set these on every quote request
     */
    public function setCarrierConfig($carrierConfig)
    {
        foreach ($carrierConfig as $carrierCode => $config) {
            $this->saveCarrierTitle($carrierCode, $config['title']);
            if (array_key_exists('sortOrder', $config)) {
                $this->saveConfig('carriers/' . $carrierCode . '/sort_order', $config['sortOrder']);
            }
        }

    }




}
