<?php

namespace App\Controller;

use App\Entity\Recipes;
use App\Form\RecipesType;
use DateTimeImmutable;
use App\Repository\RecipesRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/recipes')]
class RecipesController extends AbstractController
{
    #[Route('/', name: 'recipes_index', methods: ['GET'])]
    public function index(RecipesRepository $recipesRepository): Response
    {
        return $this->render('recipes/index.html.twig', [
            'recipes' => $recipesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'recipes_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recipe = new Recipes();
        $recipe->setCreatedAt(new \DateTimeImmutable());
        $recipe->setUser($this->getUser());
        $form = $this->createForm(RecipesType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $recipeFile */
            $recipeFile = $form->get('imageFile')->getData();

            if ($recipeFile) {
                $newFilename = uniqid().'.'.$recipeFile ->guessExtension();

                try {
                    $recipeFile ->move(
                        $this->getParameter('kernel.project_dir') .'/public/uploads/',
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', $e->getMessage());
                }

                $recipe->setRecipeImagePath($newFilename);
            }

            $entityManager->persist($recipe);
            $entityManager->flush();

            return $this->redirectToRoute('recipes_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('recipes/new.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'recipes_show', methods: ['GET'])]
    public function show(Recipes $recipe): Response
    {
        return $this->render('recipes/show.html.twig', [
            'recipe' => $recipe,

        ]);
    }

    #[Route('/{id}/edit', name: 'recipes_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recipes $recipe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecipesType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('recipes_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('recipes/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'recipes_delete', methods: ['POST'])]
    public function delete(Request $request, Recipes $recipe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->request->get('_token'))) {
            //$filename =$recipe->getRecipeImagePath();
            $entityManager->remove($recipe);
            $entityManager->flush();

            // $fs = new Filesystem();
           // $fs->remove($this->getParameter('kernel.project_dir') .'/public/uploads/'. $filename);
        }

        return $this->redirectToRoute('recipes_index', [], Response::HTTP_SEE_OTHER);
    }
}
