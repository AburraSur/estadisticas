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
use Ob\HighchartsBundle\Highcharts\Highchart;

class DefaultController extends Controller
{    
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em =  $this->getDoctrine()->getManager();   
        $usuario = $em->getRepository('AppBundle:User')->findOneById($user);
        $router = $this->container->get('router');
        
        if($usuario->hasRole('ROLE_PRESIDENCIA')){
            return new RedirectResponse($router->generate('estadisticasComparativas'), 307);
        }else{
            return $this->render('default/index.html.twig');
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
                    . "AND inscritos.ultanoren between :annoInicial AND :annoFinal ";
            
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
            $paramsRenovados = array('fecIni'=>$fecIni , 'fecEnd' => $fecEnd , 'annoInicial'=>$fechaInicial[0] , 'annoFinal'=>$fechaFinal[0]);
            
//            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
            $stmt = $SIIem->getConnection()->prepare($sqlMat);
            $strv = $SIIem->getConnection()->prepare($sqlRen);
            $stcn = $SIIem->getConnection()->prepare($sqlCan);
            
//            Ejecución de las consultas
            $stmt->execute($params);
            $strv->execute($paramsRenovados);
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
                
                
                

                $nomExcel = 'ResumenMatRenCan';
                
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
        $data = Array();
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
                    $resultadosServicios[$i]['Cliente'] = $resultadosServicios[$i]['Cliente'];
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
                    $sqlMat.="AND ((inscrip.libro='$key' AND inscrip.acto IN('$actos')) ";
                    $sw++;
                }else{
                    $sqlMat.="OR (inscrip.libro='$key' AND inscrip.acto IN('$actos')) ";
                }
                
                
            }
            
            $sqlMat.=") GROUP BY inscrip.id";
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
                        . "<td>".number_format($value,"0","",".")."</td>";
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
        $utilities = new UtilitiesController();
        $data = Array();
        $listMun = $utilities->municipios($SIIem);
        $municipios = $listMun['municipios'];
        
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
                            mei.ciiu1 AS 'sector21',
                            mei.ciiu1 AS 'sector9',
                            mei.ciiu1,
                            mei.personal,
                            mei.acttot,
                            mei.acttot AS 'clasificacion',
                            mei.muncom,
                            mei.dircom,
                            mei.telcom1,                            
                            inscrip.registro,
                            inscrip.noticia,         
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
                    $sqlMat.="AND ((inscrip.libro='$key' AND inscrip.acto IN('$actos')) ";
                    $sw++;
                }else{
                    $sqlMat.="OR (inscrip.libro='$key' AND inscrip.acto IN('$actos')) ";
                }
                
                
            }
            
            $sqlMat.=") GROUP BY inscrip.id ";
//            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
            $stLibros = $SIIem->getConnection()->prepare($sqlMat);
//            Ejecución de las consultas
            $stLibros->execute($params);
            $resultadoLibros = $stLibros->fetchAll();
            $totalData = $totalFiltered = sizeof($resultadoLibros);
            
            for($i=0;$i<sizeof($resultadoLibros);$i++){
                $resultadoLibros[$i]['comerciante'] = $resultadoLibros[$i]['comerciante'];
                $resultadoLibros[$i]['noticia'] = $resultadoLibros[$i]['noticia'];
                $resultadoLibros[$i]['acto'] = $resultadoLibros[$i]['acto'];
                $resultadoLibros[$i]['libro'] = $resultadoLibros[$i]['libro'];
                if(key_exists($resultadoLibros[$i]['muncom'], $municipios)){
                   $resultadoLibros[$i]['muncom'] = $municipios[$resultadoLibros[$i]['muncom']]; 
                }                
                $resultadoLibros[$i]['clasificacion'] = $utilities->rangoActivos($SIIem, $resultadoLibros[$i]['clasificacion']);
                
                $resultadoLibros[$i]['sector21'] = substr($resultadoLibros[$i]['ciiu1'], 0, 1);
                $sec9 = substr($resultadoLibros[$i]['ciiu1'], 1, 2);
                switch (true) {
                    case ($sec9 >=1) && ($sec9<=3):
                        $resultadoLibros[$i]['sector9'] = 1;
                        break;
                    case ($sec9 >=5) && ($sec9<=9):
                        $resultadoLibros[$i]['sector9'] = 2;
                        break;
                    case ($sec9 >=10) && ($sec9<=33):
                        $resultadoLibros[$i]['sector9'] = 3;
                        break;
                    case ($sec9 >=35) && ($sec9<=39):
                        $resultadoLibros[$i]['sector9'] = 4;
                        break;
                    case ($sec9 >=41) && ($sec9<=43):
                        $resultadoLibros[$i]['sector9'] = 5;
                        break;
                    case ($sec9 >=45) && ($sec9<=47):
                        $resultadoLibros[$i]['sector9'] = 6;
                        break;
                    case ($sec9 >=49) && ($sec9<=63):
                        $resultadoLibros[$i]['sector9'] = 7;
                        break;
                    case ($sec9 >=64) && ($sec9<=68):
                        $resultadoLibros[$i]['sector9'] = 8;
                        break;
                    case ($sec9 >=69) && ($sec9<=99):
                        $resultadoLibros[$i]['sector9'] = 9;
                        break;

                    default:
                        $resultadoLibros[$i]['sector9'] = 6;
                        break;
                }
                
            }
            
            if($_POST['excel']==1){
                
                $nomExcel = 'ExtraccionLibros';
                $columns = ['FEC REGISTRO','MATRICULA','EST MAT','ORGANIZACION','CATEGORIA','FEC MATRICULA','FEC CONSTITUCION','FEC RENOVACION','UAR','IDENTIFICACION','RAZON SOCIAL','SECTOR21','SECTOR9','CIIU','PERSONAL','ACTIVOS','TAMANNO','MUNICIPIO','DIRECCION','TELEFONO','NUM REGISTRO','NOTICIA','ID. ACTO','ACTO','ID LIBRO','LIBRO'];
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
                
                $response = $utilities->exportExcel( $resultadoLibros, $columns,$nomExcel);
                return $response;
            }else{
                if( !empty($_POST['search']['value']) ) {   // if there is a search parameter, $_POST['search']['value'] contains search parameter
                        $sqlMat.=" AND ( inscrip.fecharegistro LIKE '".$_POST['search']['value']."%' ";    
                        $sqlMat.=" OR inscrip.matricula LIKE '".$_POST['search']['value']."%' ";    
                        $sqlMat.=" OR identificacion LIKE '".$_POST['search']['value']."%' ";       
                        $sqlMat.=" OR razonsocial LIKE '".$_POST['search']['value']."%' ";    
                        $sqlMat.=" OR inscrip.noticia LIKE '".$_POST['search']['value']."%' ";      
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
        $datosAfiliados = '';
        $data = Array();
        if($usuario->hasRole('ROLE_AFILIADOS') || $usuario->hasRole('ROLE_SUPER_ADMIN')){
            $datosAfiliados = ', mei.telaflia, mei.diraflia, mei.munaflia, mei.contaflia, mei.dircontaflia, mei.muncontaflia, mei.numactaaflia, mei.fecactaaflia, mei.numactacanaflia, mei.fecactacanaflia ';
        }
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
                                CONCAT(mei.nombre1,' ',
                                mei.nombre2,' ',
                                mei.apellido1,' ',
                                mei.apellido2) AS 'nomPerNat',
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
                                (CASE 
                                    when mei.organizacion IN ('03','04','05','06','07','08','09','10','11','16') AND (mei.categoria='1') then (select fechadocumento from mreg_est_inscripciones where matricula=mei.matricula and libro='RM09' and acto='0040')
                                    else mei.fecmatricula
                                END) AS 'fecconstitucion',
                                mei.fecdisolucion,
                                mei.fecliquidacion,
                                mei.fecvigencia,
                                (CASE 
                                    when mev.numid IS NULL then mev2.numid
                                    else mev.numid       
                                END) AS 'idRepLegal',                                
                                (CASE 
                                    when mev.nombre IS NULL then mev2.nombre
                                    else mev.nombre       
                                END) AS 'RepresentanteLegal',
                                (CASE 
                                    when mev.nom1 IS NULL then mev2.nom1
                                    else mev.nom1       
                                END) AS nomRepLegal1,
                                (CASE 
                                    when mev.nom2 IS NULL then mev2.nom2
                                    else mev.nom2       
                                END) AS nomRepLegal2,
                                (CASE 
                                    when mev.ape1 IS NULL then mev2.ape1
                                    else mev.ape1       
                                END) AS apeRepLEgal1,
                                (CASE 
                                    when mev.ape2 IS NULL then mev2.ape2
                                    else mev.ape2       
                                END) AS apeRepLegal2,
                                (CASE
                                   WHEN mei.organizacion = '02' AND mep.nit != ''
                                        THEN mep.nit
                                    WHEN mei.organizacion = '02' AND mep.nit = '' 
                                        THEN mep.identificacion 
                                    ELSE mep.matriculapropietario
                                END) AS 'idPropietario',
                                (CASE 
                                    WHEN mei.organizacion='02' 
                                        THEN mep.razonsocial 
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
                                mei.fecrenaflia,
                                mei.valpagaflia,
                                (CASE 
                                    WHEN mei.ctrafiliacion='1' THEN 'AFILIADO'
                                    WHEN mei.ctrafiliacion='2' THEN 'DESAFILIADO'
                                    ELSE 'NO AFILIADO'
                                END) as ctrafiliacion
                                $datosAfiliados
                        FROM
                            mreg_est_inscritos mei
                        LEFT JOIN mreg_est_inscritos inscritos ON mei.matricula = inscritos.matricula
                        LEFT JOIN mreg_est_propietarios mep ON (mei.matricula = mep.matricula AND mep.estado='V')
                        LEFT JOIN mreg_est_vinculos mev ON (mep.matriculapropietario = mev.matricula AND mev.vinculo IN (2170 , 2600, 4170) AND mev.estado='V' )
                        LEFT JOIN mreg_est_vinculos mev2 ON (mei.matricula = mev2.matricula AND mev2.vinculo IN (2170 , 2600, 4170) AND mev2.estado='V' )
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
                                    'NOMBRE COMPLETO',
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
                                    'NOMBRE REP. LEGAL 1',
                                    'NOMBRE REP. LEGAL 2',
                                    'APELLIDO REP. LEGAL 1',
                                    'APELLIDO REP. LEGAL 2',
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
                                    'FEC-ULT-PAG-AFIL',
                                    'VAL-ULT-PAG-AFIL',
                                    'CTR-AFILIACION'
                                    ];
                        if($usuario->hasRole('ROLE_AFILIADOS') || $usuario->hasRole('ROLE_SUPER_ADMIN')){
                            $columns[] = 'TEL-AFILIADO';
                            $columns[] = 'DIR-AFILIADO';
                            $columns[] = 'MUN-AFILIADO';
                            $columns[] = 'CONTACTO-AFIL';
                            $columns[] = 'DIR-CONT-AFIL';
                            $columns[] = 'MUN-CONT-AFIL';                            
                            $columns[] = 'NUM-ACTA-AFIL';   
                            $columns[] = 'FEC-ACTA-AFIL';                             
                            $columns[] = 'NUM-ACTA-CAN-AFIL';                          
                            $columns[] = 'FEC-ACTA-CAN-AFIL';                            
                        }

                $i=0;
                foreach($_POST['organizacion'] as $organiza){
                    if($organiza != ''){
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
                                    CONCAT(mei.nombre1,' ',
                                    mei.nombre2,' ',
                                    mei.apellido1,' ',
                                    mei.apellido2) AS 'nomCompleto',
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
                                            'NOMBRE COMPLETO',
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
                    }    
                    $i++;
                }
                
                $fechaWhere = $_POST['tipoFecha'];
                $muncom = "'".implode("','",$_POST['municipio'])."'";
                $replace = array(",",".");
                $activoIni = str_replace($replace, "", $_POST['activoIni']);
                $activoFinal = str_replace($replace, "", $_POST['activoFinal']);
                   
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
                    $sqlExtracMatri.=" AND mei.ctrafiliacion IN ('1','2') ";
                }elseif($_POST['afiliacion']==2){
                    $sqlExtracMatri.=" AND mei.ctrafiliacion='0' ";
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
                
                $utilMuni = $utilities->municipios($em);
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
                    
                    /*
                     * Si la organizacion no es un establecimiento, es necesario consultar los representantes legales de las sociedades aparte para
                     * poder tener los datos tanto de establecimientos como de sociedades
                     */                    
                    
                    
                    if($usuario->hasRole('ROLE_AFILIADOS') || $usuario->hasRole('ROLE_SUPER_ADMIN')){
                        if(key_exists($resultados[$i]['munaflia'] , $municipios)){
                            $resultados[$i]['munaflia'] = $municipios[$resultados[$i]['munaflia']];
                        } 
                        
                        if(key_exists($resultados[$i]['muncontaflia'] , $municipios)){
                            $resultados[$i]['muncontaflia'] = $municipios[$resultados[$i]['muncontaflia']];
                        } 
                    }
                    
                    $arrayOrganizaciones = array('03','04','05','06','07','08','09','10','11','16');
                    
                    /*if(in_array($resultados[$i]['organizacion'], $arrayOrganizaciones) &&  ($resultados[$i]['categoria']='1')){
                        $sqlFechaConstitucion = "SELECT meis.fechadocumento from mreg_est_inscripciones meis where meis.matricula=:matricula AND meis.acto='0040' ";
                        $fechaConstitucionQuery = $em->getConnection()->prepare($sqlFechaConstitucion);
                        $fechaConstitucionQuery->execute(array('matricula'=>$resultados[$i]['matricula']));
                        $resultadosFecConstitucion = $fechaConstitucionQuery->fetchAll();
                        $resultados[$i]['fec']
                    }*/
                    
                }
                
                if($_POST['excel']==1){
                    
//                    for($i=0;$i<sizeof($resultados);$i++){
//                        $resultados[$i]['razonsocialMat'] = $resultados[$i]['razonsocialMat'];
//                        $resultados[$i]['NombrePropietario'] = $resultados[$i]['NombrePropietario'];
//                        $resultados[$i]['nombre1'] = $resultados[$i]['nombre1'];
//                        $resultados[$i]['nombre2'] = $resultados[$i]['nombre2'];
//                        $resultados[$i]['apellido1'] = $resultados[$i]['apellido1'];
//                        $resultados[$i]['apellido2'] = $resultados[$i]['apellido2'];
//                    }
                    
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

        $codMuni = $util->municipios($em);
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
                    if($datosInforma1[$i]['idclase']>0){
                        $idclase = $datosInforma1[$i]['idclase'];
                    }else{
                        $idclase = '0';
                    }
                    $arreglo.= $util->preparaInforma($idclase, 'string', 1);
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
                    if($datosInforma1[$i]['categoria']>0){
                        $categoria = $datosInforma1[$i]['categoria'];
                    }else{
                        $categoria = '0';
                    }
                    $arreglo.= $util->preparaInforma($categoria, 'string', 1);
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
                        if($muncom=='')$muncom='05360';
                        $arreglo.= $util->preparaInforma($municipios[$muncom], 'string', 25);
                    }else{
                        $arreglo.= $util->preparaInforma('', 'string', 25);
                    }
                    $zipCode= $util->preparaInforma(0, 'entero', 4);
                    $arreglo.=$zipCode['dato'];
                    $telcom1 = $util->preparaInforma($datosInforma1[$i]['telcom1'], 'entero', 10);
                    $arreglo.= $telcom1['dato'];
                    $faxcom = $util->preparaInforma($datosInforma1[$i]['faxcom'], 'entero', 10);
                    $arreglo.= $faxcom['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['emailcom'], 'string', 50);
                    $cantest = $util->preparaInforma($datosInforma1[$i]['cantest'], 'entero', 5);
                    $arreglo.= $cantest['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cprazsoc'], 'string', 65);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cpnumnit'], 'string', 11);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cpdircom'], 'string', 65);
                    if(key_exists($datosInforma1[$i]['cpcodmun'], $municipios)){
                        $indexMun = $datosInforma1[$i]['cpcodmun'];
                    }else{
                        $indexMun='05360';
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
                            if($resultVinculos[$j]['idclase']>0){
                                $idclase = $resultVinculos[$j]['idclase'];
                            }else{
                                $idclase = '0';
                            }
                            $vinculos.= $util->preparaInforma($idclase, 'string', 1);
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

        $codMuni = $util->municipios($em);
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
                                mei.actnocte,
                                mei.actfij,
                                mei.actotr,
                                mei.actval,
                                mei.acttot,
                                mei.actvin,
                                mei.actsinaju,
                                mei.fijnet,
                                mei.pascte,
                                mei.paslar,
                                mei.pastot,
                                mei.pattot,
                                mei.paspat,
                                mai.balsoc,
                                mei.ingope AS ventas,
                                mei.cosven,
                                mei.gasnoope,
                                mei.gasimp,
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
                                mer.horaoperacion,
                                mei.proponente,
                                mei.urlcom,
                                mei.barriocom,
                                mei.telcom3,
                                mei.sigla,
                                (CASE 
                                    when mei.organizacion IN ('03','04','05','06','07','08','09','10','11','16') AND (mei.categoria='1') then (select fechadocumento from mreg_est_inscripciones where matricula=mei.matricula and libro='RM09' and acto='0040')
                                    else mei.fecmatricula
                                END) AS 'fecconstitucion',
                                mev.idclase,
                                mev.numid,
                                mev.nombre AS repLegal,
                                mei.actnocte
                                
                                
                            FROM
                                mreg_est_inscritos mei
                                    INNER JOIN
                                mreg_est_recibos mer ON mei.matricula = mer.matricula
                                LEFT JOIN mreg_est_vinculos mev ON mei.matricula=mev.matricula
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
                    if($datosInforma1[$i]['idclase']>0){
                        $idclase = $datosInforma1[$i]['idclase'];
                    }else{
                        $idclase = '0';
                    }
                    $arreglo.= $util->preparaInforma($idclase, 'string', 1);
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
                    if($datosInforma1[$i]['categoria']>0){
                        $categoria = $datosInforma1[$i]['categoria'];
                    }else{
                        $categoria = '0';
                    }
                    $arreglo.= $util->preparaInforma($categoria, 'string', 1);
//                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['categoria'], 'string', 1);
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
                    $gasnoope = $util->preparaInforma($datosInforma1[$i]['gasnoope'], 'entero', 17);
                    $arreglo.= $gasnoope['signo'];
                    $arreglo.= $gasnoope['dato'];
                    $utinet = $util->preparaInforma($datosInforma1[$i]['utinet'], 'entero', 17);
                    $arreglo.= $utinet['signo'];
                    $arreglo.= $utinet['dato'];                
                    $utiope = $util->preparaInforma($datosInforma1[$i]['utiope'], 'entero', 17);
                    $arreglo.= $utiope['signo'];
                    $arreglo.= $utiope['dato'];
                    $valringnoope = $util->preparaInforma($datosInforma1[$i]['ingnoope'], 'entero', 17);
                    $arreglo.= $valringnoope['dato']; 
                    $fijnet = $util->preparaInforma($datosInforma1[$i]['ingnoope'], 'entero', 17);
                    $arreglo.= $fijnet['fijnet']; 
                    $gasope = $util->preparaInforma($datosInforma1[$i]['gasope'], 'entero', 17);
                    $arreglo.= $gasope['signo'];
                    $arreglo.= $gasope['dato'];
                    /**
                     * falta campo depreciaciones
                     */
                    
                    $inventario = $util->preparaInforma($datosInforma1[$i]['invent'], 'entero', 17);
                    $arreglo.= $inventario['signo'];
                    $arreglo.= $inventario['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['dircom'], 'string', 65);
                    if(key_exists($datosInforma1[$i]['muncom'], $municipios)){
                        $muncom = $datosInforma1[$i]['muncom'];
                        if($muncom=='')$muncom='05360';
                        $arreglo.= $util->preparaInforma($municipios[$muncom], 'string', 25);
                    }else{
                        $arreglo.= $util->preparaInforma('', 'string', 25);
                    }
                    $zipCode= $util->preparaInforma(0, 'entero', 4);
                    $arreglo.=$zipCode['dato'];
                    $telcom1 = $util->preparaInforma($datosInforma1[$i]['telcom1'], 'entero', 10);
                    $arreglo.= $telcom1['dato'];
                    $faxcom = $util->preparaInforma($datosInforma1[$i]['faxcom'], 'entero', 10);
                    $arreglo.= $faxcom['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['emailcom'], 'string', 50);
                    $cantest = $util->preparaInforma($datosInforma1[$i]['cantest'], 'entero', 5);
                    $arreglo.= $cantest['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cprazsoc'], 'string', 65);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cpnumnit'], 'string', 11);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['cpdircom'], 'string', 65);
                    if(key_exists($datosInforma1[$i]['cpcodmun'], $municipios)){
                        $indexMun = $datosInforma1[$i]['cpcodmun'];
                    }else{
                        $indexMun='05360';
                    }
                    $arreglo.= $util->preparaInforma($municipios[$indexMun], 'string', 25);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['liquidacion'], 'string', 1);
                    /**
                     * agregar campo de concordato
                     */
                    if($datosInforma1[$i]['liquidacion']>0){
                        $arreglo.= $util->preparaInforma(1, 'string', 1);
                    }else{
                        $arreglo.= $util->preparaInforma(0, 'string', 1);
                    }
                    
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['urlcom'], 'string', 80);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['barriocom'], 'string', 80);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['telcom3'], 'string', 10);
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['sigla'], 'string', 100);
                    $fecconst= $util->preparaInforma($datosInforma1[$i]['fecconstitucion'], 'entero', 8);
                    $arreglo.=$fecconst['dato'];
                    $feccancel = $util->preparaInforma($datosInforma1[$i]['feccancelacion'], 'entero', 8);
                    $arreglo.= $feccancel['dato'];
                    $arreglo.= $util->preparaInforma('55', 'string', 2);
                    $idclase= $util->preparaInforma($datosInforma1[$i]['idclase'], 'entero', 2);
                    $arreglo.=$idclase['dato'];
                    $numid = $util->preparaInforma($datosInforma1[$i]['numid'], 'entero', 14);
                    $arreglo.= $numid['dato'];
                    $arreglo.= $util->preparaInforma($datosInforma1[$i]['repLegal'], 'string', 100);
                    $actnocte= $util->preparaInforma($datosInforma1[$i]['actnocte'], 'entero', 17);
                    $arreglo.= $actnocte['dato'];
                    $gasimp= $util->preparaInforma($datosInforma1[$i]['gasimp'], 'entero', 17);
                    $arreglo.= $gasimp['dato'];
                    $balsoc= $util->preparaInforma($datosInforma1[$i]['balsoc'], 'entero', 17);
                    $arreglo.= $balsoc['dato'];
                    
                    
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
//                            $idclase = $util->preparaInforma($resultVinculos[$j]['idclase'], 'entero', 1);
//                            $vinculos .= $idclase['dato'];
                            if($resultVinculos[$j]['idclase']>0){
                                $idclase = $resultVinculos[$j]['idclase'];
                            }else{
                                $idclase = '0';
                            }
                            $vinculos.= $util->preparaInforma($idclase, 'string', 1);
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
                        $telcom1 = $util->preparaInforma($datosInforma1[$i]['telcom1'], 'entero', 10);
                        $propietario.= $telcom1['dato'];
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
    
    /**
     * @Route("/patFacturacion" , name="patFacturacion" )
     */
    public function patFacturacionAction() {
        $em =  $this->getDoctrine()->getManager();
        $year = date('Y');
//        if(isset($_POST['programa'])){
//            $sqlActividad = $em->createQuery("SELECT ac.codigo, ac.descripcion FROM AppBundle:Actividad ac WHERE ac.programa=:idprog ")->setParameter('idprog',$_POST['programa']);
            $sqlActividad = $em->createQuery("SELECT ac.codigo, ac.descripcion FROM AppBundle:Actividad ac ORDER BY ac.codigo ASC ");
            $resultActividad = $sqlActividad->getResult();
//            $actividades = '';
            $actividades = '<select name="actividad" id="actividad" class="selectpicker form-control" data-live-search="true" title="Seleccione una actividad" >';
            for($i=0;$i<sizeof($resultActividad);$i++){
                $actividades .= "<option value='".$resultActividad[$i]['codigo']."' >".$resultActividad[$i]['descripcion']."</option>";
            }
            $actividades.='</select>';
            return $this->render('default/patFacturacion.html.twig',array('programas'=>$actividades));
//            return new Response(json_encode(array('actividades' => $actividades )));
//        }else{
//            $sqlProgr = $em->createQuery("SELECT ln.id AS idLinea,ln.descripcion AS linea,pg.id AS idProg, pg.descripcion AS programa FROM AppBundle:Programa pg JOIN pg.linea ln WHERE ln.vigencia=:year ORDER BY ln.id ASC ")->setParameter('year',$year);
//            $result = $sqlProgr->getResult();
//            
//            $listProgramas = '<select name="programa" id="programa" class="selectpicker form-control" data-live-search="true" title="Seleccione un programa" ><optgroup label="'.$result[0]['linea'].'" >';
//            $auxLinea = $result[0]['idLinea'];  
//            for($i=0;$i<sizeof($result);$i++){
//                if($auxLinea != $result[$i]['idLinea']){
//                    $listProgramas .= '</optgroup><optgroup label="'.$result[$i]['linea'].'" >';
//                    $auxLinea = $result[$i]['idLinea'];
//                }
//                $listProgramas.="<option value='".$result[$i]['idProg']."' >".$result[$i]['programa']."</option>";
//            }
//            $listProgramas.= "</optgroup></select>";
//            
//            return $this->render('default/patFacturacion.html.twig',array('programas'=>$listProgramas));
//        }
    }
    
    /**
     * @Route("/estadisticasComparativas" , name="estadisticasComparativas" ) 
     */
    public function estadisticasComparativasAction(){
        $fecha = new \DateTime();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $SIIem =  $this->getDoctrine()->getManager('sii');
        $logem =  $this->getDoctrine()->getManager();
        $horaGenracionReporte = $fecha->format('H:i:s');
        
        $usuario = $logem->getRepository('AppBundle:User')->findOneById($user);
        $ipaddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
        
        $util = new UtilitiesController();
        $bancos = $util->bancos($SIIem);
        if(isset($_POST['dateInit']) && isset($_POST['dateEnd'])){
            $fechaInicial = explode("-", $_POST['dateInit']);
            $fechaFinal = explode("-", $_POST['dateEnd']);
            
            $annoComparativo = ($fechaFinal[0]-1);
            $annoInicial = $fechaFinal[0];
            $tablaComparativa = "mreg_est_inscritos_$annoComparativo";
            $fecIniComparativa = $annoComparativo.$fechaInicial[1].$fechaInicial[2];
            $fecEndComparativa = $annoComparativo.$fechaFinal[1].$fechaFinal[2];
            
            $fecIni = str_replace("-", "", $_POST['dateInit']);
            $fecEnd = str_replace("-", "", $_POST['dateEnd']);
            
            $fechas = array( $tablaComparativa=>array($fecIniComparativa,$fecEndComparativa) , 'mreg_est_inscritos'=>array($fecIni,$fecEnd));
            $annosComp = array( $tablaComparativa=>$annoComparativo , 'mreg_est_inscritos'=>$annoInicial);
            
            foreach ($fechas as $key => $value) {

    //          Consulta para los matriculados en el rango de fechas consultado  
                $sqlMat = "SELECT inscritos.matricula, inscritos.organizacion,basorganiza.descripcion, inscritos.categoria, inscritos.muncom, inscritos.razonsocial, inscritos.fecmatricula, inscritos.fecrenovacion, inscritos.feccancelacion, inscritos.ultanoren  "
                        . "FROM $key inscritos  "
                        . "INNER JOIN bas_organizacionjuridica basorganiza ON basorganiza.id=inscritos.organizacion "
                        . "WHERE inscritos.fecmatricula between :fecIni AND :fecEnd  "
                        . "AND inscritos.ctrestmatricula NOT IN ('NA','NM') "
                        . "AND inscritos.matricula IS NOT NULL "
                        . "AND inscritos.matricula !='' ";

    //          Consulta para las matriculas renovadas en el rango de fechas consultado  
                $sqlRen = "SELECT inscritos.matricula, inscritos.organizacion,basorganiza.descripcion, inscritos.categoria, inscritos.muncom, inscritos.razonsocial, inscritos.fecmatricula, inscritos.fecrenovacion, inscritos.feccancelacion, inscritos.ultanoren "
                        . "FROM $key inscritos  "
                        . "INNER JOIN bas_organizacionjuridica basorganiza ON basorganiza.id=inscritos.organizacion "
                        . "WHERE inscritos.fecmatricula < :fecIni "
                        . "AND inscritos.fecrenovacion between :fecIni AND :fecEnd  "
                        . "AND inscritos.matricula IS NOT NULL "
                        . "AND inscritos.matricula !='' "
                        . "AND inscritos.ultanoren ='".$annosComp[$key]."' ";

    //          Consulta para las matriculas canceladas en el rango de fechas consultado
                $sqlCan = "SELECT inscritos.matricula, inscritos.organizacion,basorganiza.descripcion, inscritos.categoria, inscritos.muncom, inscritos.razonsocial, inscritos.fecmatricula, inscritos.fecrenovacion, inscritos.feccancelacion, inscritos.ultanoren "
                        . "FROM $key inscritos  "
                        . "INNER JOIN bas_organizacionjuridica basorganiza ON basorganiza.id=inscritos.organizacion "
                        . "INNER JOIN mreg_est_inscripciones mei ON  inscritos.matricula = mei.matricula "
                        . "WHERE mei.fecharegistro between :fecIni AND :fecEnd "
                        //. "AND inscritos.ctrestmatricula IN ('MC','IC','MF') "
                        . "AND inscritos.ctrestmatricula IN ('MC','IC') "
                        . "AND inscritos.matricula IS NOT NULL "
                        . "AND inscritos.matricula !='' "
                        . "AND libro IN ('RM15' , 'RM51', 'RE51', 'RM53', 'RM54', 'RM55', 'RM13') "
                        . "AND acto IN ('0180' , '0530','0531','0532','0536','0520','0540','0498','0300')";
                
                $sqlTrans = "SELECT idestado, iptramite, valortotal, idcodban 
                                FROM mreg_liquidacion
                                WHERE idestado IN ('09','07','20') AND tipotramite LIKE '%renovacionmat%'
                                AND fechaultimamodificacion BETWEEN  :fecIni AND :fecEnd ";
                


                $params = array('fecIni'=>$value[0] , 'fecEnd' => $value[1]);

    //            Parametrizacion de cada una de las consultas Matriculados-Renovados-Cancelados 
                $stmt = $SIIem->getConnection()->prepare($sqlMat);
                $strv = $SIIem->getConnection()->prepare($sqlRen);
                $stcn = $SIIem->getConnection()->prepare($sqlCan);
                $sttn = $SIIem->getConnection()->prepare($sqlTrans);

    //            Ejecución de las consultas
                $stmt->execute($params);
                $resultadoMat = $stmt->fetchAll();
                $strv->execute($params);
                $resultadoRen = $strv->fetchAll();
                $stcn->execute($params);           
                $resultadoCan = $stcn->fetchAll();
                $sttn->execute($params);           
                $resultadoTrans = $sttn->fetchAll();
                
                $totalMat[] = sizeof($resultadoMat);
                $totalRen[] = sizeof($resultadoRen);
                $totalCan[] = sizeof($resultadoCan);
                $totalTrans[] = sizeof($resultadoTrans);
                $valorTotalTrans[$key] = 0;
                for($i=0;$i<sizeof($resultadoTrans);$i++){
                    $valorTotalTrans[$key] = $valorTotalTrans[$key]+$resultadoTrans[$i]['valortotal'];
                }
                
                $pagosEstado[$key] = array('enlinea'=>0,'bancos'=>0,'caja'=>0);
                $pagosInterExter[$key] = array('internos'=>0,'externos'=>0);
                
                for($i=0;$i<sizeof($resultadoTrans);$i++){
                    switch ($resultadoTrans[$i]['idestado']) {
                        case '07':
                            $pagosEstado[$key]['enlinea']=$pagosEstado[$key]['enlinea']+1;
                            break;
                        case '09':
                            $pagosEstado[$key]['caja']=$pagosEstado[$key]['caja']+1;
                            $ip = explode(".", $resultadoTrans[$i]['iptramite']);
                            $cadenaIp = $ip[0].".".$ip[1];
                            switch ($cadenaIp) {
                                case '192.168':
                                    if($resultadoTrans[$i]['iptramite']=='192.168.1.8'){
                                        $pagosInterExter[$key]['externos']=$pagosInterExter[$key]['externos']+1;
                                    }else{
                                        $pagosInterExter[$key]['internos']=$pagosInterExter[$key]['internos']+1;
                                    }
                                    
                                    break;
                                default:
                                    $pagosInterExter[$key]['externos']=$pagosInterExter[$key]['externos']+1;
                                    break;
                            }
                            break;
                        case '20':
                            $pagosEstado[$key]['bancos']=$pagosEstado[$key]['bancos']+1;
                            if($key==='mreg_est_inscritos'){
                                if($resultadoTrans[$i]['idcodban']!=''){
                                    $nomBanco = $bancos[$resultadoTrans[$i]['idcodban']];
                                }else{
                                    $nomBanco = 'Otros';
                                }
                                
                                if(isset($pagosbancos[$nomBanco])){
                                    $pagosbancos[$nomBanco]=$pagosbancos[$nomBanco]+1;
                                }else{
                                    $pagosbancos[$nomBanco] = 1;
                                }
                            }    
                            break;
                        default:
                            break;
                    }
                    
                    
                }
                
            
            }
            
            
            $mesesInicial = $util->mes($fechaInicial[1]-1);
            $mesesFinal = $util->mes($fechaFinal[1]-1);
            
            $tablaResultado= "<table id='tabla_comparativa' class='table table-hover table-striped table-bordered dt-responsive cell-border extraccionesProponentes' cellspacing='0' width='100%'>"
                    . "<thead><tr><th></th><th>$annoComparativo</th><th>$annoInicial</th></tr></thead>"
                    . "<tbody><tr><td>Matriculados</td><td>".$totalMat[0]."</td><td>".$totalMat[1]."</td></tr>"
                    . "<tr><td>Renovados</td><td>".$totalRen[0]."</td><td>".$totalRen[1]."</td></tr>"
                    . "<tr><td>Matriculados + Renovados</td><td>".($totalMat[0]+$totalRen[0])."</td><td>".($totalMat[1]+$totalRen[1])."</td></tr>"
                    . "<tr><td>Cancelados</td><td>".$totalCan[0]."</td><td>".$totalCan[1]."</td></tr></tbody></table>";
            
            $tablaTransacciones ="<table id='tabla_transacciones' class='table table-hover table-striped table-bordered dt-responsive cell-border extraccionesProponentes' cellspacing='0' width='100%'>"
                    . "<thead><tr><th></th><th>$annoComparativo</th><th>$annoInicial</th></tr></thead>"
                    . "<tbody><tr><td>Total Transacciones</td><td>".$totalTrans[0]."</td><td>".$totalTrans[1]."</td></tr>"
                    . "<tr><td>Pagadas en Línea</td><td>".$pagosEstado[$tablaComparativa]['enlinea']."</td><td>".$pagosEstado['mreg_est_inscritos']['enlinea']."</td></tr>"
                    . "<tr><td>Pagadas en Bancos</td><td>".$pagosEstado[$tablaComparativa]['bancos']."</td><td>".$pagosEstado['mreg_est_inscritos']['bancos']."</td></tr>"
                    . "<tr><td>Pagadas en Caja</td><td>".$pagosEstado[$tablaComparativa]['caja']."</td><td>".$pagosEstado['mreg_est_inscritos']['caja']."</td></tr>"
                    . "<tr><td>Pago en caja Trámite Externo</td><td>".$pagosInterExter[$tablaComparativa]['externos']."</td><td>".$pagosInterExter['mreg_est_inscritos']['externos']."</td></tr>"
                    . "<tr><td>Pago en caja Asistencia CCAS</td><td>".$pagosInterExter[$tablaComparativa]['internos']."</td><td>".$pagosInterExter['mreg_est_inscritos']['internos']."</td></tr></tbody></table>";
            
            $tablaResultado2= "<div class='panel panel-primary' ><div class='panel-heading'><h4 class='h4' ><span class='glyphicon glyphicon-user' aria-hidden='true'></span>Comparativo $mesesInicial ".$fechaInicial[2]." a $mesesFinal ".$fechaFinal[2]."  <a href='#' id='toggle' class='btn btn-primary pull-right' >Cambiar Grafico</a> </h4></div>"
                    . "<div class='panel-body table-responsive' id='div_tabla_detallada' style='width:100%;' ><table id='tabla_detallada2' class='table table-hover table-striped table-bordered dt-responsive cell-border extraccionesProponentes' cellspacing='0' width='100%'>"
                    . "<thead><tr><th></th><th>$annoComparativo</th><th>$annoInicial</th></tr></thead>"
                    . "<tbody><tr><td>Matriculados</td><td>".number_format($totalMat[0],"0","",".")."</td><td>".number_format($totalMat[1],"0","",".")."</td></tr>"
                    . "<tr><td>Renovados</td><td>".number_format($totalRen[0],"0","",".")."</td><td>".number_format($totalRen[1],"0","",".")."</td></tr>"
                    . "<tr><th>Matriculados + Renovados</th><th>".number_format(($totalMat[0]+$totalRen[0]),"0","",".")."</th><th>".number_format(($totalMat[1]+$totalRen[1]),"0","",".")."</th></tr>"
                    . "<tr><td>Cancelados</td><td>".number_format($totalCan[0],"0","",".")."</td><td>".number_format($totalCan[1],"0","",".")."</td></tr></tbody></table></div></div>";
            
            $tablaBancos = "<table id='tabla_bancos' class='table table-hover table-striped table-bordered dt-responsive cell-border detBancos' cellspacing='0'  style='display: none' ><tbody>";
            foreach ($pagosbancos as $key => $value) {
                $tablaBancos.="<tr><td>$key</td><td>$value</td></tr>";
            }
            $tablaBancos.="</tbody></table>";
            
            $tablaTransacciones2 ="<div class='panel panel-primary' ><div class='panel-heading'><h4 class='h4' ><span class='glyphicon glyphicon-user' aria-hidden='true'></span>Transacciones de Renovación $mesesInicial ".$fechaInicial[2]." a $mesesFinal ".$fechaFinal[2]." <a href='#' id='toggle2' class='btn btn-primary pull-right' >Cambiar Grafico</a></h4></div>"
                    . "<div class='panel-body table-responsive' id='div_tabla_transacciones' style='width:100%;' ><table id='tabla_transacciones2' class='table table-hover table-striped table-bordered dt-responsive cell-border extraccionesProponentes' cellspacing='0' width='100%'>"
                    . "<thead><tr><th></th><th>$annoComparativo</th><th>$annoInicial</th></tr></thead>"
                    . "<tbody><tr><th>Total Transacciones</th><th><span id='totalComparativo'  class='tooltipFont' data-toggle='tooltip' title='Total Ingresos: $".number_format($valorTotalTrans[$tablaComparativa],"0","",".")."' >".number_format($totalTrans[0],"0","",".")."</span></th><th><span  id='totalActual' class='tooltipFont' data-toggle='tooltip' title='Total Ingresos: $".number_format($valorTotalTrans['mreg_est_inscritos'],"0","",".")."'>".number_format($totalTrans[1],"0","",".")."</span></th></tr>"
                    . "<tr><td>Pagadas en Línea</td><td>".number_format($pagosEstado[$tablaComparativa]['enlinea'],"0","",".")."</td><td>".number_format($pagosEstado['mreg_est_inscritos']['enlinea'],"0","",".")."</td></tr>"
                    . "<tr><td><h5>Pagadas en Bancos <span class='glyphicon glyphicon-arrow-down detBancos' aria-hidden='true' ></span><span class='glyphicon glyphicon-arrow-up detBancos' aria-hidden='true' style='display: none;' ></span></h5>$tablaBancos</td><td>".number_format($pagosEstado[$tablaComparativa]['bancos'],"0","",".")."</td><td>".number_format($pagosEstado['mreg_est_inscritos']['bancos'],"0","",".")."</td></tr>"
//                    . "<tr style='display: none' class='detBancos' ><td>$tablaBancos</td></tr>"
                    . "<tr><th>Pagadas en Caja</th><th>".number_format($pagosEstado[$tablaComparativa]['caja'],"0","",".")."</th><th>".number_format($pagosEstado['mreg_est_inscritos']['caja'],"0","",".")."</th></tr>"
                    . "<tr><td>Pago en caja Trámite Externo</td><td>".number_format($pagosInterExter[$tablaComparativa]['externos'],"0","",".")."</td><td>".number_format($pagosInterExter['mreg_est_inscritos']['externos'],"0","",".")."</td></tr>"
                    . "<tr><td>Pago en caja Asistencia CCAS</td><td>".number_format($pagosInterExter[$tablaComparativa]['internos'],"0","",".")."</td><td>".number_format($pagosInterExter['mreg_est_inscritos']['internos'],"0","",".")."</td></tr></tbody></table></div></div>";
            
            
            return new Response(json_encode(array(
                'tablaResultado' => $tablaResultado ,
                'tablaTransacciones'=>$tablaTransacciones ,
                'tablaResultado2' => $tablaResultado2 ,
                'tablaTransacciones2'=>$tablaTransacciones2 ,
                'tablaBancos'=>$tablaBancos,
                'horaGenracionReporte'=>$horaGenracionReporte )));
        }else{
            return $this->render('default/estadisticasComparativas.html.twig');
        }    
    }
    
    /**
     * @Route("/cambioRepresentantesLegales", name="cambioRepresentantesLegales")
     */
    public function cambioRepresentantesLegalesAction() {
        $emSII = $this->getDoctrine()->getManager('sii');
        $em = $this->getDoctrine()->getManager();
        if(!isset($_POST['dateInit']) || !isset($_POST['dateEnd'])){
            return $this->render('default/cambioRepresentantesLegales.html.twig');
        }else{
            $estado = $_POST['estado'];
            $tipoFecha = $_POST['tipoFecha'];
            $fecha = new \DateTime();
            $util = new UtilitiesController();
            $tipoId = $util->tipoId($emSII);
            $municipios = $util->municipios($emSII);
            
            $sqlProponente = "SELECT 
                                mep.proponente,
                                mep.matricula,
                                mep.nombre as razonSocial,
                                mep.ape1 as apellido1,
                                mep.ape2 as apellido2,
                                mep.nom1 as nombre1,
                                mep.nom2 as nombre2,
                                mep.sigla,
                                mep.idtipoidentificacion,
                                mep.identificacion,
                                mep.nit,
                                mep.organizacion,
                                mep.idestadoproponente,
                                mep.fechaultimarenovacion,
                                mep.fecactualizacion,
                                mep.telcom1,
                                mep.dircom,
                                mep.muncom,
                                mep.emailcom,
                                mep.telnot,
                                mep.dirnot,
                                mep.munnot,
                                mep.emailnot,
                                mev.numid,
                                mev.ape1,
                                mev.ape2,
                                mev.nom1,
                                mev.nom2,
                                mev.nombre
                            FROM
                                sii_aburra.mreg_est_proponentes mep
                                    LEFT JOIN
                                mreg_est_vinculos mev ON (mep.matricula = mev.matricula
                                    AND mev.vinculo IN (2170 , 2600, 4170) AND mev.estado='V')
                            WHERE
                                mep.idestadoproponente IN ($estado)  ";
            if($tipoFecha != ''){
                $sqlProponente.=" AND $tipoFecha BETWEEN :dateInit AND :dateEnd 
                            GROUP BY mep.proponente ORDER BY mep.proponente ";            
            
                $rowsProp = $emSII->getConnection()->prepare($sqlProponente);
                $params = array('dateInit' => $_POST['dateInit'] , 'dateEnd' => $_POST['dateEnd']);    
                $rowsProp->execute($params);
            }else{
                $rowsProp = $emSII->getConnection()->prepare($sqlProponente);  
                $rowsProp->execute();
            }
            $proponentes = $rowsProp->fetchAll();
            $totalFiltered = $totalData = sizeof($proponentes);
            
            if( isset($_POST['start']) ) {   
                    $sqlProponente.=" LIMIT ".$_POST['start']." ,".$_POST['length']."   ";
                    $rowsProp = $emSII->getConnection()->prepare($sqlProponente);
                    $params = array('dateInit' => $_POST['dateInit'] , 'dateEnd' => $_POST['dateEnd']);    
                    $rowsProp->execute($params);
                    $proponentes = $rowsProp->fetchAll();
//                    $totalFiltered = sizeof($proponentes);
            
            }
            $accion = "Consulta";
            
            for($i=0;$i<sizeof($proponentes);$i++){
                $excelData = array();
                $excelData[] = $proponentes[$i]['proponente'];
                $excelData[] = $proponentes[$i]['matricula'];
                $excelData[] = $tipoId[$proponentes[$i]['idtipoidentificacion']];
                $excelData[] = $proponentes[$i]['identificacion'];
                $excelData[] = $proponentes[$i]['razonSocial'];
                $excelData[] = $proponentes[$i]['sigla'];
                if($_POST['excel']==1){
                    $excelData[] = $proponentes[$i]['apellido1'];
                    $excelData[] = $proponentes[$i]['apellido2'];
                    $excelData[] = $proponentes[$i]['nombre1'];
                    $excelData[] = $proponentes[$i]['nombre2'];
                    $excelData[] = $proponentes[$i]['nit'];
                    $excelData[] = $proponentes[$i]['organizacion'];
                    $excelData[] = $proponentes[$i]['idestadoproponente'];
                    $excelData[] = $proponentes[$i]['fechaultimarenovacion'];
                    $excelData[] = $proponentes[$i]['fecactualizacion'];
                    $excelData[] = $proponentes[$i]['telcom1'];
                    $excelData[] = $proponentes[$i]['dircom'];
                    $excelData[] = $municipios['municipios'][$proponentes[$i]['muncom']];
                    $excelData[] = $proponentes[$i]['emailcom'];
                    $excelData[] = $proponentes[$i]['telnot'];
                    $excelData[] = $proponentes[$i]['dirnot'];
                    $excelData[] = $municipios['municipios'][$proponentes[$i]['munnot']];
                    $excelData[] = $proponentes[$i]['emailnot'];
                    $excelData[] = $proponentes[$i]['numid'];
                    $excelData[] = $proponentes[$i]['ape1'];
                    $excelData[] = $proponentes[$i]['ape2'];
                    $excelData[] = $proponentes[$i]['nom1'];
                    $excelData[] = $proponentes[$i]['nom2'];
                    $excelData[] = $proponentes[$i]['nombre'];
                    
                    $accion = "Exportación";
                    $encabezado = ['Proponente',
                                'matricula',
                                'Tipo Identificacion',
                                'Identificacion',
                                'Razon Social',
                                'Sigla',
                                'Primer Apellido',
                                'Segundo Apellido',
                                'Primer Nombre',
                                'Segundo Nombre',
                                'Nit',
                                'Organizacion',
                                'Estado',
                                'Fec ult ren',
                                'Fec actualizacion',
                                'Tel.comercial',
                                'Dir comercial',
                                'Mun comercial',
                                'email comercial',
                                'Tel notificacion',
                                'Dir notificacion',
                                'Mun notificacion',
                                'email notificacion',
                                'Id. Rep. Legal',
                                'Primer Apellido Rep. Legal',
                                'Segundo Apellido Rep. Legal',
                                'Primer Nombre Rep. Legal',
                                'Segundo Nombre Rep. Legal',
                                'Rep. Leagl'];
                }
                $excelResultados[] = $excelData;
//                $totalFiltered++;
            }
            
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $usuario = $em->getRepository('AppBundle:User')->findOneById($user);
            $ipaddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
            
            $logs = new Logs();
            $logs->setFecha($fecha);
            $logs->setModulo('Extracción Proponentes');
            $logs->setQuery('Query: '.$sqlProponente.' Parametros: fecIni=>'.$_POST['dateInit'].'  , fecEnd => '.$_POST['dateEnd']." Acción: ".$accion);
            $logs->setUsuario($usuario->getUsername());
            $logs->setIp($ipaddress);

            $em->persist($logs);
            $em->flush($logs);
            
            if($_POST['excel']==1){
                $nomExcel = 'ExtraccionProponentes';
                $utilities = new UtilitiesController();
                $response = $utilities->exportExcel( $excelResultados, $encabezado,$nomExcel);
                return $response;
            }else{
                $json_data = array(
                    "draw"            => intval( $_POST['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
                    "recordsTotal"    => intval( $totalData ),  // total number of records
                    "recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
                    "data"            => $excelResultados,   // total data array
                    "sql"             => $sqlProponente  
		);
                return new response(json_encode($json_data));
            }    
            
        }
        
    }
}
