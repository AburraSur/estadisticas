<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        $router = $this->container->get('router');
        $rol = $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
        if($rol){
            return new RedirectResponse($router->generate('estadisticasGenerales'), 307);
        }else{
            return new RedirectResponse($router->generate('supervisores'), 307);
        }
    }
    
    /**
     * @Route("/estadisticasGenerales", name="estadisticasGenerales")
     */
    public function estadisticasGeneralesAction(Request $request)
    {
        
        $user = $this->get('security.token_storage')->getToken()->getUser();
        return $this->render('default/adminHome.html.twig',array('usuarioLogueado'=>$user));
    }
    
    /**
     * @Route("/supervisor", name="supervisores")
     */
    public function supervisorAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }
    
    
}
