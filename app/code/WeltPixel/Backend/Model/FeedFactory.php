<?php
namespace WeltPixel\Backend\Model;

class FeedFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $data
     * @return Feed
     */
    public function create(array $data = [])
    {
        return $this->objectManager->create(Feed::class, $data);
    }
}
