<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchEngineController extends AbstractController
{
    #[Route('/search/engine', name: 'app_search_engine')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {

        if ($request->isMethod('POST')){ // Vérifie si la requête est de type GET
            $word = $request-> get('word');
            // $data = $request->query->all(); // Récupère les données de la requête
            // $word = $data['word']; // Récupère le mot-clé de recherche
            $results = $productRepository->searchEngine($word);// Appelle la méthode searchEngine du repository pour récupérer les résultats de recherche
        }
        return $this->render('search_engine/index.html.twig', [
            'products' => $results,
            'word' => $word,
        ]);
    }
}
