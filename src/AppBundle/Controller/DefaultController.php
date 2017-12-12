<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use ZipArchive;
use AppBundle\Controller\UtilitiesController;
use AppBundle\Entity\Logs;

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
        
        $fecha = new \DateTime();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $SIIem =  $this->getDoctrine()->getManager('sii');
        $logem =  $this->getDoctrine()->getManager();
        
        $usuario = $logem->getRepository('AppBundle:User')->findOneById($user);
        $ipaddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
                
        if(isset($_POST['dateInit']) && isset($_POST['dateEnd'])){
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
                
                $fecActua = $fecha->format('Y/m/d - H:i:s');
                
                
                $arrayExcel2[]= 'CAMARA DE COMERCIO';
                $arrayExcel2[]='';
                $arrayExcel2[]='';
                $arrayExcel2[]= 'REPORTE DE MATRICULADOS ';                
                $arrayExcel2[]='';
                $arrayExcel2[]='';
                $arrayExcel2[]= $fecActua;
                
                $arrayExcel[] = $arrayExcel2;
                
                $arrayExcel3[]= 'ABURRA SUR';
                $arrayExcel3[]='';
                $arrayExcel3[]='';
                $arrayExcel3[]= 'RENOVADOS Y CANCELADOS'; 
                
                $arrayExcel[] = $arrayExcel3;
                
                $arrayExcelP[]='';
                $arrayExcelP[]='Periodo: '.$_POST['dateInit'].' Hasta '.$_POST['dateEnd'];
                $arrayExcelP[]='';
                $arrayExcelP[]= ''; 
                
                $arrayExcel[] = $arrayExcelP;
                
                //for($i=0;$i<sizeof($excelRegistro);$i++){
                foreach ($excelRegistro as $key => $valueExcel) {
                    foreach($valueExcel as $value){
                       $arrayExcel[] = $value; 
                    }
                }
                
                
                foreach ($valueExcel as $key => $value) {
                    
                }

                $nomExcel = 'ResumenMatRenCan';
//                $columns[] = 'Municipio';
//                $columns[]= 'P. Naturales';
//                $columns[]= 'Establecimientos';
//                $columns[]= 'Sociedades';
//                $columns[]= 'Agencias - Sucursales';
//                $columns[]= 'ESAL';
//                $columns[]= 'Civil';
//                $columns[]= 'Total';
                
                $utilities = new UtilitiesController();
                $response = $utilities->exportExcel( $arrayExcel, '',$nomExcel);
                return $response;
                
            }else{
                $logs = new Logs();
                $logs->setFecha($fecha);
                $logs->setModulo('Extracción Matriculados, Renovados y Cancelados');
                $logs->setQuery('Consulta: '.$sqlMat.' ** '.$sqlRen.' ** '.$sqlCan.' / Parametros: fecIni=>'.$fecIni.'  , fecEnd => '.$fecEnd);
                $logs->setUsuario($usuario->getUsername());
                $logs->setIp($ipaddress);
                
                $logem->persist($logs);
                $logem->flush($logs);
                
                
                
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
        $fecha = new \DateTime();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $SIIem =  $this->getDoctrine()->getManager('sii');
        $logem =  $this->getDoctrine()->getManager();
        $usuario = $logem->getRepository('AppBundle:User')->findOneById($user);
        $ipaddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
        
        $fechaInicial = explode("-", $_POST['dateInit']);
        $fechaFinal = explode("-", $_POST['dateEnd']);

        $fecIni = str_replace("-", "", $_POST['dateInit']);
        $fecEnd = str_replace("-", "", $_POST['dateEnd']);

        $excelReg[] =['matricula', 
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
            
            
            $logs = new Logs();
            $logs->setFecha($fecha);
            $logs->setModulo('Extracción Matriculados, Renovados y Cancelados');
            $logs->setQuery('Exporta: '.$sqlMat.' ** '.$sqlRen.' ** '.$sqlCan.' / Parametros: fecIni=>'.$fecIni.'  , fecEnd => '.$fecEnd);
            $logs->setUsuario($usuario->getUsername());
            $logs->setIp($ipaddress);

            $logem->persist($logs);
            $logem->flush($logs);


            
            $nomExcel = 'ExtraccionMatRenCan';
            /*$response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$excelReg, 'columnas'=>$columns , 'nomExcel'=>$nomExcel , 'fecIni'=>$_POST['dateInit'] ,  'fecEnd'=>$_POST['dateEnd']));
            return $response;*/
            $utilities = new UtilitiesController();
            $response = $utilities->exportExcel( $excelReg, '',$nomExcel);
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
        
        $fecha = new \DateTime();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $SIIem =  $this->getDoctrine()->getManager('sii');
        $logem =  $this->getDoctrine()->getManager();
        $usuario = $logem->getRepository('AppBundle:User')->findOneById($user);
        $ipaddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
        
        $sedes = new UtilitiesController();
        $listaSedes = $sedes->sedes($SIIem);
        $listaUsuarios = $sedes->usuarios($SIIem);
        
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            $impServi = "'".implode("','",$_POST['servicio'])."'";

            $columns = ['Identificacion',
                    'Cliente',
                    'Matricula',
                    'Organizacion',
                    'Categoria',
                    'Sede',
                    'cod. operador',
                    'Operador',
                    'Numero operacion',
                    'Recibo',
                    'Fecha Recibo',
                    'idservicio',
                    'Servicio',
                    'Cantidad',
                    'Valor'];
            
//          Consulta para los servicios seleccionados en el rango de fechas consultado  
            $sqlMat = "SELECT recibos.identificacion, recibos.nombre as 'Cliente',recibos.matricula,recibos.anorenovacion as 'organijuridica',recibos.activos as 'categoria',recibos.sucursal, recibos.operador,recibos.horaoperacion, recibos.numerooperacion,recibos.numerorecibo, recibos.fecoperacion, servicios.idservicio,servicios.nombre as 'Servicio', recibos.cantidad, recibos.valor "
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
                    $resultadosServicios[$i]['Cliente'] = utf8_decode($resultadosServicios[$i]['Cliente']);
                }
                
                
                $nomExcel = 'ExtraccionServicios';
                /*$response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$resultadosServicios , 'columnas'=>$columns , 'nomExcel'=>$nomExcel , 'fecIni'=>$_POST['dateInit'] ,  'fecEnd'=>$_POST['dateEnd']));
                return $response;*/
                $logs = new Logs();
                $logs->setFecha($fecha);
                $logs->setModulo('Extracción Servicios');
                $logs->setQuery('Extracción: '.$sqlMat.' / Parametros: fecIni=>'.$fecIni.'  , fecEnd => '.$fecEnd);
                $logs->setUsuario($usuario->getUsername());
                $logs->setIp($ipaddress);
                
                $logem->persist($logs);
                $logem->flush($logs);
                $utilities = new UtilitiesController();
                $response = $utilities->exportExcel( $resultadosServicios, $columns,$nomExcel);
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
                $logs = new Logs();
                $logs->setFecha($fecha);
                $logs->setModulo('Extracción Servicios');
                $logs->setQuery('Consulta: '.$sqlMat.' / Parametros: fecIni=>'.$fecIni.'  , fecEnd => '.$fecEnd);
                $logs->setUsuario($usuario->getUsername());
                $logs->setIp($ipaddress);
                
                $logem->persist($logs);
                $logem->flush($logs);
            
                return new Response(json_encode($json_data ));
            }    
            
        
    }

    
    /**
     * @Route("/extracionLibros", name="extracionLibros")
     */
    public function extracionLibrosAction(Request $request)
    {
        
        $fecha = new \DateTime();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $SIIem =  $this->getDoctrine()->getManager('sii');
        $logem =  $this->getDoctrine()->getManager();
        
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
            $sqlMat = "SELECT 
                            actos.idacto,
                            actos.nombre AS 'Nomacto',
                            libros.idlibro,
                            libros.nombre AS 'Nomlibro'
                        FROM
                            mreg_est_inscripciones inscrip
                                LEFT JOIN
                            mreg_actos actos ON inscrip.acto=actos.idacto
                                LEFT JOIN
                            mreg_libros libros ON inscrip.libro=libros.idlibro
                                LEFT JOIN
                            mreg_est_inscritos mei ON inscrip.matricula=mei.matricula
                     WHERE inscrip.fecharegistro BETWEEN :fecIni AND :fecEnd ";
            
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
            
            $sqlMat.="GROUP BY inscrip.id";
//            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
            $stLibros = $SIIem->getConnection()->prepare($sqlMat);
//            Ejecución de las consultas
            $stLibros->execute($params);
            $resultadoLibros = $stLibros->fetchAll();
            $librosID = array();
            for($i=0;$i<sizeof($resultadoLibros);$i++){
                
                if(isset($acto[$resultadoLibros[$i]['idlibro']][$resultadoLibros[$i]['idacto']])){
                    $acto[$resultadoLibros[$i]['idlibro']][$resultadoLibros[$i]['idacto']] = $acto[$resultadoLibros[$i]['idlibro']][$resultadoLibros[$i]['idacto']]+1;
                }else{
                    $acto[$resultadoLibros[$i]['idlibro']][$resultadoLibros[$i]['idacto']] = 1;
                    if(!in_array($resultadoLibros[$i]['idlibro'], $librosID)){
                        $librosID[] = $resultadoLibros[$i]['idlibro'];                        
                        $NomLibros[$resultadoLibros[$i]['idlibro']] = $resultadoLibros[$i]['Nomlibro'];
                    }    
                    $NomActos[$resultadoLibros[$i]['idacto']] = $resultadoLibros[$i]['Nomacto'];
                }
            }
            
            foreach ($librosID as $idlibro){
                foreach ($acto[$idlibro] as $key => $value) {
                    $tablaTotales.= "<tr>"
                        . "<td>".$idlibro."</td>"
                        . "<td>".$NomLibros[$idlibro]."</td>"
                        . "<td>".$key."</td>"
                        . "<td>".$NomActos[$key]."</td>"
                        . "<td>".$value."</td>";
                }
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
        
        $fecha = new \DateTime();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $SIIem =  $this->getDoctrine()->getManager('sii');
        $logem =  $this->getDoctrine()->getManager();
        $usuario = $logem->getRepository('AppBundle:User')->findOneById($user);
        $ipaddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
        
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            if($_POST['excel']==1){
                $libroActo = $_POST['acto'];
            }else{
                $libroActo = $_POST['libroActo'];
            }
            
            foreach ($libroActo as $value) {
                $registro = explode("-", $value);
                $libros[$registro[0]][] = $registro[1];
            }
            
//          Consulta para los servicios seleccionados en el rango de fechas consultado  
            $sqlMat = "SELECT 
                            inscrip.id,
                            inscrip.fecharegistro,
                            inscrip.matricula,
                            mei.ctrestmatricula,
                            mei.organizacion,
                            mei.categoria,
                            mei.fecmatricula,
                            mei.fecconstitucion,
                            mei.fecrenovacion,
                            mei.ultanoren,
                            mei.numid AS 'identificacion',    
                            mei.razonsocial AS 'comerciante',
                            mei.ciiu1,
                            mei.personal,
                            mei.acttot,
                            mei.muncom,
                            mei.telcom1,                            
                            inscrip.registro,
                            inscrip.noticia,
                            inscrip.operador,
                            inscrip.numerooperacion,
                            actos.idacto,
                            actos.nombre AS 'acto',
                            libros.idlibro,
                            libros.nombre AS 'libro'
                        FROM
                            mreg_est_inscripciones inscrip
                                LEFT JOIN
                            mreg_actos actos ON inscrip.acto=actos.idacto
                                LEFT JOIN
                            mreg_libros libros ON inscrip.libro=libros.idlibro
                                LEFT JOIN
                            mreg_est_inscritos mei ON inscrip.matricula=mei.matricula
                       WHERE inscrip.fecharegistro BETWEEN :fecIni AND :fecEnd ";
            
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
            
            for($i=0;$i<sizeof($resultadoLibros);$i++){
                $resultadoLibros[$i]['comerciante'] = utf8_decode($resultadoLibros[$i]['comerciante']);
                $resultadoLibros[$i]['noticia'] = utf8_decode($resultadoLibros[$i]['noticia']);
                $resultadoLibros[$i]['acto'] = utf8_decode($resultadoLibros[$i]['acto']);
                $resultadoLibros[$i]['libro'] = utf8_decode($resultadoLibros[$i]['libro']);
            }
            
            if($_POST['excel']==1){
                
                $nomExcel = 'ExtraccionLibros';
                $columns = ['ID','FECHA INSCRIPCION','MATRICULA','EST. MAT','ORGANIZACION','CATEGORIA','FEC. MATRICULA','FEC. CONSTITUCION','FEC. RENOVACION','UAR','IDENTIFICACION','RAZON SOCIAL','CIIU','PERSONAL','ACTIVOS','MUNICIPIO','TELEFONO','NUM. REGISTRO','NOTICIA','OPERADOR','OPERACION','ID. ACTO','ACTO','ID. LIBRO','LIBRO'];
                /*$response = $this->forward('AppBundle:Default:exportExcel',array('resultadosServicios'=>$resultadoLibros , 'columnas'=>$columns , 'nomExcel'=>$nomExcel , 'fecIni'=>$_POST['dateInit'] ,  'fecEnd'=>$_POST['dateEnd']));
                return $response;*/
                $logs = new Logs();
                $logs->setFecha($fecha);
                $logs->setModulo('Extracción Libros Detallado');
                $logs->setQuery('Extracción: '.$sqlMat.' / Parametros: fecIni=>'.$fecIni.'  , fecEnd => '.$fecEnd);
                $logs->setUsuario($usuario->getUsername());
                $logs->setIp($ipaddress);
                
                $logem->persist($logs);
                $logem->flush($logs);
                
                $utilities = new UtilitiesController();
                $response = $utilities->exportExcel( $resultadoLibros, $columns,$nomExcel);
                return $response;
            }else{
                if( !empty($_POST['search']['value']) ) {   // if there is a search parameter, $_POST['search']['value'] contains search parameter
                        $sqlMat.=" AND ( inscrip.fecharegistro LIKE '".$_POST['search']['value']."%' ";    
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
                    $nestedData[] = $resultadoLibros[$i]['fecharegistro'];
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
                $logs = new Logs();
                $logs->setFecha($fecha);
                $logs->setModulo('Extracción Libros Detallado');
                $logs->setQuery('Consulta: '.$sqlMat.' / Parametros: fecIni=>'.$fecIni.'  , fecEnd => '.$fecEnd);
                $logs->setUsuario($usuario->getUsername());
                $logs->setIp($ipaddress);
                
                $logem->persist($logs);
                $logem->flush($logs);
            
                return new Response(json_encode($json_data ));
            } 
            
            return new Response(json_encode(array("sql"=>$sqlMat)));
    }
    
    
    /**
     * @Route("/extraccionMatriculados", name="extraccionMatriculados")
     */
    
    public function extraccionMatriculadosAction() {
        $fecha = new \DateTime();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em =  $this->getDoctrine()->getManager('sii');
        $logem =  $this->getDoctrine()->getManager();   
        $usuario = $logem->getRepository('AppBundle:User')->findOneById($user);
        $ipaddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
        
        $utilities = new UtilitiesController();
        
        if(isset($_POST['organizacion']) && isset($_POST['estadoMat']) && isset($_POST['afiliacion']) && isset($_POST['municipio']) ){
                        
            if($_POST['estadoMat']==1){
                $where =" WHERE mei.matricula <> '' ";
                $estado = "('MA','MI','IA')";
            }else{
                $where =" LEFT JOIN mreg_est_inscripciones insc ON mei.matricula=insc.matricula
                        WHERE mei.matricula <> '' ";
                $estado = "('MC','IC') AND insc.libro IN ('RM15' , 'RM51','RE51', 'RM53', 'RM54', 'RM55', 'RM13') "
                    . "AND insc.acto IN ('0180' , '0530','0531','0532','0536','0520','0540','0498','0300')";
            }
            
            $sqlExtracMatri = "SELECT 
                            mei.matricula,
                                mei.proponente,
                                mei.organizacion,
                                mei.organizacion as 'descorganiza',
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
                                (CASE
                                    WHEN mei.organizacion = '02' AND mep.nit !='' THEN mep.nit
                                    WHEN mei.organizacion = '02' AND mep.nit ='' THEN mep.identificacion
                                    ELSE ''
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
                                mei.ctrclasegenesadl AS 'descgenesal',
                                mei.ctrclaseespeesadl,
                                mei.ctrclaseespeesadl  AS 'descespesal',
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
                        $where
                        AND mei.ctrestmatricula IN $estado 
                        AND (";
            
                        $columns=['MATRICULA',
                                    'PROPONENTE',
                                    'ORGANIZACION',
                                    'DESC.ORGANIZACION',
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
                                    'RAZON SOCIAL',
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
                                    'DESCRIP. CLA-GEN-ESADL',
                                    'CLA-ESPE-ESADL',
                                    'DESCRIP. CLA-ESPE-ESADL',
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

                $i=0;
                foreach($_POST['organizacion'] as $organiza){
                    if($organiza==0){
                        $condiOrga = " (mei.organizacion = '01') ";
                    }elseif($organiza==1){
                        $condiOrga = " (mei.organizacion IN ('03','04','05','06','07','08','09','10','11','16') AND (mei.categoria='1')) ";
                    }elseif($organiza==2){
                        $condiOrga = " (mei.organizacion = '02') ";
                    }elseif($organiza==3){
                        $condiOrga = " (mei.organizacion IN ('03','04','05','06','07','08','09','10','11','16') AND mei.categoria IN ('2','3')) ";
                    }elseif($organiza==4){
                        $condiOrga = " mei.organizacion IN ('12','14') ";
                    }elseif($organiza==5){
                        $sqlExtracMatri = "SELECT 
                                mei.matricula,
                                mei.proponente,
                                mei.organizacion,
                                mei.organizacion as 'descorganiza',
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
                                mep.identificacion AS 'idPropietario',
                                mep.razonsocial AS 'NombrePropietario',
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
                                mei.ctrclasegenesadl AS 'descgenesal',
                                mei.ctrclaseespeesadl,
                                mei.ctrclaseespeesadl  AS 'descespesal',
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
                                mei.valpagaflia,
                                mem.nomprop
                            FROM
                                mreg_est_inscritos mei
                            LEFT JOIN mreg_est_propietarios mep ON mep.matricula = mei.matricula
                            LEFT JOIN mreg_est_matriculados mem ON mem.matricula = mei.matricula 
                            $where 
                            AND mei.ctrestmatricula IN $estado"; 
                        
                            $condiOrga = "AND ((mei.organizacion = '02')
                            AND mep.codigocamara != '55'
                            AND mep.estado='V' ";
                            
                            
                            $columns=['MATRICULA',
                                        'PROPONENTE',
                                        'ORGANIZACION',
                                        'DESC.ORGANIZACION',
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
                                        'RAZON SOCIAL',
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
                                        'DESCRIP. CLA-GEN-ESADL',
                                        'CLA-ESPE-ESADL',
                                        'DESCRIP. CLA-ESPE-ESADL',
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
                                        'VAL-ULT-PAG-AFIL',
                                        'NOMPROPIETARIO'
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
                
                $utilMuni = $utilities->municipios();
                $municipios = $utilMuni['municipios'];
                
                $sqlESAL= "SELECT mce.id, mce.descripcion FROM mreg_clase_esadl mce ";
                $emESAL = $em->getConnection()->prepare($sqlESAL);
                $emESAL->execute();
                $claseESAL = $emESAL->fetchAll();
                for($i=0;$i<sizeof($claseESAL);$i++){
                    $claseEspecial[$claseESAL[$i]['id']] = $claseESAL[$i]['descripcion'];
                }
                
                $sqlOrg= "SELECT borg.id, borg.descripcion FROM bas_organizacionjuridica borg ";
                $emOrg = $em->getConnection()->prepare($sqlOrg);
                $emOrg->execute();
                $Organiza = $emOrg->fetchAll();
                for($i=0;$i<sizeof($Organiza);$i++){
                    $Organizacion[$Organiza[$i]['id']] = $Organiza[$i]['descripcion'];
                }
                
                $sqlESALgen= "SELECT mceg.id, mceg.descripcion FROM mreg_clase_esadl_gen mceg ";
                $emESALgen = $em->getConnection()->prepare($sqlESALgen);
                $emESALgen->execute();
                $claseESALgen = $emESALgen->fetchAll();
                for($i=0;$i<sizeof($claseESALgen);$i++){
                    $claseGeneral[$claseESALgen[$i]['id']] = $claseESALgen[$i]['descripcion'];
                }
                
                for($i=0;$i<sizeof($resultados);$i++){
                    if(key_exists($resultados[$i]['descorganiza'],$Organizacion)){
                        $resultados[$i]['descorganiza'] = $Organizacion[$resultados[$i]['descorganiza']];
                    }
                    if(key_exists($resultados[$i]['muncom'],$municipios)){
                        $resultados[$i]['muncom'] = $municipios[$resultados[$i]['muncom']];
                    }    
                    if(key_exists($resultados[$i]['munnot'],$municipios)){
                        $resultados[$i]['munnot'] = $municipios[$resultados[$i]['munnot']];
                    }
                    
                    if(key_exists($resultados[$i]['descgenesal'] , $claseGeneral)){
                        $resultados[$i]['descgenesal'] = $claseGeneral[$resultados[$i]['descgenesal']];
                    } 
                    
                    if(key_exists($resultados[$i]['descespesal'] , $claseEspecial)){
                        $resultados[$i]['descespesal'] = $claseEspecial[$resultados[$i]['descespesal']];
                    } 
                    
                    
                }
                
                if($_POST['excel']==1){
                    
                    for($i=0;$i<sizeof($resultados);$i++){
                        $resultados[$i]['razonsocialMat'] = utf8_decode($resultados[$i]['razonsocialMat']);
                        $resultados[$i]['NombrePropietario'] = utf8_decode($resultados[$i]['NombrePropietario']);
                        $resultados[$i]['nombre1'] = utf8_decode($resultados[$i]['nombre1']);
                        $resultados[$i]['nombre2'] = utf8_decode($resultados[$i]['nombre2']);
                        $resultados[$i]['apellido1'] = utf8_decode($resultados[$i]['apellido1']);
                        $resultados[$i]['apellido2'] = utf8_decode($resultados[$i]['apellido2']);
                    }
                    
                    $nomExcel = 'ExtraccionMatriculados';
                    
                    $logs = new Logs();
                    $logs->setFecha($fecha);
                    $logs->setModulo('Extracción Bases de Datos Generales');
                    $logs->setQuery("Extraccion: ".$sqlExtracMatri." GROUP BY mei.matricula ORDER BY mei.matricula DESC;");
                    $logs->setUsuario($usuario->getUsername());
                    $logs->setIp($ipaddress);

                    $logem->persist($logs);
                    $logem->flush($logs);
                    
                    $response = $utilities->exportExcel( $resultados, $columns,$nomExcel);
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
                    $logs = new Logs();
                    $logs->setFecha($fecha);
                    $logs->setModulo('Extracción Bases de Datos Generales');
                    $logs->setQuery("Consulta: ".$sqlExtracMatri);
                    $logs->setUsuario($usuario->getUsername());
                    $logs->setIp($ipaddress);

                    $logem->persist($logs);
                    $logem->flush($logs);

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
        $libros->execute();
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
    
    /**
     * @Route("/experian" , name="experian" )
     */
    public function experianAction() {
        $fecha = new \DateTime();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em =  $this->getDoctrine()->getManager('sii');
        $logem =  $this->getDoctrine()->getManager();
        $usuario = $logem->getRepository('AppBundle:User')->findOneById($user);
        $ipaddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
        
        $util = new UtilitiesController();
        $fecha = new \DateTime();
        $fecActual = $fecha->format('Ymd');

        $codMuni = $util->municipios();
        $municipios = $codMuni['municipios'];
        if(isset($_POST['generar'])){
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            $sqlInforma1 = "SELECT 
                                mei.matricula,
                                mei.razonsocial,
                                mei.idclase,
                                mei.nit,
                                mei.organizacion,
                                mei.categoria,
                                mei.ctrestmatricula,
                                mei.fecmatricula,
                                mei.fecrenovacion,
                                mei.fecvigencia,
                                mei.ciiu1,
                                mei.ciiu2,
                                mei.ciiu3,
                                mei.personal,
                                mei.capaut,
                                mei.capsus,
                                (SELECT 
                                        registro
                                    FROM
                                        mreg_est_capitales
                                    WHERE
                                        matricula = mei.matricula
                                    ORDER BY fechadatos DESC
                                    LIMIT 1) AS numreg,
                                (SELECT 
                                        fechadatos
                                    FROM
                                        mreg_est_capitales
                                    WHERE
                                        matricula = mei.matricula
                                    ORDER BY fechadatos DESC
                                    LIMIT 1) fechareg,
                                mei.cappag,
                                mei.actcte,
                                mei.actfij,
                                mei.actotr,
                                mei.actval,
                                mei.acttot,
                                mei.actsinaju,
                                mei.pascte,
                                mei.paslar,
                                mei.pastot,
                                mei.pattot,
                                mei.paspat,
                                mei.ingope AS ventas,
                                mei.cosven,
                                mei.utinet,
                                mei.utiope,
                                mei.dircom,
                                mei.muncom,
                                mei.telcom1,
                                mei.faxcom,
                                mei.emailcom,
                                mei.cantest,
                                mei.cprazsoc,
                                mei.cpnumnit,
                                mei.cpdircom,
                                mei.cpcodmun,
                                (SELECT 
                                        id
                                    FROM
                                        mreg_est_inscripciones
                                    WHERE
                                        matricula = mei.matricula
                                            AND acto = '0510'
                                    LIMIT 1) AS liquidacion
                            FROM
                                mreg_est_inscritos mei
                                    INNER JOIN
                                mreg_est_recibos mer ON mei.matricula = mer.matricula
                            WHERE
                                mer.fecoperacion BETWEEN '$fecIni' AND '$fecEnd'
                            AND (mer.servicio LIKE '010202%'
                                    OR mer.servicio LIKE '010203%'
                                    OR mer.servicio LIKE '0103%')
                            AND mer.ctranulacion = '0'
                            AND mei.matricula !='' 
                            GROUP BY mei.matricula ";    
            
            
            $info1 = $em->getConnection()->prepare($sqlInforma1);
            $info1->execute();
            $datosInforma1 = $info1->fetchAll();
            $infomaData = array();
            
            $contReg = 0; 
            $contRegCert = 0; 
            $contRegRepLeg = 0; 
            
            for($i=0;$i<sizeof($datosInforma1);$i++){
                if($datosInforma1[$i]['organizacion'] !=='02'){
                    $contReg++;
                    $arreglo = '';
                    $matricula = $util->preparaInforma($datosInforma1[$i]['matricula'], 'entero', 8);
                    $arreglo.= $matricula['dato'];
                    $arreglo.= $util->preparaInforma('', 'string', 11);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['razonsocial'], 'string', 260);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['idclase'], 'string', 1);
                    $id = $util->preparaInforma(substr($datosInforma1[$i]['nit'], 0, 9), 'entero', 14);
                    $arreglo.= $id['dato'];
                    if(substr($datosInforma1[$i]['nit'],-1,1)==''){
                        $dvv=0;
                    }else{
                        $dvv=substr($datosInforma1[$i]['nit'],-1,1);
                    }
                    $dv = $util->preparaInforma($dvv, 'entero', 1);
                    $arreglo.= $dv['dato'];
                    $catg = $util->preparaInforma($datosInforma1[$i]['organizacion'], 'entero', 2);
                    $arreglo.= $catg['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['categoria'], 'string', 1);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['ctrestmatricula'], 'string', 2);
                    $fecMat = $util->preparaInforma($datosInforma1[$i]['fecmatricula'], 'entero', 8);
                    $arreglo.= $fecMat['dato'];
                    $fecRen = $util->preparaInforma($datosInforma1[$i]['fecrenovacion'], 'entero', 8);
                    $arreglo.= $fecRen['dato'];
                    $fecVig = $util->preparaInforma($datosInforma1[$i]['fecvigencia'], 'entero', 8);
                    $arreglo.= $fecVig['dato'];
                    $ciiu1 = substr($datosInforma1[$i]['ciiu1'],1);
                    $arreglo.= $util->preparaInforma($ciiu1, 'ciiu', 7);
                    $ciiu2 = substr($datosInforma1[$i]['ciiu2'],1);
                    $arreglo.= $util->preparaInforma($ciiu2, 'ciiu', 7);
                    $ciiu3 = substr($datosInforma1[$i]['ciiu3'],1);
                    $arreglo.= $util->preparaInforma($ciiu3, 'ciiu', 7);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['personal'], 'string', 6);
                    $capaut = $util->preparaInforma($datosInforma1[$i]['capaut'], 'entero', 17);
                    $arreglo.= $capaut['dato'];
                    $capsus = $util->preparaInforma($datosInforma1[$i]['capsus'], 'entero', 17);
                    $arreglo.= $capsus['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['numreg'], 'string', 8);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['fechareg'], 'string', 8);
                    $cappag = $util->preparaInforma($datosInforma1[$i]['cappag'], 'entero', 17);
                    $arreglo.= $cappag['dato'];
                    $actcte = $util->preparaInforma($datosInforma1[$i]['actcte'], 'entero', 17);
                    $arreglo.= $actcte['dato'];
                    $actfij = $util->preparaInforma($datosInforma1[$i]['actfij'], 'entero', 17);
                    $arreglo.= $actfij['dato'];
                    $actotr = $util->preparaInforma($datosInforma1[$i]['actotr'], 'entero', 17);
                    $arreglo.= $actotr['dato'];
                    $actval = $util->preparaInforma($datosInforma1[$i]['actval'], 'entero', 17);
                    $arreglo.= $actval['dato'];
                    $acttot = $util->preparaInforma($datosInforma1[$i]['acttot'], 'entero', 17);
                    $arreglo.= $acttot['dato'];
                    $actsinaju = $util->preparaInforma($datosInforma1[$i]['actsinaju'], 'entero', 17);
                    $arreglo.= $actsinaju['dato'];
                    $pascte = $util->preparaInforma($datosInforma1[$i]['pascte'], 'entero', 17);
                    $arreglo.= $pascte['dato'];
                    $paslar = $util->preparaInforma($datosInforma1[$i]['paslar'], 'entero', 17);
                    $arreglo.= $paslar['dato'];
                    $pastot = $util->preparaInforma($datosInforma1[$i]['pastot'], 'entero', 17);
                    $arreglo.= $pastot['dato'];
                    $pattot = $util->preparaInforma($datosInforma1[$i]['pattot'], 'entero', 17);
                    $arreglo.= $pattot['signo'];
                    $arreglo.= $pattot['dato'];
                    $paspat = $util->preparaInforma($datosInforma1[$i]['paspat'], 'entero', 17);
                    $arreglo.= $paspat['dato'];
                    $ventas = $util->preparaInforma($datosInforma1[$i]['ventas'], 'entero', 17);
                    $arreglo.= $ventas['signo'];
                    $arreglo.= $ventas['dato'];
                    $cosven = $util->preparaInforma($datosInforma1[$i]['cosven'], 'entero', 17);
                    $arreglo.= $cosven['signo'];
                    $arreglo.= $cosven['dato'];  
                    $utinet = $util->preparaInforma($datosInforma1[$i]['utinet'], 'entero', 17);
                    $arreglo.= $utinet['signo'];
                    $arreglo.= $utinet['dato'];                
                    $utiope = $util->preparaInforma($datosInforma1[$i]['utiope'], 'entero', 17);
                    $arreglo.= $utiope['signo'];
                    $arreglo.= $utiope['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['dircom'], 'string', 65);
                    if(key_exists($datosInforma1[$i]['muncom'], $municipios)){
                        $muncom = $datosInforma1[$i]['muncom'];
                        if($muncom=='')$muncom='0000';
                        $arreglo.= $util->preparaInforma($municipios[$muncom], 'string', 25);
                    }else{
                        $arreglo.= $util->preparaInforma('', 'string', 25);
                    }
                    $arreglo.= $util->preparaInforma(0, 'string', 10);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['telcom1'], 'string', 10);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['faxcom'], 'string', 10);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['emailcom'], 'string', 50);
                    $cantest = $util->preparaInforma($datosInforma1[$i]['cantest'], 'entero', 5);
                    $arreglo.= $cantest['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cprazsoc'], 'string', 65);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cpnumnit'], 'string', 11);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cpdircom'], 'string', 65);
                    if(key_exists($datosInforma1[$i]['cpcodmun'], $municipios)){
                        $indexMun = $datosInforma1[$i]['cpcodmun'];
                    }else{
                        $indexMun='0000';
                    }
                    $arreglo.= $util->preparaInforma($municipios[$indexMun], 'string', 25);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['liquidacion'], 'string', 1);

                    $infomaData[] = $arreglo;
                
                    if($datosInforma1[$i]['organizacion']!=='01' && $datosInforma1[$i]['organizacion']!=='02' && $datosInforma1[$i]['organizacion']!=='12'  && $datosInforma1[$i]['organizacion']!=='14' ){
                        $sqlVinculos = "SELECT mev.matricula, mev.nombre, mev.idclase, mev.numid, mev.idcargo, mev.vinculo, mev.descargo, mev.cuotasref, mev.valorref FROM mreg_est_vinculos mev WHERE mev.matricula=:matricula";
                        $info2 = $em->getConnection()->prepare($sqlVinculos);
                        $info2->execute(array('matricula'=>$datosInforma1[$i]['matricula']));
                        $resultVinculos = $info2->fetchAll();


                        for($j=0;$j<sizeof($resultVinculos);$j++){
                            $vinculos = '';
                            $matricula = $util->preparaInforma($resultVinculos[$j]['matricula'], 'entero', 8);
                            $vinculos .= $matricula['dato'];
                            $vinculos .= $util->preparaInforma($resultVinculos[$j]['nombre'], 'string', 65);
                            $idclase = $util->preparaInforma($resultVinculos[$j]['idclase'], 'entero', 1);
                            $vinculos .= $idclase['dato'];
                            $numid = $util->preparaInforma($resultVinculos[$j]['numid'], 'entero', 11);
                            $vinculos .= $numid['dato'];

                            $ctrCargo = substr($resultVinculos[$j]['vinculo'],0,3);
                            if($ctrCargo=='217'){
                                $vctrcargo = 3;
                            }elseif($ctrCargo=='214'){
                                $vctrcargo = 1;
                            }elseif($ctrCargo=='216'){
                                $vctrcargo = 4;
                            }else{
                                $vctrcargo = 2;
                            }
                            $vlrCtrCargo = $util->preparaInforma($vctrcargo, 'entero', 2);
                            $vinculos .= $vlrCtrCargo['dato'];
                            $vlrVinculo = $util->preparaInforma($resultVinculos[$j]['vinculo'], 'entero', 4);
                            $vinculos .= $vlrVinculo['dato'];
                            $vlrCargo = $util->preparaInforma($resultVinculos[$j]['idcargo'], 'entero', 4);
                            $vinculos .= $vlrCargo['dato'];
                            $vinculos .= $util->preparaInforma($resultVinculos[$j]['descargo'], 'string', 65);
                            $cuotasref = $resultVinculos[$j]['cuotasref'].'00';
                            $vlrcuotasref = $util->preparaInforma($cuotasref, 'entero', 19);
                            $vinculos .= $vlrcuotasref['dato'];
                            $valorref = $resultVinculos[$j]['valorref'].'00';
                            $vlrValorref = $util->preparaInforma($valorref, 'entero', 19);
                            $vinculos .= $vlrValorref['dato'];
                            $contRegRepLeg++;
                            $infoVinculos[] = $vinculos;
                        }
                    }

                    $sqlCertificas = "SELECT mecerti.matricula, mecerti.idcertifica, mecerti.texto "
                            . "FROM mreg_est_certificas mecerti "
                            . "WHERE mecerti.matricula=:matricula "
                            . "ORDER BY mecerti.matricula, mecerti.id ASC ";
                    $info3 = $em->getConnection()->prepare($sqlCertificas);
                    $info3->execute(array('matricula'=>$datosInforma1[$i]['matricula']));
                    $resultCertificas = $info3->fetchAll();                    

                    for($k=0;$k<sizeof($resultCertificas);$k++){


                        $longCertifica = strlen($resultCertificas[$k]['texto']);
                        $consec = 1;
    //                    for($n=0;$n<=$longCertifica;$n++){

                            $matricula = $util->preparaInforma($resultCertificas[$k]['matricula'], 'entero', 8);
                            for($n=0;$n<$longCertifica;$n++){
                                $certificas = '';
                                $certificas .= $matricula['dato'];
                                $certificas .= $resultCertificas[$k]['idcertifica'];
                                $contConse = $util->preparaInforma($consec, 'entero', 4);
                                $certificas .= $contConse['dato'];
                                $partCertifica = substr($resultCertificas[$k]['texto'], $n, 70);
                                $certificas .= $util->preparaInforma($partCertifica, 'string', 70);
                                $n=$n+69;
                                $consec++;
                                $contRegCert++;
                                $infoCertifica[] = $certificas;
                            }
                            $certificas = '';
                            $certificas .= $matricula['dato'];
                            $certificas .= $resultCertificas[$k]['idcertifica'];
                            $contConse = $util->preparaInforma($consec, 'entero', 4);
                            $certificas .= $contConse['dato'];
                            $certificas .= $util->preparaInforma('  ', 'string', 70);
                            $n=$n+69;
                            $consec++;
                            $contRegCert++;
                            $infoCertifica[] = $certificas; 


                    }


                }elseif($datosInforma1[$i]['organizacion']=='02'){
                    
                }
            }
            $infomaData[] = '********'.$contReg;
            $infoVinculos[] = '********'.$contRegRepLeg;
            $infoCertifica[] = '   ';
            $content = implode("\n", $infomaData);
            $informe = $this->renderView('informa1.txt.twig',array('infomaData'=>$content));
            
            $logs = new Logs();
            $logs->setFecha($fecha);
            $logs->setModulo('informaColombia');
            $logs->setQuery('Genera Archivos: '.$sqlInforma1);
            $logs->setUsuario($usuario->getUsername());
            $logs->setIp($ipaddress);

            $logem->persist($logs);
            $logem->flush($logs);
                       
            $fs = new Filesystem();
            $archivo = $this->container->getParameter('kernel.root_dir').'/data/informes/informa1.txt';

            try {
                $fs->dumpFile($archivo, $informe);
            } catch (IOExceptionInterface $e) {
                echo "Se ha producido un error al crear el archivo ".$e->getPath();
            }
            
            $contentVinc = implode("\n", $infoVinculos);
            $informeVinc = $this->renderView('informa1.txt.twig',array('infomaData'=>$contentVinc));
            
                       
            $fsv = new Filesystem();
            $archivoVinc = $this->container->getParameter('kernel.root_dir').'/data/informes/informa2.txt';

            try {
                $fsv->dumpFile($archivoVinc, $informeVinc);
            } catch (IOExceptionInterface $e) {
                echo "Se ha producido un error al crear el archivo ".$e->getPath();
            }
            
//            Lineas para crear informa3.txt
            
            $contentCert = implode("\n", $infoCertifica);
            $informeCert = $this->renderView('informa1.txt.twig',array('infomaData'=>$contentCert));
            
                       
            $fsc = new Filesystem();
            $archivoCert = $this->container->getParameter('kernel.root_dir').'/data/informes/informa3.txt';

            try {
                $fsc->dumpFile($archivoCert, $informeCert);
            } catch (IOExceptionInterface $e) {
                echo "Se ha producido un error al crear el archivo ".$e->getPath();
            }
            
//            se agregan archivos para el zip
           $informaName = 'informaColombia'.$fecActual.'.zip' ;
           $zip = new \ZipArchive();
           $archivoZip = $this->container->getParameter('kernel.root_dir').'/data/informes/'.$informaName;
           
            if ($zip->open($archivoZip, \ZipArchive::CREATE) !== TRUE) {
            exit("cannot open <$archivoZip>\n");
            }

            $zip->addFile($archivo, "informa1.txt");

            $zip->addFile($archivoVinc, "informa2.txt");
            
            $zip->addFile($archivoCert, "data03.txt");

            $zip->close();
            header('Content-Type', 'application/zip');
            header('Content-disposition: attachment; filename="'.$informaName.'"');
            header('Content-Length: ' . filesize($archivoZip));
            readfile($archivoZip);
            return new Response(json_encode(array('ruta' => $archivo )));
        }else{
            return $this->render('default/experian.html.twig');
        }
    } 
    
    /**
     * @Route("/informaColombia" , name="informaColombia" )
     */
    public function informaColombiaAction() {
        $fecha = new \DateTime();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em =  $this->getDoctrine()->getManager('sii');
        $logem =  $this->getDoctrine()->getManager();
        
        $usuario = $logem->getRepository('AppBundle:User')->findOneById($user);
        $ipaddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
        
        $util = new UtilitiesController();
        $fecha = new \DateTime();
        $fecActual = $fecha->format('Ymd');

        $codMuni = $util->municipios();
        $municipios = $codMuni['municipios'];
        if(isset($_POST['generar'])){
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            $sqlInforma1 = "SELECT 
                                mei.matricula,
                                mei.razonsocial,
                                mei.idclase,
                                mei.nit,
                                mei.organizacion,
                                mei.categoria,
                                mei.ctrestmatricula,
                                mei.fecmatricula,
                                mei.fecrenovacion,
                                mei.feccancelacion,
                                mei.fecvigencia,
                                mei.ultanoren,
                                mei.ciiu1,
                                mei.ciiu2,
                                mei.ciiu3,
                                mei.personal,
                                mei.capaut,
                                mei.capsus,
                                (SELECT 
                                        registro
                                    FROM
                                        mreg_est_capitales
                                    WHERE
                                        matricula = mei.matricula
                                    ORDER BY fechadatos DESC
                                    LIMIT 1) AS numreg,
                                (SELECT 
                                        fechadatos
                                    FROM
                                        mreg_est_capitales
                                    WHERE
                                        matricula = mei.matricula
                                    ORDER BY fechadatos DESC
                                    LIMIT 1) fechareg,
                                mei.cappag,
                                mei.actcte,
                                mei.actfij,
                                mei.actotr,
                                mei.actval,
                                mei.acttot,
                                mei.actvin,
                                mei.actsinaju,
                                mei.pascte,
                                mei.paslar,
                                mei.pastot,
                                mei.pattot,
                                mei.paspat,
                                mei.ingope AS ventas,
                                mei.cosven,
                                mei.utinet,
                                mei.utiope,
                                mei.dircom,
                                mei.muncom,
                                mei.telcom1,
                                mei.faxcom,
                                mei.emailcom,
                                mei.cantest,
                                mei.cprazsoc,
                                mei.cpnumnit,
                                mei.cpdircom,
                                mei.cpcodmun,
                                (SELECT 
                                        id
                                    FROM
                                        mreg_est_inscripciones
                                    WHERE
                                        matricula = mei.matricula
                                            AND acto = '0510'
                                    LIMIT 1) AS liquidacion,
                                mer.fecoperacion,
                                mer.horaoperacion
                            FROM
                                mreg_est_inscritos mei
                                    INNER JOIN
                                mreg_est_recibos mer ON mei.matricula = mer.matricula
                            WHERE
                                mer.fecoperacion BETWEEN '$fecIni' AND '$fecEnd'
                            AND (mer.servicio LIKE '010202%'
                                    OR mer.servicio LIKE '010203%'
                                    OR mer.servicio LIKE '0103%')
                            AND mer.ctranulacion = '0'
                            AND mei.matricula !='' 
                            GROUP BY mei.matricula ";    
            
            
            $info1 = $em->getConnection()->prepare($sqlInforma1);
            $info1->execute();
            $datosInforma1 = $info1->fetchAll();
            $infomaData = array();
            
            $contReg = 0; 
            $contRegCert = 0; 
            $contRegRepLeg = 0; 
            
            for($i=0;$i<sizeof($datosInforma1);$i++){
                if($datosInforma1[$i]['organizacion'] !=='02'){
                    $contReg++;
                    $arreglo = '';
                    $matricula = $util->preparaInforma($datosInforma1[$i]['matricula'], 'entero', 8);
                    $arreglo.= $matricula['dato'];
                    $arreglo.= $util->preparaInforma('', 'string', 11);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['razonsocial'], 'string', 260);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['idclase'], 'string', 1);
                    $id = $util->preparaInforma(substr($datosInforma1[$i]['nit'], 0, 9), 'entero', 14);
                    $arreglo.= $id['dato'];
                    if(substr($datosInforma1[$i]['nit'],-1,1)==''){
                        $dvv=0;
                    }else{
                        $dvv=substr($datosInforma1[$i]['nit'],-1,1);
                    }
                    $dv = $util->preparaInforma($dvv, 'entero', 1);
                    $arreglo.= $dv['dato'];
                    $catg = $util->preparaInforma($datosInforma1[$i]['organizacion'], 'entero', 2);
                    $arreglo.= $catg['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['categoria'], 'string', 1);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['ctrestmatricula'], 'string', 2);
                    $fecMat = $util->preparaInforma($datosInforma1[$i]['fecmatricula'], 'entero', 8);
                    $arreglo.= $fecMat['dato'];
                    $fecRen = $util->preparaInforma($datosInforma1[$i]['fecrenovacion'], 'entero', 8);
                    $arreglo.= $fecRen['dato'];
                    $fecVig = $util->preparaInforma($datosInforma1[$i]['fecvigencia'], 'entero', 8);
                    $arreglo.= $fecVig['dato'];
                    $ciiu1 = substr($datosInforma1[$i]['ciiu1'],1);
                    $arreglo.= $util->preparaInforma($ciiu1, 'ciiu', 7);
                    $ciiu2 = substr($datosInforma1[$i]['ciiu2'],1);
                    $arreglo.= $util->preparaInforma($ciiu2, 'ciiu', 7);
                    $ciiu3 = substr($datosInforma1[$i]['ciiu3'],1);
                    $arreglo.= $util->preparaInforma($ciiu3, 'ciiu', 7);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['personal'], 'string', 6);
                    $capaut = $util->preparaInforma($datosInforma1[$i]['capaut'], 'entero', 17);
                    $arreglo.= $capaut['dato'];
                    $capsus = $util->preparaInforma($datosInforma1[$i]['capsus'], 'entero', 17);
                    $arreglo.= $capsus['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['numreg'], 'string', 8);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['fechareg'], 'string', 8);
                    $cappag = $util->preparaInforma($datosInforma1[$i]['cappag'], 'entero', 17);
                    $arreglo.= $cappag['dato'];
                    $actcte = $util->preparaInforma($datosInforma1[$i]['actcte'], 'entero', 17);
                    $arreglo.= $actcte['dato'];
                    $actfij = $util->preparaInforma($datosInforma1[$i]['actfij'], 'entero', 17);
                    $arreglo.= $actfij['dato'];
                    $actotr = $util->preparaInforma($datosInforma1[$i]['actotr'], 'entero', 17);
                    $arreglo.= $actotr['dato'];
                    $actval = $util->preparaInforma($datosInforma1[$i]['actval'], 'entero', 17);
                    $arreglo.= $actval['dato'];
                    $acttot = $util->preparaInforma($datosInforma1[$i]['acttot'], 'entero', 17);
                    $arreglo.= $acttot['dato'];
                    $actsinaju = $util->preparaInforma($datosInforma1[$i]['actsinaju'], 'entero', 17);
                    $arreglo.= $actsinaju['dato'];
                    $pascte = $util->preparaInforma($datosInforma1[$i]['pascte'], 'entero', 17);
                    $arreglo.= $pascte['dato'];
                    $paslar = $util->preparaInforma($datosInforma1[$i]['paslar'], 'entero', 17);
                    $arreglo.= $paslar['dato'];
                    $pastot = $util->preparaInforma($datosInforma1[$i]['pastot'], 'entero', 17);
                    $arreglo.= $pastot['dato'];
                    $pattot = $util->preparaInforma($datosInforma1[$i]['pattot'], 'entero', 17);
                    $arreglo.= $pattot['signo'];
                    $arreglo.= $pattot['dato'];
                    $paspat = $util->preparaInforma($datosInforma1[$i]['paspat'], 'entero', 17);
                    $arreglo.= $paspat['dato'];
                    $ventas = $util->preparaInforma($datosInforma1[$i]['ventas'], 'entero', 17);
                    $arreglo.= $ventas['signo'];
                    $arreglo.= $ventas['dato'];
                    $cosven = $util->preparaInforma($datosInforma1[$i]['cosven'], 'entero', 17);
                    $arreglo.= $cosven['signo'];
                    $arreglo.= $cosven['dato'];  
                    $utinet = $util->preparaInforma($datosInforma1[$i]['utinet'], 'entero', 17);
                    $arreglo.= $utinet['signo'];
                    $arreglo.= $utinet['dato'];                
                    $utiope = $util->preparaInforma($datosInforma1[$i]['utiope'], 'entero', 17);
                    $arreglo.= $utiope['signo'];
                    $arreglo.= $utiope['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['dircom'], 'string', 65);
                    if(key_exists($datosInforma1[$i]['muncom'], $municipios)){
                        $muncom = $datosInforma1[$i]['muncom'];
                        if($muncom=='')$muncom='0000';
                        $arreglo.= $util->preparaInforma($municipios[$muncom], 'string', 25);
                    }else{
                        $arreglo.= $util->preparaInforma('', 'string', 25);
                    }
                    $arreglo.= $util->preparaInforma(0, 'string', 10);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['telcom1'], 'string', 10);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['faxcom'], 'string', 10);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['emailcom'], 'string', 50);
                    $cantest = $util->preparaInforma($datosInforma1[$i]['cantest'], 'entero', 5);
                    $arreglo.= $cantest['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cprazsoc'], 'string', 65);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cpnumnit'], 'string', 11);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cpdircom'], 'string', 65);
                    if(key_exists($datosInforma1[$i]['cpcodmun'], $municipios)){
                        $indexMun = $datosInforma1[$i]['cpcodmun'];
                    }else{
                        $indexMun='0000';
                    }
                    $arreglo.= $util->preparaInforma($municipios[$indexMun], 'string', 25);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['liquidacion'], 'string', 1);

                    $infomaData[] = $arreglo;
                
                    if($datosInforma1[$i]['organizacion']!=='01' && $datosInforma1[$i]['organizacion']!=='02' && $datosInforma1[$i]['organizacion']!=='12'  && $datosInforma1[$i]['organizacion']!=='14' ){
                        $sqlVinculos = "SELECT mev.matricula, mev.nombre, mev.idclase, mev.numid, mev.idcargo, mev.vinculo, mev.descargo, mev.cuotasref, mev.valorref FROM mreg_est_vinculos mev WHERE mev.matricula=:matricula";
                        $info2 = $em->getConnection()->prepare($sqlVinculos);
                        $info2->execute(array('matricula'=>$datosInforma1[$i]['matricula']));
                        $resultVinculos = $info2->fetchAll();


                        for($j=0;$j<sizeof($resultVinculos);$j++){
                            $vinculos = '';
                            $matricula = $util->preparaInforma($resultVinculos[$j]['matricula'], 'entero', 8);
                            $vinculos .= $matricula['dato'];
                            $vinculos .= $util->preparaInforma($resultVinculos[$j]['nombre'], 'string', 65);
                            $idclase = $util->preparaInforma($resultVinculos[$j]['idclase'], 'entero', 1);
                            $vinculos .= $idclase['dato'];
                            $numid = $util->preparaInforma($resultVinculos[$j]['numid'], 'entero', 11);
                            $vinculos .= $numid['dato'];

                            $ctrCargo = substr($resultVinculos[$j]['vinculo'],0,3);
                            if($ctrCargo=='217'){
                                $vctrcargo = 3;
                            }elseif($ctrCargo=='214'){
                                $vctrcargo = 1;
                            }elseif($ctrCargo=='216'){
                                $vctrcargo = 4;
                            }else{
                                $vctrcargo = 2;
                            }
                            $vlrCtrCargo = $util->preparaInforma($vctrcargo, 'entero', 2);
                            $vinculos .= $vlrCtrCargo['dato'];
                            $vlrVinculo = $util->preparaInforma($resultVinculos[$j]['vinculo'], 'entero', 4);
                            $vinculos .= $vlrVinculo['dato'];
                            $vlrCargo = $util->preparaInforma($resultVinculos[$j]['idcargo'], 'entero', 4);
                            $vinculos .= $vlrCargo['dato'];
                            $vinculos .= $util->preparaInforma($resultVinculos[$j]['descargo'], 'string', 65);
                            $cuotasref = $resultVinculos[$j]['cuotasref'].'00';
                            $vlrcuotasref = $util->preparaInforma($cuotasref, 'entero', 19);
                            $vinculos .= $vlrcuotasref['dato'];
                            $valorref = $resultVinculos[$j]['valorref'].'00';
                            $vlrValorref = $util->preparaInforma($valorref, 'entero', 19);
                            $vinculos .= $vlrValorref['dato'];
                            $contRegRepLeg++;
                            $infoVinculos[] = $vinculos;
                        }
                    }

                    $sqlCertificas = "SELECT mecerti.matricula, mecerti.idcertifica, mecerti.texto "
                            . "FROM mreg_est_certificas mecerti "
                            . "WHERE mecerti.matricula=:matricula "
                            . "ORDER BY mecerti.matricula, mecerti.id ASC ";
                    $info3 = $em->getConnection()->prepare($sqlCertificas);
                    $info3->execute(array('matricula'=>$datosInforma1[$i]['matricula']));
                    $resultCertificas = $info3->fetchAll();                    

                    for($k=0;$k<sizeof($resultCertificas);$k++){


                        $longCertifica = strlen($resultCertificas[$k]['texto']);
                        $consec = 1;
    //                    for($n=0;$n<=$longCertifica;$n++){

                            $matricula = $util->preparaInforma($resultCertificas[$k]['matricula'], 'entero', 8);
                            for($n=0;$n<$longCertifica;$n++){
                                $certificas = '';
                                $certificas .= $matricula['dato'];
                                $certificas .= $resultCertificas[$k]['idcertifica'];
                                $contConse = $util->preparaInforma($consec, 'entero', 4);
                                $certificas .= $contConse['dato'];
                                $partCertifica = substr($resultCertificas[$k]['texto'], $n, 65);
                                $certificas .= $util->preparaInforma($partCertifica, 'string', 65);
                                $n=$n+64;
                                $consec++;
                                $contRegCert++;
                                $infoCertifica[] = $certificas;
                            }



                    }


                }elseif($datosInforma1[$i]['organizacion']=='02'){
                    $sqlPropie = "SELECT mep.matriculapropietario, (CASE
                                    WHEN mep.tipoidentificacion !='' THEN mep.nit
                                    WHEN mep.nit ='' THEN mep.identificacion
                                    ELSE ''
                                END) AS 'idPropietario',
                                mep.tipoidentificacion,
                                mep.codigocamara,
                                mep.apellido1
                                FROM mreg_est_propietarios mep
                                WHERE mep.matricula=:matricula 
                                AND mep.estado='V' ";
                    $prop03 = $em->getConnection()->prepare($sqlPropie);
                    $prop03->execute(array('matricula'=>$datosInforma1[$i]['matricula']));
                    $propiet = $prop03->fetchAll();  
                    if(sizeof($propiet)>0){
                        $propietario = '';
                        if($propiet[0]['codigocamara']=='55'){
                            $matProp = $util->preparaInforma($propiet[0]['matriculapropietario'], 'entero', 8);
                            $propietario.= $matProp['dato'];
                        }else{
                            $matProp= $util->preparaInforma(0, 'entero', 8);
                            $propietario.= $matProp['dato'];
                        }
                        $matricula = $util->preparaInforma($datosInforma1[$i]['matricula'], 'entero', 8);
                        $propietario.= $matricula['dato'];
                        
                        if($propiet[0]['tipoidentificacion']==''){
                            $tipo=0;
                        }else{
                            $tipo=$propiet[0]['tipoidentificacion'];
                        }
                        $propietario.= $tipo;
                        
                        if($propiet[0]['apellido1']!=''){
                            $idProp = $util->preparaInforma($propiet[0]['idPropietario'], 'entero', 14);
                            $propietario.= $idProp['dato'];
                            $propietario.= 0;
                        }else{
                            $idProp = $util->preparaInforma($propiet[0]['idPropietario'], 'entero', 15);
                            $propietario.= $idProp['dato'];
                        }
                        $propietario.= $util->preparaInforma($datosInforma1[$i]['fecmatricula'], 'string', 8);
                        $propietario.= $util->preparaInforma($datosInforma1[$i]['razonsocial'], 'string', 130);
                        $propietario.= $util->preparaInforma($datosInforma1[$i]['dircom'], 'string', 65);                      
                        if(key_exists($datosInforma1[$i]['muncom'],$municipios)){
                            $propietario.= $util->preparaInforma($municipios[$datosInforma1[$i]['muncom']], 'string', 25);
                        }else{
                            $propietario.= $util->preparaInforma($datosInforma1[$i]['muncom'], 'string', 25);
                        }

                        $propietario.= $util->preparaInforma($datosInforma1[$i]['telcom1'], 'string', 10);
                        $propietario.= $util->preparaInforma($datosInforma1[$i]['ctrestmatricula'], 'string', 2);
                        $propietario.= $util->preparaInforma($datosInforma1[$i]['feccancelacion'], 'string', 8);
                        $ciiu1 = substr($datosInforma1[$i]['ciiu1'],1);
                        $propietario.= $util->preparaInforma($ciiu1, 'ciiu', 7);
                        $personal = $util->preparaInforma($datosInforma1[$i]['personal'], 'entero', 6);
                        $propietario.=$personal['dato'];
                        $actEst = $util->preparaInforma($datosInforma1[$i]['actvin'], 'entero', 17);
                        $propietario.= $actEst['dato'];
                        $propietario.= $util->preparaInforma($datosInforma1[$i]['ultanoren'], 'string', 4);
                        $propietario.= $util->preparaInforma($datosInforma1[$i]['fecoperacion'], 'string', 8);
                        $propietario.= $util->preparaInforma(substr($datosInforma1[$i]['horaoperacion'],0,6), 'string', 6);
                        $propietario.= $util->preparaInforma(' ', 'string', 1);

                        $informaEst[] =  $propietario;
                    }        
                }
            }
            $infomaData[] = '********'.$contReg;
            $infoVinculos[] = '********'.$contRegRepLeg;
            $infoCertifica[] = '********'.$contRegCert;
            $content = implode("\n", $infomaData);
            $informe = $this->renderView('informa1.txt.twig',array('infomaData'=>$content));
            
            $logs = new Logs();
            $logs->setFecha($fecha);
            $logs->setModulo('informaColombia');
            $logs->setQuery('Genera Archivos: '.$sqlInforma1);
            $logs->setUsuario($usuario->getUsername());
            $logs->setIp($ipaddress);

            $logem->persist($logs);
            $logem->flush($logs);
                       
            $fs = new Filesystem();
            $archivo = $this->container->getParameter('kernel.root_dir').'/data/informes/informa1.txt';

            try {
                $fs->dumpFile($archivo, $informe);
            } catch (IOExceptionInterface $e) {
                echo "Se ha producido un error al crear el archivo ".$e->getPath();
            }
            
            $contentVinc = implode("\n", $infoVinculos);
            $informeVinc = $this->renderView('informa1.txt.twig',array('infomaData'=>$contentVinc));
            
                       
            $fsv = new Filesystem();
            $archivoVinc = $this->container->getParameter('kernel.root_dir').'/data/informes/informa2.txt';

            try {
                $fsv->dumpFile($archivoVinc, $informeVinc);
            } catch (IOExceptionInterface $e) {
                echo "Se ha producido un error al crear el archivo ".$e->getPath();
            }
            
//            Lineas para crear informa3.txt
            
            $contentCert = implode("\n", $infoCertifica);
            $informeCert = $this->renderView('informa1.txt.twig',array('infomaData'=>$contentCert));
            
                       
            $fsc = new Filesystem();
            $archivoCert = $this->container->getParameter('kernel.root_dir').'/data/informes/informa3.txt';

            try {
                $fsc->dumpFile($archivoCert, $informeCert);
            } catch (IOExceptionInterface $e) {
                echo "Se ha producido un error al crear el archivo ".$e->getPath();
            }
            
            //            Lineas para crear informa4.txt
            
            $contentEst = implode("\n", $informaEst);
            $informeEst = $this->renderView('informa1.txt.twig',array('infomaData'=>$contentEst));
            
                       
            $fEst = new Filesystem();
            $archivoEst = $this->container->getParameter('kernel.root_dir').'/data/informes/informa4.txt';

            try {
                $fEst->dumpFile($archivoEst, $informeEst);
            } catch (IOExceptionInterface $e) {
                echo "Se ha producido un error al crear el archivo ".$e->getPath();
            }
            
//            se agregan archivos para el zip
           $informaName = 'informaColombia'.$fecActual.'.zip' ;
           $zip = new \ZipArchive();
           $archivoZip = $this->container->getParameter('kernel.root_dir').'/data/informes/'.$informaName;
           
            if ($zip->open($archivoZip, \ZipArchive::CREATE) !== TRUE) {
            exit("cannot open <$archivoZip>\n");
            }

            $zip->addFile($archivo, "informa1.txt");

            $zip->addFile($archivoVinc, "informa2.txt");
            
            $zip->addFile($archivoCert, "informa3.txt");
            
            $zip->addFile($archivoEst, "informa4.txt");

            $zip->close();
            header('Content-Type', 'application/zip');
            header('Content-disposition: attachment; filename="'.$informaName.'"');
            header('Content-Length: ' . filesize($archivoZip));
            readfile($archivoZip);
            return new Response(json_encode(array('ruta' => $archivo )));
        }else{
            return $this->render('default/informaColombia.html.twig',array('ip'=>$ipaddress));
        }
    } 

}
