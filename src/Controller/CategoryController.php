<?php
declare(strict_types=1);

namespace App\Controller;

use App\Form\Type\CategoryType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/category")
 */
class CategoryController extends AbstractController
{

    /**
     * @Route(path="/list", name="category.list")
     */
    public function index(Request $request): Response
    {
        $filter  = $request->query->all();
        $categories = $this->categoryManager->paginate($filter);

        return $this->render(
            'default/category/index.html.twig',
            [
                'categories'      => $categories,
                'categoryManager' => $this->categoryManager,
            ]
        );
    }

    /**
     * @Route(path="/add", name="category.add")
     */
    public function add(Request $request): Response
    {
        $form = $this->categoryManager->form(CategoryType::class);
        if ($request->isMethod(Request::METHOD_POST) &&
            !($errors = $this->categoryManager->handleForm($form, $request))
        ) {
            $data = $form->getData();
            $this->categoryManager->create($data);

            return $this->redirectToRoute('category.list');
        }

        return $this->render(
            'default/category/add.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route(path="/{id}/edit", name="category.edit")
     */
    public function edit(Request $request, int $id): Response
    {
        $data = $this->categoryManager->get($id);
        $form = $this->categoryManager->form(CategoryType::class, $data ?? [], ['id' => $id]);
        if ($request->isMethod(Request::METHOD_POST) &&
            !($errors = $this->categoryManager->handleForm($form, $request))
        ) {
            $data = $form->getData();
            $this->categoryManager->update($id, $data);

            return $this->redirectToRoute('category.list');
        }

        return $this->render(
            'default/category/edit.html.twig',
            [
                'id'   => $id,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route(path="/{id}/delete", name="category.delete")
     */
    public function delete(int $id): RedirectResponse
    {
        $this->categoryManager->delete($id);

        return $this->redirectToReferer('category.list');
    }

}
