<?php

require_once('AbstractController.php');
require_once(__DIR__.'/../helpers/JWT.php');
require_once Mage::getModuleDir('controllers', 'Mage_Contacts').DS.'IndexController.php';

use Mage_Contacts_IndexController as ContactController;

/**
 * Class Divante_VueStorefrontBridge_ContactController
 *
 * @package     Divante
 * @category    VueStoreFrontIndexer
 * @author      Bartosz Liburski <bliburski@divante.pl> Agata Firlejczyk <afirlejczyk@divante.pl
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 */
class Divante_VueStorefrontBridge_ContactController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     *
     */
    public function submitAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        }

        $json = $this->getJsonBody();

        if (!$json) {
            return $this->_result(500, 'No JSON object found in the request body');
        }

        if (!isset($json->form)) {
            return $this->_result(500, 'No form data provided');
        }

        $formData = $json->form;

        if (property_exists($formData, 'checker') && (null === $formData->checker)) {
            try {
                $this->sendEmail($formData);

                return $this->_result(200, true);
            } catch (\Exception $exception) {
                return $this->_result(500, $exception->getMessage());
            }
        }

        return $this->_result(500, 'Unable to submit your request. Please, try again later');
    }

    /**
     * @inheritdoc
     */
    private function getJsonBody()
    {
        return $this->_getJsonBody();
    }

    /**
     * @param stdClass $formData
     *
     * @return bool
     * @throws Exception
     */
    private function sendEmail($formData)
    {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        try {
            $error = false;

            if (!Zend_Validate::is(trim($formData->name), 'NotEmpty')) {
                $error = true;
            }

            if (!Zend_Validate::is(trim($formData->email), 'EmailAddress')) {
                $error = true;
            }

            if (!Zend_Validate::is(trim($formData->comment), 'NotEmpty')) {
                $error = true;
            }

            if ($error) {
                throw new Exception('Unable to submit your request. Please, try again later');
            }

            $formData = (array)$formData;
            $postObject = new Varien_Object();
            $postObject->setData($formData);

            $mailTemplate = Mage::getModel('core/email_template');

            $mailTemplate->setDesignConfig(['area' => 'frontend'])
                ->setReplyTo($formData['email'])
                ->sendTransactional(
                    Mage::getStoreConfig(ContactController::XML_PATH_EMAIL_TEMPLATE),
                    Mage::getStoreConfig(ContactController::XML_PATH_EMAIL_SENDER),
                    Mage::getStoreConfig(ContactController::XML_PATH_EMAIL_RECIPIENT),
                    null,
                    ['data' => $postObject]
                );

            if (!$mailTemplate->getSentSuccess()) {
                throw new Exception(
                    Mage::helper('contacts')->__('Unable to submit your request. Please, try again later')
                );
            }

            $translate->setTranslateInline(true);

            return true;
        } catch (Exception $e) {
            $translate->setTranslateInline(true);

            throw $e;
        }
    }
}
