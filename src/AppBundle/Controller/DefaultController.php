<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use AppBundle\Controller\UtilitiesController;

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
            
//          Consulta para los matriculados en el rango de fechas consultado  
            $sqlMat = "SELECT mem.matricula, mem.organizacion, mem.categoria, bm.ciudad FROM mreg_est_matriculados mem INNER JOIN bas_municipios bm WHERE mem.fecmatricula between :fecIni AND :fecEnd AND bm.codigomunicipio=mem.muncom AND mem.estmatricula IN ('MA','MI','IA') AND mem.matricula IS NOT NULL AND mem.matricula !='' ";
            
//          Consulta para las matriculas renovadas en el rango de fechas consultado  
            $sqlRen = "SELECT mem.matricula, mem.organizacion, mem.categoria, bm.ciudad FROM mreg_est_matriculados mem INNER JOIN bas_municipios bm WHERE mem.fecmatricula < :fecIni AND mem.fecrenovacion between :fecIni AND :fecEnd AND bm.codigomunicipio=mem.muncom AND mem.estmatricula IN ('MA','MI','IA') AND mem.matricula IS NOT NULL AND mem.matricula !='' ";
            
//          Consulta para las matriculas canceladas en el rango de fechas consultado
            $sqlCan = "SELECT mem.matricula, mem.organizacion, mem.categoria, bm.ciudad FROM mreg_est_matriculados mem INNER JOIN bas_municipios bm WHERE mem.feccancelacion between :fecIni AND :fecEnd AND bm.codigomunicipio=mem.muncom AND mem.estmatricula = 'MC' AND mem.matricula IS NOT NULL AND mem.matricula !='' ";
            
            
            $params = array('fecIni'=>$fecIni , 'fecEnd' => $fecEnd);
            
//            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
            $stmt = $SIIem->getConnection()->prepare($sqlMat);
            $strv = $SIIem->getConnection()->prepare($sqlRen);
            $stcn = $SIIem->getConnection()->prepare($sqlCan);
            
//            Ejecución de las consultas
            $stmt->execute($params);
            $strv->execute($params);
            $stcn->execute($params);
            
//            Se invoca objeto constructor de las tablas resumen como parametros se envia el resultado de la consulta y la categoria Matriculados-Renovados-Cancelados 
            $tabla = new UtilitiesController();
            
            $resultadosMat = $stmt->fetchAll();
            $tablaMatri['matriculados'] = $tabla->construirTablaResumen($resultadosMat, 'matriculados');
            
            $resultadosRen = $strv->fetchAll();
            $tablaMatri['renovados'] = $tabla->construirTablaResumen($resultadosRen, 'renovados');
            
            $resultadosCan = $stcn->fetchAll();
            $tablaMatri['cancelados'] = $tabla->construirTablaResumen($resultadosCan, 'cancelados');

            
            return new Response(json_encode(array('tablaMatri' => $tablaMatri )));
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
