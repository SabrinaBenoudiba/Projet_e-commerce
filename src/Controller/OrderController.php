<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\Cart;
use App\Service\StripePayment;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Contrôleur pour gérer les commandes
class OrderController extends AbstractController
{
    public function __construct(private MailerInterface $mailer){

    }

#region ORDER

    #[Route('/order', name: 'app_order')] // Déclare une route pour la création de commande
    public function index(EntityManagerInterface $entityManager, ProductRepository $productRepository, 
                         SessionInterface $session, Request $request, Cart $cart): Response
    {
        $data = $cart->getCart($session); // Récupère les données du panier depuis la session utilisateur (using le service Cart)         

       
        $order = new Order(); // Crée une nouvelle instance de la commande (de Order)

        // $order->setIsCompleted(false);

        // Génère le formulaire de commande lié à l'objet $order
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) { // Quand c'est true
            if(!empty($data['total'])){ // Vérifie si le total du panier n'est pas vide
                $totalPrice = $data['total'] + $order->getCity()->getShippingCost();
                // $shippingCost = $order->getCity()->getShippingCost(); // pour ajouter les frais de livraison avec le prix de la commande
                // $totalWithShipping = $data['total'] + $shippingCost;

                $order->setTotalPrice($totalPrice); // Définit le prix total de la commande
                $order->setCreatedAt(new \DateTimeImmutable()); // Définit la date de création de la commande à "maintenant"
                $order->setIsPaymentCompleted(0); //on initialise a false 
                //dd($order);
                $entityManager->persist($order); // Prépare la commande pour l'enregistrement en base de données
                $entityManager->flush(); // Enregistre la commande dans la base de données

                foreach($data['cart'] as $value) {  // Parcourt chaque article du panier pour l'enregistrer en tant que OrderProducts
                    $orderProduct = new OrderProducts(); // Crée un nouvel objet OrderProducts
                    $orderProduct->setOrder($order); // Lie l'objet OrderProducts à la commande courante
                    $orderProduct->setProduct($value['product']);// Lie le produit acheté
                    $orderProduct->setQuantity($value['quantity']);  // Spécifie la quantité pour ce produit
                    $entityManager->persist($orderProduct);
                    $entityManager->flush();// Enregistre chaque OrderProduct en base
                }
                if($order->isPayOnDelivery()){ // Vérifie si le mode de paiement est "paiement à la livraison"      
                    $session->set('cart', []); //Mise à jout du contenu du panier en session
                    $html = $this->renderView('mail/orderConfirm.html.twig',[
                        'order'=>$order  //on recupere le $order apres le flush donc on a toutes les infos
                    ]);
                    $email = (new Email())
                    ->from('monSite@gmail.com') // modifier le mail par celui du site !
                    // ->to ('toa@gmail.com')
                    ->to($order->getEmail())
                    ->subject ('Confirmation de réception de commande')
                    ->html($html);
                    $this->mailer->send($email);
                    return $this->redirectToRoute('app_order_message'); // Redirection vers la page du panier
                }

                // Quand c'est false
                $paymentStripe = new StripePayment(); 
                $shippingCost = $order->getCity()->getShippingCost();
                $paymentStripe->startPayment($data, $shippingCost, $order->getId()); // on importe le panier donc $data
                $stripeRedirectUrl = $paymentStripe->getStripeRedirectUrl();
                // dd( $stripeRedirectUrl);
                return $this->redirect($stripeRedirectUrl);
            }
        }
            return $this->render('order/index.html.twig', [ // Affiche le formulaire et le total du panier dans la vue "order/index.html.twig"
                'form' => $form->createView(),
                'total' => $data['total']
            ]);
        }
#endregion ORDER

#region MESSAGE
    #[Route('/order_message', name: 'app_order_message')]
    public function orderMessage() :Response 
    { 
        return  $this->render('order/order_message.html.twig');
    }
#endregion MESSAGE 

#region SHOW
    #[Route('editor/orders/{type}', name: 'app_orders_show')]
    #[IsGranted('ROLE_EDITOR')]
    public function getAllOrder($type, OrderRepository $orderRepository, Request $request, PaginatorInterface $paginator, ProductRepository $productRepository):Response
    {
        if($type == 'is-completed'){
             $data = $orderRepository->findBy(['isCompleted'=>1],['id'=>'DESC']);
        }else if($type == 'pay-on-stripe-not-delivered'){
            $data = $orderRepository->findBy(['isCompleted'=>null,'payOnDelivery'=>0,'isPaymentCompleted'=>1],['id'=>'DESC']);
        }else if($type == 'pay-on-stripe-is-delivered'){
            $data = $orderRepository->findBy(['isCompleted'=>1,'payOnDelivery'=>0,'isPaymentCompleted'=>1],['id'=>'DESC']);
        }else if($type == 'no_delivery'){
            $data = $orderRepository->findBy(['isCompleted'=>null,'payOnDelivery'=>0,'isPaymentCompleted'=>0],['id'=>'DESC']);
        }

        // $orders = $orderRepository->findAll();
        $orders = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),//met en place la pagination
            5 //je choisi la limite de 5 commandes par page
        );
        return $this->render('order/orders.html.twig',[
            'orders' => $orders,
        ]);
    }
#endregion SHOW

#region UPDATE
    #[Route('editor/orders/{id}/is-completed/update', name: 'app_orders_is-completed-update')]
    #[IsGranted('ROLE_EDITOR')]
    public function isCompletedUpdate(Request $request, $id, OrderRepository $orderRepository, EntityManagerInterface $entityManager):Response
    {
        $order = $orderRepository->find($id);

        $order->setIsCompleted(true);
        $entityManager->flush();
        $this->addflash('success', 'Modification effectuée');
        return $this->redirect($request->headers->get('referer'));//cela fait reference a la route precedent cette route ci
        // return $this->redirectToRoute('app_orders_show');
    }
#endregion UPDATE

#region REMOVE
    #[Route('/editor/orders/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $entityManager):Response 
    {
        $entityManager->remove($order);
        $entityManager->flush();
        $this->addFlash('danger', 'Commande supprimée');
        return $this->redirectToRoute('app_orders_show',['type']);
    }
#endregion REMOVE

#region SHIPPING COST
    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')] // Déclare une route pour obtenir le coût de livraison selon la ville choisie
    public function cityShippingCost(City $city): Response
    {
        $cityShippingPrice = $city->getShippingCost(); // Récupère le prix de livraison de la ville demandée
        
        return new Response(json_encode([  // Retourne la réponse au format JSON (utilisable par le JavaScript côté front)
            'status' => 200,
            "message" => 'on',
            'content' => $cityShippingPrice
        ]));
        // dd($city);  // (Ancienne ligne de debug)
    }
}
#endregion SHIPPING COST
