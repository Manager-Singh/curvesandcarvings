<?php

namespace Yereone\Testimonials\Controller\Index;

use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Yereone\Testimonials\Helper\Data
     */
    protected $helper;

    /**
     * @var \Yereone\Testimonials\Model\TestimonialFactory
     */
    protected $testimonialFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Yereone\Testimonials\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Yereone\Testimonials\Helper\Data $helper,
        \Yereone\Testimonials\Model\TestimonialFactory $testimonialFactory
    ) {
        $this->helper = $helper;
        $this->testimonialFactory = $testimonialFactory;
        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->helper->isAllowedToAddTestimonial()) {
            $this->messageManager->addError(__('You are not allowed to add testimonial'));
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        $data = $this->getRequest()->getPostValue();
        $model = $this->testimonialFactory->create();
        if ($data) {
            $imageRequest = $this->getRequest()->getFiles('image');
            $data = $this->helper->imageUpload($data, $imageRequest);
            $model->setData($data);
            try {
                $model->save();
                $this->messageManager->addSuccess(__('Thank You for Adding Your Testimonial.'));
                return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the testimonial.'));
            }
        }
        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
