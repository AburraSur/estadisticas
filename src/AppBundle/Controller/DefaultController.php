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
            $sqlMat = "SELECT inscritos.matricula, inscritos.organizacion,basorganiza.descripcion, inscritos.categoria, inscritos.muncom, inscritos.razonsocial, inscritos.fecmatricula, inscritos.fecrenovacion, inscritos.feccancelacion, inscritos.ultanoren  "
                    . "FROM mreg_est_inscritos inscritos  "
                    . "INNER JOIN bas_organizacionjuridica basorganiza ON basorganiza.id=inscritos.organizacion "
                    . "WHERE inscritos.fecmatricula between :fecIni AND :fecEnd  "
                    . "AND inscritos.ctrestmatricula NOT IN ('NA','NM') "
                    . "AND inscritos.matricula IS NOT NULL "
                    . "AND inscritos.matricula !='' ";
            
//          Consulta para las matriculas renovadas en el rango de fechas consultado  
            $sqlRen = "SELECT inscritos.matricula, inscritos.organizacion,basorganiza.descripcion, inscritos.categoria, inscritos.muncom, inscritos.razonsocial, inscritos.fecmatricula, inscritos.fecrenovacion, inscritos.feccancelacion, inscritos.ultanoren "
                    . "FROM mreg_est_inscritos inscritos  "
                    . "INNER JOIN bas_organizacionjuridica basorganiza ON basorganiza.id=inscritos.organizacion "
                    . "WHERE inscritos.fecmatricula < :fecIni "
                    . "AND inscritos.fecrenovacion between :fecIni AND :fecEnd  "
                    . "AND inscritos.matricula IS NOT NULL "
                    . "AND inscritos.matricula !='' "
                    . "AND inscritos.ultanoren ='".$fechaFinal[0]."' ";
            
//          Consulta para las matriculas canceladas en el rango de fechas consultado
            $sqlCan = "SELECT inscritos.matricula, inscritos.organizacion,basorganiza.descripcion, inscritos.categoria, inscritos.muncom, inscritos.razonsocial, inscritos.fecmatricula, inscritos.fecrenovacion, inscritos.feccancelacion, inscritos.ultanoren "
                    . "FROM mreg_est_inscritos inscritos  "
                    . "INNER JOIN bas_organizacionjuridica basorganiza ON basorganiza.id=inscritos.organizacion "
                    . "INNER JOIN mreg_est_inscripciones mei ON  inscritos.matricula = mei.matricula "
                    . "WHERE mei.fecharegistro between :fecIni AND :fecEnd "
                    //. "AND inscritos.ctrestmatricula IN ('MC','IC','MF') "
                    . "AND inscritos.ctrestmatricula IN ('MC','IC') "
                    . "AND inscritos.matricula IS NOT NULL "
                    . "AND inscritos.matricula !='' "
                    . "AND libro IN ('RM15' , 'RM51', 'RE51', 'RM53', 'RM54', 'RM55', 'RM13') "
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
                                    <th>Cod. Organización</th>
                                    <th>Organización</th>
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
            
            $excelRegistro = array();
            
            $arregloTotales['PN'] = 0;
            $arregloTotales['EST'] = 0;
            $arregloTotales['SOC'] = 0;
            $arregloTotales['AGSUC'] = 0;
            $arregloTotales['ESAL'] = 0;
            $arregloTotales['CIVILES'] = 0;
            
            $arregloMatMun = array();
            
            $tabla = new UtilitiesController();
            $totalMatRen = 0;
            $resultadosMat = $stmt->fetchAll();
            $resumenMat = $tabla->construirTablaResumen($resultadosMat, 'matriculados',$tablaDetalle,$arregloTotales,$arregloMatMun);
            $tablaMatri['matriculados'] = $resumenMat['tabla'];
            $tablaDetalle = $resumenMat['tablaDetalle'];
            $totalMatRen = $totalMatRen+$resumenMat['granTotal'];
            $excelRegistro[] = $resumenMat['excelRegistro'];
            $arregloTotales = $resumenMat['arregloTotales'];
            $totttt[] = $arregloMatMun = $resumenMat['arregloMatMun'];
            
            
            
            $resultadosRen = $strv->fetchAll();
            $resumenRen = $tabla->construirTablaResumen($resultadosRen, 'renovados', $tablaDetalle, $arregloTotales,$arregloMatMun);
            $tablaMatri['renovados'] = $resumenRen['tabla'];
            $tablaDetalle = $resumenRen['tablaDetalle'];
            $totalMatRen = $totalMatRen + $resumenRen['granTotal'];
            $excelRegistro[] = $resumenRen['excelRegistro'];
            $arregloTotales = $resumenRen['arregloTotales'];
            $arregloMatMun = $resumenRen['arregloMatMun'];
                    
            $resultadosCan = $stcn->fetchAll();
            $resumenCan = $tabla->construirTablaResumen($resultadosCan, 'cancelados', $tablaDetalle, $arregloTotales,$arregloMatMun);
            $tablaMatri['cancelados'] = $resumenCan['tabla'];
            $tablaDetalle = $resumenCan['tablaDetalle'];
            $excelRegistro[] = $resumenCan['excelRegistro'];
            $arregloMatMun = $resumenCan['arregloMatMun'];
            
//            $fecha = new \DateTime();
//            $fecExcel = $fecha->format('YmdHis');
//            $nomExcel = 'ExtraccionMatRenCan'.$fecExcel;
//            $response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$excelReg, 'columnas'=>$columns , 'nomExcel'=>$nomExcel));
            //return $response;
            
            if($_POST['excel']==1){
                
//                for($i=0;$i<sizeof($excelRegistro);$i++){
//                    foreach($excelRegistro[$i] as $value){
//                       $arrayExcel[] = $value; 
//                    }
//                }
                
                //for($i=0;$i<sizeof($excelRegistro);$i++){
                foreach ($excelRegistro as $key => $valueExcel) {
                    foreach($valueExcel as $value){
                       $arrayExcel[] = $value; 
                    }
                }
                
                
                foreach ($valueExcel as $key => $value) {
                    
                }

                $nomExcel = 'ResumenMatRenCan';
                $columns[] = 'Municipio';
                $columns[]= 'P. Naturales';
                $columns[]= 'Establecimientos';
                $columns[]= 'Sociedades';
                $columns[]= 'Agencias - Sucursales';
                $columns[]= 'ESAL';
                $columns[]= 'Civil';
                $columns[]= 'Total';
                $response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$arrayExcel, 'columnas'=>'' , 'nomExcel'=>$nomExcel , 'fecIni'=>$_POST['dateInit'] ,  'fecEnd'=>$_POST['dateEnd']) );
                return $response;
            }else{
                return new Response(json_encode(array('tablaMatri' => $tablaMatri , 'totalMatRen' => number_format($totalMatRen,"0","",".") , 'excelRegistro' => $excelRegistro , 'resultadosCan'=>$resultadosCan , 'arregloTotales'=>$arregloTotales , 'arregloMatMun'=>$arregloMatMun , '$totttt'=>$totttt)));
            }
//            return new Response(json_encode(array('tablaMatri' => $tablaMatri , 'tablaDetalle' => $tablaDetalle )));
            
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
            
            $columns =['matricula', 
                    'Cod organizacion',
                    'organizacion',
                    'categoria',
                    'muncom',
                    'razonsocial',
                    'estado',
                    'fecmatricula',
                    'fecrenovacion',
                    'feccancelacion',
                    'ultanoren',
           ];
            
//          Consulta para los matriculados en el rango de fechas consultado  
            $sqlMatT = $sqlMat = "SELECT inscritos.matricula, inscritos.organizacion,basorganiza.descripcion, inscritos.categoria, inscritos.muncom, inscritos.razonsocial, inscritos.fecmatricula, inscritos.fecrenovacion, inscritos.feccancelacion, inscritos.ultanoren  "
                    . "FROM mreg_est_inscritos inscritos  "
                    . "INNER JOIN bas_organizacionjuridica basorganiza ON basorganiza.id=inscritos.organizacion "
                    . "WHERE inscritos.fecmatricula between :fecIni AND :fecEnd  "
                    . "AND inscritos.ctrestmatricula NOT IN ('NA','NM') "
                    . "AND inscritos.matricula IS NOT NULL "
                    . "AND inscritos.matricula !='' ";
            
//          Consulta para las matriculas renovadas en el rango de fechas consultado  
            $sqlRenT = $sqlRen = "SELECT inscritos.matricula, inscritos.organizacion,basorganiza.descripcion, inscritos.categoria, inscritos.muncom, inscritos.razonsocial, inscritos.fecmatricula, inscritos.fecrenovacion, inscritos.feccancelacion, inscritos.ultanoren "
                    . "FROM mreg_est_inscritos inscritos  "
                    . "INNER JOIN bas_organizacionjuridica basorganiza ON basorganiza.id=inscritos.organizacion "
                    . "WHERE inscritos.fecmatricula < :fecIni "
                    . "AND inscritos.fecrenovacion between :fecIni AND :fecEnd  "
                    . "AND inscritos.matricula IS NOT NULL "
                    . "AND inscritos.matricula !='' "
                    . "AND inscritos.ultanoren ='".$fechaFinal[0]."' ";
            
//          Consulta para las matriculas canceladas en el rango de fechas consultado
            $sqlCanT = $sqlCan = "SELECT inscritos.matricula, inscritos.organizacion,basorganiza.descripcion, inscritos.categoria, inscritos.muncom, inscritos.razonsocial, inscritos.fecmatricula, inscritos.fecrenovacion, inscritos.feccancelacion, inscritos.ultanoren "
                    . "FROM mreg_est_inscritos inscritos  "
                    . "INNER JOIN bas_organizacionjuridica basorganiza ON basorganiza.id=inscritos.organizacion "
                    . "INNER JOIN mreg_est_inscripciones mei ON  inscritos.matricula = mei.matricula  "
                    . "WHERE mei.fecharegistro between :fecIni AND :fecEnd "
                    //. "AND inscritos.ctrestmatricula IN ('MC','IC','MF') "
                    . "AND inscritos.ctrestmatricula IN ('MC','IC') "
                    . "AND inscritos.matricula IS NOT NULL "
                    . "AND inscritos.matricula !='' "
                    . "AND libro IN ('RM15' , 'RM51','RE51', 'RM53', 'RM54', 'RM55', 'RM13') "
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
                    $excelData[] = $matT[$i]["descripcion"];
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
                    $excelData[] = $renT[$i]["descripcion"];
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
                    $excelData[] = $canT[$i]["descripcion"];
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
            
            $nomExcel = 'ExtraccionMatRenCan';
            $response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$excelReg, 'columnas'=>$columns , 'nomExcel'=>$nomExcel , 'fecIni'=>$_POST['dateInit'] ,  'fecEnd'=>$_POST['dateEnd']));
            return $response;
        }else{  
            $t1 = sizeof($matT);
            $t2 = sizeof($renT);
            $t3 = sizeof($canT);
            
            $totalFiltered = $totalData = $t1 + $t2 + $t3;
            
            if( !empty($_POST['columns'][1]['search']['value']) ) {   // if there is a search parameter, $_POST['search']['value'] contains search parameter
                $sqlMat.=" AND inscritos.muncom = '".$_POST['columns'][1]['search']['value']."' ";
                $sqlRen.=" AND inscritos.muncom = '".$_POST['columns'][1]['search']['value']."' ";
                $sqlCan.=" AND inscritos.muncom = '".$_POST['columns'][1]['search']['value']."' ";

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
                    $nestedData[] = $matriculados[$i]["descripcion"];
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
                    $nestedData[] = $renovados[$i]["descripcion"];
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
                    $nestedData[] = $cancelados[$i]["descripcion"];
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
        $listaUsuarios = $sedes->usuarios($SIIem);
        
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            $impServi = "'".implode("','",$_POST['servicio'])."'";

            $columns = ['identificacion',
                    'Cliente',
                    'Matricula',
                    'Organizacion',
                    'Categoria',
                    'Sede',
                    'cod. operador',
                    'Operador',
                    'Numero operacion',
                    'Recibo',
                    'idservicio',
                    'Servicio',
                    'Cantidad',
                    'Valor'];
            
//          Consulta para los servicios seleccionados en el rango de fechas consultado  
            $sqlMat = "SELECT recibos.identificacion, recibos.nombre as 'Cliente',recibos.matricula,recibos.anorenovacion as 'organijuridica',recibos.activos as 'categoria',recibos.sucursal, recibos.operador,recibos.horaoperacion, recibos.numerooperacion,recibos.numerorecibo, servicios.idservicio,servicios.nombre as 'Servicio', recibos.cantidad, recibos.valor "
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
                for($i=0;$i<sizeof($resultadosServicios);$i++){
                    if($resultadosServicios[$i]['matricula']!=''){
                        $sqlOrg = "SELECT organizacion, categoria FROM mreg_est_matriculados WHERE matricula='".$resultadosServicios[$i]['matricula']."' ";
                        $matri = $SIIem->getConnection()->prepare($sqlOrg);
                        $matri->execute();
                        $resultadosOrg = $matri->fetchAll();  
                        $resultadosServicios[$i]['organijuridica'] = $resultadosOrg[0]['organizacion'];
                        $resultadosServicios[$i]['categoria'] = $resultadosOrg[0]['categoria'];
                    }else{
                        $resultadosServicios[$i]['matricula']='N/A';
                        $resultadosServicios[$i]['organijuridica'] = 'N/A';
                        $resultadosServicios[$i]['categoria'] = 'N/A';
                    }
                    
                    $sucursal = substr($resultadosServicios[$i]['numerooperacion'], 0,2);
                    $resultadosServicios[$i]['sucursal'] = $listaSedes[$sucursal];
                    $codOpera = substr($resultadosServicios[$i]['numerooperacion'], 2,3);
                    $resultadosServicios[$i]['horaoperacion'] = $listaUsuarios[$codOpera];
                   
                }
                
                
                $nomExcel = 'ExtraccionServicios';
                $response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$resultadosServicios , 'columnas'=>$columns , 'nomExcel'=>$nomExcel , 'fecIni'=>$_POST['dateInit'] ,  'fecEnd'=>$_POST['dateEnd']));
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
                    $sucursal = substr($resultadosServicios[$i]['numerooperacion'], 0,2);
                    $nestedData[] = $listaSedes[$sucursal];
                    $nestedData[] = $resultadosServicios[$i]['operador'];  
                    $nestedData[] = $resultadosServicios[$i]['numerooperacion'];
                    $nestedData[] = $resultadosServicios[$i]['numerorecibo'];
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
                            actos.idacto,
                            actos.nombre AS 'acto',
                            libros.idlibro,
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
                
                $nomExcel = 'ExtraccionLibros';
                $response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$resultadoLibros , 'columnas'=>$columns , 'nomExcel'=>$nomExcel , 'fecIni'=>$_POST['dateInit'] ,  'fecEnd'=>$_POST['dateEnd']));
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
                    $nestedData[] = $resultadoLibros[$i]['idlibro'];
                    $nestedData[] = $resultadoLibros[$i]['libro'];
                    $nestedData[] = $resultadoLibros[$i]['idacto'];
                    $nestedData[] = $resultadoLibros[$i]['acto'];

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
        $em = $this->getDoctrine()->getManager('sii');
        
        if(isset($_POST['organizacion']) && isset($_POST['estadoMat']) && isset($_POST['afiliacion']) && isset($_POST['municipio']) ){
                        
            if($_POST['estadoMat']==1){
                $estado = "('MA','MI','IA')";
            }else{
                $estado = "('MC','IC')";
            }
            
            $sqlExtracMatri = "SELECT 
                            mei.matricula,
                                mei.proponente,
                                mei.organizacion,
                                mei.categoria,
                                mei.ctrestmatricula,
                                mei.ctrestdatos,
                                mei.nombre1,
                                mei.nombre2,
                                mei.apellido1,
                                mei.apellido2,
                                mei.idclase,
                                mei.numid AS 'numidMat',
                                mei.nit AS 'nitMat',
                                mei.razonsocial AS 'razonsocialMat',
                                mei.fecmatricula AS 'FEC-MATRICULA',
                                mei.fecrenovacion AS 'FEC-RENOVACION',
                                mei.ultanoren,
                                mei.feccancelacion AS 'FEC-CANCELACION',
                                mei.fecconstitucion,
                                mei.fecdisolucion,
                                mei.fecliquidacion,
                                mei.fecvigencia,
                                mev.numid AS idRepLegal,
                                mev.nombre AS RepresentanteLegal,
                                (CASE WHEN mei.organizacion='02' THEN mep.nit 
                                    ELSE inscritos.nit 
                                END) AS 'idPropietario',
                                (CASE WHEN mei.organizacion='02' THEN mep.razonsocial 
                                    ELSE inscritos.razonsocial 
                                END) AS 'NombrePropietario',
                                mei.dircom,
                                mei.barriocom,
                                mei.muncom,
                                mei.telcom1,
                                mei.telcom2,
                                mei.telcom3,
                                mei.emailcom,
                                mei.dirnot,
                                mei.munnot,
                                mei.telnot,
                                mei.telnot2,
                                mei.emailnot,
                                mei.ciiu1,
                                mei.ciiu2,
                                mei.ciiu3,
                                mei.ciiu4,
                                mei.personal,
                                mei.ctrlibroscomercio,
                                mei.ctrafiliacion,
                                mei.ctrembargo,
                                mei.ctrimpexp,
                                mei.ctrtipolocal,
                                mei.ctrfun,
                                mei.ctrubi,
                                mei.ctrclasegenesadl,
                                mei.ctrclaseespeesadl,
                                mei.ctrclaseeconsoli,
                                mei.ctrbenart7,
                                mei.ctrbenley1780,
                                mei.ctrtipopropiedad,
                                mei.tamanoempresa,
                                mei.actcte,
                                mei.actnocte,
                                mei.actfij,
                                mei.actval,
                                mei.actotr,
                                mei.acttot,
                                mei.pascte,
                                mei.paslar,
                                mei.pastot,
                                mei.patrimonio,
                                mei.paspat,
                                mei.ingope,
                                mei.ingnoope,
                                mei.gasope,
                                mei.gasnoope,
                                mei.utiope,
                                mei.utinet,                                
                                mei.anodatos,
                                mei.fecdatos,
                                mei.capaut,
                                mei.capsus,
                                mei.cappag,
                                mei.capsoc,
                                mei.apolab,
                                mei.apolabadi,
                                mei.apodin,
                                mei.apotra,
                                mei.apoact,
                                mei.apotot,
                                mei.actvin,
                                mei.capesadl,
                                mei.cantest,
                                mei.anorenaflia,
                                mei.fecactaaflia,
                                mei.fecrenaflia,
                                mei.valpagaflia
                        FROM
                            mreg_est_inscritos mei
                        LEFT JOIN mreg_est_vinculos mev ON mei.matricula = mev.matricula 
                        LEFT JOIN mreg_est_inscritos inscritos ON mei.matricula = inscritos.matricula
                        LEFT JOIN mreg_est_propietarios mep ON mei.matricula = mep.matricula
                        WHERE mei.matricula <> '' 
                        AND mei.ctrestmatricula IN $estado 
                        AND (";
            
                        $columns=['MATRICULA',
                                    'PROPONENTE',
                                    'ORGANIZACION',
                                    'CATEGORIA',
                                    'EST-MATRICULA',
                                    'EST_DATOS',
                                    'NOMBRE 1',
                                    'NOMBRE 2',
                                    'APELLIDO 1',
                                    'APELLIDO 2',
                                    'CLASE-ID',
                                    'IDENTIFICACION',
                                    'NIT',
                                    'RAZÓN SOCIAL',
                                    'FEC-MATRICULA',
                                    'FEC-RENOVACION',
                                    'ULT-ANO_REN',
                                    'FEC-CANCELACION',
                                    'FEC-CONSTITUCION',
                                    'FEC-DISOLUCION',
                                    'FEC-LIQUIDACION',
                                    'FEC-VIGENCIA',
                                    'ID. REP. LEGAL',
                                    'REPRESENTANTE LEGAL',
                                    'ID. PROPIETARIO',
                                    'PROPIETARIO',
                                    'DIR-COMERCIAL',
                                    'BARRIO-COMERCIAL',
                                    'MUN-COMERCIAL',
                                    'TEL-COM-1',
                                    'TEL-COM-2',
                                    'TEL-COM-3',
                                    'EMAIL-COMERCIAL',
                                    'DIR-NOTIFICACION',
                                    'MUN-NOTIFICACION',
                                    'TEL-NOTF-1',
                                    'TEL-NOTF-2',
                                    'EMAIL-NOTIFICACION',
                                    'CIIU-1',
                                    'CIIU-2',
                                    'CIIU-3',
                                    'CIIU-4',
                                    'PERSONAL',
                                    'LIBROS-COMERCIO',
                                    'CTR-AFILIACION',
                                    'CTR-EMBARGO',
                                    'IMPORTA-EXPORTA',
                                    'TIPO-LOCAL',
                                    'TIEMPO-FUN',
                                    'UBICACION',
                                    'CLA-GEN-ESADL',
                                    'CLA-ESPE-ESADL',
                                    'CLA-ECON-SOLI',
                                    'BEN-ART-7',
                                    'BEN-LEY-1780',
                                    'TIPO-PROPIEDAD',
                                    'TAM-EMPRESA',
                                    'ACTIVO-CORRIENTE',
                                    'ACTIVO-NO-CORRIENTE',
                                    'ACTIVO-FIJO',
                                    'ACTIVO-VALORIZ',
                                    'ACTIVO-OTROS',
                                    'ACTIVO-TOTAL',
                                    'PASIVO-CORRIENTE',
                                    'PASIVO-LRG-PLAZO',
                                    'PASIVO-TOTAL',
                                    'PATRIMONIO',
                                    'PASIVO+PATRIM',
                                    'ING-OPERACIONES',
                                    'ING-NO-OPERACIONALES',
                                    'GAS-OPERACIONALES',
                                    'GAS-NO-OPERAC.',
                                    'UTIL-OPERACIONAL',
                                    'UTIL-NETA',
                                    'ANIO-DATOS',
                                    'FECHA-DATOS',
                                    'CAPITAL-AUTORIZ.',
                                    'CAPITAL-SUSCRITO',
                                    'CAPITAL-PAGADO',
                                    'CAPITAL-SOCIAL',
                                    'APORTE-LABORAL',
                                    'APORTE-LABORAL-ADI',
                                    'APORTE-DINERO',
                                    'APORTE-TRABAJO',
                                    'APORTE-ACTIVOS',
                                    'APORTE-TOTAL',
                                    'VLR-ESTABLEC.',
                                    'PATRIM-ESADL.',
                                    'CANT-ESTABLECIM.',
                                    'ANIO-REN-AFIL',
                                    'FEC-AFIL.',
                                    'FEC-ULT-PAG-AFIL',
                                    'VAL-ULT-PAG-AFIL'
                                    ];
                /*$sqlExtracMatri = "SELECT 
                            mei.matricula,
                            mei.organizacion ,
                            mei.categoria ,
                            mei.ctrestmatricula,
                            mei.numid AS 'numidMat',
                            mei.nit AS 'nitMat',
                            mei.razonsocial AS 'razonsocialMat',
                            mei.fecmatricula AS 'FEC-MATRICULA',
                            mei.fecrenovacion AS 'FEC-RENOVACION',
                            mei.feccancelacion AS 'FEC-CANCELACION',
                            mei.dircom AS 'DIR-COMERCIAL',
                            mei.muncom AS 'MUNICIPIO',
                            mei.telcom1 AS 'TEL-COM-1',
                            mei.telcom2 AS 'TEL-COM-2',
                            mei.telcom3 AS 'TEL-COM-3',
                            mei.emailcom AS 'EMAIL-COM',
                            mei.ciiu1 AS 'CIIU1',
                            mei.ciiu2 AS 'CIIU2',
                            mei.ciiu3 AS 'CIIU3',
                            mei.ciiu4 AS 'CIIU4',
                            mei.acttot,
                            mei.actvin,
                            mev.numid AS idRepLegal,
                            mev.nombre AS RepresentanteLegal,
                            (CASE WHEN mei.organizacion='02' THEN mep.nit 
                                ELSE inscritos.nit 
                            END) AS 'idPropietario',
                            (CASE WHEN mei.organizacion='02' THEN mep.razonsocial 
                                ELSE inscritos.razonsocial 
                            END) AS 'NombrePropietario'
                        FROM
                            mreg_est_inscritos mei
                        LEFT JOIN mreg_est_vinculos mev ON mei.matricula = mev.matricula 
                        LEFT JOIN mreg_est_inscritos inscritos ON mei.matricula = inscritos.matricula
                        LEFT JOIN mreg_est_propietarios mep ON mei.matricula = mep.matricula
                        WHERE mei.matricula <> '' 
                        AND mei.ctrestmatricula IN $estado 
                        AND (";*/

                $i=0;
                foreach($_POST['organizacion'] as $organiza){
                    if($organiza==0){
                        $condiOrga = " (mei.organizacion = '01') ";
                    }elseif($organiza==1){
                        $condiOrga = " (mei.organizacion IN ('03','04','05','06','07','08','09','10','11','16') AND (mei.categoria='1')) ";
                    }elseif($organiza==2){
                        $condiOrga = " (mei.organizacion = '02' OR mei.categoria = '2' OR mei.categoria = '3') ";
                    }elseif($organiza==3){
                        $condiOrga = " (mei.organizacion IN ('03','04','05','06','07','08','09','10','11','16') AND mei.categoria IN ('2','3')) ";
                    }elseif($organiza==4){
                        $condiOrga = " mei.organizacion IN ('12','14') ";
                    }elseif($organiza==5){
                        $sqlExtracMatri = "SELECT 
                                mei.matricula,
                                mei.proponente,
                                mei.organizacion,
                                mei.categoria,
                                mei.ctrestmatricula,
                                mei.ctrestdatos,
                                mei.nombre1,
                                mei.nombre2,
                                mei.apellido1,
                                mei.apellido2,
                                mei.idclase,
                                mei.numid AS 'numidMat',
                                mei.nit AS 'nitMat',
                                mei.razonsocial AS 'razonsocialMat',
                                mei.fecmatricula AS 'FEC-MATRICULA',
                                mei.fecrenovacion AS 'FEC-RENOVACION',
                                mei.ultanoren,
                                mei.feccancelacion AS 'FEC-CANCELACION',
                                mei.fecconstitucion,
                                mei.fecdisolucion,
                                mei.fecliquidacion,
                                mei.fecvigencia,
                                mep.identificacion AS 'Ident. Propietario',
                                mep.razonsocial AS 'Propietario',
                                mei.dircom,
                                mei.barriocom,
                                mei.muncom,
                                mei.telcom1,
                                mei.telcom2,
                                mei.telcom3,
                                mei.emailcom,
                                mei.dirnot,
                                mei.munnot,
                                mei.telnot,
                                mei.telnot2,
                                mei.emailnot,
                                mei.ciiu1,
                                mei.ciiu2,
                                mei.ciiu3,
                                mei.ciiu4,
                                mei.personal,
                                mei.ctrlibroscomercio,
                                mei.ctrafiliacion,
                                mei.ctrembargo,
                                mei.ctrimpexp,
                                mei.ctrtipolocal,
                                mei.ctrfun,
                                mei.ctrubi,
                                mei.ctrclasegenesadl,
                                mei.ctrclaseespeesadl,
                                mei.ctrclaseeconsoli,
                                mei.ctrbenart7,
                                mei.ctrbenley1780,
                                mei.ctrtipopropiedad,
                                mei.tamanoempresa,
                                mei.actcte,
                                mei.actnocte,
                                mei.actfij,
                                mei.actval,
                                mei.actotr,
                                mei.acttot,
                                mei.pascte,
                                mei.paslar,
                                mei.pastot,
                                mei.pattot,
                                mei.patrimonio,
                                mei.paspat,
                                mei.ingope,
                                mei.ingnoope,
                                mei.gasope,
                                mei.gasnoope,
                                mei.utiope,
                                mei.utinet,                                
                                mei.anodatos,
                                mei.fecdatos,
                                mei.capaut,
                                mei.capsus,
                                mei.cappag,
                                mei.capsoc,
                                mei.apolab,
                                mei.apolabadi,
                                mei.apodin,
                                mei.apotra,
                                mei.apoact,
                                mei.apotot,
                                mei.actvin,
                                mei.capesadl,
                                mei.cantest,
                                mei.anorenaflia,
                                mei.fecactaaflia,
                                mei.fecrenaflia,
                                mei.valpagaflia
                            FROM
                                mreg_est_propietarios mep
                            LEFT JOIN   mreg_est_inscritos mei ON mep.matricula = mei.matricula
                            WHERE mei.matricula <> '' 
                            AND mei.ctrestmatricula IN $estado"; 
                        
                            $condiOrga = "AND ((mei.organizacion = '02')
                            AND mep.codigocamara != '55'
                            AND mep.estado='V' ";
                            
                            
                            $columns=['MATRICULA',
                                        'PROPONENTE',
                                        'ORGANIZACION',
                                        'CATEGORIA',
                                        'EST-MATRICULA',
                                        'EST_DATOS',
                                        'NOMBRE 1',
                                        'NOMBRE 2',
                                        'APELLIDO 1',
                                        'APELLIDO 2',
                                        'CLASE-ID',
                                        'IDENTIFICACION',
                                        'NIT',
                                        'RAZÓN SOCIAL',
                                        'FEC-MATRICULA',
                                        'FEC-RENOVACION',
                                        'ULT-ANO_REN',
                                        'FEC-CANCELACION',
                                        'FEC-CONSTITUCION',
                                        'FEC-DISOLUCION',
                                        'FEC-LIQUIDACION',
                                        'FEC-VIGENCIA',
                                        'ID. PROPIETARIO',
                                        'PROPIETARIO',
                                        'DIR-COMERCIAL',
                                        'BARRIO-COMERCIAL',
                                        'MUN-COMERCIAL',
                                        'TEL-COM-1',
                                        'TEL-COM-2',
                                        'TEL-COM-3',
                                        'EMAIL-COMERCIAL',
                                        'DIR-NOTIFICACION',
                                        'MUN-NOTIFICACION',
                                        'TEL-NOTF-1',
                                        'TEL-NOTF-2',
                                        'EMAIL-NOTIFICACION',
                                        'CIIU-1',
                                        'CIIU-2',
                                        'CIIU-3',
                                        'CIIU-4',
                                        'PERSONAL',
                                        'LIBROS-COMERCIO',
                                        'CTR-AFILIACION',
                                        'CTR-EMBARGO',
                                        'IMPORTA-EXPORTA',
                                        'TIPO-LOCAL',
                                        'TIEMPO-FUN',
                                        'UBICACION',
                                        'CLA-GEN-ESADL',
                                        'CLA-ESPE-ESADL',
                                        'CLA-ECON-SOLI',
                                        'BEN-ART-7',
                                        'BEN-LEY-1780',
                                        'TIPO-PROPIEDAD',
                                        'TAM-EMPRESA',
                                        'ACTIVO-CORRIENTE',
                                        'ACTIVO-NO-CORRIENTE',
                                        'ACTIVO-FIJO',
                                        'ACTIVO-VALORIZ',
                                        'ACTIVO-OTROS',
                                        'ACTIVO-TOTAL',
                                        'PASIVO-CORRIENTE',
                                        'PASIVO-LRG-PLAZO',
                                        'PASIVO-TOTAL',
                                        'PATRIMONIO TOTAL',
                                        'PATRIMONIO',
                                        'PASIVO+PATRIM',
                                        'ING-OPERACIONES',
                                        'ING-NO-OPERACIONALES',
                                        'GAS-OPERACIONALES',
                                        'GAS-NO-OPERAC.',
                                        'UTIL-OPERACIONAL',
                                        'UTIL-NETA',
                                        'ANIO-DATOS',
                                        'FECHA-DATOS',
                                        'CAPITAL-AUTORIZ.',
                                        'CAPITAL-SUSCRITO',
                                        'CAPITAL-PAGADO',
                                        'CAPITAL-SOCIAL',
                                        'APORTE-LABORAL',
                                        'APORTE-LABORAL-ADI',
                                        'APORTE-DINERO',
                                        'APORTE-TRABAJO',
                                        'APORTE-ACTIVOS',
                                        'APORTE-TOTAL',
                                        'VLR-ESTABLEC.',
                                        'PATRIM-ESADL.',
                                        'CANT-ESTABLECIM.',
                                        'ANIO-REN-AFIL',
                                        'FEC-AFIL.',
                                        'FEC-ULT-PAG-AFIL',
                                        'VAL-ULT-PAG-AFIL'
                                        ];
                    }
                    if($i==0){
                        $sqlExtracMatri.= $condiOrga;
                    }else {
                        $sqlExtracMatri.=" OR $condiOrga";
                    }
                    $i++;
                }
                
                $fechaWhere = $_POST['tipoFecha'];
                $muncom = "'".implode("','",$_POST['municipio'])."'";
                $activoIni = str_replace(",", "", $_POST['activoIni']);
                $activoFinal = str_replace(",", "", $_POST['activoFinal']);
                   
                $sqlExtracMatri.=")";
                if($_POST['tipoFecha']!='all'){
                    $fechaInicial = explode("-", $_POST['dateInit']);
                    $fechaFinal = explode("-", $_POST['dateEnd']);

                    $fecIni = str_replace("-", "", $_POST['dateInit']);
                    $fecEnd = str_replace("-", "", $_POST['dateEnd']);
                    $sqlExtracMatri.= " AND $fechaWhere BETWEEN '$fecIni' AND '$fecEnd' ";   
                }
                      
                $sqlExtracMatri.= " AND mei.muncom IN ($muncom) "
                      . " AND ((mei.acttot BETWEEN $activoIni AND $activoFinal ) OR (mei.actvin BETWEEN $activoIni AND $activoFinal)) ";
                if(isset($_POST['ciius'][0])){
                    $ciiu = "'".implode("','",$_POST['ciius'])."'";
                    $sqlExtracMatri.=" AND (mei.ciiu1 IN ($ciiu) OR mei.ciiu2 IN ($ciiu) OR mei.ciiu3 IN ($ciiu) OR mei.ciiu4 IN ($ciiu)) ";
                } 
                
                if($_POST['afiliacion']==1){
                    $sqlExtracMatri.=" AND mei.ctrafiliacion=1 ";
                }elseif($_POST['afiliacion']==2){
                    $sqlExtracMatri.=" AND mei.ctrafiliacion<>1 ";
                }
                
                if($_POST['yearInit']!=''){
                    if($_POST['yearInit']==$_POST['yearEnd']){
                        $sqlExtracMatri.=" AND mei.ultanoren = '".$_POST['yearInit']."'  ";
                    }else{
                        $sqlExtracMatri.=" AND mei.ultanoren BETWEEN '".$_POST['yearInit']."' AND '".$_POST['yearEnd']."' ";
                    }    
                }
                
                $stmt = $em->getConnection()->prepare($sqlExtracMatri." GROUP BY mei.matricula ORDER BY mei.matricula DESC;");
                $stmt->execute();
                $resultados = $stmt->fetchAll();
                $totalFiltered = $totalData = sizeof($resultados);
                
                if($_POST['excel']==1){
                    
                    $nomExcel = 'ExtraccionMatriculados';
                    $response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$resultados , 'columnas'=>$columns , 'nomExcel'=>$nomExcel ));
                    return $response;
                }else{
                    if( !empty($_POST['search']['value']) ) {   // if there is a search parameter, $_POST['search']['value'] contains search parameter
                        $sqlExtracMatri.=" AND (mei.matricula LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.organizacion  LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.categoria  LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.ctrestmatricula LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.numid LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.nit LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.razonsocial LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.fecmatricula LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.fecrenovacion LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.feccancelacion LIKE '".$_POST['search']['value']."%' ";
                        /*$sqlExtracMatri.=" OR mei.dircom LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.muncom LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.telcom1 LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.telcom2 LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.telcom3 LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.emailcom LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.ciiu1 LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.acttot LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mei.actvin LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mev.numid LIKE '".$_POST['search']['value']."%' ";
                        $sqlExtracMatri.=" OR mev.nombre LIKE '".$_POST['search']['value']."%' )";*/
                    }
                    $sqlExtracMatri.= " GROUP BY mei.matricula ";
                    $stmt = $em->getConnection()->prepare($sqlExtracMatri);
        //            Ejecución de las consultas
                    $stmt->execute();
                    $resultados = $stmt->fetchAll();
                    $totalFiltered = sizeof($resultados);
                    
                    $sqlExtracMatri.= " ORDER BY mei.matricula DESC LIMIT ".$_POST['start']." ,".$_POST['length']."   ";


        //            Parametrizacion de cada una de las consultas para extracción de bases de datos
                    $stmt = $em->getConnection()->prepare($sqlExtracMatri);
        //            Ejecución de las consultas
                    $stmt->execute();
                    $resultados = $stmt->fetchAll();

        //           Ciclo para crear contadores de valores y cantidades por servicio, se construye la tabla detalla con la información consultada 
                    $idservAux = 0;
                    for($i=0;$i<sizeof($resultados);$i++){
                        $nestedData=array();
                        $nestedData[] = $resultados[$i]['matricula'];
                        $nestedData[] = $resultados[$i]['organizacion'];
                        $nestedData[] = $resultados[$i]['categoria'];                  
                        $nestedData[] = $resultados[$i]['ctrestmatricula'];
                        $nestedData[] = $resultados[$i]['numidMat'];
                        $nestedData[] = $resultados[$i]['nitMat'];
                        $nestedData[] = $resultados[$i]['razonsocialMat'];
                        $nestedData[] = $resultados[$i]['FEC-MATRICULA'];
                        $nestedData[] = $resultados[$i]['FEC-RENOVACION'];
                        $nestedData[] = $resultados[$i]['FEC-CANCELACION'];
                        /*$nestedData[] = $resultados[$i]['DIR-COMERCIAL'];
                        $nestedData[] = $resultados[$i]['MUNICIPIO'];
                        $nestedData[] = $resultados[$i]['TEL-COM-1'];
                        $nestedData[] = $resultados[$i]['TEL-COM-2'];
                        $nestedData[] = $resultados[$i]['TEL-COM-3'];
                        $nestedData[] = $resultados[$i]['EMAIL-COM'];
                        $nestedData[] = $resultados[$i]['CIIU'];
                        $nestedData[] = number_format($resultados[$i]['acttot'],"0","",".");
                        $nestedData[] = number_format($resultados[$i]['actvin'],"0","",".");                        
                        $nestedData[] = $resultados[$i]['idRepLegal'];  
                        $nestedData[] = $resultados[$i]['RepresentanteLegal'];  
                        $nestedData[] = $resultados[$i]['idPropietario'];
                        $nestedData[] = $resultados[$i]['NombrePropietario'];*/

                        $data[] = $nestedData;
                    }           

                    $json_data = array(
                                "draw"            => intval( $_POST['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
                                "recordsTotal"    => intval( $totalData ),  // total number of records
                                "recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
                                "data"            => $data ,  // total data array
                                "i"               => $sqlExtracMatri  
                                );

        //            echo json_encode($json_data);


                    return new Response(json_encode($json_data ));

                }
            //}
            
            //return new Response (json_encode(array('sqlExtracMatri'=>$sqlExtracMatri)));
        }else{
            $consultCiius = new UtilitiesController();
            $ciius = $consultCiius->ciius($em);
            return $this->render('default/extraccionMatriculados.html.twig',array('ciius' => $ciius));
        }    
            
    }
 
    /**
     * @Route("/exportExcel", name="exportExcel")
     */
    public function exportExcelAction($resultadosServicios=NULL , $columnas=NULL , $nomExcel=NULL , $fecIni=NULL , $fecEnd=NULL )
    {
        
        
            
             $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();

       $phpExcelObject->getProperties()->setCreator("liuggio")
           ->setLastModifiedBy("Giulio De Donato")
           ->setTitle("Office 2005 XLSX Test Document")
           ->setSubject("Office 2005 XLSX Test Document")
           ->setDescription("Test document for Office 2005 XLSX, generated using PHP classes.")
           ->setKeywords("office 2005 openxml php")
           ->setCategory("Test result file");
       
        $fecha = new \DateTime();
        $fecExcel = $fecha->format('YmdHis');
        $fecCuerpo = $fecha->format('Y-m-d H:i:s');
        
        for($ll=0; $ll<=5; $ll++) {  
         
            for($l=65; $l<=90; $l++) {  
                if($ll==0){
                    $letra = chr($l);  
                    $columns[]=$letra;
                }else{
                    $ls = $ll+64;
                    $letra1 = chr($ls); 
                    $letra2 = chr($l);
                    $columns[]=$letra1.$letra2;
                }
                  
            }           
        }
        $c=0;
        $pos = 'C'.('1');
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue($pos, $nomExcel.' Generado el '.$fecCuerpo)->mergeCells('C1:H1');
//        $phpExcelObject->getActiveSheet()
        $pos = 'C'.('2');
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue($pos, 'Para el rango de fechas: '.$fecIni." - ".$fecEnd);
        $phpExcelObject->getActiveSheet()->mergeCells('C2:H2');
        
        
        if($columnas!=''){
            foreach ($columnas as $value) {
                $pos = $columns[$c].('4');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue($pos, $value);
                $c++;
            }
        }
        for($i=0;$i<sizeof($resultadosServicios);$i++){
            if($columnas!=''){
                $p=$i+5;
            }else{
                $p=$i+4;
            }    
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
            $nomExcel.$fecExcel.'.xlsx'
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
            $listaLibros.="<option value='".$rowLibros[$i]['idlibro']."-".$rowLibros[$i]['idacto']."' >".$rowLibros[$i]['idlibro']." - ".$rowLibros[$i]['idacto']."-".$rowLibros[$i]['acto']."</option>";
            
        }
        
        $listaLibros.="<opygroup>";
        return new Response(json_encode(array('Libros' => $listaLibros )));
           
    }
    
}
