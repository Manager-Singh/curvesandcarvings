<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Setup\Patch\Data;

use Curvesandcarvings\Faq\Model\FaqFactory;
use Curvesandcarvings\Faq\Model\ResourceModel\Faq as FaqResource;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SeedProductFaqs implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly FaqFactory $faqFactory,
        private readonly FaqResource $faqResource
    ) {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $items = [
            [
                'question' => 'Do you make this furniture yourself or is it imported from China?',
                'answer' => '<p>We do not import any furniture item from China or anywhere else. All our furniture is hand-crafted \'Made in India\' by our skilled artisans.</p>',
                'sort_order' => 10,
            ],
            [
                'question' => 'What is wood used for manufacturing Furniture shown in your website',
                'answer' => '<p>We primarily use seasoned teak and other high-quality hardwoods suited to each design. Exact wood details are listed on the product page where applicable.</p>',
                'sort_order' => 20,
            ],
            [
                'question' => 'How do we know wood used is Teak wood or not anything else?',
                'answer' => '<p>You are welcome to visit our workshop/showroom to inspect the wood and craftsmanship firsthand. We are happy to explain the materials used for each piece.</p>',
                'sort_order' => 30,
            ],
            [
                'question' => 'What if we want furniture fully made of Teakwood?',
                'answer' => '<p>Yes, many pieces can be customised to be fully made in teakwood. Share your requirements with our team and we will confirm feasibility and pricing.</p>',
                'sort_order' => 40,
            ],
            [
                'question' => 'I need some of my furniture like Wardrobe to be fixed with wall? Will you do that for us?',
                'answer' => '<p>Yes, for applicable items we can assist with wall fixing / installation as part of delivery and setup, subject to site conditions.</p>',
                'sort_order' => 50,
            ],
            [
                'question' => 'Do you have any more designs than ones shown on your website?',
                'answer' => '<p>Yes. The website shows featured collections; our artisans can create additional designs and customisations based on your preference.</p>',
                'sort_order' => 60,
            ],
            [
                'question' => 'Can we see your products to understand their quality and finish?',
                'answer' => '<p>Absolutely. Visit our showroom to see and feel the quality and finish of our furniture before you buy.</p>',
                'sort_order' => 70,
            ],
        ];

        foreach ($items as $item) {
            $faq = $this->faqFactory->create();
            $faq->setData([
                'question' => $item['question'],
                'answer' => $item['answer'],
                'sort_order' => $item['sort_order'],
                'is_active' => 1,
                'show_on_product' => 1,
                'show_on_about' => 0,
                'show_on_contact' => 0,
                'show_on_home' => 0,
            ]);
            $this->faqResource->save($faq);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
