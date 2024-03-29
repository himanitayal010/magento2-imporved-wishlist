<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magneto\ImprovedWishlist\Controller\Index;

use Magento\Framework\App\Action;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Wishlist\Controller\WishlistProviderInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Remove extends \Magento\Wishlist\Controller\AbstractIndex
{
    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;
     protected $wishlist;
     protected $_messageManager;

    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @param Action\Context $context
     * @param WishlistProviderInterface $wishlistProvider
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Action\Context $context,
        WishlistProviderInterface $wishlistProvider,
        \Magento\Wishlist\Model\Wishlist $wishlist,
        Validator $formKeyValidator,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->wishlist = $wishlist;
        $this->formKeyValidator = $formKeyValidator;
        $this->_messageManager = $messageManager;
        parent::__construct($context);
    }

    /**
     * Remove item
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws NotFoundException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if(isset($params['method']) && $params['method'] == 'ajax' ){    
            $productId = (int)$this->getRequest()->getParam('item');
            $customerSession = $this->_objectManager->get('Magento\Customer\Model\Session');
            $customerId = $customerSession->getId();
            
            $wishlistModelObject = $this->wishlist->loadByCustomerId($customerId);
            $items = $wishlistModelObject->getItemCollection();
            try{
                foreach ($items as $item) {
                    if ($item->getProductId() == $productId) {
                        $item->delete();
                        $wishlistModelObject->save();
                         $this->_messageManager->addSuccess(__('"'.$item->getName().'"'.' Removed Successfully From The Whistlist'));
                    }
                }
            }catch (Exception $e){
                $this->messageManager->addError(__($e->getMessage()));
            }
            echo "success";
            exit;
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = (int)$this->getRequest()->getParam('item');
        $item = $this->_objectManager->create(\Magento\Wishlist\Model\Item::class)->load($id);
        if (!$item->getId()) {
            throw new NotFoundException(__('Page not found.'));
        }
        $wishlist = $this->wishlistProvider->getWishlist($item->getWishlistId());
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }
        try {
            $item->delete();
            $wishlist->save();
             $this->_messageManager->addSuccess(__("Product Removed Successfully From The Whistlist"));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                __('We can\'t delete the item from Wish List right now because of an error: %1.', $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t delete the item from the Wish List right now.'));
        }

        $this->_objectManager->get(\Magento\Wishlist\Helper\Data::class)->calculate();
        $request = $this->getRequest();
        $refererUrl = (string)$request->getServer('HTTP_REFERER');
        $url = (string)$request->getParam(\Magento\Framework\App\Response\RedirectInterface::PARAM_NAME_REFERER_URL);
        if ($url) {
            $refererUrl = $url;
        }
        if ($request->getParam(\Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED) && $refererUrl) {
            $redirectUrl = $refererUrl;
        } else {
            $redirectUrl = $this->_redirect->getRedirectUrl($this->_url->getUrl('*/*'));
        }
        $resultRedirect->setUrl($redirectUrl);
        return $resultRedirect;
    }
}