<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UtilitiesController extends Controller
{
    public function construirTablaResumen($results, $categoria) {
        $municipios = array('CALDAS','ENVIGADO','ITAGUI','LA ESTRELLA','SABANETA'); 
            $sociedades = array('03','04','05','06','07','08','09','11','14','15','16');
            
            foreach ($municipios as $value) {
                $arregloMatriculados[$value.$categoria]['PN'] = 0;
                $arregloMatriculados[$value.$categoria]['EST'] = 0;
                $arregloMatriculados[$value.$categoria]['SOC'] = 0;
                $arregloMatriculados[$value.$categoria]['AGSUC'] = 0;
                $arregloMatriculados[$value.$categoria]['ESAL'] = 0;
                $arregloMatriculados[$value.$categoria]['CIVILES'] = 0;
                $totalMunicipio[$value.$categoria] = 0;
                
            }
//            foreach ($resultados as $categoria => $value) {
//                $results = $value;
            
            $totalPN = $totalEST = $totalSOC = $totalAGSUC = $totalESAL = $totalCV = $granTotal = 0;
            
            for($i=0;$i<sizeof($results);$i++){
                
                if(!in_array($results[$i]['ciudad'], $municipios)){
                    $posMunic = 'ITAGUI';
                }else{
                    $posMunic = $results[$i]['ciudad'];
                }
                if($results[$i]['organizacion']=='01'){
                    $arregloMatriculados[$posMunic.$categoria]['PN']++;
                    $totalPN++;
                }elseif($results[$i]['organizacion']=='02'){
                    $arregloMatriculados[$posMunic.$categoria]['EST']++;
                    $totalEST++;
                }elseif(in_array($results[$i]['organizacion'], $sociedades) && ($results[$i]['categoria']==0 || $results[$i]['categoria']==1) ){
                    $arregloMatriculados[$posMunic.$categoria]['SOC']++;
                    $totalSOC++;
                }elseif(in_array($results[$i]['organizacion'], $sociedades) && ($results[$i]['categoria']==2 || $results[$i]['categoria']==3) ){
                    $arregloMatriculados[$posMunic.$categoria]['AGSUC']++;
                    $totalAGSUC++;
                }elseif($results[$i]['organizacion']=='10' && $results[$i]['categoria']==1 ){
                    $arregloMatriculados[$posMunic.$categoria]['CIVILES']++;
                    $totalCV++;
                }elseif($results[$i]['organizacion']=='12' && $results[$i]['categoria']==1 ){
                    $arregloMatriculados[$posMunic.$categoria]['ESAL']++;
                    $totalESAL++;
                }
                $totalMunicipio[$posMunic.$categoria] = $totalMunicipio[$posMunic.$categoria]+1;
                $granTotal++;
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
                            <th>$valueMun</th>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['PN']."</td>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['EST']."</td>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['SOC']."</td>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['AGSUC']."</td>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['ESAL']."</td>
                            <td>".$arregloMatriculados[$valueMun.$categoria]['CIVILES']."</td>
                            <td>".$totalMunicipio[$valueMun.$categoria]."</td>
                        </tr>  ";

            }
                                  
                               
            $tabla .="<tr>
                            <th>TOTAL</th>
                            <td>".$totalPN."</td>
                            <td>".$totalEST."</td>
                            <td>".$totalSOC."</td>
                            <td>".$totalAGSUC."</td>
                            <td>".$totalESAL."</td>
                            <td>".$totalCV."</td>
                            <td>".$granTotal."</td>
                        </tr>
                    </tbody>
                </table>";
            
            return $tabla;
    }
}