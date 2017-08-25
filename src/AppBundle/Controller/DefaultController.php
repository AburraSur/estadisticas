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
            $sqlMat = "SELECT mem.matricula, mem.organizacion, mem.categoria, mem.muncom, mem.razonsocial, mem.fecmatricula, mem.fecrenovacion, mem.feccancelacion, mem.ultanoren  "
                    . "FROM mreg_est_matriculados mem  "
                    . "WHERE mem.fecmatricula between :fecIni AND :fecEnd  "
                    . "AND mem.estmatricula NOT IN ('NA','NM') "
                    . "AND mem.matricula IS NOT NULL "
                    . "AND mem.matricula !='' ";
            
//          Consulta para las matriculas renovadas en el rango de fechas consultado  
            $sqlRen = "SELECT mem.matricula, mem.organizacion, mem.categoria, mem.muncom, mem.razonsocial, mem.fecmatricula, mem.fecrenovacion, mem.feccancelacion, mem.ultanoren "
                    . "FROM mreg_est_matriculados mem  "
                    . "WHERE mem.fecmatricula < :fecIni "
                    . "AND mem.fecrenovacion between :fecIni AND :fecEnd  "
                    . "AND mem.matricula IS NOT NULL "
                    . "AND mem.matricula !='' "
                    . "AND mem.ultanoren ='".$fechaFinal[0]."' ";
            
//          Consulta para las matriculas canceladas en el rango de fechas consultado
            $sqlCan = "SELECT mem.matricula, mem.organizacion, mem.categoria, mem.muncom, mem.razonsocial, mem.fecmatricula, mem.fecrenovacion, mem.feccancelacion, mem.ultanoren "
                    . "FROM mreg_est_matriculados mem  "
                    . "INNER JOIN mreg_est_inscripciones mei "
                    . "WHERE mem.matricula = mei.matricula "
                    . "AND mei.fecharegistro between :fecIni AND :fecEnd "
                    //. "AND mem.estmatricula IN ('MC','IC','MF') "
                    . "AND mem.estmatricula IN ('MC','IC') "
                    . "AND mem.matricula IS NOT NULL "
                    . "AND mem.matricula !='' "
                    . "AND libro IN ('RM15' , 'RM51', 'RM53', 'RM54', 'RM55', 'RM13') "
                    . "AND acto IN ('0180' , '0530','0531','0532','0536','0520','0540','0498','0300')";
            
            
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
    
    /**
     * @Route("/extraeservicios", name="extraeservicios")
     */
    public function extraeserviciosAction(Request $request)
    {
        
        $SIIem =  $this->getDoctrine()->getManager('sii');
        
        if(isset($_POST['dateInit']) && isset($_POST['dateEnd'])){
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            $impServi = "'".implode("','",$_POST['servicio'])."'";
            
            //            Crear tabla con datos de los servicios consultados
            $tablaDetalle = " <table id='tablaDetalle' class='table table-hover table-striped table-bordered dt-responsive' cellspacing='0' width='100%'>
                            <thead>
                                <tr>
                                    <th>ID. Cliente</th>
                                    <th>Cliente</th>
                                    <th>Operador</th>
                                    <th>Operación</th>
                                    <th>ID. Servicio</th>
                                    <th>Servicio</th>
                                    <th>Cantidad</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>";
            $tablaTotales= " <table id='tablaTotales' class='table table-hover table-striped table-bordered dt-responsive' cellspacing='0' width='100%'>
                            <thead>
                                <tr>
                                    <th>ID. Servicio</th>
                                    <th>Servicio</th>
                                    <th>Cantidad</th>
                                    <th>Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>";
            
//          Consulta para los servicios seleccionados en el rango de fechas consultado  
            $sqlMat = "SELECT mrr.identificacion, mrr.nombre as 'Cliente', mrr.operador, mrr.numerooperacion,ms.nombre as 'Servicio',ms.idservicio, mrr.cantidad, mrr.valor "
                    . "FROM mreg_est_recibos mrr "
                    . "INNER JOIN mreg_servicios ms "
                    . "WHERE mrr.servicio = ms.idservicio "
                    . "AND mrr. fecoperacion BETWEEN :fecIni AND :fecEnd "
                    . "AND mrr.servicio IN ($impServi) "
                    . "AND mrr.cantidad > 0 "
                    . "ORDER BY idservicio ASC";
            
//          
            $params = array('fecIni'=>$fecIni , 'fecEnd' => $fecEnd );
            
//            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
            $stmt = $SIIem->getConnection()->prepare($sqlMat);
//            Ejecución de las consultas
            $stmt->execute($params);
            $resultadosServicios = $stmt->fetchAll();
           
//           Ciclo para crear contadores de valores y cantidades por servicio, se construye la tabla detalla con la información consultada 
            $idservAux = 0;
            for($i=0;$i<sizeof($resultadosServicios);$i++){
                if($idservAux == $resultadosServicios[$i]['idservicio']){
                    $cantServ[$idservAux] = $cantServ[$idservAux] + $resultadosServicios[$i]['cantidad'];
                    $totalServ[$idservAux] = $totalServ[$idservAux] + $resultadosServicios[$i]['valor'];
                }else{
                    if(isset($cantServ[$idservAux])){
                        $tablaTotales.= " <tr>
                                            <td>".$idservAux."</td>
                                            <td>".$servicio."</td>
                                            <td>".number_format($cantServ[$idservAux])."</td>
                                            <td>$".number_format($totalServ[$idservAux])."</td>
                                        </tr>";
                    }
                    $cantServ[$resultadosServicios[$i]['idservicio']] = $resultadosServicios[$i]['cantidad'];
                    $totalServ[$resultadosServicios[$i]['idservicio']] = $resultadosServicios[$i]['valor'];
                    $idservAux = $resultadosServicios[$i]['idservicio'];
                    $servicio = $resultadosServicios[$i]['Servicio'];
                }
                
                $tablaDetalle.= " <tr>
                                    <td>".$resultadosServicios[$i]['identificacion']."</td>
                                    <td>".$resultadosServicios[$i]['Cliente']."</td>
                                    <td>".$resultadosServicios[$i]['operador']."</td>
                                    <td>".$resultadosServicios[$i]['numerooperacion']."</td>
                                    <td>".$resultadosServicios[$i]['idservicio']."</td>
                                    <td>".$resultadosServicios[$i]['Servicio']."</td>
                                    <td>".number_format($resultadosServicios[$i]['cantidad'])."</td>
                                    <td>$".number_format($resultadosServicios[$i]['valor'])."</td>
                                </tr>";
            }
           
            $tablaDetalle.= " </tbody></table>";
            if(sizeof($resultadosServicios)>0){
                $tablaTotales.= " <tr>
                                        <td>".$idservAux."</td>
                                        <td>".$servicio."</td>
                                        <td>".number_format($cantServ[$idservAux])."</td>
                                        <td>$".number_format($totalServ[$idservAux])."</td>
                                    </tr>";
            }
            $tablaTotales.= "</tbody></table>";
            return new Response(json_encode(array('tablaTotales' => $tablaTotales , 'tablaDetalle' => $tablaDetalle )));
        }else{
            $sqlServ = "SELECT sv.idservicio, sv.nombre FROM mreg_servicios sv WHERE nombre!='' ";
            $prepareServ = $SIIem->getConnection()->prepare($sqlServ);
            $prepareServ->execute();
            $servicios =  $prepareServ->fetchAll();
            return $this->render('default/extraccionServicios.html.twig',array('Servicios' => $servicios));
        }
    }
    
    
}
