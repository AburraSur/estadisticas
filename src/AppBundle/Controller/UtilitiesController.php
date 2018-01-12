<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;


class UtilitiesController extends Controller
{
    public function municipios($em){
        
        $sqlMun = "SELECT basmun.codigomunicipio, basmun.ciudad FROM bas_municipios basmun ";
        
        $mun = $em->getConnection()->prepare($sqlMun);
        $mun->execute();
        $muni = $mun->fetchAll();
        for($i=0;$i<sizeof($muni);$i++){
            $codigos[] = $muni[$i]['codigomunicipio'];
            $ciudad[$muni[$i]['codigomunicipio']] = $muni[$i]['ciudad'];
        }
        $municipios['codigos'] = $codigos;
        $municipios['municipios'] = $ciudad;
        
        return $municipios;
    }
    
    public function rangoActivos($em,$param) {
        $fecha = new \DateTime();
        $anno = $fecha->format('Y');
        $clasifica = $em->getConnection()->prepare("SELECT descripcion FROM AS_clasificacionEmpresarial where (rango_final>=:acttot AND rango_inicial<=:acttot) AND anno_clasificacion=:anno ;");

        $params = array('acttot' => $param , 'anno' => $anno);    
        $clasifica->execute($params);
        $resultado = $clasifica->fetchAll();
        if(sizeof($resultado)>0){
            return ($resultado[0]['descripcion']);
        }else{
            return ('N/A');
        }
        
    }


    public function construirTablaResumen($results, $categoria, $tablaDetalle,$arregloTotales,$arregloMatMun) {
        $municipios = array('05129','05266','05360','05380','05631');
        $codMuni = array('05129'=>'CALDAS','05266'=>'ENVIGADO','05360'=>'ITAGUI','05380'=>'LA ESTRELLA','05631'=>'SABANETA','otroDom' => 'otroDomicilio'); 
            $sociedades = array('03','04','05','06','07','09','11','15','16');
            
            foreach ($municipios as $value) {
                $arregloMatriculados[$value.$categoria]['PN'] = 0;
                $arregloMatriculados[$value.$categoria]['EST'] = 0;
                $arregloMatriculados[$value.$categoria]['SOC'] = 0;
                $arregloMatriculados[$value.$categoria]['AGSUC'] = 0;
                $arregloMatriculados[$value.$categoria]['ESAL'] = 0;
                $arregloMatriculados[$value.$categoria]['CIVILES'] = 0;
                
                $arregloMatriculados['otroDom'.$categoria]['PN'] = 0;
                $arregloMatriculados['otroDom'.$categoria]['EST'] = 0;
                $arregloMatriculados['otroDom'.$categoria]['SOC'] = 0;
                $arregloMatriculados['otroDom'.$categoria]['AGSUC'] = 0;
                $arregloMatriculados['otroDom'.$categoria]['ESAL'] = 0;
                $arregloMatriculados['otroDom'.$categoria]['CIVILES'] = 0;
                
                $totalMunicipio['otroDom'.$categoria] = 0;
                $totalMunicipio[$value.$categoria] = 0;
                
            }
//            foreach ($resultados as $categoria => $value) {
//                $results = $value;
            
            $totalPN = $totalEST = $totalSOC = $totalAGSUC = $totalESAL = $totalCV = $granTotal = 0;
            
            for($i=0;$i<sizeof($results);$i++){
                
                if((!in_array($results[$i]['muncom'], $municipios)) || $results[$i]['muncom'] =='' || $results[$i]['muncom'] == NULL){
                    $posMunic = '05360';
                }else{
                    $posMunic = $results[$i]['muncom'];
                }
                if($results[$i]['organizacion']=='01'){
                    $arregloMatriculados[$posMunic.$categoria]['PN']++;
                    $totalPN++;
                }elseif($results[$i]['organizacion']=='02'){
                    $arregloMatriculados[$posMunic.$categoria]['EST']++;
                    $totalEST++;
                }elseif(in_array($results[$i]['organizacion'], $sociedades) && ($results[$i]['categoria']==1 || $results[$i]['categoria']=='' || $results[$i]['categoria']==null )){
                    $arregloMatriculados[$posMunic.$categoria]['SOC']++;
                    $totalSOC++;
                }elseif(in_array($results[$i]['organizacion'], $sociedades) && ($results[$i]['categoria']==2 || $results[$i]['categoria']==3) ){
                    $arregloMatriculados[$posMunic.$categoria]['AGSUC']++;
                    $totalAGSUC++;
                }elseif($results[$i]['organizacion']=='08'){
                    $arregloMatriculados[$posMunic.$categoria]['AGSUC']++;
                    $totalAGSUC++;
                }elseif($results[$i]['organizacion']=='10' && $results[$i]['categoria']==1 ){
                    $arregloMatriculados[$posMunic.$categoria]['CIVILES']++;
                    $totalCV++;
                }elseif($results[$i]['organizacion']=='10' && ($results[$i]['categoria']==2 || $results[$i]['categoria']==3) ){
                    $arregloMatriculados[$posMunic.$categoria]['AGSUC']++;
                    $totalAGSUC++;
                }elseif(($results[$i]['organizacion']=='12' || $results[$i]['organizacion']=='14' )&& $results[$i]['categoria']==1 ){
                    $arregloMatriculados[$posMunic.$categoria]['ESAL']++;
                    $totalESAL++;
                }elseif(($results[$i]['organizacion']=='12' || $results[$i]['organizacion']=='14' )&& ($results[$i]['categoria']==2 || $results[$i]['categoria']==3) ){
                    $arregloMatriculados[$posMunic.$categoria]['AGSUC']++;
                    $totalAGSUC++;
                }
                
//                if(!in_array($posMunic, $municipios)){
//                    $totalMunicipio['05360'.$categoria] = $totalMunicipio[$posMunic.$categoria]+1;
//                }else{
//                    $totalMunicipio[$posMunic.$categoria] = $totalMunicipio[$posMunic.$categoria]+1;
//                }
                $totalMunicipio[$posMunic.$categoria] = $totalMunicipio[$posMunic.$categoria]+1;
                $granTotal++;
                $estado = strtoupper(str_replace("s", "", $categoria));
                $tablaDetalle .= "<tr>
                            <td>".$results[$i]['matricula']."</td>
                            <td>".$results[$i]['organizacion']."</td>
                            <td>".$results[$i]['descripcion']."</td>
                            <td>".$results[$i]['categoria']."</td>
                            <td>".$results[$i]['razonsocial']."</td>
                            <td>".$codMuni[$posMunic]."</td>
                            <td>".$estado."</td>
                            <td>".$results[$i]['fecmatricula']."</td>
                            <td>".$results[$i]['fecrenovacion']."</td>
                            <td>".$results[$i]['feccancelacion']."</td>
                            <td>".$results[$i]['ultanoren']."</td>
                        </tr>  ";
            }
            
            $tabla = " <table id='rel$categoria' class='table table-hover table-striped table-bordered dt-responsive' cellspacing='0' width='100%'>
                            <thead>
                                <tr>
                                    <th>Municipio</th>
                                    <th>P. Naturales</th>
                                    <th>Establecimientos</th>
                                    <th>Sociedades</th>
                                    <th>Agencias - Sucursales</th>
                                    <th>ESAL</th>
                                    <th>Civil</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>";
            $categ[] = strtoupper($categoria);
            $excelTitle[] = 'Municipio';
            $excelTitle[]= 'P. Naturales';
            $excelTitle[]= 'Establecimientos';
            $excelTitle[]= 'Sociedades';
            $excelTitle[]= 'Agencias - Sucursales';
            $excelTitle[]= 'ESAL';
            $excelTitle[]= 'Civil';
            $excelTitle[]= 'Total';
            
            $excelDatos[] = $categ;
            $excelDatos[] = $excelTitle;
            
            foreach ($municipios as $valueMun) {
                $tabla .="<tr>
                            <th>$codMuni[$valueMun]</th>
                            <td>".number_format($arregloMatriculados[$valueMun.$categoria]['PN'],"0","",".")."</td>
                            <td>".number_format($arregloMatriculados[$valueMun.$categoria]['EST'],"0","",".")."</td>
                            <td>".number_format($arregloMatriculados[$valueMun.$categoria]['SOC'],"0","",".")."</td>
                            <td>".number_format($arregloMatriculados[$valueMun.$categoria]['AGSUC'],"0","",".")."</td>
                            <td>".number_format($arregloMatriculados[$valueMun.$categoria]['ESAL'],"0","",".")."</td>
                            <td>".number_format($arregloMatriculados[$valueMun.$categoria]['CIVILES'],"0","",".")."</td>
                            <td><b>".number_format($totalMunicipio[$valueMun.$categoria],"0","",".")."</b></td>
                        </tr>  ";
                
                $excelData=array();
                
                $excelData[]=$codMuni[$valueMun];
                $excelData[]= number_format($arregloMatriculados[$valueMun.$categoria]['PN'],"0","",".");
                $excelData[]= number_format($arregloMatriculados[$valueMun.$categoria]['EST'],"0","",".");
                $excelData[]= number_format($arregloMatriculados[$valueMun.$categoria]['SOC'],"0","",".");
                $excelData[]= number_format($arregloMatriculados[$valueMun.$categoria]['AGSUC'],"0","",".");
                $excelData[]= number_format($arregloMatriculados[$valueMun.$categoria]['ESAL'],"0","",".");
                $excelData[]= number_format($arregloMatriculados[$valueMun.$categoria]['CIVILES'],"0","",".");
                $excelData[]= number_format($totalMunicipio[$valueMun.$categoria],"0","",".");
                
                $excelDatos[] = $excelData;
                
                $organizacion = ['PN','EST','SOC','AGSUC','ESAL','CIVILES','TOTAL'];
                
                if($categoria!='cancelados'){
                    foreach ($organizacion as $value) {
                        if($value=='TOTAL'){
                            //$arregloMatMun[$valueMun][$value] = $arregloMatMun[$valueMun][$value]+$totalMunicipio[$valueMun.$categoria];
                            if(isset($arregloMatMun[$valueMun][$value])){
                                $arregloMatMun[$valueMun][$value] = $arregloMatMun[$valueMun][$value]+$totalMunicipio[$valueMun.$categoria];
                            }else{
                                $arregloMatMun[$valueMun][$value] = $totalMunicipio[$valueMun.$categoria];
                            }
                        }else{
                            if(isset($arregloMatMun[$valueMun][$value])){
                                $arregloMatMun[$valueMun][$value] = $arregloMatMun[$valueMun][$value] + $arregloMatriculados[$valueMun.$categoria][$value];                       
                            }else{
                                $arregloMatMun[$valueMun][$value] = $arregloMatriculados[$valueMun.$categoria][$value];
                            }
                        }    
                    }
                }
                
                

            }
                                  
                               
            $tabla .="<tr>
                            <th>TOTAL</th>
                            <th>".number_format($totalPN,"0","",".")."</th>
                            <th>".number_format($totalEST,"0","",".")."</th>
                            <th>".number_format($totalSOC,"0","",".")."</th>
                            <th>".number_format($totalAGSUC,"0","",".")."</th>
                            <th>".number_format($totalESAL,"0","",".")."</th>
                            <th>".number_format($totalCV,"0","",".")."</th>
                            <th>".number_format($granTotal,"0","",".")."</th>
                        </tr>
                    </tbody>
                </table>";
            $excelData = array();
            
            $excelData[] = 'TOTAL';
            $excelData[] = number_format($totalPN,"0","",".");
            $excelData[] = number_format($totalEST,"0","",".");
            $excelData[] = number_format($totalSOC,"0","",".");
            $excelData[] = number_format($totalAGSUC,"0","",".");
            $excelData[] = number_format($totalESAL,"0","",".");
            $excelData[] = number_format($totalCV,"0","",".");
            $excelData[] = number_format($granTotal,"0","",".");
            $excelDatos[] = $excelData;
            
            if($categoria!='cancelados'){
                $arregloTotales['PN'] = $arregloTotales['PN']+$totalPN;
                $arregloTotales['EST'] = $arregloTotales['EST']+$totalEST;
                $arregloTotales['SOC'] = $arregloTotales['SOC']+$totalSOC;
                $arregloTotales['AGSUC'] = $arregloTotales['AGSUC']+$totalAGSUC;
                $arregloTotales['ESAL'] = $arregloTotales['ESAL']+$totalESAL;
                $arregloTotales['CIVILES'] = $arregloTotales['CIVILES']+$totalCV;
            }else{
                $catego[] = 'Total Matriculados + Renovados';
                

                $excelDatos[] = $catego;
                $excelDatos[] = $excelTitle;
                foreach ($codMuni as $key => $value) {
                    if($key!='otroDom'){
                        $excelTotalesMun = array();
                        $excelTotalesMun[] = "$value";
                        $excelTotalesMun[] = number_format($arregloMatMun[$key]['PN'],"0","",".");
                        $excelTotalesMun[] = number_format($arregloMatMun[$key]['EST'],"0","",".");
                        $excelTotalesMun[] = number_format($arregloMatMun[$key]['SOC'],"0","",".");
                        $excelTotalesMun[] = number_format($arregloMatMun[$key]['AGSUC'],"0","",".");
                        $excelTotalesMun[] = number_format($arregloMatMun[$key]['ESAL'],"0","",".");
                        $excelTotalesMun[] = number_format($arregloMatMun[$key]['CIVILES'],"0","",".");
                        $excelTotalesMun[] = number_format($arregloMatMun[$key]['TOTAL'],"0","",".");
                        $excelDatos[] = $excelTotalesMun;
                    }
                }                
                $excelTotales[] = 'Totales';
                $excelTotales[] = number_format($arregloTotales['PN'],"0","",".");
                $excelTotales[] = number_format($arregloTotales['EST'],"0","",".");
                $excelTotales[] = number_format($arregloTotales['SOC'],"0","",".");
                $excelTotales[] = number_format($arregloTotales['AGSUC'],"0","",".");
                $excelTotales[] = number_format($arregloTotales['ESAL'],"0","",".");
                $excelTotales[] = number_format($arregloTotales['CIVILES'],"0","",".");
                $excelTotales[] = number_format(($arregloTotales['PN']+$arregloTotales['EST']+$arregloTotales['SOC']+$arregloTotales['AGSUC']+$arregloTotales['ESAL']+$arregloTotales['CIVILES']),"0","",".");
                
                $excelDatos[] = $excelTotales;
                
            }
            return $tablas = array('tabla' => $tabla , 'tablaDetalle' => $tablaDetalle , 'granTotal' => $granTotal,'excelRegistro' => $excelDatos,'arregloTotales'=>$arregloTotales,'arregloMatMun'=>$arregloMatMun);
    }
    
    public function sedes( $em ){
        
        $sql = "SELECT id, descripcion FROM mreg_sedes ";
        $sqlSedes = $em->getConnection()->prepare($sql);
        $sqlSedes->execute();
        $sedes = $sqlSedes->fetchAll();
        for($i=0;$i<sizeof($sedes);$i++){
            $listaSedes[$sedes[$i]['id']] = $sedes[$i]['descripcion'];
        }
        return $listaSedes;    
    }
    
    public function usuarios( $em ){
        
        $sql = "SELECT idcodigosirepcaja, nombreusuario FROM usuarios ";
        $sqlUsuario = $em->getConnection()->prepare($sql);
        $sqlUsuario->execute();
        $usuario = $sqlUsuario->fetchAll();
        for($i=0;$i<sizeof($usuario);$i++){
            $listaUsuarios[$usuario[$i]['idcodigosirepcaja']] = $usuario[$i]['nombreusuario'];
        }
        return $listaUsuarios;    
    }
    
    public function ciius($em) {
        $sqlCIIUS = "SELECT ciius.idciiu, ciius.descripcion FROM bas_ciius ciius";
            $prepareCIIUS = $em->getConnection()->prepare($sqlCIIUS);
            $prepareCIIUS->execute();
            $ciius =  $prepareCIIUS->fetchAll();
            return $ciius;
    }
    
    public function exportExcel($resultados,$columns,$nomExcel) {
        
        if($columns!=''){    
            $rows[] = '"'.implode('";"', $columns).'"';
        }    
        foreach ($resultados as $event) {
            $data = $event;

            $rows[] = '"'.implode('";"', $data).'"';
        }


        $fecha = new \DateTime();
        $fecExcel = $fecha->format('YmdHis');
        
        $content = implode("\n", $rows);
        $response = new Response($content);
        $dispositionHeader = $response->headers->makeDisposition(
                                        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                                        $nomExcel.$fecExcel.'.csv'
                                    );
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);
        //$response->headers->set('Content-Type', 'text/csv');

        return $response;


    }
    
    
//  función para exportar informacion en formato TXT, los datos deben ser enviado en un array
    public function exportTxt($resultados,$nomArchivo) {
                
        $content = implode("\n", $resultados);
        $response = new Response($content);
        $dispositionHeader = $response->headers->makeDisposition(
                                        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                                        $nomArchivo.'.txt'
                                    );
        $response->headers->set('Content-Type', 'text/plain; charset=utf-8');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
    
    
    //    funcion para dar formato a los diferentes datos que conforman los archivos para informaColombia, los string agregan espacios en blanco a la derecha,
//    los entero agregan 0 a la izquierda y a su vez comprueban si son + o -
    public function preparaInforma($dato, $tipo, $long) {
        
        $string= '';
        $numero='';
        
        
        if($tipo=='string'){
//            if(strpos($dato,"Ñ")){
//                $long++;                
//            }
            $dato = str_replace("´","'",$dato);
            $addLong = substr_count($dato, 'Ñ');
            $long = $long+$addLong;
            $dato = str_pad($dato, $long);
            $resultado=$dato;
        }elseif($tipo=='ciiu'){
            $dato = str_pad($dato, $long," ",STR_PAD_LEFT);
            $resultado=$dato;
        }else{
            $signo = 1;
            if($dato<0){
                if($dato[0]=='-'){
                    $dato = substr($dato, 1);
                    $signo = 0;
                }
            }    
            if(strlen($dato)<$long){
                $dato = str_pad($dato, $long,"0",STR_PAD_LEFT); 
            }
            $resultado['signo']=$signo;
            $resultado['dato']=$dato;
            
        }
        return $resultado;
    }
}