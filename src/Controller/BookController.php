<?php
declare(strict_types=1);

namespace App\Controller;

use App\Form\Type\BookType;
use App\Form\Type\OrderBookType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function array_flip;

/**
 * @Route(path="/book")
 */
class BookController extends AbstractController
{

    /**
     * @Route(path="/list", name="book.list")
     */
    public function index(Request $request): Response
    {
        $filter     = $request->query->all();
        $books      = $this->bookManager->paginate($filter);
        $categories = array_flip($this->categoryManager->choices(['name']));

        return $this->render('default/book/index.html.twig',
            [
                'books'          => $books,
                'categories'     => $categories,
                'authorManager'  => $this->authorManager,
                'bookManager'    => $this->bookManager,
                'readingManager' => $this->readingManager,
                'userManager'    => $this->userManager,
                'orderManager'   => $this->orderManager,
            ]
        );
    }

    /**
     * @Route(path="/add", name="book.add")
     */
    public function add(Request $request): Response
    {
        $form = $this->bookManager->form(BookType::class);
        if ($request->isMethod(Request::METHOD_POST) &&
            !($errors = $this->bookManager->handleForm($form, $request))
        ) {
            $data = $form->getData();
            $this->bookManager->create($data);

            return $this->redirectToRoute('book.list');
        }

        return $this->render(
            'default/book/add.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route(path="/{id}/edit", name="book.edit")
     */
    public function edit(Request $request, int $id): Response
    {
        $data               = $this->bookManager->get($id);
        $data['authors']    = !empty($data['authors']) ? array_keys($data['authors']) : [];
        $data['categories'] = !empty($data['categories']) ? array_keys($data['categories']) : [];
        $form               = $this->bookManager->form(BookType::class, $data ?? [], ['id' => $id]);
        if ($request->isMethod(Request::METHOD_POST) &&
            !($errors = $this->bookManager->handleForm($form, $request))
        ) {
            $data = $form->getData();
            $this->bookManager->update($id, $data);

            return $this->redirectToRoute('book.list');
        }

        return $this->render(
            'default/book/edit.html.twig',
            [
                'id'   => $id,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route(path="/{id}/delete", name="book.delete")
     */
    public function delete(int $id): Response
    {
        $this->bookManager->delete($id);

        return $this->redirectToRoute('book.list');
    }

    /**
     * @Route(path="/{id}/order", name="book.order")
     */
    public function order(Request $request, int $id): Response
    {
        $userId = $this->getUser() ? $this->getUser()->getId() : null;
        $book   = $this->bookManager->get($id);
        $form   = $this->bookManager->form(OrderBookType::class, [], ['book_id' => $id, 'user_id' => $userId]);
        if ($request->isMethod(Request::METHOD_POST) &&
            !($errors = $this->bookManager->handleForm($form, $request))
        ) {
            $data = $form->getData();
            $this->bookManager->order($data);

            return $this->redirectToRoute('book.list');
        }

        return $this->render(
            'default/book/order.html.twig',
            [
                'id'   => $id,
                'book' => $book,
                'form' => $form->createView(),
            ]
        );
    }
}
