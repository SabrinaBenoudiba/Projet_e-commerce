<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripePayment
{
    private $redirectUrl;

     public function __construct(){
        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);
        Stripe::setApiVersion('2025-07-30.basil');
     }

     public function startPayment($cart, $shippingCost, $orderId){
        //dd($cart)
        $cartProducts = $cart['cart'];
        $products = [
            [
                'qte' => 1,
                'price' => $shippingCost,
                'name' => "Frais de livraison"
            ]
        ];

        foreach ($cartProducts as $value) { // Boucle pour parcourir chaque produit du panier
            $productItem = [];  // Initialisation d'un tableau vide pour stocker les infos d'un produit
            $productItem['name'] = $value['product']->getName(); // Récupération du nom du produit
            $productItem['price'] = $value['product']->getPrice(); // Récupération du prix du produit
            $productItem['qte'] = $value['quantity']; // Récupération de la quantité du produit
            $products[] = $productItem; // Ajout du produit formaté au tableau des produits
        }

        $session = Session::create([ // Création de la session Stripe
            'line_items'=>[ // produits qui vont être payés
                array_map(fn(array $product)=> [
                    'quantity' => $product['qte'],
                    'price_data' => [
                        'currency' => 'Eur',
                        'product_data' => [
                            'name' => $product['name']
                        ],
                        'unit_amount' => $product['price']*100, // prix donné en centimes donc on multiplie par 100
                    ],   
                ],$products) 

            ],
            'mode' => 'payment', //mode de paiement
            'cancel_url' => 'http://127.0.0.1:8000/pay/cancel', //si paiement annulé on redirige ici
            'success_url' => 'http://127.0.0.1:8000/pay/success', //si paiement réussi
            'billing_address_collection' => 'required', //si on autorise les factures
            'shipping_address_collection' => [ //pays où on souhaite autoriser le paiement
                'allowed_countries' => ['FR'],
            ],
           'payment_intent_data' => [
                'metadata' => [
                    'orderid' =>$orderId//id de la commande
                ]
            ]
            
        ]); 

         $this->redirectUrl = $session->url; //redirection vers stripe pour le paiement

     }
     public function getStripeRedirectUrl(){ //permet de recuperer l'url de l'utilisateur pour stripe
        return $this->redirectUrl;
     }


}