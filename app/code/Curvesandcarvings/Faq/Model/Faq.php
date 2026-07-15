<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Model;

use Curvesandcarvings\Faq\Api\Data\FaqInterface;
use Magento\Framework\Model\AbstractModel;

class Faq extends AbstractModel implements FaqInterface
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Faq::class);
    }

    public function getFaqId(): ?int
    {
        $id = $this->getData(self::FAQ_ID);
        return $id !== null ? (int)$id : null;
    }

    public function setFaqId(int $faqId): FaqInterface
    {
        return $this->setData(self::FAQ_ID, $faqId);
    }

    public function getQuestion(): string
    {
        return (string)$this->getData(self::QUESTION);
    }

    public function setQuestion(string $question): FaqInterface
    {
        return $this->setData(self::QUESTION, $question);
    }

    public function getAnswer(): string
    {
        return (string)$this->getData(self::ANSWER);
    }

    public function setAnswer(string $answer): FaqInterface
    {
        return $this->setData(self::ANSWER, $answer);
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): FaqInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): FaqInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive ? 1 : 0);
    }

    public function getShowOnProduct(): bool
    {
        return (bool)$this->getData(self::SHOW_ON_PRODUCT);
    }

    public function setShowOnProduct(bool $show): FaqInterface
    {
        return $this->setData(self::SHOW_ON_PRODUCT, $show ? 1 : 0);
    }

    public function getShowOnAbout(): bool
    {
        return (bool)$this->getData(self::SHOW_ON_ABOUT);
    }

    public function setShowOnAbout(bool $show): FaqInterface
    {
        return $this->setData(self::SHOW_ON_ABOUT, $show ? 1 : 0);
    }

    public function getShowOnContact(): bool
    {
        return (bool)$this->getData(self::SHOW_ON_CONTACT);
    }

    public function setShowOnContact(bool $show): FaqInterface
    {
        return $this->setData(self::SHOW_ON_CONTACT, $show ? 1 : 0);
    }

    public function getShowOnHome(): bool
    {
        return (bool)$this->getData(self::SHOW_ON_HOME);
    }

    public function setShowOnHome(bool $show): FaqInterface
    {
        return $this->setData(self::SHOW_ON_HOME, $show ? 1 : 0);
    }
}
