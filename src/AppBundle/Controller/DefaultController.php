<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        if(isset($_POST['dateInit']) && isset($_POST['dateEnd'])){
            $SIIem =  $this->getDoctrine()->getManager('sii');
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
//            $consulta = $SIIem->createQuery("SELECT * FROM mreg_est_matriculados WHERE fecmatricula between :fecIni AND :fecEnd  ")
//                    ->setParameter('fecIni',$fecIni)
//                    ->setParameter('fecEnd',$fecEnd);
//            $rowConsulta = $consulta->getResult();
            $sql = "SELECT * FROM mreg_est_matriculados WHERE fecmatricula between :fecIni AND :fecEnd";
            $params = array('fecIni'=>$fecIni , 'fecEnd' => $fecEnd);
            $stmt = $SIIem->getConnection()->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            for($i=0;$i<sizeof($results);$i++){
                if(isset($arreglo[$results[$i]['muncom']][$results[$i]['organizacion']])){
                    $arreglo[$results[$i]['muncom']][$results[$i]['organizacion']] = $arreglo[$results[$i]['muncom']][$results[$i]['organizacion']]+1;
                }else{
                    $arreglo[$results[$i]['muncom']][$results[$i]['organizacion']] = 1;
                }
            }
            
            
            return new Response(json_encode(array('fechaIni' => $fecIni , 'fechaFin' => $fecEnd , 'arreglo' => $arreglo)));
        }else{
            return $this->render('default/estadisticasGenerales.html.twig');
        }
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
