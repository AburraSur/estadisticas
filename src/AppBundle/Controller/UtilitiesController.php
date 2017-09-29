<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class UtilitiesController extends Controller
{
    public function construirTablaResumen($results, $categoria, $tablaDetalle) {
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
                    $posMunic = 'otroDom';
                }else{
                    $posMunic = $results[$i]['muncom'];
                }
                if($results[$i]['organizacion']=='01'){
                    $arregloMatriculados[$posMunic.$categoria]['PN']++;
                    $totalPN++;
                }elseif($results[$i]['organizacion']=='02'){
                    $arregloMatriculados[$posMunic.$categoria]['EST']++;
                    $totalEST++;
                }elseif(in_array($results[$i]['organizacion'], $sociedades) && $results[$i]['categoria']==1 ){
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
                $totalMunicipio[$posMunic.$categoria] = $totalMunicipio[$posMunic.$categoria]+1;
                $granTotal++;
                $estado = strtoupper(str_replace("s", "", $categoria));
                $tablaDetalle .= "<tr>
                            <td>".$results[$i]['matricula']."</td>
                            <td>".$results[$i]['organizacion']."</td>
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
            
            return $tablas = array('tabla' => $tabla , 'tablaDetalle' => $tablaDetalle , 'granTotal' => $granTotal);
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
}