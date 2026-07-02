<?php

namespace Yereone\Testimonials\Observer;

class AddFontawesome implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var string
     */
    const FONT_AWESOME_CDN = 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css';

    /**
     * @var \Magento\Framework\View\Page\Config
     */
    protected $pageConfig;

    /**
     * @var \Yereone\Testimonials\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Framework\View\Page\Config $pageConfig
     * @param \Yereone\Testimonials\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\View\Page\Config $pageConfig,
        \Yereone\Testimonials\Helper\Data $helper
    ) {
        $this->pageConfig = $pageConfig;
        $this->helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isFontAwesomeEnabled()) {
            return;
        }

        if ($this->helper->canUseCdn()) {
            $this->addRemoteAsset($this->getRemoteAsset());
        } else {
            $this->addLocalAsset($this->getLocalAsset());
        }
    }

    /**
     * Get basic font awesome asset object
     *
     * @return \Magento\Framework\DataObject
     */
    protected function getBaseAsset()
    {
        $asset = new \Magento\Framework\DataObject();
        $asset->addData([
            'properties' => [],
            'name' => 'yereone_fontawesome'
        ]);
        return $asset;
    }

    /**
     * Get remote font awesome asset object
     *
     * @return \Magento\Framework\DataObject
     */
    public function getRemoteAsset()
    {
        $url  = self::FONT_AWESOME_CDN;
        $type = 'css';
        $asset = $this->getBaseAsset();
        $asset->addData([
            'url'  => $url,
            'type' => $type
        ]);
        return $asset;
    }

    /**
     * Get local font awesome data object
     *
     * @return \Magento\Framework\DataObject
     */
    public function getLocalAsset()
    {
        $asset = $this->getBaseAsset();
        $asset->addData([
            'url'  => 'Yereone_Testimonials::css/font-awesome.min.css',
            'type' => 'css'
        ]);
        return $asset;
    }

    /**
     * @param \Magento\Framework\DataObject $asset
     */
    protected function addRemoteAsset($asset)
    {
        $this->pageConfig->addRemotePageAsset(
            $asset->getUrl(),
            $asset->getType(),
            $asset->getProperties(),
            $asset->getName()
        );
    }

    /**
     * @param \Magento\Framework\DataObject $asset
     */
    protected function addLocalAsset($asset)
    {
        $this->pageConfig->addPageAsset(
            $asset->getUrl(),
            $asset->getProperties(),
            $asset->getName()
        );
    }
}
