<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
                            <td>".$arregloMatriculados[$valueMun.$categoria]['PN']."</td>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['EST']."</td>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['SOC']."</td>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['AGSUC']."</td>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['ESAL']."</td>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['CIVILES']."</td>
                            <td><b>".$totalMunicipio[$valueMun.$categoria]."</b></td>
                        </tr>  ";

            }
                                  
                               
            $tabla .="<tr>
                            <th>TOTAL</th>
                            <th>".$totalPN."</th>
                            <th>".$totalEST."</th>
                            <th>".$totalSOC."</th>
                            <th>".$totalAGSUC."</th>
                            <th>".$totalESAL."</th>
                            <th>".$totalCV."</th>
                            <th>".$granTotal."</th>
                        </tr>
                    </tbody>
                </table>";
            
            return $tablas = array('tabla' => $tabla , 'tablaDetalle' => $tablaDetalle);
    }
}