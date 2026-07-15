<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Api\Data;

interface FaqInterface
{
    public const FAQ_ID = 'faq_id';
    public const QUESTION = 'question';
    public const ANSWER = 'answer';
    public const SORT_ORDER = 'sort_order';
    public const IS_ACTIVE = 'is_active';
    public const SHOW_ON_PRODUCT = 'show_on_product';
    public const SHOW_ON_ABOUT = 'show_on_about';
    public const SHOW_ON_CONTACT = 'show_on_contact';
    public const SHOW_ON_HOME = 'show_on_home';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const LOCATION_PRODUCT = 'product';
    public const LOCATION_ABOUT = 'about';
    public const LOCATION_CONTACT = 'contact';
    public const LOCATION_HOME = 'home';

    public function getFaqId(): ?int;

    public function setFaqId(int $faqId): self;

    public function getQuestion(): string;

    public function setQuestion(string $question): self;

    public function getAnswer(): string;

    public function setAnswer(string $answer): self;

    public function getSortOrder(): int;

    public function setSortOrder(int $sortOrder): self;

    public function getIsActive(): bool;

    public function setIsActive(bool $isActive): self;

    public function getShowOnProduct(): bool;

    public function setShowOnProduct(bool $show): self;

    public function getShowOnAbout(): bool;

    public function setShowOnAbout(bool $show): self;

    public function getShowOnContact(): bool;

    public function setShowOnContact(bool $show): self;

    public function getShowOnHome(): bool;

    public function setShowOnHome(bool $show): self;
}
