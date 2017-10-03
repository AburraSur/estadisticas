<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
            return new RedirectResponse($router->generate('estadisticasGenerales'), 307);
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
            $totalMatRen = 0;
            $resultadosMat = $stmt->fetchAll();
            $resumenMat = $tabla->construirTablaResumen($resultadosMat, 'matriculados',$tablaDetalle);
            $tablaMatri['matriculados'] = $resumenMat['tabla'];
            $tablaDetalle = $resumenMat['tablaDetalle'];
            $totalMatRen = $totalMatRen+$resumenMat['granTotal'];
            
            $resultadosRen = $strv->fetchAll();
            $resumenRen = $tabla->construirTablaResumen($resultadosRen, 'renovados', $tablaDetalle);
            $tablaMatri['renovados'] = $resumenRen['tabla'];
            $tablaDetalle = $resumenRen['tablaDetalle'];
            $totalMatRen = $totalMatRen + $resumenRen['granTotal'];
                    
            $resultadosCan = $stcn->fetchAll();
            $resumenCan = $tabla->construirTablaResumen($resultadosCan, 'cancelados', $tablaDetalle);
            $tablaMatri['cancelados'] = $resumenCan['tabla'];
            $tablaDetalle = $resumenCan['tablaDetalle'];

            
//            return new Response(json_encode(array('tablaMatri' => $tablaMatri , 'tablaDetalle' => $tablaDetalle )));
            return new Response(json_encode(array('tablaMatri' => $tablaMatri , 'totalMatRen' => number_format($totalMatRen,"0","",".") )));
        }else{
            return $this->render('default/estadisticasGenerales.html.twig');
        }
    }
    
     /**
     * @Route("/tabladetalle", name="tabladetalle")
     */
    public function tabladetalleAction(Request $request)
    {
        $SIIem =  $this->getDoctrine()->getManager('sii');
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            $columns = array( 
            // datatable column index  => database column name
                    0 =>'matricula', 
                    1 => 'organizacion',
                    2=> 'categoria',
                    3=>'muncom',
                    4=>'razonsocial',
                    5=> 'estado',
                    6=>'fecmatricula',
                    7=>'fecrenovacion',
                    8=>'feccancelacion',
                    9=>'ultanoren',
            );
            
//          Consulta para los matriculados en el rango de fechas consultado  
            $sqlMatT = $sqlMat = "SELECT mem.matricula, mem.organizacion, mem.categoria, mem.muncom, mem.razonsocial, mem.fecmatricula, mem.fecrenovacion, mem.feccancelacion, mem.ultanoren  "
                    . "FROM mreg_est_matriculados mem  "
                    . "WHERE mem.fecmatricula between :fecIni AND :fecEnd  "
                    . "AND mem.estmatricula NOT IN ('NA','NM') "
                    . "AND mem.matricula IS NOT NULL "
                    . "AND mem.matricula !='' ";
            
//          Consulta para las matriculas renovadas en el rango de fechas consultado  
            $sqlRenT = $sqlRen = "SELECT mem.matricula, mem.organizacion, mem.categoria, mem.muncom, mem.razonsocial, mem.fecmatricula, mem.fecrenovacion, mem.feccancelacion, mem.ultanoren "
                    . "FROM mreg_est_matriculados mem  "
                    . "WHERE mem.fecmatricula < :fecIni "
                    . "AND mem.fecrenovacion between :fecIni AND :fecEnd  "
                    . "AND mem.matricula IS NOT NULL "
                    . "AND mem.matricula !='' "
                    . "AND mem.ultanoren ='".$fechaFinal[0]."' ";
            
//          Consulta para las matriculas canceladas en el rango de fechas consultado
            $sqlCanT = $sqlCan = "SELECT mem.matricula, mem.organizacion, mem.categoria, mem.muncom, mem.razonsocial, mem.fecmatricula, mem.fecrenovacion, mem.feccancelacion, mem.ultanoren "
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
            
            $stmtT = $SIIem->getConnection()->prepare($sqlMatT);
            $strvT = $SIIem->getConnection()->prepare($sqlRenT);
            $stcnT = $SIIem->getConnection()->prepare($sqlCanT);
            
            $params = array('fecIni'=>$fecIni , 'fecEnd' => $fecEnd);
            
//            Ejecución de las consultas
            $stmtT->execute($params);
            $strvT->execute($params);
            $stcnT->execute($params);
            
            $matT= $stmtT->fetchAll();
            $renT = $strvT->fetchAll();
            $canT = $stcnT->fetchAll();
            
            
        if($_POST['excel']==1){
            $totalFiltered = 0;
            $codMuni = array("05129"=>"CALDAS","05266"=>"ENVIGADO","05360"=>"ITAGUI","05380"=>"LA ESTRELLA","05631"=>"SABANETA","otroDom" => "otroDomicilio"); 
            for($i=0;$i< sizeof($matT);$i++){
                $excelData=array();
                if(isset($matT[$i]["matricula"])){                    
                    $excelData[] = $matT[$i]["matricula"];
                    $excelData[] = $matT[$i]["organizacion"];
                    $excelData[] = $matT[$i]["categoria"];
                    $posMuni=$matT[$i]["muncom"];
                    if(array_key_exists($posMuni, $codMuni)){
                        $excelData[] = $codMuni[$posMuni];
                    }else{
                        $excelData[] = "****".$posMuni."**";
                    }
                    //$excelData[] = $codMuni[$matT[$i]["muncom"]];

                    $excelData[] = $matT[$i]["razonsocial"];
                    $excelData[] = 'MATRICULADO';
                    $excelData[] = $matT[$i]["fecmatricula"];
                    $excelData[] = $matT[$i]["fecrenovacion"];
                    $excelData[] = $matT[$i]["feccancelacion"];
                    $excelData[] = $matT[$i]["ultanoren"];

                    $excelReg[] = $excelData;
                    $totalFiltered++;
                }
            }
            
            for($i=0;$i<sizeof($renT);$i++){
                $excelData=array();
                if(isset($renT[$i]["matricula"])){
                    $excelData[] = $renT[$i]["matricula"];
                    $excelData[] = $renT[$i]["organizacion"];
                    $excelData[] = $renT[$i]["categoria"];
                    $posMuni=$renT[$i]["muncom"];
                    if(array_key_exists($posMuni, $codMuni)){
                        $excelData[] = $codMuni[$posMuni];
                    }else{
                        $excelData[] = $renT[$i]["muncom"];
                    }
                    $excelData[] = $renT[$i]["razonsocial"];
                    $excelData[] = 'RENOVADO';
                    $excelData[] = $renT[$i]["fecmatricula"];
                    $excelData[] = $renT[$i]["fecrenovacion"];
                    $excelData[] = $renT[$i]["feccancelacion"];
                    $excelData[] = $renT[$i]["ultanoren"];

                    $excelReg[] = $excelData;
                    $totalFiltered++;
                }    
            }
            
            for($i=0;$i<sizeof($canT);$i++){
                $excelData=array();
                if(isset($canT[$i]["matricula"])){
                    $excelData[] = $canT[$i]["matricula"];
                    $excelData[] = $canT[$i]["organizacion"];
                    $excelData[] = $canT[$i]["categoria"];
                    $posMuni=$canT[$i]["muncom"];
                    if(array_key_exists($posMuni, $codMuni)){
                        $excelData[] = $codMuni[$posMuni];
                    }else{
                        $excelData[] = $canT[$i]["muncom"];
                    }
                    $excelData[] = $canT[$i]["razonsocial"];
                    $excelData[] = 'CANCELADO';
                    $excelData[] = $canT[$i]["fecmatricula"];
                    $excelData[] = $canT[$i]["fecrenovacion"];
                    $excelData[] = $canT[$i]["feccancelacion"];
                    $excelData[] = $canT[$i]["ultanoren"];

                    $excelReg[] = $excelData;
                    $totalFiltered++;
                }    
            }
            $response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$excelReg, 'columnas'=>$columns));
            return $response;
        }else{  
            $t1 = sizeof($matT);
            $t2 = sizeof($renT);
            $t3 = sizeof($canT);
            
            $totalFiltered = $totalData = $t1 + $t2 + $t3;
            
            if( !empty($_POST['columns'][1]['search']['value']) ) {   // if there is a search parameter, $_POST['search']['value'] contains search parameter
                $sqlMat.=" AND mem.muncom = '".$_POST['columns'][1]['search']['value']."' ";
                $sqlRen.=" AND mem.muncom = '".$_POST['columns'][1]['search']['value']."' ";
                $sqlCan.=" AND mem.muncom = '".$_POST['columns'][1]['search']['value']."' ";

            }
            
            $params = array('fecIni'=>$fecIni , 'fecEnd' => $fecEnd);

//            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
            $stmt = $SIIem->getConnection()->prepare($sqlMat);
            $strv = $SIIem->getConnection()->prepare($sqlRen);
            $stcn = $SIIem->getConnection()->prepare($sqlCan);
            
//            Ejecución de las consultas
            $stmt->execute($params);
            $strv->execute($params);
            $stcn->execute($params);
            
            
            $data = array();
            $todosReg = array();
            
            $matriculadosT = $stmt->fetchAll();
            $renovadosT = $strv->fetchAll();
            $canceladosT = $stcn->fetchAll();
            
            $tf1 = sizeof($matriculadosT);
            $tf2 = sizeof($renovadosT);
            $tf3 = sizeof($canceladosT);
            

            
            
//            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
            $stmt = $SIIem->getConnection()->prepare($sqlMat);
            $strv = $SIIem->getConnection()->prepare($sqlRen);
            $stcn = $SIIem->getConnection()->prepare($sqlCan);
            
            if($_POST['columns'][0]['search']['value'] == 'MATRICULADO'){
                $stmt->execute($params);
                $matriculados = $stmt->fetchAll();
                $renovados = 0;
                $cancelados = 0;
            }elseif($_POST['columns'][0]['search']['value'] == 'RENOVADO'){
                $strv->execute($params);
                $matriculados = 0;
                $renovados = $strv->fetchAll();
                $cancelados = 0;
            }elseif($_POST['columns'][0]['search']['value'] == 'CANCELADO'){
                $stcn->execute($params);
                $matriculados = 0;
                $renovados = 0;
                $cancelados = $stcn->fetchAll();
            }else{
//            Ejecución de las consultas
                $stmt->execute($params);
                $strv->execute($params);
                $stcn->execute($params);

                $matriculados = $stmt->fetchAll();
                $renovados = $strv->fetchAll();
                $cancelados = $stcn->fetchAll();
            }
  
            $totalFiltered = 0;
            $codMuni = array("05129"=>"CALDAS","05266"=>"ENVIGADO","05360"=>"ITAGUI","05380"=>"LA ESTRELLA","05631"=>"SABANETA","otroDom" => "otroDomicilio"); 
            for($i=0;$i< sizeof($matriculados);$i++){
                $nestedData=array();
                if(isset($matriculados[$i]["matricula"])){                    
                    $nestedData[] = $matriculados[$i]["matricula"];
                    $nestedData[] = $matriculados[$i]["organizacion"];
                    $nestedData[] = $matriculados[$i]["categoria"];
                    $posMuni=$matriculados[$i]["muncom"];
                    if(array_key_exists($posMuni, $codMuni)){
                        $nestedData[] = $codMuni[$posMuni];
                    }else{
                        $nestedData[] = "****".$posMuni."**";
                    }
                    //$nestedData[] = $codMuni[$matriculados[$i]["muncom"]];

                    $nestedData[] = $matriculados[$i]["razonsocial"];
                    $nestedData[] = 'MATRICULADO';
                    $nestedData[] = $matriculados[$i]["fecmatricula"];
                    $nestedData[] = $matriculados[$i]["fecrenovacion"];
                    $nestedData[] = $matriculados[$i]["feccancelacion"];
                    $nestedData[] = $matriculados[$i]["ultanoren"];

                    $todosReg[] = $nestedData;
                    $totalFiltered++;
                }
            }
            
            for($i=0;$i<sizeof($renovados);$i++){
                $nestedData=array();
                if(isset($renovados[$i]["matricula"])){
                    $nestedData[] = $renovados[$i]["matricula"];
                    $nestedData[] = $renovados[$i]["organizacion"];
                    $nestedData[] = $renovados[$i]["categoria"];
                    $posMuni=$renovados[$i]["muncom"];
                    if(array_key_exists($posMuni, $codMuni)){
                        $nestedData[] = $codMuni[$posMuni];
                    }else{
                        $nestedData[] = $renovados[$i]["muncom"];
                    }
                    $nestedData[] = $renovados[$i]["razonsocial"];
                    $nestedData[] = 'RENOVADO';
                    $nestedData[] = $renovados[$i]["fecmatricula"];
                    $nestedData[] = $renovados[$i]["fecrenovacion"];
                    $nestedData[] = $renovados[$i]["feccancelacion"];
                    $nestedData[] = $renovados[$i]["ultanoren"];

                    $todosReg[] = $nestedData;
                    $totalFiltered++;
                }    
            }
            
            for($i=0;$i<sizeof($cancelados);$i++){
                $nestedData=array();
                if(isset($cancelados[$i]["matricula"])){
                    $nestedData[] = $cancelados[$i]["matricula"];
                    $nestedData[] = $cancelados[$i]["organizacion"];
                    $nestedData[] = $cancelados[$i]["categoria"];
                    $posMuni=$cancelados[$i]["muncom"];
                    if(array_key_exists($posMuni, $codMuni)){
                        $nestedData[] = $codMuni[$posMuni];
                    }else{
                        $nestedData[] = $cancelados[$i]["muncom"];
                    }
                    $nestedData[] = $cancelados[$i]["razonsocial"];
                    $nestedData[] = 'CANCELADO';
                    $nestedData[] = $cancelados[$i]["fecmatricula"];
                    $nestedData[] = $cancelados[$i]["fecrenovacion"];
                    $nestedData[] = $cancelados[$i]["feccancelacion"];
                    $nestedData[] = $cancelados[$i]["ultanoren"];

                    $todosReg[] = $nestedData;
                    $totalFiltered++;
                }    
            }
            
            for($i=$_POST['start'];$i<($_POST['start']+$_POST['length']);$i++){
               if(isset( $todosReg[$i])){
                    $data[] = $todosReg[$i];
               }else{
                   $i=($_POST['start']+$_POST['length']);
               }
            }
            
            $json_data = array(
			"draw"            => intval( $_POST['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
			"recordsTotal"    => intval( $totalData ),  // total number of records
			"recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
			"data"            => $data   // total data array
			);

//            echo json_encode($json_data);
            
            
            return new Response(json_encode($json_data ));
        }
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

            $tablaTotales= " <table id='tablaTotales' class='table table-hover table-striped table-bordered dt-responsive' cellspacing='0' width='100%'>
                            <thead>
                                <tr>
                                    <th>ID. Servicio</th>
                                    <th>Servicio</th>
                                    <th>Cantidad Registros</th>
                                    <th>Cantidad</th>
                                    <th>Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>";
            
//          Consulta para los servicios seleccionados en el rango de fechas consultado  
            $sqlMat = "SELECT recibos.identificacion, recibos.nombre as 'Cliente', recibos.operador, recibos.numerooperacion,servicios.nombre as 'Servicio',servicios.idservicio, recibos.cantidad, recibos.valor, recibos.ctranulacion "
                    . "FROM mreg_est_recibos recibos "
                    . "INNER JOIN mreg_servicios servicios "
                    . "WHERE recibos.servicio = servicios.idservicio "
                    . "AND recibos. fecoperacion BETWEEN :fecIni AND :fecEnd "
                    . "AND recibos.servicio IN ($impServi) "
                    . "AND recibos.cantidad > 0 "
                    . "AND recibos.ctranulacion ='0' "
                    . "AND recibos.tipogasto IN ('0','8')"
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
                    $cantRegistros[$idservAux]=$cantRegistros[$idservAux]+1;
                }else{
                    if(isset($cantServ[$idservAux])){
                        $tablaTotales.= " <tr>
                                            <td>".$idservAux."</td>
                                            <td>".$servicio."</td>
                                            <td>".number_format($cantRegistros[$idservAux],"0","",".")."</td>
                                            <td>".number_format($cantServ[$idservAux],"0","",".")."</td>    
                                            <td>$".number_format($totalServ[$idservAux],"0","",".")."</td>
                                        </tr>";
                    }
                    $cantServ[$resultadosServicios[$i]['idservicio']] = $resultadosServicios[$i]['cantidad'];
                    $totalServ[$resultadosServicios[$i]['idservicio']] = $resultadosServicios[$i]['valor'];
                    $idservAux = $resultadosServicios[$i]['idservicio'];
                    $servicio = $resultadosServicios[$i]['Servicio'];
                    $cantRegistros[$idservAux]=1;
                }
                
            }
           
            if(sizeof($resultadosServicios)>0){
                $tablaTotales.= " <tr>
                                        <td>".$idservAux."</td>
                                        <td>".$servicio."</td>
                                        <td>".number_format($cantRegistros[$idservAux],"0","",".")."</td>
                                        <td>".number_format($cantServ[$idservAux],"0","",".")."</td>
                                        <td>$".number_format($totalServ[$idservAux],"0","",".")."</td>
                                    </tr>";
            }
            $tablaTotales.= "</tbody></table>";
//            return new Response(json_encode(array('tablaTotales' => $tablaTotales , 'tablaDetalle' => $tablaDetalle )));
            return new Response(json_encode(array('tablaTotales' => $tablaTotales )));
        }else{
            $sqlServ = "SELECT sv.idservicio, sv.nombre FROM mreg_servicios sv WHERE nombre!='' ";
            $prepareServ = $SIIem->getConnection()->prepare($sqlServ);
            $prepareServ->execute();
            $servicios =  $prepareServ->fetchAll();
            return $this->render('default/extraccionServicios.html.twig',array('Servicios' => $servicios));
        }
    }
    
    /**
     * @Route("/extraeserviciosDetalle", name="extraeserviciosDetalle")
     */
    public function extraeserviciosDetalleAction(Request $request)
    {
        
        $SIIem =  $this->getDoctrine()->getManager('sii');
        
        $sedes = new UtilitiesController();
        $listaSedes = $sedes->sedes($SIIem);
        
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            $impServi = "'".implode("','",$_POST['servicio'])."'";
            
            $columns = array(0=>'identificacion', 1=>'Cliente', 2=>'operador', 3=>'numerooperacion',4=>'Servicio',5=>'idservicio', 6=>'cantidad', 7=>'valor');
            
//          Consulta para los servicios seleccionados en el rango de fechas consultado  
            $sqlMat = "SELECT recibos.identificacion, recibos.nombre as 'Cliente', recibos.operador, recibos.numerooperacion,servicios.nombre as 'Servicio',servicios.idservicio, recibos.cantidad, recibos.valor "
                    . "FROM mreg_est_recibos recibos "
                    . "INNER JOIN mreg_servicios servicios "
                    . "WHERE recibos.servicio = servicios.idservicio "
                    . "AND recibos. fecoperacion BETWEEN :fecIni AND :fecEnd "
                    . "AND recibos.servicio IN ($impServi) "
                    . "AND recibos.ctranulacion ='0' "
                    . "AND recibos.tipogasto IN ('0','8')"
                    . "AND recibos.cantidad > 0 ";
            
            $params = array('fecIni'=>$fecIni , 'fecEnd' => $fecEnd );
            $stmt = $SIIem->getConnection()->prepare($sqlMat);
            $stmt->execute($params);
            $resultadosServicios = $stmt->fetchAll();
            $totalFiltered = $totalData = sizeof($resultadosServicios);
            
            if($_POST['excel']==1){
                $response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$resultadosServicios , 'columnas'=>$columns));
                return $response;
            }else{
                if( !empty($_POST['search']['value']) ) {   // if there is a search parameter, $_POST['search']['value'] contains search parameter
                        $sqlMat.=" AND ( recibos.identificacion LIKE '".$_POST['search']['value']."%' ";    
                        $sqlMat.=" OR recibos.nombre LIKE '".$_POST['search']['value']."%' ";
                        $sqlMat.=" OR recibos.operador LIKE '".$_POST['search']['value']."%' ";
                        $sqlMat.=" OR recibos.numerooperacion LIKE '".$_POST['search']['value']."%' ";
                        $sqlMat.=" OR servicios.nombre LIKE '".$_POST['search']['value']."%' ";
                        $sqlMat.=" OR servicios.idservicio LIKE '".$_POST['search']['value']."%' ";
                        $sqlMat.=" OR recibos.cantidad LIKE '".$_POST['search']['value']."%' ";
                        $sqlMat.=" OR recibos.valor LIKE '".$_POST['search']['value']."%' )";
                }

                $stmt = $SIIem->getConnection()->prepare($sqlMat);
    //            Ejecución de las consultas
                $stmt->execute($params);
                $resultadosServicios = $stmt->fetchAll();
                $totalFiltered = sizeof($resultadosServicios);

                $sqlMat.=" ORDER BY idservicio ASC LIMIT ".$_POST['start']." ,".$_POST['length']."   ";


    //            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
                $stmt = $SIIem->getConnection()->prepare($sqlMat);
    //            Ejecución de las consultas
                $stmt->execute($params);
                $resultadosServicios = $stmt->fetchAll();

    //           Ciclo para crear contadores de valores y cantidades por servicio, se construye la tabla detalla con la información consultada 
                $idservAux = 0;
                for($i=0;$i<sizeof($resultadosServicios);$i++){
                    $nestedData=array();
                    $nestedData[] = $resultadosServicios[$i]['identificacion'];
                    $nestedData[] = $resultadosServicios[$i]['Cliente'];
                    $nestedData[] = substr($resultadosServicios[$i]['numerooperacion'], 2,3);                    
                    $sucursal = substr($resultadosServicios[$i]['numerooperacion'], 0,2);
                    $nestedData[] = $listaSedes[$sucursal];
                    $nestedData[] = $resultadosServicios[$i]['numerooperacion'];
                    $nestedData[] = $resultadosServicios[$i]['idservicio'];
                    $nestedData[] = $resultadosServicios[$i]['Servicio'];
                    $nestedData[] = number_format($resultadosServicios[$i]['cantidad'],"0","",".");
                    $nestedData[] = number_format($resultadosServicios[$i]['valor'],"0","",".");

                    $data[] = $nestedData;
                }           

                $json_data = array(
                            "draw"            => intval( $_POST['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
                            "recordsTotal"    => intval( $totalData ),  // total number of records
                            "recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
                            "data"            => $data ,  // total data array
                            "i"               => $i  
                            );

    //            echo json_encode($json_data);
            
            
                return new Response(json_encode($json_data ));
            }    
            
        
    }

    
    /**
     * @Route("/extracionLibros", name="extracionLibros")
     */
    public function extracionLibrosAction(Request $request)
    {
        
        $SIIem =  $this->getDoctrine()->getManager('sii');
        
        if(isset($_POST['dateInit']) && isset($_POST['dateEnd'])){
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            foreach ($_POST['libroActo'] as $value) {
                $registro = explode("-", $value);
                $libros[$registro[0]][] = $registro[1];
            }
            
            
            //$impServi = "'".implode("','",$_POST['libroActo'])."'";

            $tablaTotales= " <table id='tablaTotales' class='table table-hover table-striped table-bordered dt-responsive' cellspacing='0' width='100%'>
                            <thead>
                                <tr>
                                    <th>ID Libro</th>
                                    <th>Libro</th>
                                    <th>ID Acto</th>
                                    <th>Acto</th>
                                    <th>Total Actos</th>
                                </tr>
                            </thead>
                            <tbody>";
            
//          Consulta para los servicios seleccionados en el rango de fechas consultado  
            $sqlMat = "SELECT libro,acto ,COUNT(acto) as 'totalActos' FROM mreg_inscripciones WHERE fecha BETWEEN :fecIni AND :fecEnd ";
            
//          
            $params = array('fecIni'=>$fecIni , 'fecEnd' => $fecEnd );
            $sw=0;
            foreach ($libros as $key => $value) {
                $actos = implode("','", $value);
                if($sw==0){
                    $sqlMat.="AND (libro='$key' AND acto IN('$actos')) ";
                    $sw++;
                }else{
                    $sqlMat.="OR (libro='$key' AND acto IN('$actos')) ";
                }
                
                
            }
            
            $sqlMat.="GROUP BY acto";
//            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
            $stLibros = $SIIem->getConnection()->prepare($sqlMat);
//            Ejecución de las consultas
            $stLibros->execute($params);
            $resultadoLibros = $stLibros->fetchAll();
            
            for($i=0;$i<sizeof($resultadoLibros);$i++){
                $sqlLibroActos = "SELECT libro.nombre as 'nomLibro', acto.nombre as 'nomActo' FROM mreg_libros libro INNER JOIN mreg_actos acto WHERE libro.idlibro=acto.idlibro AND acto.idacto='".$resultadoLibros[$i]['acto']."' AND libro.idlibro='".$resultadoLibros[$i]['libro']."' ";
                $stnombres = $SIIem->getConnection()->prepare($sqlLibroActos);
                $stnombres->execute();
                $nombreLibroActo = $stnombres->fetchAll();
                $tablaTotales.= "<tr>"
                        . "<td>".$resultadoLibros[$i]['libro']."</td>"
                        . "<td>".$nombreLibroActo[0]['nomLibro']."</td>"
                        . "<td>".$resultadoLibros[$i]['acto']."</td>"
                        . "<td>".$nombreLibroActo[0]['nomActo']."</td>"
                        . "<td>".$resultadoLibros[$i]['totalActos']."</td>";
            }
           
//           
            $tablaTotales.= "</tbody></table>";
            return new Response(json_encode(array('tablaTotales' => $tablaTotales , 'libros'=>$libros , 'sql'=>$sqlMat)));
        }else{
            $sqlLibros = "SELECT libros.idlibro, libros.nombre FROM mreg_libros libros";
            $prepareServ = $SIIem->getConnection()->prepare($sqlLibros);
            $prepareServ->execute();
            $libros =  $prepareServ->fetchAll();
            return $this->render('default/extraccionLibros.html.twig',array('libros' => $libros));
        }
    }
    
    /**
     * @Route("/extraeLibroActosDetalle", name="extraeLibroActosDetalle")
     */
    public function extraeLibroActosDetalleAction(Request $request)
    {
        
        $SIIem =  $this->getDoctrine()->getManager('sii');
        
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            foreach ($_POST['libroActo'] as $value) {
                $registro = explode("-", $value);
                $libros[$registro[0]][] = $registro[1];
            }
            
//          Consulta para los servicios seleccionados en el rango de fechas consultado  
            $sqlMat = "SELECT 
                            inscrip.id,
                            inscrip.fecha,
                            inscrip.matricula,
                            matriculados.numid AS 'identificacion',    
                            matriculados.razonsocial AS 'comerciante',
                            inscrip.noticia,
                            inscrip.operador,
                            inscrip.numerooperacion,
                            actos.nombre AS 'acto',
                            libros.nombre AS 'libro'
                        FROM
                            mreg_inscripciones inscrip
                                LEFT JOIN
                            mreg_actos actos ON inscrip.acto=actos.idacto
                                LEFT JOIN
                            mreg_libros libros ON inscrip.libro=libros.idlibro
                                LEFT JOIN
                            mreg_est_matriculados matriculados ON inscrip.matricula=matriculados.matricula
                       WHERE inscrip.fecha BETWEEN :fecIni AND :fecEnd ";
            
//          
            $params = array('fecIni'=>$fecIni , 'fecEnd' => $fecEnd );
            $sw=0;
            foreach ($libros as $key => $value) {
                $actos = implode("','", $value);
                if($sw==0){
                    $sqlMat.="AND (inscrip.libro='$key' AND inscrip.acto IN('$actos')) ";
                    $sw++;
                }else{
                    $sqlMat.="OR (inscrip.libro='$key' AND inscrip.acto IN('$actos')) ";
                }
                
                
            }
            
            $sqlMat.=" GROUP BY inscrip.id ";
//            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
            $stLibros = $SIIem->getConnection()->prepare($sqlMat);
//            Ejecución de las consultas
            $stLibros->execute($params);
            $resultadoLibros = $stLibros->fetchAll();
            $totalData = $totalFiltered = sizeof($resultadoLibros);
                       
            
            if($_POST['excel']==1){
                $response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$resultadoLibros , 'columnas'=>$columns));
                return $response;
            }else{
                if( !empty($_POST['search']['value']) ) {   // if there is a search parameter, $_POST['search']['value'] contains search parameter
                        $sqlMat.=" AND ( inscrip.fecha LIKE '".$_POST['search']['value']."%' ";    
                        $sqlMat.=" OR inscrip.matricula LIKE '".$_POST['search']['value']."%' ";    
                        $sqlMat.=" OR identificacion LIKE '".$_POST['search']['value']."%' ";       
                        $sqlMat.=" OR razonsocial LIKE '".$_POST['search']['value']."%' ";    
                        $sqlMat.=" OR inscrip.noticia LIKE '".$_POST['search']['value']."%' ";     
                        $sqlMat.=" OR inscrip.operador LIKE '".$_POST['search']['value']."%' ";    
                        $sqlMat.=" OR inscrip.numerooperacion LIKE '".$_POST['search']['value']."%' ";    
                        $sqlMat.=" OR actos.nombre LIKE '".$_POST['search']['value']."%' ";    
                        $sqlMat.=" OR libros.nombre LIKE '".$_POST['search']['value']."%' )";    
                }

                $stmt = $SIIem->getConnection()->prepare($sqlMat);
    //            Ejecución de las consultas
                $stmt->execute($params);
                $resultadosServicios = $stmt->fetchAll();
                $totalFiltered = sizeof($resultadosServicios);

                $sqlMat.=" LIMIT ".$_POST['start']." ,".$_POST['length']."   ";


    //            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
                $stmt = $SIIem->getConnection()->prepare($sqlMat);
    //            Ejecución de las consultas
                $stmt->execute($params);
                $resultadoLibros = $stmt->fetchAll();

    //           Ciclo para crear contadores de valores y cantidades por servicio, se construye la tabla detalla con la información consultada 
                $idservAux = 0;
                for($i=0;$i<sizeof($resultadoLibros);$i++){
                    $nestedData=array();
                    $nestedData[] = $resultadoLibros[$i]['fecha'];
                    $nestedData[] = $resultadoLibros[$i]['matricula'];
                    $nestedData[] = $resultadoLibros[$i]['identificacion'];                    
                    $nestedData[] = $resultadoLibros[$i]['comerciante'];
                    $nestedData[] = $resultadoLibros[$i]['noticia'];
                    $nestedData[] = $resultadoLibros[$i]['operador'];
                    $nestedData[] = $resultadoLibros[$i]['numerooperacion'];
                    $nestedData[] = $resultadoLibros[$i]['acto'];
                    $nestedData[] = $resultadoLibros[$i]['libro'];

                    $data[] = $nestedData;
                }           

                $json_data = array(
                            "draw"            => intval( $_POST['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
                            "recordsTotal"    => intval( $totalData ),  // total number of records
                            "recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
                            "data"            => $data ,  // total data array
                            "sql"               => $sqlMat
                            );

    //            echo json_encode($json_data);
            
            
                return new Response(json_encode($json_data ));
            } 
            
            return new Response(json_encode(array("sql"=>$sqlMat)));
    }
    
    
    /**
     * @Route("/extraccionMatriculados", name="extraccionMatriculados")
     */
    
    public function extraccionMatriculadosAction() {
        $em = $this->getDoctrine()->getManagger('sii');
        
        if(isset($_POST['dateInit']) && isset($_POST['dateEnd'])){
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            $impServi = "'".implode("','",$_POST['servicio'])."'";
        }else{
            $sqlCIIUS = "SELECT ciius.idciiu, ciius.descripcion FROM bas_ciius4 ciius";
            $prepareCIIUS = $em->getConnection()->prepare($sqlCIIUS);
            $prepareCIIUS->execute();
            $ciius =  $prepareCIIUS->fetchAll();
            return $this->render('default/extraccionMatriculados.html.twig',array('ciius' => $ciius));
        }    
            
    }
 
    /**
     * @Route("/exportExcel", name="exportExcel")
     */
    public function exportExcelAction($resultadosServicios=NULL , $columnas=NULL)
    {
        
        
            
             $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();

       $phpExcelObject->getProperties()->setCreator("liuggio")
           ->setLastModifiedBy("Giulio De Donato")
           ->setTitle("Office 2005 XLSX Test Document")
           ->setSubject("Office 2005 XLSX Test Document")
           ->setDescription("Test document for Office 2005 XLSX, generated using PHP classes.")
           ->setKeywords("office 2005 openxml php")
           ->setCategory("Test result file");
       
        for($l=65; $l<=90; $l++) {  
            $letra = chr($l);  
            $columns[]=$letra;  
        }
        
        $c=0;
        foreach ($columnas as $value) {
                $pos = $columns[$c].('1');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue($pos, $value);
                $c++;
            }
        
        for($i=0;$i<sizeof($resultadosServicios);$i++){
            $p=$i+2;
            $j=0;
            foreach ($resultadosServicios[$i] as $value) {
                $pos = $columns[$j].($p);
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue($pos, $value);
                $j++;
            }
        }

       $phpExcelObject->getActiveSheet()->setTitle('Simple');
       // Set active sheet index to the first sheet, so Excel opens this as the first sheet
       $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'PhpExcelFileSample.xlsx'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;     
    } 
        
    /**
     * @Route("/consultaActos" , name="consultaActos" )
     */
    public function consultaActosAction(Request $request) {
        $SIIem =  $this->getDoctrine()->getManager('sii');
        
        $idlibro = "'".implode("','",$_POST['idlibro'])."'";

        $sqlLibro = "SELECT acto.idacto, acto.nombre as 'acto', acto.idlibro, libro.nombre as 'libro' FROM mreg_actos acto INNER JOIN mreg_libros libro WHERE acto.idlibro=libro.idlibro AND acto.idlibro IN ($idlibro) ORDER BY libro.nombre ASC ";
    //          
        $params = array('idlibro'=> $idlibro );

    //            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
        $libros = $SIIem->getConnection()->prepare($sqlLibro);
    //            Ejecución de las consultas
        $libros->execute($params);
        $rowLibros = $libros->fetchAll();
        $listaLibros ='<optgroup label="'.$rowLibros[0]['libro'].'" >';
        $auxLibro = $rowLibros[0]['idlibro'];
        for($i=0;$i<sizeof($rowLibros);$i++){
            if($auxLibro!=$rowLibros[$i]['idlibro']){
                $listaLibros.='</optgroup><optgroup label="'.$rowLibros[$i]['libro'].'" >';
                $auxLibro=$rowLibros[$i]['idlibro'];
            }
            $listaLibros.="<option value='".$rowLibros[$i]['idlibro']."-".$rowLibros[$i]['idacto']."' >".$rowLibros[$i]['idlibro']." - ".$rowLibros[$i]['acto']."</option>";
            
        }
        
        $listaLibros.="<opygroup>";
        return new Response(json_encode(array('Libros' => $listaLibros )));
           
    }
    
}
