<?php

namespace App\Service;

use App\Repository\ProductRepository;

class Cart{

    public function __construct (private readonly ProductRepository $productRepository){
     
    }

        public function getCart ($session):array{
            // récupére les données du panier
        $cart = $session->get('cart', []);
        // initialise un tableau pour stocker les données du panier avec les informations de produits
        $cartWithData = [];

        // Boucle sur les éléments du panier pour récupérer les informations de produit
        foreach ($cart as $id => $quantity) {
            // récupère le produit correspondant à l'id et à la quantité
            $cartWithData[] = [
                'product' => $this->productRepository->find($id),
                'quantity'=> $quantity
            ];
        }
        // calcul total du panier, on mappe sur le tableau pour récupérer ts les items
        $total = array_sum(array_map(function ($item){
            // Pour chaque élément du panier, multiplie le prix du produit par la quantité
             return $item['product']->getPrice() * $item['quantity'];
        }, $cartWithData));
            
        // retourne la vue pour afficher le panier
        return [
            'cart' => $cartWithData, // on retourne ces deux variables afin de la récupérer ds la vue
            'total' => $total,
        ];
    
        }
        
}