<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\Manager\AuthorManager;
use App\Service\Manager\BookManager;
use App\Service\Manager\CategoryManager;
use App\Service\Manager\OrderManager;
use App\Service\Manager\ReadingManager;
use App\Service\Manager\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method User|null getUser()
 **/
abstract class AbstractController extends BaseAbstractController
{

    protected AuthorManager   $authorManager;
    protected BookManager     $bookManager;
    protected ReadingManager  $readingManager;
    protected UserManager     $userManager;
    protected OrderManager    $orderManager;
    protected CategoryManager $categoryManager;

    public function __construct(
        AuthorManager   $authorManager,
        BookManager     $bookManager,
        ReadingManager  $readingManager,
        UserManager     $userManager,
        OrderManager    $orderManager,
        CategoryManager $categoryManager
    )
    {
        $this->authorManager   = $authorManager;
        $this->bookManager     = $bookManager;
        $this->readingManager  = $readingManager;
        $this->userManager     = $userManager;
        $this->orderManager    = $orderManager;
        $this->categoryManager = $categoryManager;
    }

    public function redirectToReferer(string $route): RedirectResponse
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->get('request_stack');
        $referer      = $requestStack->getMasterRequest()
            ? $requestStack->getMasterRequest()->headers->get('referer')
            : null;

        if ($referer) {
            return new RedirectResponse($referer, Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->redirectToRoute($route);
    }

}
