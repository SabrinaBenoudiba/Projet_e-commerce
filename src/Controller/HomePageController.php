<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page', methods: ['GET'])]// methode Get on demande des infos au serveur et post pour envoyer des demandes au serveurs
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository, Request $request, PaginatorInterface $paginator): Response
    {

        $data = $productRepository->findby([],['id'=>"DESC"]);
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),//met en place la pagination
            8 //je choisi la limite de 8 articles par page
        );

        return $this->
        render('home_page/homePage.html.twig', [
            'controller_name' => 'HomePageController',
            'products' => $products,
            'categories' => $categoryRepository -> findAll()
        ]);

    }

    #[Route('/product/{id}/show', name: 'app_home_product_show', methods: ['GET'])]
    public function showProduct(Product $product, ProductRepository $productRepository): Response
    {
        $lastProductsAdd = $productRepository->findBy([], ['id'=>'DESC'],5); //1ere condition : aucun filtrage pour récuperer tous les produtis, le 2eme : est pour classer les résultats par l'id par l'ordre décroissant et le 3ème argument c'est pour demander de nous retourner que 5 résultats

        return $this->render('home_page/show.html.twig', [
            'product' => $product,
            'products' =>$lastProductsAdd
        ]);
    }

    #[Route('/product/subCategory/{id}/filter', name: 'app_home_product_filter', methods: ['GET'])]
        public function filter($id, SubCategoryRepository $subCategoryRepository, CategoryRepository $categoryRepository): Response
    {
        //on récupère la sous catégorie correspondant à l'id passé en paramère
        //on accède aux produits de cette sous catégorie
        $product = $subCategoryRepository->find($id)->getProducts();
        // on récupère la sous catégorie complète (objet)
        $subCategory = $subCategoryRepository->find($id); 

        return $this->render('home_page/filter.html.twig', [
            'products' => $product, // liste des produits liés à la sous catégorie
            'subCategory' => $subCategory, // l'objet sous catégorie qui correspond à l'id
            'categories' => $categoryRepository  ->findAll() // la liste de toutes les catégorie via le repo
        ]);
    }

}

            