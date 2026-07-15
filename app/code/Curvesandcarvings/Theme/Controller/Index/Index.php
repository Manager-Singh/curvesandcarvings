<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Search\Helper\Data as SearchHelper;

/**
 * Magento frontName "search" only has ajax/suggest + term routes.
 * Browsers/bookmarks hitting /search (or /search?q=…) 404 — send them to catalog search.
 */
class Index implements HttpGetActionInterface, HttpPostActionInterface
{
    public function __construct(
        private readonly RedirectFactory $redirectFactory,
        private readonly RequestInterface $request,
        private readonly SearchHelper $searchHelper
    ) {
    }

    public function execute(): Redirect
    {
        $queryParam = $this->searchHelper->getQueryParamName();
        $query = trim((string)$this->request->getParam($queryParam, ''));

        $params = [];
        if ($query !== '') {
            $params[$queryParam] = $query;
        }

        /** @var Redirect $redirect */
        $redirect = $this->redirectFactory->create();
        $redirect->setPath('catalogsearch/result', $params);
        $redirect->setHttpResponseCode(301);

        return $redirect;
    }
}
