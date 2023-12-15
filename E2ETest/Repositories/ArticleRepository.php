<?php

namespace AdyenPayment\E2ETest\Repositories;

use Shopware\Models\Article\Article;
use Shopware\Models\Article\Price;
use Shopware\Models\Article\Repository;

/**
 * Class UserRepository
 *
 * @package AdyenPayment\E2ETest\Repositories
 */
class ArticleRepository
{
    /**
     * @var Repository
     */
    private $articleRepository;

    /**
     * UserRepository constructor.
     */
    public function __construct()
    {
        $this->articleRepository = Shopware()->Models()->getRepository(Article::class);
    }

    /**
     * Returns array of Shopware users from database
     *
     * @param int $articleId
     * @return Article
     */
    public function getShopwareArticle(int $articleId): Article
    {
        return $this->articleRepository->getArticleBaseDataQuery($articleId)->getResult()[0];
    }

    /**
     * Returns array of Shopware users from database
     *
     * @param int $articleDetailId
     * @return Price
     */
    public function getShopwareArticleDetailsPrices(int $articleDetailId): Price
    {
        return $this->articleRepository->getPricesQuery($articleDetailId)->getResult()[0];
    }
}
