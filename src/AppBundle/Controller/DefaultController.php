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
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
//          Consulta para los matriculados en el rango de fechas consultado  
            $sqlMat = "SELECT mem.matricula, mem.organizacion, mem.categoria, mem.muncom, mem.razonsocial, mem.fecmatricula, mem.fecrenovacion, mem.feccancelacion, mem.ultanoren  FROM mreg_est_matriculados mem  WHERE mem.fecmatricula between :fecIni AND :fecEnd  AND mem.estmatricula IN ('MA','MI','IA', 'MC') AND mem.matricula IS NOT NULL AND mem.matricula !='' ";
            
//          Consulta para las matriculas renovadas en el rango de fechas consultado  
            $sqlRen = "SELECT mem.matricula, mem.organizacion, mem.categoria, mem.muncom, mem.razonsocial, mem.fecmatricula, mem.fecrenovacion, mem.feccancelacion, mem.ultanoren FROM mreg_est_matriculados mem  WHERE mem.fecmatricula < :fecIni AND mem.fecrenovacion between :fecIni AND :fecEnd  AND mem.estmatricula IN ('MA','MI','IA') AND mem.matricula IS NOT NULL AND mem.matricula !='' AND mem.ultanoren='".$fechaInicial[0]."' ";
            
//          Consulta para las matriculas canceladas en el rango de fechas consultado
            $sqlCan = "SELECT mem.matricula, mem.organizacion, mem.categoria, mem.muncom, mem.razonsocial, mem.fecmatricula, mem.fecrenovacion, mem.feccancelacion, mem.ultanoren FROM mreg_est_matriculados mem  WHERE mem.feccancelacion between :fecIni AND :fecEnd AND mem.estmatricula IN ('MC','IC') AND mem.matricula IS NOT NULL AND mem.matricula !='' ";
            
            
            $params = array('fecIni'=>$fecIni , 'fecEnd' => $fecEnd);
            
//            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
            $stmt = $SIIem->getConnection()->prepare($sqlMat);
            $strv = $SIIem->getConnection()->prepare($sqlRen);
            $stcn = $SIIem->getConnection()->prepare($sqlCan);
            
//            Ejecución de las consultas
            $stmt->execute($params);
            $strv->execute($params);
            $stcn->execute($params);
            
            $tablaDetalle = " <table id='tablaDetalle' class='table table-hover table-striped table-bordered dt-responsive' cellspacing='0' width='100%'>
                            <thead>
                                <tr>
                                    <th>Matricula</th>
                                    <th>Organizacion</th>
                                    <th>Categoria</th>
                                    <th>Razón Social</th>
                                    <th>Municipio</th>
                                    <th>Estado</th>
                                    <th>Fecha Matricula</th>
                                    <th>Fecha Renovacion</th>
                                    <th>Fecha Cancelación</th>
                                    <th>UAR</th>
                                </tr>
                            </thead>
                            <tbody>";
            
//            Se invoca objeto constructor de las tablas resumen como parametros se envia el resultado de la consulta y la categoria Matriculados-Renovados-Cancelados 
            $tabla = new UtilitiesController();
            
            $resultadosMat = $stmt->fetchAll();
            $resumenMat = $tabla->construirTablaResumen($resultadosMat, 'matriculados',$tablaDetalle);
            $tablaMatri['matriculados'] = $resumenMat['tabla'];
            $tablaDetalle = $resumenMat['tablaDetalle'];
            
            $resultadosRen = $strv->fetchAll();
            $resumenRen = $tabla->construirTablaResumen($resultadosRen, 'renovados', $tablaDetalle);
            $tablaMatri['renovados'] = $resumenRen['tabla'];
            $tablaDetalle = $resumenRen['tablaDetalle'];
            
            $resultadosCan = $stcn->fetchAll();
            $resumenCan = $tabla->construirTablaResumen($resultadosCan, 'cancelados', $tablaDetalle);
            $tablaMatri['cancelados'] = $resumenCan['tabla'];
            $tablaDetalle = $resumenCan['tablaDetalle'];

            
            return new Response(json_encode(array('tablaMatri' => $tablaMatri , 'tablaDetalle' => $tablaDetalle )));
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
